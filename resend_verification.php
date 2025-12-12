<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

$success = false;
$error = false;
$message = '';

// Procesar solicitud de reenvío
if($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Método 1: Reenvío directo desde login (con user_id)
    if(isset($_POST['user_id']) && !empty($_POST['user_id'])) {
        $user_id = (int)$_POST['user_id'];
        $email = sanitize_input($_POST['email']);
        $nombre = sanitize_input($_POST['nombre']);

        // Verificar que el usuario existe y no está verificado
        $database = new Database();
        $db = $database->connect();

        $query = "SELECT u.id_usuario, u.email_verificado FROM usuarios u WHERE u.id_usuario = :id";
        $stmt = $db->prepare($query);
        $stmt->execute([':id' => $user_id]);

        if($stmt->rowCount() > 0) {
            $user = $stmt->fetch();

            if($user['email_verificado']) {
                $success = true;
                $message = 'Tu cuenta ya está verificada. Puedes iniciar sesión normalmente.';
            } else {
                // Generar nuevo token y enviar correo
                $token = create_verification_token($user_id);
                if($token && send_verification_email($email, $nombre, $token)) {
                    $success = true;
                    if(isset($_SESSION['verification_url'])) {
                        $message = 'Se ha generado un nuevo enlace de verificación.';
                    } else {
                        $message = 'Se ha enviado un nuevo correo de verificación. Revisa tu bandeja de entrada.';
                    }
                } else {
                    $error = true;
                    $message = 'Error al enviar el correo. Intenta de nuevo.';
                }
            }
        } else {
            $error = true;
            $message = 'Usuario no encontrado.';
        }
    }
    // Método 2: Reenvío manual (con username y email)
    elseif(isset($_POST['username']) && isset($_POST['email'])) {
        $username = sanitize_input($_POST['username']);
        $email = sanitize_input($_POST['email']);

        $database = new Database();
        $db = $database->connect();

        $query = "SELECT u.id_usuario, u.email_verificado, p.nombre, p.email
                  FROM usuarios u
                  INNER JOIN personas p ON u.id_persona = p.id_persona
                  WHERE u.username = :username AND p.email = :email";

        $stmt = $db->prepare($query);
        $stmt->execute([
            ':username' => $username,
            ':email' => $email
        ]);

        if($stmt->rowCount() > 0) {
            $user = $stmt->fetch();

            if($user['email_verificado']) {
                $error = true;
                $message = 'Este correo electrónico ya ha sido verificado. Puedes iniciar sesión normalmente.';
            } else {
                $token = create_verification_token($user['id_usuario']);
                if($token && send_verification_email($user['email'], $user['nombre'], $token)) {
                    $success = true;
                    if(isset($_SESSION['verification_url'])) {
                        $message = 'Se ha generado un nuevo enlace de verificación.';
                    } else {
                        $message = 'Se ha enviado un nuevo correo de verificación. Revisa tu bandeja de entrada y spam.';
                    }
                } else {
                    $error = true;
                    $message = 'Error al enviar el correo de verificación. Por favor, intenta de nuevo más tarde.';
                }
            }
        } else {
            $error = true;
            $message = 'No se encontró una cuenta con esos datos. Verifica tu usuario y correo electrónico.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reenviar Verificación - Sistema de Clases</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="login-box">
            <div class="logo">
                <h1>📚 Sistema de Clases</h1>
            </div>

            <h2>Reenviar Verificación de Email</h2>

            <?php if($success): ?>
                <div class="alert alert-success">
                    <strong>✓ Éxito!</strong><br>
                    <?php echo $message; ?>

                    <?php if(isset($_SESSION['verification_url'])): ?>
                        <br><br>
                        <strong>Enlace de verificación:</strong><br>
                        <a href="<?php echo htmlspecialchars($_SESSION['verification_url']); ?>"
                           style="color: #155724; text-decoration: underline; word-break: break-all;">
                            <?php echo htmlspecialchars($_SESSION['verification_url']); ?>
                        </a>
                        <?php unset($_SESSION['verification_url']); ?>
                    <?php endif; ?>
                </div>
                <div class="form-actions">
                    <a href="index.php" class="btn btn-primary btn-block">Volver al Login</a>
                </div>
            <?php else: ?>
                <?php if($error): ?>
                    <div class="alert alert-danger">
                        <strong>✗ Error!</strong><br>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <p>Ingresa tu usuario y correo electrónico para recibir un nuevo enlace de verificación.</p>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Usuario:</label>
                        <input type="text" id="username" name="username" class="form-control" required
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Correo Electrónico:</label>
                        <input type="email" id="email" name="email" class="form-control" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-block">Reenviar Verificación</button>
                        <a href="index.php" class="btn btn-secondary btn-block">Volver al Login</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
