<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login_admin/');
    exit;
}

// 1. Obtener Año Escolar Activo
$stmt_ano = $pdo->query("SELECT * FROM anos_escolares WHERE estado = 1 LIMIT 1");
$ano_activo = $stmt_ano ? $stmt_ano->fetch(PDO::FETCH_ASSOC) : null;
$ano_activo_id = $ano_activo ? $ano_activo['id'] : 0;

// 2. Obtener TODAS las secciones disponibles de la BD (con consulta directa y limpia)
$secciones = [];
try {
    $stmt_sec_list = $pdo->query("SELECT id, grado, seccion FROM grados_secciones ORDER BY id ASC");
    if ($stmt_sec_list) {
        $secciones = $stmt_sec_list->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $secciones = [];
}

$grado_seccion_id = isset($_GET['grado_seccion_id']) ? $_GET['grado_seccion_id'] : '';
$info_seccion = null;
$reporte_alumnos = [];
$materias = [];

if (!empty($grado_seccion_id) && $ano_activo_id) {
    // Info de la sección seleccionada
    $stmt_sec = $pdo->prepare("SELECT * FROM grados_secciones WHERE id = ?");
    $stmt_sec->execute([$grado_seccion_id]);
    $info_seccion = $stmt_sec->fetch(PDO::FETCH_ASSOC);

    // Obtener materias
    $materias = $pdo->query("SELECT * FROM materias ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

    // Obtener alumnos matriculados en esta sección para el año activo
    $stmt_al = $pdo->prepare("SELECT m.id as matricula_id, e.cedula_escolar, e.nombre, e.apellido 
                              FROM matriculas m 
                              JOIN estudiantes e ON m.estudiante_id = e.id 
                              WHERE m.grado_seccion_id = ? AND m.ano_escolar_id = ? 
                              ORDER BY e.apellido ASC");
    $stmt_al->execute([$grado_seccion_id, $ano_activo_id]);
    $alumnos = $stmt_al->fetchAll(PDO::FETCH_ASSOC);

    foreach ($alumnos as $al) {
        // Cargar notas de todas las materias y lapsos para este alumno
        $stmt_n = $pdo->prepare("SELECT materia_id, lapso, nota_cualitativa FROM notas WHERE matricula_id = ?");
        $stmt_n->execute([$al['matricula_id']]);
        $registros = $stmt_n->fetchAll(PDO::FETCH_ASSOC);

        $notas_map = [];
        foreach ($registros as $reg) {
            $notas_map[$reg['materia_id']][$reg['lapso']] = $reg['nota_cualitativa'];
        }

        $reporte_alumnos[] = [
            'alumno' => $al,
            'notas'  => $notas_map
        ];
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=0.4">
<link rel="SHORTCUT ICON" href="../img_web/logot.png" />
    <title>Rendimiento Acad&eacute;mico - Administrador</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; background: white; }
        th, td { padding: 0.6rem; text-align: center; border: 1px solid #ddd; font-size: 0.82rem; }
        th { background-color: #1e3c72; color: white; }
        .td-nombre { text-align: left !important; font-weight: bold; }
        .nota-badge {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-weight: bold;
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
                <small>Panel Administrador</small>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">Inicio</a></li>
                <li><a href="profesores.php">Gesti&oacute;n de Profesores</a></li>
                <li><a href="estudiantes.php">Gesti&oacute;n de Estudiantes</a></li>
                <li><a href="representantes.php">Gesti&oacute;n Representantes</a></li>
                <li><a href="academico.php">A&ntilde;os y Grados</a></li>
                <li><a href="materias.php">Gesti&oacute;n de Materias</a></li>
                <li><a href="promedios.php" class="active">Rendimiento Acad&eacute;mico</a></li>
                <li><a href="solicitudes.php">Solicitudes Cupo</a></li>
                <li><a href="estadisticas.php">Estad&iacute;sticas y Resumen</a></li>
                <li><a href="institucion.php">Instituci&oacute;n</a></li>
                <li class="logout"><a href="logout.php">Cerrar Sesi&oacute;n</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header class="top-navbar">
                <h2>Consulta General de Rendimiento por Secci&oacute;n</h2>
                <div>A&ntilde;o Activo: <strong><?= $ano_activo ? htmlspecialchars($ano_activo['nombre']) : 'Sin configurar' ?></strong></div>
            </header>

            <div class="container">
                <!-- Filtro de Sección -->
                <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                    <form method="GET" action="promedios.php" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
                        <div class="form-group" style="margin-bottom:0; flex:1; min-width:250px;">
                            <label>Seleccionar Aula / Secci&oacute;n a Consultar</label>
                            <select name="grado_seccion_id" required>
                                <option value="">-- Seleccione Secci&oacute;n --</option>
                                <?php if (!empty($secciones) && count($secciones) > 0): ?>
                                    <?php foreach ($secciones as $sec): ?>
                                        <option value="<?= $sec['id'] ?>" <?= $grado_seccion_id == $sec['id'] ? 'selected' : '' ?>>
                                            <?php echo $sec['grado'] . ' - Secci&oacute;n "' . $sec['seccion'] . '"'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>No hay secciones registradas en la base de datos</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn-primary" style="width:auto; padding: 0.75rem 1.5rem;">Generar Reporte</button>
                    </form>
                </div>

                <?php if ($info_seccion): ?>
                    <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); overflow-x: auto;">
                        <h3 style="color:#1e3c72; margin-bottom: 1rem;">
                            Secci&oacute;n: <?= htmlspecialchars($info_seccion['grado'] . ' "' . $info_seccion['seccion'] . '"') ?> 
                            <span style="font-size:0.85rem; color:#666; font-weight:normal;">(Evaluaci&oacute;n Cualitativa por Materia y Lapso)</span>
                        </h3>

                        <table>
                            <thead>
                                <tr>
                                    <th rowspan="2" style="vertical-align: middle; text-align: left;">Estudiante</th>
                                    <?php foreach ($materias as $mat): ?>
                                        <th colspan="3"><?= htmlspecialchars($mat['nombre']) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                                <tr>
                                    <?php foreach ($materias as $mat): ?>
                                        <th>1L</th>
                                        <th>2L</th>
                                        <th>3L</th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($reporte_alumnos) > 0): ?>
                                    <?php foreach ($reporte_alumnos as $item): ?>
                                    <tr>
                                        <td class="td-nombre">
                                            <?= htmlspecialchars($item['alumno']['apellido'] . ', ' . $item['alumno']['nombre']) ?><br>
                                            <small style="color:#666; font-weight:normal;"><?= htmlspecialchars($item['alumno']['cedula_escolar']) ?></small>
                                        </td>
                                        <?php foreach ($materias as $mat): ?>
                                            <?php for ($l = 1; $l <= 3; $l++): ?>
                                                <td>
                                                    <?php if (isset($item['notas'][$mat['id']][$l])): ?>
                                                        <span class="nota-badge"><?= htmlspecialchars($item['notas'][$mat['id']][$l]) ?></span>
                                                    <?php else: ?>
                                                        <span style="color:#ccc;">--</span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endfor; ?>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?= 1 + (count($materias) * 3) ?>" style="text-align:center; color:#777; padding: 2rem;">
                                            No hay alumnos matriculados en esta sección para el a&ntilde;o escolar activo.
                                        </td>
                                    </tr>
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