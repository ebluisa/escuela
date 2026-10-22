<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login_admin.php');
    exit;
}

$mensaje = '';
$error = '';

// Procesar actualizaci�n de textos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_config'])) {
    try {
        $pdo->beginTransaction();
        
        $claves = ['resena_historica', 'mision', 'vision', 'objetivos', 'alcance', 'avisos'];
        
        foreach ($claves as $clave) {
            if (isset($_POST[$clave])) {
                $valor = trim($_POST[$clave]);
                $stmt = $pdo->prepare("UPDATE configuracion_institucional SET contenido = ? WHERE clave = ?");
                $stmt->execute([$valor, $clave]);
            }
        }
        
        $pdo->commit();
        $mensaje = "Informaci&oacute;n institucional actualizada con &eacute;xito";
    } catch (\PDOException $e) {
        $pdo->rollBack();
        $error = "Error al actualizar: " . $e->getMessage();
    }
}

// Obtener los datos actuales de la base de datos
$config = [];
$stmt_cfg = $pdo->query("SELECT clave, contenido FROM configuracion_institucional");
while ($row = $stmt_cfg->fetch()) {
    $config[$row['clave']] = $row['contenido'];
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=0.4">
<link rel="SHORTCUT ICON" href="../img_web/logot.png" />
    <title>Configuraci&oacute;n Institucional - Administrador</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .form-card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 1.5rem; }
        textarea { width: 100%; height: 120px; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 5px; font-size: 0.95rem; font-family: inherit; resize: vertical; }
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
                <li><a href="estadisticas.php">Estad&iacute;sticas y Resumen</a></li>
                <li><a href="institucion.php" class="active">Instituci&oacute;n</a></li>
                <li class="logout"><a href="logout.php">Cerrar Sesi&oacute;n</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header class="top-navbar">
                <h2>Editar Informaci&oacute;n Institucional y Avisos</h2>
                <div>Admin: <strong><?= htmlspecialchars($_SESSION['admin_nombre']) ?></strong></div>
            </header>

            <div class="container" style="max-width: 900px;">
                <?php if ($mensaje): ?><div class="alert" style="background:#d4edda; color:#155724; border:1px solid #c3e6cb; padding:0.75rem; border-radius:5px; margin-bottom:1rem;"><?= $mensaje ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

                <form method="POST" action="institucion.php">
                    <div class="form-card">
                        <h3>Rese&ntilde;a Hist&oacute;rica</h3>
                        <textarea name="resena_historica"><?= htmlspecialchars($config['resena_historica'] ?? '') ?></textarea>
                    </div>

                    <div class="form-card">
                        <h3>Misi&oacute;n Institucional</h3>
                        <textarea name="mision"><?= htmlspecialchars($config['mision'] ?? '') ?></textarea>
                    </div>

                    <div class="form-card">
                        <h3>Visi&oacute;n Institucional</h3>
                        <textarea name="vision"><?= htmlspecialchars($config['vision'] ?? '') ?></textarea>
                    </div>

                    <div class="form-card">
                        <h3>Objetivos Institucionales</h3>
                        <textarea name="objetivos"><?= htmlspecialchars($config['objetivos'] ?? '') ?></textarea>
                    </div>

                    <div class="form-card">
                        <h3>Alcance de la Instituci&oacute;n</h3>
                        <textarea name="alcance"><?= htmlspecialchars($config['alcance'] ?? '') ?></textarea>
                    </div>

                    <div class="form-card" style="border-left: 5px solid #ffc107;">
                        <h3 style="color: #856404;">Avisos e Inscripciones (Cartelera P&uacute;blica)</h3>
                        <textarea name="avisos"><?= htmlspecialchars($config['avisos'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" name="guardar_config" class="btn-primary" style="padding: 0.9rem; font-size: 1rem; width: 100%;">Guardar Cambios Institucionales</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>