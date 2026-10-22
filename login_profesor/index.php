<?php
session_start();
require_once '../config/db.php';

if (isset($_SESSION['profesor_id'])) {
    header('Location: ../profesor/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula   = trim($_POST['cedula']);
    $password = trim($_POST['password']);

    if (!empty($cedula) && !empty($password)) {
        $stmt = $pdo->prepare('SELECT * FROM profesores WHERE cedula = ?');
        $stmt->execute([$cedula]);
        $profesor = $stmt->fetch();

        if ($profesor && password_verify($password, $profesor['password'])) {
            $_SESSION['profesor_id']     = $profesor['id'];
            $_SESSION['profesor_nombre'] = $profesor['nombre'] . ' ' . $profesor['apellido'];
            
            header('Location: ../profesor/dashboard.php');
            exit;
        } else {
            $error = 'C&eacute;dula o contrase&ntilde;a incorrectas.';
        }
    } else {
        $error = 'Por favor complete todos los campos.';
    }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=0.4">
<link rel="SHORTCUT ICON" href="../img_web/logot.png" />
    <title>Login Docente - E. B. "Luisa Ramona Lop&eacute;z Ru&iacute;z"</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-body">
    <div class="login-card">
        <h2>Portal Docente</h2>
        <p>Ingrese sus credenciales para acceder</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <input type="text" name="cedula" id="cedula" required placeholder="C&eacute;dula">
            </div>

            <div class="form-group">
                <input type="password" name="password" id="password" required placeholder="Contrase&ntilde;a">
            </div>

            <button type="submit" class="btn-primary">Ingresar</button>
        </form>
    </div>
</body>
</html>