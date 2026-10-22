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

// Materias registradas
$materias = $pdo->query("SELECT * FROM materias ORDER BY nombre ASC")->fetchAll();

$grado_seccion_id = isset($_GET['grado_seccion_id']) ? $_GET['grado_seccion_id'] : '';
$materia_id       = isset($_GET['materia_id']) ? $_GET['materia_id'] : '';

// Datos auxiliares de selección actual
$info_seccion = null;
$info_materia = null;

if (!empty($grado_seccion_id)) {
    $s = $pdo->prepare("SELECT * FROM grados_secciones WHERE id = ?");
    $s->execute([$grado_seccion_id]);
    $info_seccion = $s->fetch();
}

if (!empty($materia_id)) {
    $m = $pdo->prepare("SELECT * FROM materias WHERE id = ?");
    $m->execute([$materia_id]);
    $info_materia = $m->fetch();
}

// Obtener alumnos de la sección y sus notas en los 3 lapsos para la materia seleccionada
$alumnos_resumen = [];
if (!empty($grado_seccion_id) && !empty($materia_id) && $ano_activo) {
    $stmt_al = $pdo->prepare("SELECT m.id as matricula_id, e.cedula_escolar, e.nombre, e.apellido 
                              FROM matriculas m 
                              JOIN estudiantes e ON m.estudiante_id = e.id 
                              WHERE m.grado_seccion_id = ? AND m.ano_escolar_id = ? 
                              ORDER BY e.apellido ASC");
    $stmt_al->execute([$grado_seccion_id, $ano_activo['id']]);
    $alumnos = $stmt_al->fetchAll();

    foreach ($alumnos as $al) {
        // Consultar notas de Lapso 1, 2 y 3 para este alumno en la materia elegida
        $stmt_n = $pdo->prepare("SELECT lapso, nota_cualitativa, apreciacion FROM notas WHERE matricula_id = ? AND materia_id = ?");
        $stmt_n->execute([$al['matricula_id'], $materia_id]);
        $registros = $stmt_n->fetchAll(PDO::FETCH_ASSOC);

        // Mapear por lapso
        $notas_lapsos = [1 => null, 2 => null, 3 => null];
        foreach ($registros as $reg) {
            $notas_lapsos[$reg['lapso']] = $reg;
        }

        $alumnos_resumen[] = [
            'alumno' => $al,
            'lapsos' => $notas_lapsos
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen de Notas - Docente</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; background: white; }
        th, td { padding: 0.75rem; text-align: left; border: 1px solid #ddd; font-size: 0.85rem; }
        th { background-color: #1e3c72; color: white; text-align: center; }
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
                <li><a href="ver_notas.php" class="active">Ver Notas por Materia</a></li>
                <li><a href="resumen_alumno.php">Resumen por Alumno</a></li>
                <li><a href="asistencia.php">Control de Asistencia</a></li>
                <li class="logout"><a href="logout.php">Cerrar Sesi&oacute;n</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header class="top-navbar">
                <h2>Resumen Acad&eacute;mico por Materia</h2>
            </header>

            <div class="container">
                <!-- Filtros de selección -->
                <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <form method="GET" action="ver_notas.php" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
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
                        <div class="form-group" style="margin-bottom:0; flex:1; min-width:200px;">
                            <label>Seleccionar Materia</label>
                            <select name="materia_id" required>
                                <option value="">-- Seleccione --</option>
                                <?php foreach ($materias as $mat): ?>
                                    <option value="<?= $mat['id'] ?>" <?= $materia_id == $mat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($mat['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn-primary" style="width:auto; padding: 0.75rem 1.5rem;">Generar Consulta</button>
                    </form>
                </div>

                <?php if (!empty($grado_seccion_id) && !empty($materia_id)): ?>
                    <div style="background: white; padding: 1.5rem; border-radius: 8px; overflow-x: auto;">
                        <div style="display:flex; justify-between; align-items:center; margin-bottom:1rem;">
                            <h3>
                                Sección: <span style="color:#1e3c72;"><?= htmlspecialchars($info_seccion['grado'] . ' "' . $info_seccion['seccion'] . '"') ?></span> | 
                                Materia: <span style="color:#1e3c72;"><?= htmlspecialchars($info_materia['nombre']) ?></span>
                            </h3>
                        </div>

                        <table>
                            <thead>
                                <tr>
                                    <th style="text-align:left;">Estudiante</th>
                                    <th style="width:180px;">1er Lapso</th>
                                    <th style="width:180px;">2do Lapso</th>
                                    <th style="width:180px;">3er Lapso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($alumnos_resumen) > 0): ?>
                                    <?php foreach ($alumnos_resumen as $item): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($item['alumno']['apellido'] . ', ' . $item['alumno']['nombre']) ?></strong><br>
                                            <small style="color:#666;"><?= htmlspecialchars($item['alumno']['cedula_escolar']) ?></small>
                                        </td>
                                        <?php for ($l = 1; $l <= 3; $l++): ?>
                                            <td style="text-align:center;">
                                                <?php if (!empty($item['lapsos'][$l])): ?>
                                                    <span class="nota-badge"><?= htmlspecialchars($item['lapsos'][$l]['nota_cualitativa']) ?></span>
                                                    <?php if (!empty($item['lapsos'][$l]['apreciacion'])): ?>
                                                        <br><small style="color:#555; font-size:0.75rem; display:block; margin-top:0.2rem;"><?= htmlspecialchars($item['lapsos'][$l]['apreciacion']) ?></small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span style="color:#aaa;">Sin nota</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endfor; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" style="text-align:center; color:#777;">No hay alumnos matriculados en esta secci&oacute;n</td></tr>
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