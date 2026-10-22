<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login_admin.php');
    exit;
}

$mensaje = '';
$error = '';

// Obtener A�o Escolar Activo
$ano_activo = $pdo->query("SELECT * FROM anos_escolares WHERE estado = 1 LIMIT 1")->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_estudiante'])) {
    if (!$ano_activo) {
        $error = "Debe activar un A&ntilde;o Escolar primero en el m&oacute;dulo 'A&ntilde;os y Grados'.";
    } else {
        $cedula_escolar = trim($_POST['cedula_escolar']);
        $nombre         = trim($_POST['nombre']);
        $apellido       = trim($_POST['apellido']);
        $fecha_nac      = trim($_POST['fecha_nacimiento']);
        $sexo           = trim($_POST['sexo']);
        $observaciones  = trim($_POST['observaciones_medicas']);
        $rep_id         = $_POST['representante_id'];
        $grado_sec_id   = $_POST['grado_seccion_id'];

        if (!empty($cedula_escolar) && !empty($nombre) && !empty($apellido) && !empty($fecha_nac) && !empty($rep_id) && !empty($grado_sec_id)) {
            try {
                $pdo->beginTransaction();

                // 1. Guardar Estudiante
                $stmt = $pdo->prepare("INSERT INTO estudiantes (cedula_escolar, nombre, apellido, fecha_nacimiento, sexo, observaciones_medicas, representante_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$cedula_escolar, $nombre, $apellido, $fecha_nac, $sexo, $observaciones, $rep_id]);
                $estudiante_id = $pdo->lastInsertId();

                // 2. Crear Matr�cula/Inscripci�n para el a�o lectivo activo
                $fecha_actual = date('Y-m-d');
                $stmt_mat = $pdo->prepare("INSERT INTO matriculas (estudiante_id, grado_seccion_id, ano_escolar_id, fecha_inscripcion) VALUES (?, ?, ?, ?)");
                $stmt_mat->execute([$estudiante_id, $grado_sec_id, $ano_activo['id'], $fecha_actual]);

                $pdo->commit();
                $mensaje = "Estudiante inscrito correctamente";
            } catch (\PDOException $e) {
                $pdo->rollBack();
                $error = "Error: El ID Escolar ya existe o hubo un fallo en la base de datos";
            }
        } else {
            $error = "Por favor complete los campos obligatorios";
        }
    }
}

// Obtener datos auxiliares
$representantes = $pdo->query("SELECT id, cedula, nombre, apellido FROM representantes ORDER BY apellido ASC")->fetchAll();
$grados_secciones = $pdo->query("SELECT id, grado, seccion FROM grados_secciones ORDER BY grado, seccion")->fetchAll();

