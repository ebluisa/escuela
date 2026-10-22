<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login_admin.php');
    exit;
}

$mensaje = '';
$error = '';

// Procesar el registro de una nueva materia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_materia'])) {
    $nombre = trim($_POST['nombre']);

    if (!empty($nombre)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO materias (nombre) VALUES (?)");
            $stmt->execute([$nombre]);
            $mensaje = "Materia registrada con &eacute;xito";
        } catch (\PDOException $e) {
            $error = "Error: Esta materia ya se encuentra registrada";
        }
    } else {
        $error = "El nombre de la materia no puede estar vac&iacute;o";
    }
}

// Obtener todas las materias registradas
$materias = $pdo->query("SELECT * FROM materias ORDER BY nombre ASC")->fetchAll();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=0.4">
<link rel="SHORTCUT ICON" href="../img_web/logot.png" />
    <title>Gesti&oacute;n de Materias - Administrador</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .grid-layout { display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; }
        @media(max-width: 900px) { .grid-layout { grid-template-columns: 1fr; } }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; background: white; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd; font-size: 0.9rem; }
        th { background-color: #1e3c72; color: white; }
    </style>
</head>
<body>
    <div  class="wrapper">
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
                <li><a href="materias.php" class="active">Gesti&oacute;n de Materias</a></li>
                <li><a href="promedios.php">Rendimiento Acad&eacute;mico</a></li>
                <li><a href="solicitudes.php">Solicitudes Cupo</a></li>
                <li><a href="estadisticas.php">Estad&iacute;sticas y Resumen</a></li>
                <li><a href="institucion.php">Instituci&oacute;n</a></li>
                <li class="logout"><a href="logout.php">Cerrar Sesi&oacute;n</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header class="top-navbar">
                <h2>Gesti&oacute;n de Materias</h2>
                <div>Admin: <strong><?= htmlspecialchars($_SESSION['admin_nombre']) ?></strong></div>
            </header>

            <div class="container">
                <?php if ($mensaje): ?><div class="alert" style="background:#d4edda; color:#155724; border:1px solid #c3e6cb; padding:0.75rem; border-radius:5px; margin-bottom:1rem;"><?= $mensaje ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

                <div class="grid-layout">
                    <!-- Formulario de Registro -->
                    <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                        <h3>Registrar Materia</h3>
                        <form method="POST" action="materias.php" style="margin-top: 1rem;">
                            <div class="form-group">
                                <label>Nombre de la Materia</label>
                                <input type="text" name="nombre" required placeholder="Ej: Matem&aacute;ticas, Lengua, Educaci&oacute;n F&iacute;sica">
                            </div>
                            <button type="submit" name="registrar_materia" class="btn-primary">Guardar Materia</button>
                        </form>
                    </div>

                    <!-- Tabla de Listado -->
                    <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); overflow-x: auto;">
                        <h3>Materias Registradas</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre de la Materia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($materias) > 0): ?>
                                    <?php foreach ($materias as $mat): ?>
                                    <tr>
                                        <td><?= $mat['id'] ?></td>
                                        <td><strong><?= htmlspecialchars($mat['nombre']) ?></strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="2" style="text-align: center; color: #777;">No hay materias registradas</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>