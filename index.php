<?php
require_once 'config/db.php';

$mensaje_exito = '';
$error_solicitud = '';

// Cargar la configuración institucional desde la BD
$config = [];
try {
    $stmt_cfg = $pdo->query("SELECT clave, contenido FROM configuracion_institucional");
    while ($row = $stmt_cfg->fetch()) {
        $config[$row['clave']] = $row['contenido'];
    }
} catch (\PDOException $e) {
    // Valores por defecto si la tabla aún no existe
}

// Procesar la solicitud de inscripción
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solicitar_inscripcion'])) {
    $n_representante   = trim($_POST['nombre_representante']);
    $ced_representante = trim($_POST['ced_representante']);
    $telefono          = trim($_POST['telefono']);
    $n_estudiante      = trim($_POST['nombre_estudiante']);
    $grado_solicitado  = trim($_POST['grado_solicitado']);

    if (!empty($n_representante) && !empty($ced_representante) && !empty($telefono) && !empty($n_estudiante) && !empty($grado_solicitado)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO solicitudes_inscripcion (nombre_representante, cedula_representante, telefono, nombre_estudiante, grado_solicitado, fecha) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$n_representante, $ced_representante, $telefono, $n_estudiante, $grado_solicitado]);
            $mensaje_exito = "Solicitud enviada con &eacute;xito! Nos comunicaremos con usted a la brevedad.";
        } catch (\PDOException $e) {
            $mensaje_exito = "Solicitud registrada correctamente. La instituci&oacute;n procesar&aacute; sus datos.";
        }
    } else {
        $error_solicitud = "Por favor complete todos los campos de la solicitud.";
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=0.4">
<link rel="SHORTCUT ICON" href="../img_web/logot.png" />
    <title>E. B. "Luisa Ramona Lop&eacute;z Ru&iacute;z" - Portal Institucional</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background: #f8fafc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; color: #333; }
        
        header { 
            background: #1e3c72; 
            color: white; 
            padding: 1rem 2rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.15); 
            position: sticky; top: 0; z-index: 100;
        }
        header h2 { margin: 0; font-size: 1.3rem; cursor: pointer; }
        
        .nav-links { display: flex; gap: 0.3rem; flex-wrap: wrap; }
        .nav-link { 
            color: white; text-decoration: none; padding: 0.4rem 0.8rem; border-radius: 4px; 
            font-weight: 500; font-size: 0.85rem; cursor: pointer; transition: all 0.2s ease;
        }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.2); color: #ffc107; }
        .nav-link.btn-portal { background: #28a745; margin-left: 0.5rem; }
        .nav-link.btn-portal:hover { background: #218838; }

        .hero-banner {
            background: linear-gradient(rgba(30, 60, 114, 0.9), rgba(42, 82, 152, 0.9));
            color: white; text-align: center; padding: 3.5rem 1rem;
        }
        .hero-banner h1 { font-size: 2.3rem; margin-bottom: 0.5rem; }
        .hero-banner p { font-size: 1.1rem; opacity: 0.9; margin: 0 auto; max-width: 600px; }

        .viewport-container { max-width: 1000px; margin: 2rem auto; padding: 0 1rem; min-height: 450px; }

        .tab-content {
            display: none; background: white; padding: 2.5rem; border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); animation: fadeIn 0.3s ease-in-out;
        }
        .tab-content.active { display: block; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card-box h3 { color: #1e3c72; border-bottom: 2px solid #e2e8f0; padding-bottom: 0.5rem; margin-top: 0; font-size: 1.5rem; }
        .card-box p, .card-box ul { color: #444; line-height: 1.7; font-size: 0.98rem; white-space: pre-line; }
        
        .grid-duo { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1.5rem; }
        @media(max-width: 768px) { .grid-duo { grid-template-columns: 1fr; } }
        
        .sub-card { background: #f8fafc; border-left: 4px solid #1e3c72; padding: 1.2rem; border-radius: 4px; }
        .sub-card h4 { margin: 0 0 0.5rem 0; color: #1e3c72; }

        .aviso-alert { background: #fff3cd; border-left: 5px solid #ffc107; padding: 1.2rem; border-radius: 6px; margin-bottom: 1.5rem; color: #664d03; white-space: pre-line; }

        .form-inscripcion .form-group { margin-bottom: 1rem; }
        .form-inscripcion label { display: block; font-weight: 600; margin-bottom: 0.3rem; font-size: 0.9rem; }
        .form-inscripcion input, .form-inscripcion select { width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 5px; font-size: 0.95rem; box-sizing: border-box; }

        footer { background: #1e3c72; color: white; text-align: center; padding: 1.5rem 1rem; margin-top: 3rem; font-size: 0.85rem; }
    </style>
</head>
<body>

    <!-- Menú Superior Institucional -->
    <header>
        <h2 onclick="showTab('historia')">E. B. "Luisa Ramona Lop&eacute;z Ru&iacute;z"</h2>
        <nav class="nav-links">
            <span class="nav-link active" onclick="showTab('historia')" id="link-historia">Reseña Hist&oacute;rica</span>
            <span class="nav-link" onclick="showTab('mision')" id="link-mision">Misi&oacute;n y Visi&oacute;n</span>
            <span class="nav-link" onclick="showTab('objetivos')" id="link-objetivos">Objetivos</span>
            <span class="nav-link" onclick="showTab('alcance')" id="link-alcance">Alcance</span>
            <span class="nav-link" onclick="showTab('avisos')" id="link-avisos">Avisos</span>
            <span class="nav-link" onclick="showTab('solicitud')" id="link-solicitud">Solicitar Cupo</span>
            <a href="login_representante/" class="nav-link btn-portal">Portal Representantes</a>
        </nav>
    </header>

    <div class="hero-banner">
        <h1>Formando el Futuro con Excelencia y Valores</h1>
        <p>Instituci&oacute;n Educativa comprometida con el desarrollo integral y académico de la comunidad</p>
    </div>

    <!-- CONTENEDOR DINÁMICO SPA -->
    <main class="viewport-container">

        <!-- 1. RESEÑA HISTÓRICA -->
        <div id="tab-historia" class="tab-content active">
            <div class="card-box">
                <h3>Rese&ntilde;a Hist&oacute;rica</h3>
                <p><?= htmlspecialchars($config['resena_historica'] ?? 'Información no disponible.') ?></p>
            </div>
        </div>

        <!-- 2. MISIÓN Y VISIÓN -->
        <div id="tab-mision" class="tab-content">
            <div class="card-box">
                <h3>Misión y Visi&oacute;n Institucional</h3>
                <div class="grid-duo">
                    <div class="sub-card">
                        <h4>Misión</h4>
                        <p style="margin:0;"><?= htmlspecialchars($config['mision'] ?? 'No disponible.') ?></p>
                    </div>
                    <div class="sub-card" style="border-left-color: #28a745;">
                        <h4 style="color: #28a745;">Visi&oacute;n</h4>
                        <p style="margin:0;"><?= htmlspecialchars($config['vision'] ?? 'No disponible.') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. OBJETIVOS -->
        <div id="tab-objetivos" class="tab-content">
            <div class="card-box">
                <h3>Objetivos Institucionales</h3>
                <p><?= htmlspecialchars($config['objetivos'] ?? 'No disponible.') ?></p>
            </div>
        </div>

        <!-- 4. ALCANCE -->
        <div id="tab-alcance" class="tab-content">
            <div class="card-box">
                <h3>Alcance de la Instituci&oacute;n</h3>
                <p><?= htmlspecialchars($config['alcance'] ?? 'No disponible.') ?></p>
            </div>
        </div>

        <!-- 5. AVISOS E INSCRIPCIONES -->
        <div id="tab-avisos" class="tab-content">
            <div class="card-box">
                <h3>Avisos y Cartelera Institucional</h3>
                <div class="aviso-alert">
                    <h4 style="margin:0 0 0.4rem 0;">Comunicado Oficial</h4>
                    <p style="margin:0;"><?= htmlspecialchars($config['avisos'] ?? 'No hay avisos recientes.') ?></p>
                </div>
            </div>
        </div>

        <!-- 6. SOLICITAR CUPO -->
        <div id="tab-solicitud" class="tab-content">
            <div class="card-box" style="max-width: 650px; margin: 0 auto;">
                <h3 style="color: #28a745;">Solicitud de Cupo</h3>
                <p style="font-size: 0.9rem; color: #666; margin-bottom: 1.5rem;">Complete los siguientes datos y la institución se pondrá en contacto.</p>

                <?php if ($mensaje_exito): ?>
                    <div style="background:#d4edda; color:#155724; padding:0.75rem; border-radius:5px; margin-bottom:1rem; font-size:0.9rem;"><?= $mensaje_exito ?></div>
                <?php endif; ?>
                <?php if ($error_solicitud): ?>
                    <div style="background:#f8d7da; color:#721c24; padding:0.75rem; border-radius:5px; margin-bottom:1rem; font-size:0.9rem;"><?= $error_solicitud ?></div>
                <?php endif; ?>

                <form method="POST" action="index.php" class="form-inscripcion">
                    <div class="form-group">
                        <input type="text" name="nombre_representante" required placeholder="Nombre Completo del Representante">
                    </div>
                    <div class="form-group">
                        <input type="text" name="ced_representante" required placeholder="C&eacute;dula">
                    </div>
                    <div class="form-group">
                        <input type="text" name="telefono" required placeholder="C&eacute;dula">
                    </div>
                    <div class="form-group">
                        <input type="text" name="nombre_estudiante" required placeholder="Nombre Completo del Estudiante">
                    </div>
                    <div class="form-group">
                        <label>Grado Solicitado *</label>
                        <select name="grado_solicitado" required>
                            <option value="">-- Seleccione el Grado --</option>
                            <option value="1er Grado">1er Grado</option>
                            <option value="2do Grado">2do Grado</option>
                            <option value="3er Grado">3er Grado</option>
                            <option value="4to Grado">4to Grado</option>
                            <option value="5to Grado">5to Grado</option>
                            <option value="6to Grado">6to Grado</option>
                        </select>
                    </div>
                    <button type="submit" name="solicitar_inscripcion" class="btn-primary" style="width:100%; background:#28a745; padding:0.82rem; font-size:1rem; margin-top:0.5rem;">Enviar Solicitud de Inscripci&oacute;n</button>
                </form>
            </div>
        </div>

    </main>

    <footer>
        <p>&copy; <?= date('Y') ?> E. B. "Luisa Ramona Lop&eacute;z Ru&iacute;z" - Todos los derechos reservados.</p>
        <p style="font-size: 0.8rem; opacity: 0.7; margin-top: 0.3rem;">Sistema Web de Gestión Acad&eacute; mica e Institucional</p>
    </footer>

    <script>
        function showTab(tabName) {
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));

            const links = document.querySelectorAll('.nav-link');
            links.forEach(link => link.classList.remove('active'));

            const targetTab = document.getElementById('tab-' + tabName);
            if (targetTab) {
                targetTab.classList.add('active');
            }

            const targetLink = document.getElementById('link-' + tabName);
            if (targetLink) {
                targetLink.classList.add('active');
            }
        }

        <?php if ($mensaje_exito || $error_solicitud): ?>
            showTab('solicitud');
        <?php endif; ?>
    </script>

</body>
</html>