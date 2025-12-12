<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

require_admin();

$error = '';
$success = '';

// Verificar que se recibió un ID
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$id_usuario = (int)$_GET['id'];

// Obtener información del usuario a editar
$user_info = get_user_info($id_usuario);

if(!$user_info) {
    header("Location: admin_dashboard.php?error=user_not_found");
    exit();
}

// Obtener roles disponibles (solo activos, pero incluir el rol actual del usuario si está inactivo)
$roles = get_active_roles();
$current_role_in_list = false;
foreach($roles as $rol) {
    if($rol['id_rol'] == $user_info['id_rol']) {
        $current_role_in_list = true;
        break;
    }
}
// Si el rol actual no está en la lista (porque está inactivo), agregarlo
if(!$current_role_in_list) {
    $current_role = get_role_info($user_info['id_rol']);
    if($current_role) {
        array_unshift($roles, $current_role);
    }
}

// Manejar verificación manual de email
if(isset($_POST['verify_email']) && $_POST['verify_email'] == '1') {
    if(verify_email_manually($id_usuario)) {
        $success = "Email verificado correctamente";
        // Recargar información del usuario
        $user_info = get_user_info($id_usuario);
    } else {
        $error = "Error al verificar el email";
    }
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['verify_email'])) {
    $nombre = sanitize_input($_POST['nombre']);
    $apellido = sanitize_input($_POST['apellido']);
    $telefono = sanitize_input($_POST['telefono']);
    $direccion = sanitize_input($_POST['direccion']);
    $id_rol = (int)$_POST['id_rol'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    $email_verificado = isset($_POST['email_verificado']) ? 1 : 0;

    if(empty($nombre) || empty($apellido)) {
        $error = "El nombre y apellido son obligatorios";
    } else {
        $database = new Database();
        $db = $database->connect();

        try {
            $db->beginTransaction();

            // Actualizar persona
            $query_persona = "UPDATE personas p
                             INNER JOIN usuarios u ON p.id_persona = u.id_persona
                             SET p.nombre = :nombre,
                                 p.apellido = :apellido,
                                 p.telefono = :telefono,
                                 p.direccion = :direccion
                             WHERE u.id_usuario = :id_usuario";

            $stmt = $db->prepare($query_persona);
            $stmt->execute([
                ':nombre' => $nombre,
                ':apellido' => $apellido,
                ':telefono' => $telefono,
                ':direccion' => $direccion,
                ':id_usuario' => $id_usuario
            ]);

            // Actualizar usuario (rol, estado y verificación de email)
            $query_usuario = "UPDATE usuarios
                             SET id_rol = :id_rol,
                                 activo = :activo,
                                 email_verificado = :email_verificado
                             WHERE id_usuario = :id_usuario";

            $stmt_usuario = $db->prepare($query_usuario);
            $stmt_usuario->execute([
                ':id_rol' => $id_rol,
                ':activo' => $activo,
                ':email_verificado' => $email_verificado,
                ':id_usuario' => $id_usuario
            ]);

            $db->commit();
            header("Location: admin_dashboard.php?updated=success");
            exit();

        } catch(Exception $e) {
            $db->rollBack();
            $error = "Error al actualizar el usuario: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="navbar">
        <div class="nav-brand">
            <h2>Sistema de Gestión</h2>
        </div>
        <div class="nav-links">
            <a href="admin_dashboard.php" class="active">Usuarios</a>
            <a href="roles.php">Roles</a>
            <a href="courses.php">Cursos</a>
            <a href="profile.php">Mi Perfil</a>
            <a href="logout.php">Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="dashboard-container">
        <div class="container" style="max-width: 600px;">
            <h1>Editar Usuario</h1>
            
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
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" value="<?php echo htmlspecialchars($user_info['email']); ?>" disabled style="background: #f0f0f0;">
                </div>
                
                <div class="form-group">
                    <label>DNI</label>
                    <input type="text" value="<?php echo htmlspecialchars($user_info['dni']); ?>" disabled style="background: #f0f0f0;">
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
                
                <div class="form-group">
                    <label for="id_rol">Rol *</label>
                    <select id="id_rol" name="id_rol" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 5px; font-size: 14px;">
                        <?php foreach($roles as $rol): ?>
                            <option value="<?php echo $rol['id_rol']; ?>" <?php echo ($user_info['id_rol'] == $rol['id_rol']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($rol['nombre_rol']); ?>
                                <?php if(!$rol['activo']): ?> (Inactivo)<?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #666; display: block; margin-top: 5px;">
                        Solo se muestran los roles activos
                    </small>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" name="activo" <?php echo $user_info['activo'] ? 'checked' : ''; ?> style="width: auto; margin-right: 10px;">
                        <span>Usuario Activo</span>
                    </label>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" name="email_verificado" <?php echo $user_info['email_verificado'] ? 'checked' : ''; ?> style="width: auto; margin-right: 10px;">
                        <span>Email Verificado</span>
                    </label>
                    <small style="color: #666; display: block; margin-top: 5px;">
                        <?php if($user_info['email_verificado']): ?>
                            Verificado el: <?php echo $user_info['fecha_verificacion'] ? date('d/m/Y H:i', strtotime($user_info['fecha_verificacion'])) : 'N/A'; ?>
                        <?php else: ?>
                            El usuario no ha verificado su email
                        <?php endif; ?>
                    </small>
                </div>

                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                
                <a href="admin_dashboard.php">
                    <button type="button" class="btn btn-secondary">Cancelar</button>
                </a>
            </form>
        </div>
    </div>
</body>
</html>