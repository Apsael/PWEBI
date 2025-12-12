<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

require_admin();

// Obtener todos los roles
$roles = get_roles();

// Obtener conteo de usuarios y permisos por cada rol
foreach($roles as $key => $rol) {
    $roles[$key]['user_count'] = count_users_by_role($rol['id_rol']);
    $roles[$key]['permission_count'] = count(get_role_permission_ids($rol['id_rol']));
}
unset($rol); // Limpiar cualquier referencia
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Roles</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
        function confirmDelete(roleName, userCount, rolId) {
            console.log('Intentando eliminar rol:', roleName, 'ID:', rolId, 'Usuarios:', userCount);

            if(parseInt(userCount) > 0) {
                alert('No se puede eliminar el rol "' + roleName + '" porque tiene ' + userCount + ' usuario(s) asignado(s).\n\nPrimero debe reasignar estos usuarios a otro rol.');
                return false;
            }

            if(parseInt(rolId) <= 3) {
                alert('No se puede eliminar el rol "' + roleName + '" porque es un rol del sistema.');
                return false;
            }

            return confirm('¿Estás seguro de que deseas eliminar el rol "' + roleName + '"?\n\nEsta acción no se puede deshacer.');
        }
    </script>
</head>
<body>
    <div class="navbar">
        <div class="nav-brand">
            <h2>Sistema de Gestión</h2>
        </div>
        <div class="nav-links">
            <a href="admin_dashboard.php">Usuarios</a>
            <a href="roles.php" class="active">Roles</a>
            <a href="courses.php">Cursos</a>
            <a href="profile.php">Mi Perfil</a>
            <a href="logout.php">Cerrar Sesión</a>
        </div>
    </div>

    <div class="dashboard-container">
        <div class="welcome-section">
            <h1>Gestión de Roles</h1>
            <p style="color: #666; text-align: center;">
                Administra los roles del sistema
            </p>
        </div>

        <?php if(isset($_GET['created']) && $_GET['created'] == 'success'): ?>
            <div class="alert alert-success">Rol creado correctamente.</div>
        <?php endif; ?>

        <?php if(isset($_GET['updated']) && $_GET['updated'] == 'success'): ?>
            <div class="alert alert-success">Rol actualizado correctamente.</div>
        <?php endif; ?>

        <?php if(isset($_GET['deleted']) && $_GET['deleted'] == 'success'): ?>
            <div class="alert alert-success">Rol eliminado correctamente.</div>
        <?php endif; ?>

        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php
                    if($_GET['error'] == 'cannot_delete_system_role') {
                        echo "No se puede eliminar un rol del sistema (Admin, Docente, Estudiante).";
                    } elseif($_GET['error'] == 'role_has_users') {
                        echo "No se puede eliminar el rol porque tiene usuarios asignados.";
                    } elseif($_GET['error'] == 'role_not_found') {
                        echo "El rol especificado no existe.";
                    } else {
                        echo "Error al procesar la solicitud.";
                    }
                ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Lista de Roles (<?php echo count($roles); ?>)</h2>
                <a href="edit_role.php">
                    <button class="btn btn-primary">Nuevo Rol</button>
                </a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>NOMBRE DEL ROL</th>
                        <th>DESCRIPCIÓN</th>
                        <th>ESTADO</th>
                        <th>USUARIOS</th>
                        <th>PERMISOS</th>
                        <th>TIPO</th>
                        <th>ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($roles as $rol): ?>
                    <tr>
                        <td><?php echo $rol['id_rol']; ?></td>
                        <td><strong><?php echo htmlspecialchars($rol['nombre_rol']); ?></strong></td>
                        <td><?php echo htmlspecialchars($rol['descripcion'] ?: '-'); ?></td>
                        <td style="text-align: center;">
                            <?php if(isset($rol['activo']) && $rol['activo']): ?>
                                <span class="status-badge status-active">Activo</span>
                            <?php else: ?>
                                <span class="status-badge status-inactive">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <span class="status-badge <?php echo $rol['user_count'] > 0 ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $rol['user_count']; ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <span class="status-badge" style="background: <?php echo $rol['permission_count'] > 0 ? '#17a2b8' : '#6c757d'; ?>;">
                                <?php echo $rol['permission_count']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if($rol['id_rol'] <= 3): ?>
                                <span class="status-badge status-active">Sistema</span>
                            <?php else: ?>
                                <span class="status-badge" style="background: #6c757d;">Personalizado</span>
                            <?php endif; ?>
                        </td>
                        <td style="white-space: nowrap;">
                            <a href="edit_role.php?id=<?php echo $rol['id_rol']; ?>">
                                <button class="btn btn-warning">Editar</button>
                            </a>

                            <?php if($rol['id_rol'] > 3): ?>
                            <form method="POST" action="delete_role.php" style="display: inline;"
                                  onsubmit="return confirmDelete('<?php echo htmlspecialchars($rol['nombre_rol']); ?>', <?php echo $rol['user_count']; ?>, <?php echo $rol['id_rol']; ?>);">
                                <input type="hidden" name="id_rol" value="<?php echo $rol['id_rol']; ?>">
                                <button type="submit" class="btn btn-danger"
                                        <?php echo ($rol['user_count'] > 0) ? 'disabled title="Este rol tiene usuarios asignados"' : ''; ?>>
                                    Eliminar
                                </button>
                            </form>
                            <?php else: ?>
                            <button class="btn btn-danger" disabled title="Los roles del sistema no se pueden eliminar">
                                Eliminar
                            </button>
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