// Listado de estudiantes inscritos en el a�o activo
$estudiantes_list = [];
if ($ano_activo) {
    $q = "SELECT e.*, r.nombre as r_nombre, r.apellido as r_apellido, gs.grado, gs.seccion 
          FROM matriculas m
          JOIN estudiantes e ON m.estudiante_id = e.id
          JOIN representantes r ON e.representante_id = r.id
          JOIN grados_secciones gs ON m.grado_seccion_id = gs.id
          WHERE m.ano_escolar_id = ?
          ORDER BY gs.grado, gs.seccion, e.apellido";
    $stmt_e = $pdo->prepare($q);
    $stmt_e->execute([$ano_activo['id']]);
    $estudiantes_list = $stmt_e->fetchAll();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=0.4">
<link rel="SHORTCUT ICON" href="../img_web/logot.png" />
    <title>Inscripci&oacute;n de Estudiantes - Administrador</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .grid-layout { display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; }
        @media(max-width: 900px) { .grid-layout { grid-template-columns: 1fr; } }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; background: white; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd; font-size: 0.85rem; }
        th { background-color: #1e3c72; color: white; }
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
                <li><a href="estudiantes.php" class="active">Gesti&oacute;n de Estudiantes</a></li>
                <li><a href="representantes.php">Gesti&oacute;n Representantes</a></li>
                <li><a href="academico.php">A&ntilde;os y Grados</a></li>
                <li><a href="materias.php">Gesti&oacute;n de Materias</a></li>
                <li><a href="promedios.php">Rendimiento Acad&eacute;mico</a></li>
                <li><a href="solicitudes.php">Solicitudes Cupo</a></li>
                <li><a href="estadisticas.php">Estad&iacute;sticas y Resumen</a></li>
                <li><a href="institucion.php">Instituci&oacute;n</a></li>
                <li class="logout"><a href="logout.php">Cerrar Sesión</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header class="top-navbar">
                <h2>Control de Estudiantes y Matr&iacute;cula</h2>
                <div>A&ntilde;o Activo: <strong><?= $ano_activo ? htmlspecialchars($ano_activo['nombre']) : 'Ninguno' ?></strong></div>
            </header>

            <div class="container">
                <?php if ($mensaje): ?><div class="alert" style="background:#d4edda; color:#155724; border:1px solid #c3e6cb; padding:0.75rem; border-radius:5px; margin-bottom:1rem;"><?= $mensaje ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

                <div class="grid-layout">
                    <!-- Formulario de Inscripci�n -->
                    <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                        <h3>Inscribir Estudiante</h3>
                        <form method="POST" action="estudiantes.php" style="margin-top: 1rem;">
                            <div class="form-group">
                                <input type="text" name="cedula_escolar" required placeholder="ID Escolar">
                            </div>
                            <div class="form-group">
                                <input type="text" name="nombre" required placeholder="Nombre">
                            </div>
                            <div class="form-group">
                                <input type="text" name="apellido" required placeholder="Apellido">
                            </div>
                            <div class="form-group">
                                <label>Fecha de Nacimiento</label>
                                <input type="date" name="fecha_nacimiento" required>
                            </div>
                            <div class="form-group">
                                <label>Sexo</label>
                                <select name="sexo" required>
                                    <option value="M">Masculino</option>
                                    <option value="F">Femenino</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Representante</label>
                                <select name="representante_id" required>
                                    <option value="">Seleccione Representante</option>
                                    <?php foreach ($representantes as $rep): ?>
                                        <option value="<?= $rep['id'] ?>"><?= htmlspecialchars($rep['cedula'] . ' - ' . $rep['nombre'] . ' ' . $rep['apellido']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Grado y Secci&oacute;n a Asignar</label>
                                <select name="grado_seccion_id" required>
                                    <option value="">Seleccione Aula</option>
                                    <?php foreach ($grados_secciones as $gs): ?>
                                        <option value="<?= $gs['id'] ?>"><?= htmlspecialchars($gs['grado'] . ' - Seccion ' . $gs['seccion']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Observaciones M&eacute;dicas</label>
                                <input type="text" name="observaciones_medicas" placeholder="Alergias, medicamentos, etc.">
                            </div>
                            <button type="submit" name="registrar_estudiante" class="btn-primary">Inscribir Alumno</button>
                        </form>
                    </div>

                    <!-- Listado -->
                    <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); overflow-x: auto;">
                        <h3>Alumnos Inscritos en el A&ntilde;o Lectivo</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID Escolar</th>
                                    <th>Estudiante</th>
                                    <th>Aula</th>
                                    <th>Representante</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($estudiantes_list) > 0): ?>
                                    <?php foreach ($estudiantes_list as $est): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($est['cedula_escolar']) ?></td>
                                        <td><?= htmlspecialchars($est['nombre'] . ' ' . $est['apellido']) ?></td>
                                        <td><strong><?= htmlspecialchars($est['grado'] . ' "' . $est['seccion'] . '"') ?></strong></td>
                                        <td><?= htmlspecialchars($est['r_nombre'] . ' ' . $est['r_apellido']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" style="text-align: center; color: #777;">No hay alumnos inscritos en este a&ntilde;o escolar</td></tr>
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