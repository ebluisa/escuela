<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['profesor_id'])) {
    header('Location: ../login_profesor.php');
    exit;
}

$profesor_id = $_SESSION['profesor_id'];
$mensaje = '';
$error = '';

$ano_activo = $pdo->query("SELECT * FROM anos_escolares WHERE estado = 1 LIMIT 1")->fetch();

// Secciones asignadas al profesor
$stmt_sec = $pdo->prepare("SELECT * FROM grados_secciones WHERE profesor_id = ?");
$stmt_sec->execute([$profesor_id]);
$mis_secciones = $stmt_sec->fetchAll();

// Listado de materias disponibles
$materias_list = $pdo->query("SELECT * FROM materias ORDER BY nombre ASC")->fetchAll();

$grado_seccion_id = isset($_GET['grado_seccion_id']) ? $_GET['grado_seccion_id'] : '';
$materia_id       = isset($_GET['materia_id']) ? $_GET['materia_id'] : '';
$lapso            = isset($_GET['lapso']) ? $_GET['lapso'] : '1';

// Guardar Notas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_notas'])) {
    $lapso_post      = $_POST['lapso'];
    $materia_post    = $_POST['materia_id'];
    $notas_data      = $_POST['notas']; 

    try {
        $pdo->beginTransaction();
        foreach ($notas_data as $matricula_id => $data) {
            $nota_cualitativa = trim($data['nota']);
            $apreciacion      = trim($data['apreciacion']);

            // Verificar si ya existe nota para este alumno, materia y lapso
            $stmt_chk = $pdo->prepare("SELECT id FROM notas WHERE matricula_id = ? AND materia_id = ? AND lapso = ?");
            $stmt_chk->execute([$matricula_id, $materia_post, $lapso_post]);
            $nota_existente = $stmt_chk->fetch();

            if ($nota_existente) {
                $stmt_upd = $pdo->prepare("UPDATE notas SET nota_cualitativa = ?, apreciacion = ? WHERE id = ?");
                $stmt_upd->execute([$nota_cualitativa, $apreciacion, $nota_existente['id']]);
            } else {
                $stmt_ins = $pdo->prepare("INSERT INTO notas (matricula_id, materia_id, lapso, nota_cualitativa, apreciacion) VALUES (?, ?, ?, ?, ?)");
                $stmt_ins->execute([$matricula_id, $materia_post, $lapso_post, $nota_cualitativa, $apreciacion]);
            }
        }
        $pdo->commit();
        $mensaje = "Evaluaciones guardadas correctamente";
    } catch (\PDOException $e) {
        $pdo->rollBack();
        $error = "Error al guardar las notas: " . $e->getMessage();
    }
}

