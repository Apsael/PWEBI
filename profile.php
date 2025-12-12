<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

require_login();

$error = '';
$success = '';

// Obtener información actual del usuario
$user_info = get_user_info($_SESSION['user_id']);

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = sanitize_input($_POST['nombre']);
    $apellido = sanitize_input($_POST['apellido']);
    $telefono = sanitize_input($_POST['telefono']);
    $direccion = sanitize_input($_POST['direccion']);
    
    if(empty($nombre) || empty($apellido)) {
        $error = "El nombre y apellido son obligatorios";
    } else {
        $data = [
            'nombre' => $nombre,
            'apellido' => $apellido,
            'telefono' => $telefono,
            'direccion' => $direccion
        ];
        
        if(update_profile($_SESSION['user_id'], $data)) {
            // Actualizar variables de sesión
            $_SESSION['nombre'] = $nombre;
            $_SESSION['apellido'] = $apellido;
            
            $success = "Perfil actualizado correctamente";
            // Recargar información
            $user_info = get_user_info($_SESSION['user_id']);
        } else {
            $error = "Error al actualizar el perfil";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="navbar">
        <div class="nav-brand">
            <h2>Sistema de Gestión</h2>
        </div>
        <div class="nav-links">
            <?php if(is_admin()): ?>
                <a href="admin_dashboard.php">Usuarios</a>
                <a href="roles.php">Roles</a>
            <?php else: ?>
                <a href="dashboard.php">Inicio</a>
            <?php endif; ?>
            <a href="profile.php" class="active">Mi Perfil</a>
            <a href="logout.php">Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="dashboard-container">
        <div class="container" style="max-width: 600px;">
            <h1>Editar Mi Perfil</h1>
            
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Usuario</label>
                    <input type="text" value="<?php echo htmlspecialchars($user_info['username']); ?>" disabled style="background: #f0f0f0;">
                    <small style="color: #999;">El nombre de usuario no se puede cambiar</small>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" value="<?php echo htmlspecialchars($user_info['email']); ?>" disabled style="background: #f0f0f0;">
                    <small style="color: #999;">El email no se puede cambiar</small>
                </div>
                
                <div class="form-group">
                    <label>DNI</label>
                    <input type="text" value="<?php echo htmlspecialchars($user_info['dni']); ?>" disabled style="background: #f0f0f0;">
                    <small style="color: #999;">El DNI no se puede cambiar</small>
                </div>
                
                <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input type="text" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($user_info['nombre']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="apellido">Apellido *</label>
                    <input type="text" id="apellido" name="apellido" required value="<?php echo htmlspecialchars($user_info['apellido']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="telefono">Teléfono</label>
                    <input type="tel" id="telefono" name="telefono" value="<?php echo htmlspecialchars($user_info['telefono']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="direccion">Dirección</label>
                    <textarea id="direccion" name="direccion"><?php echo htmlspecialchars($user_info['direccion']); ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                
                <a href="<?php echo is_admin() ? 'admin_dashboard.php' : 'dashboard.php'; ?>">
                    <button type="button" class="btn btn-secondary">Cancelar</button>
                </a>
            </form>
        </div>
    </div>
</body>
</html>