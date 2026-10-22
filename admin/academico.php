<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login_admin.php');
    exit;
}

$mensaje = '';
$error = '';

// 1. Procesar Creación de Año Escolar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_ano'])) {
    $nombre_ano = trim($_POST['nombre_ano']);
    
    if (!empty($nombre_ano)) {
        try {
            // Si este nuevo año va a ser activo, desactivamos los demás
            if (isset($_POST['activo']) && $_POST['activo'] == '1') {
                $pdo->exec("UPDATE anos_escolares SET estado = 0");
                $estado = 1;
            } else {
                $estado = 0;
            }

            $stmt = $pdo->prepare("INSERT INTO anos_escolares (nombre, estado) VALUES (?, ?)");
            $stmt->execute([$nombre_ano, $estado]);
            $mensaje = "A&ntilde;o escolar registrado con &eacute;xito!";
        } catch (\PDOException $e) {
            $error = "Error al registrar el a&ntilde;o escolar";
        }
    } else {
        $error = "El nombre del a&ntilde;o escolar no puede estar vac&iacute;o.";
    }
}

// 2. Procesar Creación de Grados y Secciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_grado'])) {
    $grado       = trim($_POST['grado']);
    $seccion     = trim($_POST['seccion']);
    $profesor_id = !empty($_POST['profesor_id']) ? $_POST['profesor_id'] : null;

    if (!empty($grado) && !empty($seccion)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO grados_secciones (grado, seccion, profesor_id) VALUES (?, ?, ?)");
            $stmt->execute([$grado, $seccion, $profesor_id]);
            $mensaje = "Grado y secci&oacute;n creados con &eacute;xito";
        } catch (\PDOException $e) {
            $error = "Error al registrar el grado o secci&oacute;n.";
        }
    } else {
        $error = "Por favor complete el grado y la secci&oacute;n.";
    }
}

// Obtener Años Escolares
$anos = $pdo->query("SELECT * FROM anos_escolares ORDER BY id DESC")->fetchAll();

// Obtener Profesores para asignarlos como guías de sección
$profesores = $pdo->query("SELECT id, nombre, apellido FROM profesores WHERE estado = 1")->fetchAll();

// Obtener Grados y Secciones con su profesor asignado (si lo tiene)
$grados_query = "SELECT gs.*, p.nombre as p_nombre, p.apellido as p_apellido 
                 FROM grados_secciones gs 
                 LEFT JOIN profesores p ON gs.profesor_id = p.id 
                 ORDER BY gs.grado, gs.seccion";
$grados_list = $pdo->query($grados_query)->fetchAll();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=0.4">
<link rel="SHORTCUT ICON" href="../img_web/logot.png" />
    <title>Configuraci&oacute;n Acad&eacute;mica - Administrador</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .grid-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem; }
        @media(max-width: 900px) { .grid-layout { grid-template-columns: 1fr; } }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; background: white; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd; font-size: 0.9rem; }
        th { background-color: #1e3c72; color: white; }
        .box-section { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
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
                <li><a href="profesores.php">Gesti&oacute;n de Profesores</a></li>
                <li><a href="estudiantes.php">Gesti&oacute;n de Estudiantes</a></li>
                <li><a href="representantes.php">Gesti&oacute;n Representantes</a></li>
                <li><a href="academico.php" class="active">A&ntilde;os y Grados</a></li>
                <li><a href="materias.php">Gesti&oacute;n de Materias</a></li>
                <li><a href="promedios.php">Rendimiento Acad&eacute;mico</a></li>
                <li><a href="solicitudes.php">Solicitudes Cupo</a></li>
                <li><a href="estadisticas.php">Estad&iacute;sticas y Resumen</a></li>
                <li><a href="institucion.php">Instituci&oacute;n</a></li>
                <li class="logout"><a href="logout.php">Cerrar Sesi&oacute;n</a></li>
            </ul>
        </nav>

        <!-- Contenido -->
        <main class="main-content">
            <header class="top-navbar">
                <h2>Configuración Acad&eacute;mica</h2>
                <div>Admin: <strong><?= htmlspecialchars($_SESSION['admin_nombre']) ?></strong></div>
            </header>

            <div class="container">
                <?php if ($mensaje): ?><div class="alert" style="background:#d4edda; color:#155724; border:1px solid #c3e6cb; padding:0.75rem; border-radius:5px; margin-bottom:1rem;"><?= $mensaje ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

                <!-- SECCIÓN 1: AÑOS ESCOLARES -->
                <div class="grid-layout">
                    <div class="box-section">
                        <h3>Registrar A&ntilde;o Escolar</h3>
                        <form method="POST" action="academico.php" style="margin-top: 1rem;">
                            <div class="form-group">
                                <label>Nombre del A&ntilde;o Escolar *</label>
                                <input type="text" name="nombre_ano" required placeholder="Ej: 2025-2026">
                            </div>
                            <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="activo" value="1" id="activo" style="width: auto;">
                                <label for="activo" style="margin-bottom: 0; cursor: pointer;">Establecer como A&ntilde;o Escolar Activo</label>
                            </div>
                            <button type="submit" name="crear_ano" class="btn-primary">Guardar A&ntilde;o Escolar</button>
                        </form>
                    </div>

                    <div class="box-section">
                        <h3>A&ntilde;os Escolares Registrados</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>A&ntilde;o Escolar</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($anos) > 0): ?>
                                    <?php foreach ($anos as $a): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($a['nombre']) ?></td>
                                        <td><?= $a['estado'] == 1 ? '<span style="color:green; font-weight:bold;">Activo</span>' : '<span style="color:#777;">Inactivo</span>' ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="2" style="text-align: center; color: #777;">No hay a&ntilde;os escolares creados.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SECCIÓN 2: GRADOS Y SECCIONES -->
                <div class="grid-layout">
                    <div class="box-section">
                        <h3>Crear Grado y Sección</h3>
                        <form method="POST" action="academico.php" style="margin-top: 1rem;">
                            <div class="form-group">
                                <label>Grado *</label>
                                <select name="grado" required>
                                    <option value="">Seleccione el grado</option>
                                    <option value="1er Grado">1er Grado</option>
                                    <option value="2do Grado">2do Grado</option>
                                    <option value="3er Grado">3er Grado</option>
                                    <option value="4to Grado">4to Grado</option>
                                    <option value="5to Grado">5to Grado</option>
                                    <option value="6to Grado">6to Grado</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Sección *</label>
                                <input type="text" name="seccion" required placeholder="Ej: A o B">
                            </div>
                            <div class="form-group">
                                <label>Profesor Guía (Opcional)</label>
                                <select name="profesor_id">
                                    <option value="">-- Sin asignar --</option>
                                    <?php foreach ($profesores as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre'] . ' ' . $p['apellido']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="crear_grado" class="btn-primary">Guardar Grado/Secci&oacute;n</button>
                        </form>
                    </div>

                    <div class="box-section">
                        <h3>Aulas y Docentes Gu&iacute;as</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Grado y Secci&oacute;n</th>
                                    <th>Profesor Gu&iacute;a</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($grados_list) > 0): ?>
                                    <?php foreach ($grados_list as $g): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($g['grado'] . ' - ' . $g['seccion']) ?></strong></td>
                                        <td><?= $g['p_nombre'] ? htmlspecialchars($g['p_nombre'] . ' ' . $g['p_apellido']) : '<span style="color:#999;">Sin asignar</span>' ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="2" style="text-align: center; color: #777;">No hay secciones creadas todav&iacute;a.</td></tr>
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