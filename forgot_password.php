<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

// Si ya está logueado, redirigir al dashboard
if(is_logged_in()) {
    if(is_admin()) {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize_input($_POST['email']);

    if(empty($email)) {
        $error = "Por favor, ingresa tu correo electrónico.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Por favor, ingresa un correo electrónico válido.";
    } else {
        // Buscar usuario por email
        $user = get_user_by_email($email);

        if($user) {
            // Verificar que el usuario esté activo
            if($user['activo']) {
                // Crear token y enviar email
                $token = create_password_reset_token($user['id_usuario']);

                if($token) {
                    $email_sent = send_password_reset_email($user['email'], $user['nombre'], $token);

                    if($email_sent) {
                        $success = "Se ha enviado un enlace de recuperación a tu correo electrónico. Por favor, revisa tu bandeja de entrada.";
                    } else {
                        $error = "Hubo un problema al enviar el correo. Por favor, intenta nuevamente.";
                    }
                } else {
                    $error = "Hubo un problema al procesar tu solicitud. Por favor, intenta nuevamente.";
                }
            } else {
                // Usuario inactivo - por seguridad mostramos el mismo mensaje
                $success = "Si el correo está registrado, recibirás un enlace de recuperación.";
            }
        } else {
            // Por seguridad, no revelamos si el email existe o no
            $success = "Si el correo está registrado, recibirás un enlace de recuperación.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Sistema de Gestión</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Recuperar Contraseña</h1>
        <p class="subtitle">Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.</p>

        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
                <?php
                // Modo desarrollo: Mostrar enlace directo
                if(isset($_SESSION['password_reset_url'])):
                    $url = $_SESSION['password_reset_url'];
                    unset($_SESSION['password_reset_url']);
                ?>
                    <br><br>
                    <strong>MODO DESARROLLO:</strong><br>
                    Haz clic aquí para restablecer tu contraseña:<br>
                    <a href="<?php echo htmlspecialchars($url); ?>"
                       style="color: #155724; text-decoration: underline; word-break: break-all;">
                        <?php echo htmlspecialchars($url); ?>
                    </a>
                    <br><br>
                    <small>También puedes consultar: <code>logs/password_reset_links.txt</code></small>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if(!$success): ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Correo Electrónico</label>
                <input type="email" id="email" name="email" required autofocus
                       placeholder="ejemplo@correo.com"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <button type="submit" class="btn btn-primary">Enviar Enlace de Recuperación</button>
        </form>
        <?php endif; ?>

        <div class="link">
            <a href="index.php">Volver al inicio de sesión</a>
        </div>
    </div>
</body>
</html>
