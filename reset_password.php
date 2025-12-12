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
$token_valid = false;
$token = '';
$user_data = null;

// Verificar token en la URL
if(isset($_GET['token'])) {
    $token = sanitize_input($_GET['token']);
    $user_data = verify_password_reset_token($token);

    if($user_data) {
        $token_valid = true;
    }
}

// Procesar formulario de nueva contraseña
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['token'])) {
    $token = sanitize_input($_POST['token']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validaciones
    if(empty($password) || empty($confirm_password)) {
        $error = "Por favor, completa todos los campos.";
        $token_valid = true;
    } elseif(strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
        $token_valid = true;
    } elseif($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden.";
        $token_valid = true;
    } else {
        // Intentar restablecer la contraseña
        if(reset_password($token, $password)) {
            $success = "Tu contraseña ha sido restablecida exitosamente. Ya puedes iniciar sesión con tu nueva contraseña.";
            $token_valid = false;
        } else {
            $error = "El enlace ha expirado o ya fue utilizado. Por favor, solicita un nuevo enlace de recuperación.";
            $token_valid = false;
        }
    }

    // Re-verificar datos del usuario si el token sigue siendo válido
    if($token_valid) {
        $user_data = verify_password_reset_token($token);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Sistema de Gestión</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .password-requirements {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        .password-requirements h4 {
            margin: 0 0 10px 0;
            color: #495057;
            font-size: 14px;
        }
        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
            color: #6c757d;
        }
        .password-requirements li {
            margin-bottom: 5px;
        }
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            transition: all 0.3s ease;
        }
        .strength-weak { background: #dc3545; width: 33%; }
        .strength-medium { background: #ffc107; width: 66%; }
        .strength-strong { background: #28a745; width: 100%; }
        .user-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .user-info .name {
            font-size: 18px;
            font-weight: bold;
        }
        .user-info .email {
            font-size: 14px;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Restablecer Contraseña</h1>

        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
            <div class="link">
                <a href="index.php" class="btn btn-primary" style="display: inline-block; margin-top: 10px;">Ir a Iniciar Sesión</a>
            </div>
        <?php elseif($token_valid && $user_data): ?>
            <div class="user-info">
                <div class="name"><?php echo htmlspecialchars($user_data['nombre']); ?></div>
                <div class="email"><?php echo htmlspecialchars($user_data['email']); ?></div>
            </div>

            <div class="password-requirements">
                <h4>Requisitos de la contraseña:</h4>
                <ul>
                    <li>Mínimo 6 caracteres</li>
                    <li>Se recomienda usar letras, números y símbolos</li>
                </ul>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="form-group">
                    <label for="password">Nueva Contraseña</label>
                    <input type="password" id="password" name="password" required
                           placeholder="Ingresa tu nueva contraseña" minlength="6">
                    <div class="password-strength" id="passwordStrength"></div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmar Contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                           placeholder="Repite tu nueva contraseña" minlength="6">
                </div>

                <button type="submit" class="btn btn-primary">Restablecer Contraseña</button>
            </form>

            <div class="link">
                <a href="index.php">Volver al inicio de sesión</a>
            </div>

            <script>
                // Password strength indicator
                document.getElementById('password').addEventListener('input', function() {
                    const password = this.value;
                    const strengthBar = document.getElementById('passwordStrength');

                    if(password.length === 0) {
                        strengthBar.className = 'password-strength';
                        return;
                    }

                    let strength = 0;
                    if(password.length >= 6) strength++;
                    if(password.length >= 8) strength++;
                    if(/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
                    if(/\d/.test(password)) strength++;
                    if(/[^a-zA-Z\d]/.test(password)) strength++;

                    if(strength <= 2) {
                        strengthBar.className = 'password-strength strength-weak';
                    } else if(strength <= 3) {
                        strengthBar.className = 'password-strength strength-medium';
                    } else {
                        strengthBar.className = 'password-strength strength-strong';
                    }
                });

                // Password match validation
                document.getElementById('confirm_password').addEventListener('input', function() {
                    const password = document.getElementById('password').value;
                    const confirmPassword = this.value;

                    if(confirmPassword.length > 0 && password !== confirmPassword) {
                        this.style.borderColor = '#dc3545';
                    } else if(confirmPassword.length > 0) {
                        this.style.borderColor = '#28a745';
                    } else {
                        this.style.borderColor = '';
                    }
                });
            </script>
        <?php else: ?>
            <div class="alert alert-error">
                <strong>Enlace inválido o expirado</strong><br>
                El enlace de recuperación no es válido o ha expirado. Los enlaces de recuperación son válidos por 1 hora.
            </div>
            <div class="link">
                <a href="forgot_password.php" class="btn btn-primary" style="display: inline-block; margin-top: 10px;">Solicitar nuevo enlace</a>
            </div>
            <div class="link" style="margin-top: 15px;">
                <a href="index.php">Volver al inicio de sesión</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
