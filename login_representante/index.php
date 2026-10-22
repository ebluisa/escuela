<?php
session_start();
require_once '../config/db.php';

if (isset($_SESSION['representante_id'])) {
    header('Location: ../representante/boletin.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula   = trim($_POST['cedula']);
    $password = trim($_POST['password']);

    if (!empty($cedula) && !empty($password)) {
        $stmt = $pdo->prepare('SELECT * FROM representantes WHERE cedula = ?');
        $stmt->execute([$cedula]);
        $rep = $stmt->fetch();

        if ($rep && password_verify($password, $rep['password'])) {
            $_SESSION['representante_id']     = $rep['id'];
            $_SESSION['representante_nombre'] = $rep['nombre'] . ' ' . $rep['apellido'];
            
            header('Location: ../representante/boletin.php');
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
    <title>Consulta de Representantes - E. B. "Luisa Ramona Lop&eacute;z Ru&iacute;z"</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-body">
    <div class="login-card">
        <h2>Consulta para Representantes</h2>
        <p>Ingrese para verificar el bolet&iacute;n escolar de su representado</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <input type="text" name="cedula" id="cedula" required placeholder="C&eacute;dula de Identidad">
            </div>

            <div class="form-group">
                <input type="password" name="password" id="password" required placeholder="Contrase&ntilde;a">
            </div>

            <button type="submit" class="btn-primary">Consultar Bolet&iacute;n</button>
        </form>
    </div>
</body>
</html>