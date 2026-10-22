<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login_admin.php');
    exit;
}

$mensaje = '';
$error = '';

// Procesar el formulario cuando se env�a para registrar un profesor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_profesor'])) {
    $cedula   = trim($_POST['cedula']);
    $nombre   = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $telefono = trim($_POST['telefono']);
    $email    = trim($_POST['email']);
    $usuario  = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    if (!empty($cedula) && !empty($nombre) && !empty($apellido) && !empty($usuario) && !empty($password)) {
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO profesores (cedula, nombre, apellido, telefono, email, usuario, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$cedula, $nombre, $apellido, $telefono, $email, $usuario, $password_hash]);
            
            $mensaje = "Profesor registrado con &eacute;xito";
        } catch (\PDOException $e) {
            $error = "Error: La c&eacute;dula o el usuario ya se encuentran registrados";
        }
    } else {
        $error = "Por favor complete los campos obligatorios.";
    }
}

// Obtener la lista de profesores
$stmt = $pdo->query("SELECT * FROM profesores ORDER BY id DESC");
$profesores = $stmt->fetchAll();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=0.4">
<link rel="SHORTCUT ICON" href="../img_web/logot.png" />
    <title>Gesti&oacute;n de Profesores - Administrador</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /*.grid-layout { display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; }
        @media(max-width: 900px) { .grid-layout { grid-template-columns: 1fr; } }*/
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; background: white; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd; font-size: 0.9rem; }
        th { background-color: #1e3c72; color: white; }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h3>E. B. "Luisa Ramona Lop&eacute;z Ru&iacute;z"</h3>
                <small>Panel Administrador</small>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">Inicio</a></li>
                <li><a href="profesores.php" class="active">Gesti&oacute;n de Profesores</a></li>
                <li><a href="estudiantes.php">Gesti&oacute;n de Estudiantes</a></li>
                <li><a href="representantes.php">Gesti&oacute;n Representantes</a></li>
                <li><a href="academico.php">A&ntilde;os y Grados</a></li>
                <li><a href="materias.php">Gesti&oacute;n de Materias</a></li>
                <li><a href="promedios.php">Rendimiento Acad&eacute;mico</a></li>
                <li><a href="solicitudes.php">Solicitudes Cupo</a></li>
                <li><a href="estadisticas.php">Estad&iacute;sticas y Resumen</a></li>
                <li><a href="institucion.php">Instituci&oacute;n</a></li>
                <li class="logout"><a href="logout.php">Cerrar SesiÇ&oacute;n</a></li>
            </ul>
        </nav>

        <!-- Contenido -->
        <main class="main-content">
            <header class="top-navbar">
                <h2>Gesti&oacute;n de Personal Docente</h2>
                <div>Admin: <strong><?= htmlspecialchars($_SESSION['admin_nombre']) ?></strong></div>
            </header>

            <div class="container">
			<div style="width:100%; text-align:right; margin-bottom:25px;"><a class="a1" onClick="document.getElementById('nuevo').style.display='block';">+ Agregar Docente</a></div>
			
			
                <?php if ($mensaje): ?><div class="alert" style="background:#d4edda; color:#155724; border:1px solid #c3e6cb; padding:0.75rem; border-radius:5px; margin-bottom:1rem;"><?= $mensaje ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

                <div>
                   

                    <!-- Tabla de Listado -->
                    <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); overflow-x: auto;">
                        <h3>Docentes Registrados</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>C&eacute;dula</th>
                                    <th>Nombre y Apellido</th>
                                    <th>Usuario</th>
                                    <th>Tel&eacute;fono</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($profesores) > 0): ?>
                                    <?php foreach ($profesores as $prof): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($prof['cedula']) ?></td>
                                        <td><?= htmlspecialchars($prof['nombre'] . ' ' . $prof['apellido']) ?></td>
                                        <td><code><?= htmlspecialchars($prof['usuario']) ?></code></td>
                                        <td><?= htmlspecialchars($prof['telefono'] ?: 'N/D') ?></td>
                                        <td><?= $prof['estado'] == 1 ? '<span style="color:green; font-weight:bold;">Activo</span>' : '<span style="color:red;">Inactivo</span>' ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: #777;">No hay profesores registrados todav&iacute;a.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

<div id="nuevo" style="background-image:url(../img_web/fondo.png); position:absolute; top:0; left:0; width:100%; height:100%; display:none;">
 <!-- Formulario de Registro -->
                    <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); width:60%; position:fixed; top:10%; left:20%;">
                        <h3>Registrar Nuevo Docente</h3>
                        <form method="POST" action="profesores.php" style="margin-top: 1rem;">
                            <div class="form-group"><input type="text" name="cedula" required placeholder="C&eacute;dula"></div>
                            <div class="form-group"><input type="text" name="nombre" required placeholder="Nombre"></div>
                            <div class="form-group"><input type="text" name="apellido" required placeholder="Apellido"></div>
                            <div class="form-group"><input type="text" name="telefono" placeholder="Tel&eacute;fono"> </div>
                            <div class="form-group"><input type="email" name="email" placeholder="Correo"></div>
                            <div class="form-group"><input type="text" name="usuario" required placeholder="Usuario"></div>
                            <div class="form-group"><input type="password" name="password" required placeholder="Contrase&ntilde;a"></div>
							<table border="0" width="100%"><tbody><tr><td width="50%">
                            <button type="submit" name="registrar_profesor" class="btn-primary">Guardar Profesor</button>
							</td><td width="50%"><center><a onClick="document.getElementById('nuevo').style.display='none';" style="cursor:pointer;">Cancelar</a></center></td></tr></tbody></table>
                        </form>
                    </div>
</div>
</body>
</html>