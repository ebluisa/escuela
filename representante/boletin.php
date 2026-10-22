<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['representante_id'])) {
    header('Location: ../login_representante.php');
    exit;
}

$rep_id = $_SESSION['representante_id'];
$ano_activo = $pdo->query("SELECT * FROM anos_escolares WHERE estado = 1 LIMIT 1")->fetch();

// Buscar los estudiantes asociados a este representante en el a�o activo
$estudiantes = [];
if ($ano_activo) {
    $stmt = $pdo->prepare("SELECT m.id as matricula_id, e.nombre, e.apellido, e.cedula_escolar, gs.grado, gs.seccion 
                           FROM matriculas m
                           JOIN estudiantes e ON m.estudiante_id = e.id
                           JOIN grados_secciones gs ON m.grado_seccion_id = gs.id
                           WHERE e.representante_id = ? AND m.ano_escolar_id = ?");
    $stmt->execute([$rep_id, $ano_activo['id']]);
    $estudiantes = $stmt->fetchAll();
}

$matricula_id_sel = isset($_GET['matricula_id']) ? $_GET['matricula_id'] : ($estudiantes[0]['matricula_id'] ?? '');

// Cargar informaci�n del bolet�n para el alumno seleccionado
$info_estudiante = null;
$boletin_materias = [];

if (!empty($matricula_id_sel) && $ano_activo) {
    // Datos del alumno
    $stmt_e = $pdo->prepare("SELECT e.nombre, e.apellido, e.cedula_escolar, gs.grado, gs.seccion 
                             FROM matriculas m
                             JOIN estudiantes e ON m.estudiante_id = e.id
                             JOIN grados_secciones gs ON m.grado_seccion_id = gs.id
                             WHERE m.id = ?");
    $stmt_e->execute([$matricula_id_sel]);
    $info_estudiante = $stmt_e->fetch();

    if ($info_estudiante) {
        $materias = $pdo->query("SELECT * FROM materias ORDER BY nombre ASC")->fetchAll();

        foreach ($materias as $mat) {
            $stmt_n = $pdo->prepare("SELECT lapso, nota_cualitativa, apreciacion FROM notas WHERE matricula_id = ? AND materia_id = ?");
            $stmt_n->execute([$matricula_id_sel, $mat['id']]);
            $registros = $stmt_n->fetchAll(PDO::FETCH_ASSOC);

            $lapsos = [1 => null, 2 => null, 3 => null];
            foreach ($registros as $reg) {
                $lapsos[$reg['lapso']] = $reg;
            }

            $boletin_materias[] = [
                'materia' => $mat['nombre'],
                'lapsos'  => $lapsos
            ];
        }
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=0.4">
<link rel="SHORTCUT ICON" href="../img_web/logot.png" />
<title>Carga de Notas - Docente</title>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bolet&iacute;n Escolar - Representante</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; background: white; }
        th, td { padding: 0.75rem; text-align: left; border: 1px solid #ddd; font-size: 0.85rem; }
        th { background-color: #1e3c72; color: white; text-align: center; }
        .ficha-card {
            background: #ffffff;
            border-left: 5px solid #28a745;
            padding: 1.2rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        .nota-badge {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.95rem;
            background: #e2e8f0;
            color: #1e293b;
        }
    </style>
</head>
<body>
    <div class="wrapper" style="display:block;">
        <!-- Header superior simple -->
        <header class="top-navbar" style="background:#1e3c72; color:white; padding:1rem 2rem; display:flex; justify-content:space-between; align-items:center;">
            <h2>Portal de Consulta del Representante</h2>
            <div>
                Bienvenido(a), <strong><?= htmlspecialchars($_SESSION['representante_nombre']) ?></strong>
                | <a href="logout.php" style="color:#ffc107; text-decoration:none; font-weight:bold; margin-left:0.5rem;">Cerrar Sesi&oacute;n</a>
            </div>
        </header>

        <main class="main-content" style="padding:2rem;">
            <div class="container" style="max-width:1100px; margin:0 auto;">
                
                <?php if (count($estudiantes) > 1): ?>
                    <!-- Selector si el representante tiene m�s de 1 hijo inscrito -->
                    <div style="background:white; padding:1rem 1.5rem; border-radius:8px; margin-bottom:1.5rem;">
                        <form method="GET" action="boletin.php" style="display:flex; align-items:center; gap:1rem;">
                            <label style="margin:0; font-weight:bold;">Seleccionar Estudiante:</label>
                            <select name="matricula_id" onChange="this.form.submit()" style="width:auto; padding:0.5rem;">
                                <?php foreach ($estudiantes as $est): ?>
                                    <option value="<?= $est['matricula_id'] ?>" <?= $matricula_id_sel == $est['matricula_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($est['nombre'] . ' ' . $est['apellido']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if ($info_estudiante): ?>
                    <div class="ficha-card">
                        <h3 style="color:#1e3c72; margin-bottom:0.4rem;"><?= htmlspecialchars($info_estudiante['nombre'] . ' ' . $info_estudiante['apellido']) ?></h3>
                        <div><strong>ID Escolar:</strong> <?= htmlspecialchars($info_estudiante['cedula_escolar']) ?> | <strong>Grado y Secci&oacute;n:</strong> <?= htmlspecialchars($info_estudiante['grado'] . ' "' . $info_estudiante['seccion'] . '"') ?></div>
                        <div style="margin-top:0.3rem;"><small style="color:#666;">A&ntilde;o Escolar Activo: <?= htmlspecialchars($ano_activo['nombre']) ?></small></div>
                    </div>

                    <div style="background: white; padding: 1.5rem; border-radius: 8px; overflow-x: auto; box-shadow:0 2px 8px rgba(0,0,0,0.05);">
                        <h3 style="margin-bottom: 1rem;">Informe Peri&oacute;dico de Evaluaci&oacute;n / Bolet&iacute;n</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th style="text-align:left;">Materia / &Aacute;rea de Aprendizaje</th>
                                    <th style="width:180px;">1er Lapso</th>
                                    <th style="width:180px;">2do Lapso</th>
                                    <th style="width:180px;">3er Lapso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($boletin_materias) > 0): ?>
                                    <?php foreach ($boletin_materias as $bm): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($bm['materia']) ?></strong></td>
                                        <?php for ($l = 1; $l <= 3; $l++): ?>
                                            <td style="text-align:center;">
                                                <?php if (!empty($bm['lapsos'][$l])): ?>
                                                    <span class="nota-badge"><?= htmlspecialchars($bm['lapsos'][$l]['nota_cualitativa']) ?></span>
                                                    <?php if (!empty($bm['lapsos'][$l]['apreciacion'])): ?>
                                                        <br><small style="color:#555; font-size:0.75rem; display:block; margin-top:0.2rem;"><?= htmlspecialchars($bm['lapsos'][$l]['apreciacion']) ?></small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span style="color:#aaa;">Sin nota</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endfor; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" style="text-align:center; color:#777;">No hay materias registradas</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="background:white; padding:2rem; border-radius:8px; text-align:center;">
                        <p style="color:#666;">No se encontraron estudiantes inscritos para este representante en el a&ntilde;o escolar activo</p>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>
</body>
</html>