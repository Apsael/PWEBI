<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

$success = false;
$error = false;
$message = '';

// Verificar si se proporcionó un token
if(isset($_GET['token'])) {
    $token = sanitize_input($_GET['token']);

    // Intentar verificar el token
    if(verify_email_token($token)) {
        $success = true;
        $message = '¡Tu correo electrónico ha sido verificado exitosamente! Ya puedes iniciar sesión.';
    } else {
        $error = true;
        $message = 'El enlace de verificación es inválido o ha expirado. Por favor, solicita un nuevo enlace de verificación.';
    }
} else {
    $error = true;
    $message = 'No se proporcionó un token de verificación.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Email - Sistema de Clases</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="login-box">
            <div class="logo">
                <h1>📚 Sistema de Clases</h1>
            </div>

            <h2>Verificación de Correo Electrónico</h2>

            <?php if($success): ?>
                <div class="alert alert-success">
                    <strong>✓ Éxito!</strong><br>
                    <?php echo $message; ?>
                </div>
                <div class="form-actions">
                    <a href="index.php" class="btn btn-primary btn-block">Iniciar Sesión</a>
                </div>
            <?php elseif($error): ?>
                <div class="alert alert-danger">
                    <strong>✗ Error!</strong><br>
                    <?php echo $message; ?>
                </div>
                <div class="form-actions">
                    <a href="resend_verification.php" class="btn btn-secondary btn-block">Reenviar Verificación</a>
                    <a href="index.php" class="btn btn-primary btn-block">Volver al Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
