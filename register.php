<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

$db = new Database();
$auth = new Auth($db->getConnection());

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Validaciones
    if (empty($username) || empty($password)) {
        $error = 'Usuario y contraseña son requeridos';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } else {
        // Intentar registrar
        if ($auth->register($username, $password)) {
            $success = '¡Registro exitoso! Ahora puedes iniciar sesión';
        } else {
            $error = 'El usuario ya existe';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Diario Emocional</title>
    <link rel="stylesheet" href="css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="auth-background">
        <div class="auth-container">
            <div class="auth-header">
                <h1>Diario Emocional</h1>
                <p>Crea tu cuenta para comenzar</p>
            </div>
            
            <div class="auth-card">
                <h2>Registro de Usuario</h2>
                
                <?php if ($error): ?>
                <div class="auth-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= $error ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="auth-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?= $success ?></span>
                    <div class="auth-links">
                        <a href="login.php" class="auth-button">
                            <i class="fas fa-sign-in-alt"></i> Ir a Inicio de Sesión
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <form method="POST" class="auth-form">
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" placeholder="Usuario" required>
                    </div>
                    
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Contraseña" required>
                    </div>
                    
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirm_password" placeholder="Confirmar Contraseña" required>
                    </div>
                    
                    <div class="password-requirements">
                        <p>La contraseña debe tener al menos 6 caracteres</p>
                    </div>
                    
                    <button type="submit" class="auth-button">
                        <i class="fas fa-user-plus"></i> Registrarse
                    </button>
                    
                    <div class="auth-footer">
                        <p>¿Ya tienes cuenta? <a href="login.php">Inicia Sesión</a></p>
                    </div>
                </form>
                <?php endif; ?>
            </div>
            
            <div class="auth-quote">
                <p>"El autoconocimiento es el primer paso para el crecimiento emocional"</p>
            </div>
        </div>
    </div>
</body>
</html>