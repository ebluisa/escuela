<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['profesor_id'])) {
    header('Location: ../login_profesor.php');
    exit;
}

$profesor_id = $_SESSION['profesor_id'];
$ano_activo  = $pdo->query("SELECT * FROM anos_escolares WHERE estado = 1 LIMIT 1")->fetch();

// Secciones asignadas al profesor
$stmt_sec = $pdo->prepare("SELECT * FROM grados_secciones WHERE profesor_id = ?");
$stmt_sec->execute([$profesor_id]);
$mis_secciones = $stmt_sec->fetchAll();

$grado_seccion_id = isset($_GET['grado_seccion_id']) ? $_GET['grado_seccion_id'] : '';
$matricula_id     = isset($_GET['matricula_id']) ? $_GET['matricula_id'] : '';

// 1. Obtener lista de alumnos si se seleccionó una sección
$alumnos_seccion = [];
if (!empty($grado_seccion_id) && $ano_activo) {
    $stmt_al = $pdo->prepare("SELECT m.id as matricula_id, e.cedula_escolar, e.nombre, e.apellido 
                              FROM matriculas m 
                              JOIN estudiantes e ON m.estudiante_id = e.id 
                              WHERE m.grado_seccion_id = ? AND m.ano_escolar_id = ? 
                              ORDER BY e.apellido ASC");
    $stmt_al->execute([$grado_seccion_id, $ano_activo['id']]);
    $alumnos_seccion = $stmt_al->fetchAll();
}

// 2. Si hay un alumno seleccionado, consultar su información y sus notas de todas las materias
$info_alumno = null;
$boletin_materias = [];

if (!empty($matricula_id) && $ano_activo) {
    // Datos del alumno y su sección
    $stmt_info = $pdo->prepare("SELECT e.cedula_escolar, e.nombre, e.apellido, e.fecha_nacimiento, gs.grado, gs.seccion,
                                       r.nombre as r_nombre, r.apellido as r_apellido, r.cedula as r_cedula
                                FROM matriculas m
                                JOIN estudiantes e ON m.estudiante_id = e.id
                                JOIN representantes r ON e.representante_id = r.id
                                JOIN grados_secciones gs ON m.grado_seccion_id = gs.id
                                WHERE m.id = ?");
    $stmt_info->execute([$matricula_id]);
    $info_alumno = $stmt_info->fetch();

    if ($info_alumno) {
        // Obtenemos todas las materias del sistema
        $materias = $pdo->query("SELECT * FROM materias ORDER BY nombre ASC")->fetchAll();

        foreach ($materias as $mat) {
            // Consultar notas del alumno en esta materia para los lapsos 1, 2 y 3
            $stmt_n = $pdo->prepare("SELECT lapso, nota_cualitativa, apreciacion FROM notas WHERE matricula_id = ? AND materia_id = ?");
            $stmt_n->execute([$matricula_id, $mat['id']]);
            $registros = $stmt_n->fetchAll(PDO::FETCH_ASSOC);

            $lapsos = [1 => null, 2 => null, 3 => null];
            foreach ($registros as $reg) {
                $lapsos[$reg['lapso']] = $reg;
            }

            $boletin_materias[] = [
                'materia' => $mat['nombre'],
                'lapsos'  => $lapsos
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen por Alumno - Docente</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; background: white; }
        th, td { padding: 0.75rem; text-align: left; border: 1px solid #ddd; font-size: 0.85rem; }
        th { background-color: #1e3c72; color: white; text-align: center; }
        .ficha-card {
            background: #ffffff;
            border-left: 5px solid #2a5298;
            padding: 1.2rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        .ficha-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 0.8rem;
        }
        .nota-badge {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.95rem;
            background: #e2e8f0;
            color: #1e293b;
        }
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
                <li><a href="resumen_alumno.php" class="active">Resumen por Alumno</a></li>
                <li><a href="asistencia.php">Control de Asistencia</a></li>
                <li class="logout"><a href="logout.php">Cerrar Sesi&oacute;n</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header class="top-navbar">
                <h2>Resumen Acad&eacute;mico Individual del Estudiante</h2>
            </header>

            <div class="container">
                <!-- Filtros de Selección -->
                <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <form method="GET" action="resumen_alumno.php" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
                        <div class="form-group" style="margin-bottom:0; flex:1; min-width:200px;">
                            <label>1. Seleccionar Aula / Secci&oacute;n</label>
                            <select name="grado_seccion_id" onchange="this.form.submit()" required>
                                <option value="">-- Seleccione Secci&oacute;n --</option>
                                <?php foreach ($mis_secciones as $ms): ?>
                                    <option value="<?= $ms['id'] ?>" <?= $grado_seccion_id == $ms['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ms['grado'] . ' - ' . $ms['seccion']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom:0; flex:1; min-width:220px;">
                            <label>2. Seleccionar Estudiante</label>
                            <select name="matricula_id" required <?= empty($grado_seccion_id) ? 'disabled' : '' ?>>
                                <option value="">-- Seleccione Alumno --</option>
                                <?php foreach ($alumnos_seccion as $al): ?>
                                    <option value="<?= $al['matricula_id'] ?>" <?= $matricula_id == $al['matricula_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($al['apellido'] . ', ' . $al['nombre'] . ' (' . $al['cedula_escolar'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="btn-primary" style="width:auto; padding: 0.75rem 1.5rem;">Ver Ficha</button>
                    </form>
                </div>

                <!-- Detalle del Alumno y Tabla de Boletín -->
                <?php if ($info_alumno): ?>
                    <div class="ficha-card">
                        <h3 style="color:#1e3c72;"><?= htmlspecialchars($info_alumno['nombre'] . ' ' . $info_alumno['apellido']) ?></h3>
                        <div class="ficha-grid">
                            <div><strong>ID Escolar:</strong> <?= htmlspecialchars($info_alumno['cedula_escolar']) ?></div>
                            <div><strong>Grado y Secci&oacute;n:</strong> <?= htmlspecialchars($info_alumno['grado'] . ' "' . $info_alumno['seccion'] . '"') ?></div>
                            <div><strong>Representante:</strong> <?= htmlspecialchars($info_alumno['r_nombre'] . ' ' . $info_alumno['r_apellido']) ?> (<?= htmlspecialchars($info_alumno['r_cedula']) ?>)</div>
                        </div>
                    </div>

                    <div style="background: white; padding: 1.5rem; border-radius: 8px; overflow-x: auto;">
                        <h3 style="margin-bottom: 1rem;">Bolet&iacute;n de Calificaciones Consolidadas</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th style="text-align:left;">Materia / &Aacute;rea de Aprendizaje</th>
                                    <th style="width:180px;">1er Lapso</th>
                                    <th style="width:180px;">2do Lapso</th>
                                    <th style="width:180px;">3er Lapso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($boletin_materias) > 0): ?>
                                    <?php foreach ($boletin_materias as $bm): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($bm['materia']) ?></strong></td>
                                        <?php for ($l = 1; $l <= 3; $l++): ?>
                                            <td style="text-align:center;">
                                                <?php if (!empty($bm['lapsos'][$l])): ?>
                                                    <span class="nota-badge"><?= htmlspecialchars($bm['lapsos'][$l]['nota_cualitativa']) ?></span>
                                                    <?php if (!empty($bm['lapsos'][$l]['apreciacion'])): ?>
                                                        <br><small style="color:#555; font-size:0.75rem; display:block; margin-top:0.2rem;"><?= htmlspecialchars($bm['lapsos'][$l]['apreciacion']) ?></small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span style="color:#aaa;">Sin nota</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endfor; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" style="text-align:center; color:#777;">No hay materias registradas en el sistema</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>