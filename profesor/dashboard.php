<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['profesor_id'])) {
    header('Location: ../login_profesor.php');
    exit;
}

$profesor_id = $_SESSION['profesor_id'];

// Obtener Año Escolar Activo
$ano_activo = $pdo->query("SELECT * FROM anos_escolares WHERE estado = 1 LIMIT 1")->fetch();

// Obtener Secciones a cargo del profesor
$secciones = [];
if ($ano_activo) {
    $stmt = $pdo->prepare("SELECT gs.*, 
                           (SELECT COUNT(*) FROM matriculas m WHERE m.grado_seccion_id = gs.id AND m.ano_escolar_id = ?) as total_alumnos
                           FROM grados_secciones gs 
                           WHERE gs.profesor_id = ?");
    $stmt->execute([$ano_activo['id'], $profesor_id]);
    $secciones = $stmt->fetchAll();
}

// Obtener materias registradas en el sistema
$materias = $pdo->query("SELECT * FROM materias ORDER BY nombre ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Docente - Escuela Básica</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .materia-badge {
            display: inline-block;
            background: #eef2f7;
            color: #1e3c72;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            font-size: 0.85rem;
            margin: 0.2rem 0.2rem 0.2rem 0;
            text-decoration: none;
            border: 1px solid #cbd5e1;
            transition: all 0.2s ease;
        }
        .materia-badge:hover {
            background: #1e3c72;
            color: #ffffff;
            border-color: #1e3c72;
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
                <li><a href="dashboard.php" class="active">Mis Secciones</a></li>
                <li><a href="notas.php">Carga de Notas</a></li>
                <li><a href="ver_notas.php">Ver Notas por Materia</a></li>
                <li><a href="resumen_alumno.php">Resumen por Alumno</a></li>
                <li><a href="asistencia.php">Control de Asistencia</a></li>
                <li class="logout"><a href="logout.php">Cerrar Sesi&oacute;n</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header class="top-navbar">
                <h2>Bienvenido(a), Prof. <?= htmlspecialchars($_SESSION['profesor_nombre']) ?></h2>
                <div>Año Activo: <strong><?= $ano_activo ? htmlspecialchars($ano_activo['nombre']) : 'Sin configurar' ?></strong></div>
            </header>

            <div class="container">
                <h3 style="margin-bottom: 1rem;">Aulas Asignadas a su Cargo</h3>

                <div class="cards-grid">
                    <?php if (count($secciones) > 0): ?>
                        <?php foreach ($secciones as $sec): ?>
                            <div class="card" style="border-left-color: #2a5298;">
                                <h4><?= htmlspecialchars($sec['grado'] . ' - Sección "' . $sec['seccion'] . '"') ?></h4>
                                <div class="number"><?= $sec['total_alumnos'] ?> <span style="font-size:1rem; color:#666; font-weight:normal;">alumnos</span></div>
                                
                                <div style="margin-top: 1rem;">
                                    <small style="font-weight: bold; color: #555; display: block; margin-bottom: 0.4rem;">Consultar boletín por materia:</small>
                                    <?php if (count($materias) > 0): ?>
                                        <?php foreach ($materias as $mat): ?>
                                            <a href="ver_notas.php?grado_seccion_id=<?= $sec['id'] ?>&materia_id=<?= $mat['id'] ?>" class="materia-badge">
                                                <?= htmlspecialchars($mat['nombre']) ?> →
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <small style="color:#888;">No hay materias registradas a&uacute;n</small>
                                    <?php endif; ?>
                                </div>

                                <div style="margin-top: 1.2rem; display:flex; gap:0.5rem;">
                                    <a href="notas.php?grado_seccion_id=<?= $sec['id'] ?>" class="btn-primary" style="text-decoration:none; text-align:center; font-size:0.85rem; padding:0.5rem;">Cargar Notas</a>
                                    <a href="asistencia.php?grado_seccion_id=<?= $sec['id'] ?>" class="btn-primary" style="background:#28a745; text-decoration:none; text-align:center; font-size:0.85rem; padding:0.5rem;">Asistencia</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="background:white; padding:1.5rem; border-radius:8px; width:100%;">
                            <p style="color:#666;">No tiene aulas/secciones asignadas como docente gu&iacute;a para este a&ntilde;o escolar. Por favor consulte con la direcci&oacute;n administrativa.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>