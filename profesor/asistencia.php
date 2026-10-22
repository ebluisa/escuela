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

$grado_seccion_id = isset($_GET['grado_seccion_id']) ? $_GET['grado_seccion_id'] : '';
$fecha            = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');

// Procesar Guardado de Asistencia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_asistencia'])) {
    $fecha_post       = $_POST['fecha'];
    $grado_sec_post   = $_POST['grado_seccion_id'];
    $asistencias_data = $_POST['asistencia']; // array[matricula_id] = 'Presente'/'Ausente'/'Justificado'

    try {
        $pdo->beginTransaction();

        foreach ($asistencias_data as $matricula_id => $estado) {
            // Verificar si ya existe un registro de asistencia para este alumno en esta fecha exacta
            $stmt_chk = $pdo->prepare("SELECT id FROM asistencias WHERE matricula_id = ? AND fecha = ?");
            $stmt_chk->execute([$matricula_id, $fecha_post]);
            $existente = $stmt_chk->fetch();

            if ($existente) {
                // Actualizar estado
                $stmt_upd = $pdo->prepare("UPDATE asistencias SET estado = ? WHERE id = ?");
                $stmt_upd->execute([$estado, $existente['id']]);
            } else {
                // Insertar nuevo registro
                $stmt_ins = $pdo->prepare("INSERT INTO asistencias (matricula_id, fecha, estado) VALUES (?, ?, ?)");
                $stmt_ins->execute([$matricula_id, $fecha_post, $estado]);
            }
        }

        $pdo->commit();
        $mensaje = "Asistencia registrada con &eacute;xito para la fecha " . $fecha_post . "!";
    } catch (\PDOException $e) {
        $pdo->rollBack();
        $error = "Error al guardar la asistencia: " . $e->getMessage();
    }
}

// Cargar Alumnos de la secci�n y su asistencia existente para la fecha seleccionada
$alumnos = [];
if (!empty($grado_seccion_id) && $ano_activo) {
    $q = "SELECT m.id as matricula_id, e.cedula_escolar, e.nombre, e.apellido, 
                 (SELECT a.estado FROM asistencias a WHERE a.matricula_id = m.id AND a.fecha = ?) as estado_asistencia
          FROM matriculas m
          JOIN estudiantes e ON m.estudiante_id = e.id
          WHERE m.grado_seccion_id = ? AND m.ano_escolar_id = ?
          ORDER BY e.apellido";
    $stmt_a = $pdo->prepare($q);
    $stmt_a->execute([$fecha, $grado_seccion_id, $ano_activo['id']]);
    $alumnos = $stmt_a->fetchAll();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=0.4">
<link rel="SHORTCUT ICON" href="../img_web/logot.png" />
    <title>Control de Asistencia - Docente</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; background: white; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd; font-size: 0.85rem; }
        th { background-color: #1e3c72; color: white; }
        .radio-group { display: flex; gap: 1.5rem; font-weight: normal; }
        .radio-group label { cursor: pointer; }
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
                <li><a href="notas.php">Carga de Notas</a></li>
                <li><a href="ver_notas.php">Ver Notas por Materia</a></li>
                <li><a href="resumen_alumno.php">Resumen por Alumno</a></li>
                <li><a href="asistencia.php" class="active">Control de Asistencia</a></li>
                <li class="logout"><a href="logout.php">Cerrar Sesi&oacute;n</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header class="top-navbar">
                <h2>Registro Diario de Asistencia</h2>
            </header>

            <div class="container">
                <?php if ($mensaje): ?><div class="alert" style="background:#d4edda; color:#155724; border:1px solid #c3e6cb; padding:0.75rem; border-radius:5px; margin-bottom:1rem;"><?= $mensaje ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

                <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <form method="GET" action="asistencia.php" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
                        <div class="form-group" style="margin-bottom:0; flex:1; min-width:200px;">
                            <label>Seleccionar Aula / Secci&oacute;n</label>
                            <select name="grado_seccion_id" required>
                                <option value="">-- Seleccione --</option>
                                <?php foreach ($mis_secciones as $ms): ?>
                                    <option value="<?= $ms['id'] ?>" <?= $grado_seccion_id == $ms['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ms['grado'] . ' - ' . $ms['seccion']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0; width:180px;">
                            <label>Fecha de Asistencia</label>
                            <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>" required>
                        </div>
                        <button type="submit" class="btn-primary" style="width:auto; padding: 0.75rem 1.5rem;">Cargar Lista</button>
                    </form>
                </div>

                <?php if (!empty($grado_seccion_id)): ?>
                    <form method="POST" action="asistencia.php?grado_seccion_id=<?= $grado_seccion_id ?>&fecha=<?= $fecha ?>">
                        <input type="hidden" name="fecha" value="<?= $fecha ?>">
                        <input type="hidden" name="grado_seccion_id" value="<?= $grado_seccion_id ?>">

                        <div style="background: white; padding: 1.5rem; border-radius: 8px; overflow-x: auto;">
                            <h3>Pasar Asistencia - Fecha: <?= htmlspecialchars($fecha) ?></h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Estudiante</th>
                                        <th>Estado de Asistencia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($alumnos) > 0): ?>
                                        <?php foreach ($alumnos as $al): ?>
                                        <?php 
                                            // Por defecto si no tiene registro, marcamos "Presente"
                                            $estado_actual = $al['estado_asistencia'] ? $al['estado_asistencia'] : 'Presente';
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($al['apellido'] . ', ' . $al['nombre']) ?></strong><br>
                                                <small style="color:#666;"><?= htmlspecialchars($al['cedula_escolar']) ?></small>
                                            </td>
                                            <td>
                                                <div class="radio-group">
                                                    <label style="color: green; font-weight: bold;">
                                                        <input type="radio" name="asistencia[<?= $al['matricula_id'] ?>]" value="Presente" <?= $estado_actual == 'Presente' ? 'checked' : '' ?>> Presente
                                                    </label>
                                                    <label style="color: red;">
                                                        <input type="radio" name="asistencia[<?= $al['matricula_id'] ?>]" value="Ausente" <?= $estado_actual == 'Ausente' ? 'checked' : '' ?>> Ausente
                                                    </label>
                                                    <label style="color: darkorange;">
                                                        <input type="radio" name="asistencia[<?= $al['matricula_id'] ?>]" value="Justificado" <?= $estado_actual == 'Justificado' ? 'checked' : '' ?>> Justificado
                                                    </label>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="2" style="text-align:center; color:#777;">No hay alumnos inscritos en esta secci&oacute;n.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>

                            <?php if (count($alumnos) > 0): ?>
                                <button type="submit" name="guardar_asistencia" class="btn-primary" style="margin-top:1.5rem; width:200px;">Guardar Asistencia</button>
                            <?php endif; ?>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>