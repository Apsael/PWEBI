<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

require_admin();

// Obtener todos los usuarios
$users = get_all_users();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        function confirmDelete(username) {
            return confirm('¿Estás seguro de que deseas eliminar al usuario "' + username + '"?\n\nEsta acción no se puede deshacer.');
        }
    </script>
</head>
<body>
    <div class="navbar">
        <div class="nav-brand">
            <h2>📚 Sistema de Clases</h2>
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
        <div class="welcome-section">
            <h1>👨‍💼 Panel de Administración</h1>
            <p style="color: #666; text-align: center;">
                Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']); ?></strong>
            </p>
        </div>
        
        <?php if(isset($_GET['deleted']) && $_GET['deleted'] == 'success'): ?>
            <div class="alert alert-success">Usuario eliminado correctamente.</div>
        <?php endif; ?>
        
        <?php if(isset($_GET['updated']) && $_GET['updated'] == 'success'): ?>
            <div class="alert alert-success">Usuario actualizado correctamente.</div>
        <?php endif; ?>
        
        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php 
                    if($_GET['error'] == 'cannot_delete_admin') {
                        echo "No se puede eliminar al administrador principal.";
                    } else {
                        echo "Error al procesar la solicitud.";
                    }
                ?>
            </div>
        <?php endif; ?>
        
        <div class="table-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Lista de Usuarios (<?php echo count($users); ?>)</h2>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>USUARIO</th>
                        <th>NOMBRE COMPLETO</th>
                        <th>EMAIL</th>
                        <th>ROL</th>
                        <th>ESTADO</th>
                        <th>EMAIL VERIFICADO</th>
                        <th>ÚLTIMO ACCESO</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id_usuario']; ?></td>
                        <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                        <td><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($user['nombre_rol']); ?>
                            <?php if(!$user['rol_activo']): ?>
                                <span style="color: #dc3545; font-size: 11px;">(Inactivo)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $user['activo'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $user['activo'] ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <?php if($user['email_verificado']): ?>
                                <span class="status-badge status-active">Verificado</span>
                            <?php else: ?>
                                <span class="status-badge status-inactive">Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            if($user['ultimo_acceso']) {
                                echo date('d/m/Y H:i', strtotime($user['ultimo_acceso']));
                            } else {
                                echo 'Nunca';
                            }
                            ?>
                        </td>
                        <td style="white-space: nowrap;">
                            <a href="edit_user.php?id=<?php echo $user['id_usuario']; ?>">
                                <button class="btn btn-warning">Editar</button>
                            </a>

                            <?php if($user['id_usuario'] != 1): ?>
                            <form method="POST" action="delete_user.php" style="display: inline;" onsubmit="return confirmDelete('<?php echo htmlspecialchars($user['username']); ?>');">
                                <input type="hidden" name="id_usuario" value="<?php echo $user['id_usuario']; ?>">
                                <button type="submit" class="btn btn-danger">Eliminar</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>