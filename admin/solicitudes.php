<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login_admin.php');
    exit;
}

$mensaje = '';
$error = '';

// Procesar cambio de estado o eliminaci�n
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cambiar_estado'])) {
        $solicitud_id = $_POST['solicitud_id'];
        $nuevo_estado = $_POST['estado'];

        try {
            $stmt = $pdo->prepare("UPDATE solicitudes_inscripcion SET estado = ? WHERE id = ?");
            $stmt->execute([$nuevo_estado, $solicitud_id]);
            $mensaje = "�Estado de la solicitud actualizado correctamente!";
        } catch (\PDOException $e) {
            $error = "Error al actualizar: " . $e->getMessage();
        }
    } elseif (isset($_POST['eliminar_solicitud'])) {
        $solicitud_id = $_POST['solicitud_id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM solicitudes_inscripcion WHERE id = ?");
            $stmt->execute([$solicitud_id]);
            $mensaje = "�Solicitud eliminada del registro!";
        } catch (\PDOException $e) {
            $error = "Error al eliminar: " . $e->getMessage();
        }
    }
}

// Obtener todas las solicitudes registradas ordenadas de la m�s reciente a la m�s antigua
$solicitudes = $pdo->query("SELECT * FROM solicitudes_inscripcion ORDER BY fecha DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gesti&oacute;n de Solicitudes - Administrador</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; background: white; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd; font-size: 0.88rem; }
        th { background-color: #1e3c72; color: white; }
        .badge-pendiente { background: #fff3cd; color: #856404; padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: bold; font-size: 0.75rem; }
        .badge-aprobada { background: #d4edda; color: #155724; padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: bold; font-size: 0.75rem; }
        .badge-rechazada { background: #f8d7da; color: #721c24; padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: bold; font-size: 0.75rem; }
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
                <li><a href="solicitudes.php" class="active">Solicitudes Cupo</a></li>
                <li><a href="estadisticas.php">Estad&iacute;sticas y Resumen</a></li>
                <li><a href="institucion.php">Instituci&oacute;n</a></li>
                <li class="logout"><a href="logout.php">Cerrar Sesi&oacute;n</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header class="top-navbar">
                <h2>Bandeja de Solicitudes de Inscripci&oacute;n</h2>
                <div>Admin: <strong><?= htmlspecialchars($_SESSION['admin_nombre']) ?></strong></div>
            </header>

            <div class="container">
                <?php if ($mensaje): ?><div class="alert" style="background:#d4edda; color:#155724; border:1px solid #c3e6cb; padding:0.75rem; border-radius:5px; margin-bottom:1rem;"><?= $mensaje ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

                <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); overflow-x: auto;">
                    <h3>Listado de Aspirantes Registrados desde la Web</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Representante / Tel&eacute;fono</th>
                                <th>Estudiante Aspirante</th>
                                <th>Grado Solicitado</th>
                                <th>Estado</th>
                                <th style="text-align:center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($solicitudes) > 0): ?>
                                <?php foreach ($solicitudes as $sol): ?>
                                <tr>
                                    <td><small><?= htmlspecialchars($sol['fecha']) ?></small></td>
                                    <td>
                                        <strong><?= htmlspecialchars($sol['nombre_representante']) ?></strong><br>
                                        <small style="color:#666;">C.I: <?= htmlspecialchars($sol['cedula_representante']) ?></small><br>
                                        <small style="color:#2a5298; font-weight:bold;"><?= htmlspecialchars($sol['telefono']) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($sol['nombre_estudiante']) ?></strong>
                                    </td>
                                    <td>
                                        <span style="background:#eef2f7; padding:0.2rem 0.5rem; border-radius:4px; font-weight:bold; color:#1e3c72;"><?= htmlspecialchars($sol['grado_solicitado']) ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                            $est = $sol['estado'] ?? 'Pendiente';
                                            $badge_class = 'badge-pendiente';
                                            if ($est == 'Aprobada') $badge_class = 'badge-aprobada';
                                            if ($est == 'Rechazada') $badge_class = 'badge-rechazada';
                                        ?>
                                        <span class="<?= $badge_class ?>"><?= $est ?></span>
                                    </td>
                                    <td style="text-align:center;">
                                        <!-- Formulario para cambiar estado y eliminar -->
                                        <div style="display:flex; gap:0.5rem; justify-content:center; align-items:center;">
                                            <form method="POST" action="solicitudes.php" style="display:flex; gap:0.3rem;">
                                                <input type="hidden" name="solicitud_id" value="<?= $sol['id'] ?>">
                                                <select name="estado" style="padding:0.3rem; font-size:0.8rem; border-radius:4px;">
                                                    <option value="Pendiente" <?= $est == 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                                    <option value="Aprobada" <?= $est == 'Aprobada' ? 'selected' : '' ?>>Aprobada</option>
                                                    <option value="Rechazada" <?= $est == 'Rechazada' ? 'selected' : '' ?>>Rechazada</option>
                                                </select>
                                                <button type="submit" name="cambiar_estado" class="btn-primary" style="padding:0.3rem 0.6rem; font-size:0.8rem; width:auto;">Actualizar</button>
                                            </form>

                                            <form method="POST" action="solicitudes.php" onSubmit="return confirm('�Desea eliminar esta solicitud?');">
                                                <input type="hidden" name="solicitud_id" value="<?= $sol['id'] ?>">
                                                <button type="submit" name="eliminar_solicitud" style="background:#dc3545; color:white; border:none; padding:0.3rem 0.6rem; border-radius:4px; cursor:pointer; font-size:0.8rem;">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" style="text-align: center; color: #777; padding: 2rem;">No hay solicitudes de inscripci&oacute;n registradas en este momento.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>