<?php
session_start();
require_once '../config/db.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: ../admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario  = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    if (!empty($usuario) && !empty($password)) {
        $stmt = $pdo->prepare('SELECT * FROM administradores WHERE usuario = ?');
        $stmt->execute([$usuario]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id']     = $admin['id'];
            $_SESSION['admin_nombre'] = $admin['nombre'];
            
            header('Location: ../admin/dashboard.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
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
    <title>Login Administrador - E. B. "Luisa Ramona Lop&eacute;z Ru&iacute;z"</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-body">
    <div class="login-card">
        <h2>Panel Administrativo</h2>
        <p>Acceso restringido para personal autorizado</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <input type="text" name="usuario" id="usuario" required placeholder="Usuario">
            </div>

            <div class="form-group">
                <input type="password" name="password" id="password" required placeholder="Contrase&ntilde;a">
            </div>

            <button type="submit" class="btn-primary">Ingresar al Sistema</button>
        </form>
    </div>
</body>
</html>