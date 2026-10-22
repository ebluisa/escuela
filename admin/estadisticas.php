<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login_admin/');
    exit;
}

$ano_activo = $pdo->query("SELECT * FROM anos_escolares WHERE estado = 1 LIMIT 1")->fetch();
$ano_activo_id = $ano_activo ? $ano_activo['id'] : 0;

// 1. Estadísticas Generales (Contadores)
$total_estudiantes = $pdo->query("SELECT COUNT(*) FROM estudiantes")->fetchColumn();
$total_profesores  = $pdo->query("SELECT COUNT(*) FROM profesores")->fetchColumn();
$total_representantes = $pdo->query("SELECT COUNT(*) FROM representantes")->fetchColumn();

// Total de matrículas en el año escolar activo
$stmt_mat = $pdo->prepare("SELECT COUNT(*) FROM matriculas WHERE ano_escolar_id = ?");
$stmt_mat->execute([$ano_activo_id]);
$total_matriculas_activo = $stmt_mat->fetchColumn();

// 2. Solicitudes de inscripción pendientes y aprobadas
$solicitudes_stats = $pdo->query("SELECT estado, COUNT(*) as total FROM solicitudes_inscripcion GROUP BY estado")->fetchAll(PDO::FETCH_KEY_PAIR);
$pendientes = $solicitudes_stats['Pendiente'] ?? 0;
$aprobadas  = $solicitudes_stats['Aprobada'] ?? 0;
$rechazadas = $solicitudes_stats['Rechazada'] ?? 0;

// 3. Matrícula desglosada por Grado / Sección en el año activo
$estadistica_secciones = [];
if ($ano_activo_id) {
    $q_sec = "SELECT gs.grado, gs.seccion, COUNT(m.id) as inscritos
              FROM grados_secciones gs
              LEFT JOIN matriculas m ON gs.id = m.grado_seccion_id AND m.ano_escolar_id = ?
              GROUP BY gs.id
              ORDER BY gs.grado ASC, gs.seccion ASC";
    $stmt_sec = $pdo->prepare($q_sec);
    $stmt_sec->execute([$ano_activo_id]);
    $estadistica_secciones = $stmt_sec->fetchAll();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=0.4">
<link rel="SHORTCUT ICON" href="../img_web/logot.png" />
    <title>Estad&iacute;sticas y Res&uacute;menes - Administrador</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 5px solid #1e3c72;
        }
        .stat-card h4 { margin: 0 0 0.5rem 0; color: #666; font-size: 0.9rem; text-transform: uppercase; }
        .stat-card .number { font-size: 2rem; font-weight: bold; color: #1e3c72; margin: 0; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; background: white; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd; font-size: 0.9rem; }
        th { background-color: #1e3c72; color: white; }
        
        .section-box { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 2rem; }
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
                <li><a href="promedios.php">Rendimiento Acad&eacute;mico</a></li>
                <li><a href="solicitudes.php">Solicitudes Cupo</a></li>
                <li><a href="estadisticas.php" class="active">Estad&iacute;sticas y Resumen</a></li>
                <li><a href="institucion.php">Instituci&oacute;n</a></li>
                <li class="logout"><a href="logout.php">Cerrar Sesi&oacute;n</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header class="top-navbar">
                <h2>Panel de Estadísticas y Res&uacute;menes Académicos</h2>
                <div>Año Activo: <strong><?= $ano_activo ? htmlspecialchars($ano_activo['nombre']) : 'Sin configurar' ?></strong></div>
            </header>

            <div class="container">
                <!-- Tarjetas de Contadores Globales -->
                <div class="stats-grid">
                    <div class="stat-card" style="border-left-color: #1e3c72;">
                        <h4>Matrícula Activa</h4>
                        <p class="number"><?= $total_matriculas_activo ?></p>
                        <small style="color:#666;">Alumnos inscritos en el per&iacute;odo</small>
                    </div>

                    <div class="stat-card" style="border-left-color: #28a745;">
                        <h4>Total Estudiantes</h4>
                        <p class="number"><?= $total_estudiantes ?></p>
                        <small style="color:#666;">Registrados en la base de datos</small>
                    </div>

                    <div class="stat-card" style="border-left-color: #ffc107;">
                        <h4>Docentes Activos</h4>
                        <p class="number"><?= $total_profesores ?></p>
                        <small style="color:#666;">Profesores en plantilla</small>
                    </div>

                    <div class="stat-card" style="border-left-color: #17a2b8;">
                        <h4>Representantes</h4>
                        <p class="number"><?= $total_representantes ?></p>
                        <small style="color:#666;">Representantes registrados</small>
                    </div>
                </div>

                <!-- Resumen de Solicitudes Web -->
                <div class="section-box">
                    <h3 style="color:#1e3c72; margin-top:0;">Estado de Solicitudes de Inscripci&oacute;n (Web)</h3>
                    <div style="display: flex; gap: 2rem; margin-top: 1rem; flex-wrap: wrap;">
                        <div style="background: #fff3cd; padding: 1rem 1.5rem; border-radius: 6px; flex:1; min-width:180px; border-left:4px solid #ffc107;">
                            <span style="font-size: 0.85rem; font-weight: bold; color: #856404;">PENDIENTES</span>
                            <h2 style="margin: 0.3rem 0 0; color: #856404;"><?= $pendientes ?></h2>
                        </div>
                        <div style="background: #d4edda; padding: 1rem 1.5rem; border-radius: 6px; flex:1; min-width:180px; border-left:4px solid #28a745;">
                            <span style="font-size: 0.85rem; font-weight: bold; color: #155724;">APROBADAS</span>
                            <h2 style="margin: 0.3rem 0 0; color: #155724;"><?= $aprobadas ?></h2>
                        </div>
                        <div style="background: #f8d7da; padding: 1rem 1.5rem; border-radius: 6px; flex:1; min-width:180px; border-left:4px solid #dc3545;">
                            <span style="font-size: 0.85rem; font-weight: bold; color: #721c24;">RECHAZADAS</span>
                            <h2 style="margin: 0.3rem 0 0; color: #721c24;"><?= $rechazadas ?></h2>
                        </div>
                    </div>
                </div>

                <!-- Desglose de Matrícula por Grado y Sección -->
                <div class="section-box">
                    <h3 style="color:#1e3c72; margin-top:0;">Distribuci&oacute;n de Matr&iacute;cula por Aula / Sección</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Grado</th>
                                <th>Secci&oacute;n</th>
                                <th style="text-align:center;">Alumnos Inscritos</th>
                                <th style="text-align:right;">Capacidad / Ocupaci&oacute;n</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($estadistica_secciones) > 0): ?>
                                <?php foreach ($estadistica_secciones as $sec): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($sec['grado']) ?></strong></td>
                                    <td>Sección "<?= htmlspecialchars($sec['seccion']) ?>"</td>
                                    <td style="text-align:center;">
                                        <span style="background: #eef2f7; padding: 0.3rem 0.8rem; border-radius: 4px; font-weight: bold; color: #1e3c72;">
                                            <?= $sec['inscritos'] ?> alumnos
                                        </span>
                                    </td>
                                    <td style="text-align:right; color: #666;">Operativa</td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align:center; color:#777;">No hay secciones configuradas o a&ntilde;o escolar activo.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </main>
    </div>
</body>
</html>