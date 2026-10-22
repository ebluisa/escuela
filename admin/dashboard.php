<?php
session_start();
require_once '../config/db.php';

// Control de seguridad: Verificar si inici� sesi�n como admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login_admin.php');
    exit;
}

// Consultas r�pidas para los contadores del Dashboard
$total_estudiantes = $pdo->query("SELECT COUNT(*) FROM estudiantes")->fetchColumn();
$total_profesores  = $pdo->query("SELECT COUNT(*) FROM profesores WHERE estado = 1")->fetchColumn();
$total_grados      = $pdo->query("SELECT COUNT(*) FROM grados_secciones")->fetchColumn();
$ano_activo        = $pdo->query("SELECT nombre FROM anos_escolares WHERE estado = 1 LIMIT 1")->fetchColumn() ?: 'No configurado';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=0.4">
<link rel="SHORTCUT ICON" href="../img_web/logot.png" />
    <title>Dashboard Administrador - Sistema Escolar</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar / Men� Lateral del Administrador -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h3>E. B. "Luisa Ramona Lop&eacute;z Ru&iacute;z"</h3>
                <small>Panel Administrador</small>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active">Inicio</a></li>
                <li><a href="profesores.php">Gesti&oacute;n de Profesores</a></li>
                <li><a href="estudiantes.php">Gesti&oacute;n de Estudiantes</a></li>
                <li><a href="representantes.php">Gesti&oacute;n Representantes</a></li>
                <li><a href="academico.php">A&ntilde;os y Grados</a></li>
                <li><a href="materias.php">Gesti&oacute;n de Materias</a></li>
                <li><a href="promedios.php">Rendimiento Acad&eacute;mico</a></li>
                <li><a href="solicitudes.php">Solicitudes Cupo</a></li>
                <li><a href="estadisticas.php">Estad&iacute;sticas y Resumen</a></li>
                <li><a href="institucion.php">Instituci&oacute;n</a></li>
                <li class="logout"><a href="logout.php">Cerrar Sesi&oacute;n</a></li>
            </ul>
        </nav>

        <!-- Contenido Principal -->
        <main class="main-content">
            <header class="top-navbar">
                <h2>Bienvenido, <?= htmlspecialchars($_SESSION['admin_nombre']) ?></h2>
                <div>A&ntilde;o Escolar Activo: <strong><?= htmlspecialchars($ano_activo) ?></strong></div>
            </header>

            <div class="container">
                <div class="cards-grid">
                    <div class="card">
                        <h4>Total Estudiantes</h4>
                        <div class="number"><?= $total_estudiantes ?></div>
                    </div>
                    <div class="card">
                        <h4>Profesores Activos</h4>
                        <div class="number"><?= $total_profesores ?></div>
                    </div>
                    <div class="card">
                        <h4>Aulas / Secciones</h4>
                        <div class="number"><?= $total_grados ?></div>
                    </div>
                </div>

                <div style="background: white; padding: 1.5rem; border-radius: 8px;">
                    <h3>Panel de Control</h3>
                    <p style="margin-top: 0.5rem; color: #555;">
                        Desde este men&uacute; puedes gestionar todos los registros del plantel: inscribir estudiantes, registrar personal docente, crear representantes y asignar aulas para el a&ntilde;o lectivo.
                    </p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>