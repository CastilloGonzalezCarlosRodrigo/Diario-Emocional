<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

$db = new Database();
$auth = new Auth($db->getConnection());

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($auth->login($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Diario Emocional</title>
    <link rel="stylesheet" href="css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <div class="auth-background">
        <div class="auth-container">
            <div class="auth-header">
                <h1>Diario Emocional</h1>
                <p>Registra y comprende tus emociones</p>
            </div>

            <div class="auth-card">
                <h2>Iniciar Sesión</h2>

                <?php if ($error): ?>
                    <div class="auth-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= $error ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" class="auth-form">
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" placeholder="Usuario" required>
                    </div>

                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Contraseña" required>
                    </div>

                    <button type="submit" class="auth-button">
                        <i class="fas fa-sign-in-alt"></i> Entrar
                    </button>
                </form>

                <div class="auth-footer">
                    <p>¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
                </div>
            </div>

            <div class="auth-quote">
                <p>"Conocer nuestras emociones es el primer paso hacia el bienestar emocional"</p>
            </div>
        </div>
    </div>
</body>

</html>