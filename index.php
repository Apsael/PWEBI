<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

// Si ya está logueado, redirigir al dashboard correspondiente
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
$warning = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];

    if(empty($username) || empty($password)) {
        $error = "Por favor, complete todos los campos";
    } else {
        $user = authenticate_user($username, $password);

        if($user) {
            // VERIFICAR SI EL EMAIL ESTÁ VERIFICADO (excepto admin)
            if(!$user['email_verificado'] && $user['id_rol'] != 1) {
                $warning = "Tu cuenta aún no ha sido verificada. Por favor, revisa tu correo electrónico y haz clic en el enlace de verificación.";
                // Guardar datos para reenviar verificación
                $_SESSION['pending_user_id'] = $user['id_usuario'];
                $_SESSION['pending_user_email'] = $user['email'];
                $_SESSION['pending_user_name'] = $user['nombre'];
            } else {
                // Email verificado o es admin - Permitir acceso
                $_SESSION['user_id'] = $user['id_usuario'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nombre'] = $user['nombre'];
                $_SESSION['apellido'] = $user['apellido'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['id_rol'] = $user['id_rol'];
                $_SESSION['nombre_rol'] = $user['nombre_rol'];
                $_SESSION['email_verificado'] = $user['email_verificado'];

                // Redirigir según el rol
                if($user['id_rol'] == 1) {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            }
        } else {
            $error = "Usuario o contraseña incorrectos";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Gestión</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Iniciar Sesión</h1>

        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if($warning): ?>
            <div class="alert" style="background: #fff3cd; color: #856404; border-left: 4px solid #ffc107;">
                <strong>⚠️ Cuenta no verificada</strong><br>
                <?php echo $warning; ?>
                <br><br>
                <form method="POST" action="resend_verification.php" style="display: inline;">
                    <input type="hidden" name="user_id" value="<?php echo $_SESSION['pending_user_id'] ?? ''; ?>">
                    <input type="hidden" name="email" value="<?php echo $_SESSION['pending_user_email'] ?? ''; ?>">
                    <input type="hidden" name="nombre" value="<?php echo $_SESSION['pending_user_name'] ?? ''; ?>">
                    <button type="submit" class="btn btn-warning" style="padding: 8px 16px;">
                        Reenviar correo de verificación
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['registered']) && $_GET['registered'] == 'success'): ?>
            <div class="alert alert-success">
                ¡Registro exitoso!
                <?php
                // Modo desarrollo: Mostrar enlace directo
                if(isset($_SESSION['verification_url'])):
                    $url = $_SESSION['verification_url'];
                    unset($_SESSION['verification_url']); // Limpiar después de mostrar
                ?>
                    <br><br>
                    <strong>MODO DESARROLLO:</strong><br>
                    Haz clic aquí para verificar tu email:<br>
                    <a href="<?php echo htmlspecialchars($url); ?>"
                       style="color: #155724; text-decoration: underline; word-break: break-all;">
                        <?php echo htmlspecialchars($url); ?>
                    </a>
                    <br><br>
                    <small>También puedes consultar: <code>logs/verification_links.txt</code></small>
                <?php else: ?>
                    Se ha enviado un correo de verificación a tu email.
                    Por favor, verifica tu correo antes de iniciar sesión.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['verified']) && $_GET['verified'] == 'success'): ?>
            <div class="alert alert-success">¡Correo verificado exitosamente! Ya puedes iniciar sesión.</div>
        <?php endif; ?>

        <?php if(isset($_GET['logout']) && $_GET['logout'] == 'success'): ?>
            <div class="alert alert-info">Sesión cerrada correctamente.</div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Usuario o Email</label>
                <input type="text" id="username" name="username" required autofocus placeholder="Ingresa tu usuario o email">
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
        </form>
        
        <div class="link">
            ¿No tienes cuenta? <a href="register.php">Regístrate aquí</a>
        </div>

        <div class="link" style="margin-top: 10px;">
            <a href="forgot_password.php">¿Olvidaste tu contraseña?</a>
        </div>
        
        <div class="link" style="margin-top: 10px; font-size: 12px; color: #999;">
            <strong>Usuario de prueba:</strong> admin / admin123 o admin@sistema.com / admin123
        </div>
    </div>
</body>
</html>