<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

// Si ya está logueado, redirigir
if(is_logged_in()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitizar datos
    $nombre = sanitize_input($_POST['nombre']);
    $apellido = sanitize_input($_POST['apellido']);
    $dni = sanitize_input($_POST['dni']);
    $email = sanitize_input($_POST['email']);
    $telefono = sanitize_input($_POST['telefono']);
    $direccion = sanitize_input($_POST['direccion']);
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validaciones
    if(empty($nombre) || empty($apellido) || empty($dni) || empty($email) || empty($username) || empty($password)) {
        $error = "Por favor, complete todos los campos obligatorios";
    } elseif($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden";
    } elseif(strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres";
    } elseif(user_exists($username)) {
        $error = "El nombre de usuario ya está en uso";
    } elseif(email_exists($email)) {
        $error = "El email ya está registrado";
    } elseif(dni_exists($dni)) {
        $error = "El DNI ya está registrado";
    } else {
        // Crear usuario
        $data = [
            'nombre' => $nombre,
            'apellido' => $apellido,
            'dni' => $dni,
            'email' => $email,
            'telefono' => $telefono,
            'direccion' => $direccion,
            'username' => $username,
            'password' => $password
        ];
        
        if(create_user($data)) {
            header("Location: index.php?registered=success");
            exit();
        } else {
            $error = "Error al crear el usuario. Intente nuevamente.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Sistema de Gestión</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Crear Cuenta</h1>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="nombre">Nombre *</label>
                <input type="text" id="nombre" name="nombre" required value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="apellido">Apellido *</label>
                <input type="text" id="apellido" name="apellido" required value="<?php echo isset($_POST['apellido']) ? htmlspecialchars($_POST['apellido']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="dni">DNI *</label>
                <input type="text" id="dni" name="dni" required value="<?php echo isset($_POST['dni']) ? htmlspecialchars($_POST['dni']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="telefono">Teléfono</label>
                <input type="tel" id="telefono" name="telefono" value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="direccion">Dirección</label>
                <textarea id="direccion" name="direccion"><?php echo isset($_POST['direccion']) ? htmlspecialchars($_POST['direccion']) : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="username">Usuario *</label>
                <input type="text" id="username" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña * (mínimo 6 caracteres)</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirmar Contraseña *</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Registrarse</button>
        </form>
        
        <div class="link">
            ¿Ya tienes cuenta? <a href="index.php">Inicia sesión aquí</a>
        </div>
    </div>
</body>
</html>