// Cargar Alumnos de la Secci�n seleccionada
$alumnos = [];
if (!empty($grado_seccion_id) && !empty($materia_id) && $ano_activo) {
    $q = "SELECT m.id as matricula_id, e.cedula_escolar, e.nombre, e.apellido, n.nota_cualitativa, n.apreciacion
          FROM matriculas m
          JOIN estudiantes e ON m.estudiante_id = e.id
          LEFT JOIN notas n ON n.matricula_id = m.id AND n.materia_id = ? AND n.lapso = ?
          WHERE m.grado_seccion_id = ? AND m.ano_escolar_id = ?
          ORDER BY e.apellido";
    $stmt_a = $pdo->prepare($q);
    $stmt_a->execute([$materia_id, $lapso, $grado_seccion_id, $ano_activo['id']]);
    $alumnos = $stmt_a->fetchAll();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=0.4">
<link rel="SHORTCUT ICON" href="../img_web/logot.png" />
    <title>Carga de Notas - Docente</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; background: white; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd; font-size: 0.85rem; }
        th { background-color: #1e3c72; color: white; }
    </style>
</head>
<body>
    <div class="wrapper">
        <nav class="sidebar">
            <div class="sidebar-header">
                <h3>E. B. "Luisa Ramona Lop&eacute;z Ru&iacute;z"</h3>
                <small>Panel del Docente</small>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">Mis Secciones</a></li>
                <li><a href="notas.php" class="active">Carga de Notas</a></li>
                <li><a href="ver_notas.php">Ver Notas por Materia</a></li>
                <li><a href="resumen_alumno.php">Resumen por Alumno</a></li>
                <li><a href="asistencia.php">Control de Asistencia</a></li>
                <li class="logout"><a href="logout.php">Cerrar Sesión</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header class="top-navbar">
                <h2>Carga de Evaluaciones por Materia</h2>
            </header>

            <div class="container">
                <?php if ($mensaje): ?><div class="alert" style="background:#d4edda; color:#155724; border:1px solid #c3e6cb; padding:0.75rem; border-radius:5px; margin-bottom:1rem;"><?= $mensaje ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

                <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <form method="GET" action="notas.php" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
                        <div class="form-group" style="margin-bottom:0; flex:1; min-width:180px;">
                            <label>Aula / Secci&oacute;n</label>
                            <select name="grado_seccion_id" required>
                                <option value="">-- Seleccione --</option>
                                <?php foreach ($mis_secciones as $ms): ?>
                                    <option value="<?= $ms['id'] ?>" <?= $grado_seccion_id == $ms['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ms['grado'] . ' - ' . $ms['seccion']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0; flex:1; min-width:180px;">
                            <label>Materia / &Aacute;rea</label>
                            <select name="materia_id" required>
                                <option value="">-- Seleccione --</option>
                                <?php foreach ($materias_list as $mat): ?>
                                    <option value="<?= $mat['id'] ?>" <?= $materia_id == $mat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($mat['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0; width:130px;">
                            <label>Lapso</label>
                            <select name="lapso">
                                <option value="1" <?= $lapso == '1' ? 'selected' : '' ?>>1er Lapso</option>
                                <option value="2" <?= $lapso == '2' ? 'selected' : '' ?>>2do Lapso</option>
                                <option value="3" <?= $lapso == '3' ? 'selected' : '' ?>>3er Lapso</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-primary" style="width:auto; padding: 0.75rem 1.5rem;">Cargar</button>
                    </form>
                </div>

                <?php if (!empty($grado_seccion_id) && !empty($materia_id)): ?>
                    <form method="POST" action="notas.php?grado_seccion_id=<?= $grado_seccion_id ?>&materia_id=<?= $materia_id ?>&lapso=<?= $lapso ?>">
                        <input type="hidden" name="lapso" value="<?= $lapso ?>">
                        <input type="hidden" name="materia_id" value="<?= $materia_id ?>">
                        
                        <div style="background: white; padding: 1.5rem; border-radius: 8px; overflow-x: auto;">
                            <h3>N&oacute;mina de Evaluaci&oacute;n</h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Alumno</th>
                                        <th style="width:120px;">Nota (A-E)</th>
                                        <th>Informe Descriptivo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($alumnos) > 0): ?>
                                        <?php foreach ($alumnos as $al): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($al['apellido'] . ', ' . $al['nombre']) ?></strong><br>
                                                <small style="color:#666;"><?= htmlspecialchars($al['cedula_escolar']) ?></small>
                                            </td>
                                            <td>
                                                <select name="notas[<?= $al['matricula_id'] ?>][nota]">
                                                    <option value="">--</option>
                                                    <option value="A" <?= $al['nota_cualitativa'] == 'A' ? 'selected' : '' ?>>A</option>
                                                    <option value="B" <?= $al['nota_cualitativa'] == 'B' ? 'selected' : '' ?>>B</option>
                                                    <option value="C" <?= $al['nota_cualitativa'] == 'C' ? 'selected' : '' ?>>C</option>
                                                    <option value="D" <?= $al['nota_cualitativa'] == 'D' ? 'selected' : '' ?>>D</option>
                                                    <option value="E" <?= $al['nota_cualitativa'] == 'E' ? 'selected' : '' ?>>E</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="notas[<?= $al['matricula_id'] ?>][apreciacion]" value="<?= htmlspecialchars($al['apreciacion'] ?: '') ?>" placeholder="Apreciaci&oacute;n pedag&oacute;gica...">
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" style="text-align:center; color:#777;">No hay alumnos inscritos en esta secci&oacute;n.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>

                            <?php if (count($alumnos) > 0): ?>
                                <button type="submit" name="guardar_notas" class="btn-primary" style="margin-top:1.5rem; width:200px;">Guardar Notas</button>
                            <?php endif; ?>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>