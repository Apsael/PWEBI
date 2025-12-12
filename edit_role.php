<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

require_admin();

$error = '';
$success = '';
$is_edit = false;
$role_info = null;
$id_rol = null;

// Verificar si es edición (por GET o por POST)
if(isset($_GET['id']) && !empty($_GET['id'])) {
    $is_edit = true;
    $id_rol = (int)$_GET['id'];
} elseif(isset($_POST['id_rol']) && !empty($_POST['id_rol'])) {
    $is_edit = true;
    $id_rol = (int)$_POST['id_rol'];
}

// Si es edición, obtener información del rol
if($is_edit && $id_rol) {
    $role_info = get_role_info($id_rol);

    if(!$role_info) {
        header("Location: roles.php?error=role_not_found");
        exit();
    }
}

// Obtener permisos agrupados y permisos actuales del rol
$permissions_grouped = get_permissions_grouped();
$current_permissions = $is_edit && $id_rol ? get_role_permission_ids($id_rol) : [];

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_rol = sanitize_input($_POST['nombre_rol']);
    $descripcion = isset($_POST['descripcion']) && !empty(trim($_POST['descripcion']))
                   ? sanitize_input($_POST['descripcion'])
                   : null;
    $activo = isset($_POST['activo']) ? 1 : 0;
    $selected_permissions = isset($_POST['permisos']) ? $_POST['permisos'] : [];

    if(empty($nombre_rol)) {
        $error = "El nombre del rol es obligatorio";
    } else {
        // Verificar si el nombre ya existe
        $exclude_id = ($is_edit && $id_rol) ? $id_rol : null;
        if(role_name_exists($nombre_rol, $exclude_id)) {
            $error = "Ya existe un rol con ese nombre";
        } else {
            if($is_edit && $id_rol) {
                // Actualizar rol existente
                if(update_role($id_rol, $nombre_rol, $descripcion, $activo)) {
                    // Asignar permisos
                    assign_permissions_to_role($id_rol, $selected_permissions);
                    header("Location: roles.php?updated=success");
                    exit();
                } else {
                    $error = "Error al actualizar el rol";
                }
            } else {
                // Crear nuevo rol
                $new_role_id = create_role($nombre_rol, $descripcion, $activo);
                if($new_role_id) {
                    // Asignar permisos al nuevo rol
                    if(!empty($selected_permissions)) {
                        assign_permissions_to_role($new_role_id, $selected_permissions);
                    }
                    header("Location: roles.php?created=success");
                    exit();
                } else {
                    $error = "Error al crear el rol";
                }
            }
        }
    }
    // Actualizar permisos seleccionados para mostrar en caso de error
    $current_permissions = $selected_permissions;
}

// Obtener valores para el formulario
$nombre_rol = $role_info ? $role_info['nombre_rol'] : '';
$descripcion = $role_info ? $role_info['descripcion'] : '';
$activo = $role_info ? $role_info['activo'] : true;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Editar Rol' : 'Nuevo Rol'; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
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
        <div class="container" style="max-width: 800px;">
            <h1><?php echo $is_edit ? 'Editar Rol' : 'Nuevo Rol'; ?></h1>

            <?php if($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if($is_edit && $role_info && $role_info['id_rol'] <= 3): ?>
                <div class="alert" style="background: #fff3cd; color: #856404; border-left: 4px solid #ffc107;">
                    <strong>Nota:</strong> Este es un rol del sistema. Solo se puede editar la descripción, no el nombre.
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <?php if($is_edit && $id_rol): ?>
                    <input type="hidden" name="id_rol" value="<?php echo $id_rol; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="nombre_rol">Nombre del Rol *</label>
                    <input type="text"
                           id="nombre_rol"
                           name="nombre_rol"
                           required
                           maxlength="50"
                           value="<?php echo htmlspecialchars($nombre_rol); ?>"
                           <?php echo ($is_edit && $role_info && $role_info['id_rol'] <= 3) ? 'readonly style="background: #f0f0f0;"' : ''; ?>>
                    <small style="color: #666; display: block; margin-top: 5px;">
                        Ejemplo: Supervisor, Coordinador, Invitado, etc.
                    </small>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion"
                              name="descripcion"
                              rows="4"
                              maxlength="255"
                              placeholder="Describe las responsabilidades y permisos de este rol..."><?php echo htmlspecialchars($descripcion); ?></textarea>
                    <small style="color: #666; display: block; margin-top: 5px;">
                        Máximo 255 caracteres
                    </small>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox"
                               name="activo"
                               id="activo"
                               value="1"
                               <?php echo $activo ? 'checked' : ''; ?>
                               style="width: auto; margin-right: 10px;">
                        <span>Rol Activo</span>
                    </label>
                    <small style="color: #666; display: block; margin-top: 5px;">
                        Los roles inactivos no pueden ser asignados a nuevos usuarios
                    </small>
                </div>

                <!-- Sección de Permisos -->
                <div class="form-group">
                    <label><strong>Permisos del Rol</strong></label>
                    <small style="color: #666; display: block; margin-bottom: 15px;">
                        Selecciona los permisos que tendrá este rol
                    </small>

                    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto;">
                        <?php foreach($permissions_grouped as $category => $perms): ?>
                            <div style="margin-bottom: 15px;">
                                <div style="background: #e9ecef; padding: 8px 12px; border-radius: 4px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">
                                    <strong><?php echo htmlspecialchars($category); ?></strong>
                                    <label style="font-size: 12px; cursor: pointer;">
                                        <input type="checkbox" class="select-all-category" data-category="<?php echo htmlspecialchars($category); ?>" style="margin-right: 5px;">
                                        Todos
                                    </label>
                                </div>
                                <?php foreach($perms as $perm): ?>
                                    <label style="display: flex; align-items: center; padding: 6px 12px; cursor: pointer; border-bottom: 1px solid #eee;">
                                        <input type="checkbox"
                                               name="permisos[]"
                                               value="<?php echo $perm['id_permiso']; ?>"
                                               class="perm-checkbox category-<?php echo htmlspecialchars($category); ?>"
                                               <?php echo in_array($perm['id_permiso'], $current_permissions) ? 'checked' : ''; ?>
                                               style="width: auto; margin-right: 10px;">
                                        <span>
                                            <code style="background: #fff; padding: 2px 6px; border-radius: 3px; font-size: 12px;"><?php echo htmlspecialchars($perm['clave_permiso']); ?></code>
                                            <span style="color: #666; margin-left: 8px;"><?php echo htmlspecialchars($perm['descripcion']); ?></span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="margin-top: 10px; display: flex; gap: 10px;">
                        <button type="button" id="selectAll" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">Seleccionar Todos</button>
                        <button type="button" id="deselectAll" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">Deseleccionar Todos</button>
                    </div>
                </div>

                <?php if($is_edit && $role_info): ?>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        <strong>Información del Rol:</strong><br>
                        <small style="color: #666;">
                            ID: <?php echo $role_info['id_rol']; ?><br>
                            Usuarios asignados: <?php echo count_users_by_role($role_info['id_rol']); ?><br>
                            Tipo: <?php echo ($role_info['id_rol'] <= 3) ? 'Sistema' : 'Personalizado'; ?><br>
                            Permisos asignados: <?php echo count($current_permissions); ?>
                        </small>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary">
                    <?php echo $is_edit ? 'Guardar Cambios' : 'Crear Rol'; ?>
                </button>

                <a href="roles.php">
                    <button type="button" class="btn btn-secondary">Cancelar</button>
                </a>
            </form>
        </div>
    </div>
    <script>
        // Seleccionar/Deseleccionar todos los permisos
        document.getElementById('selectAll').addEventListener('click', function() {
            document.querySelectorAll('.perm-checkbox').forEach(function(cb) {
                cb.checked = true;
            });
            document.querySelectorAll('.select-all-category').forEach(function(cb) {
                cb.checked = true;
            });
        });

        document.getElementById('deselectAll').addEventListener('click', function() {
            document.querySelectorAll('.perm-checkbox').forEach(function(cb) {
                cb.checked = false;
            });
            document.querySelectorAll('.select-all-category').forEach(function(cb) {
                cb.checked = false;
            });
        });

        // Seleccionar/Deseleccionar por categoría
        document.querySelectorAll('.select-all-category').forEach(function(categoryCheckbox) {
            categoryCheckbox.addEventListener('change', function() {
                var category = this.getAttribute('data-category');
                var isChecked = this.checked;
                document.querySelectorAll('.category-' + category).forEach(function(cb) {
                    cb.checked = isChecked;
                });
            });
        });

        // Actualizar checkbox de categoría cuando se cambian los permisos individuales
        document.querySelectorAll('.perm-checkbox').forEach(function(permCheckbox) {
            permCheckbox.addEventListener('change', function() {
                var classes = this.className.split(' ');
                var categoryClass = classes.find(function(c) { return c.startsWith('category-'); });
                if(categoryClass) {
                    var category = categoryClass.replace('category-', '');
                    var allInCategory = document.querySelectorAll('.category-' + category);
                    var checkedInCategory = document.querySelectorAll('.category-' + category + ':checked');
                    var categoryCheckbox = document.querySelector('.select-all-category[data-category="' + category + '"]');
                    if(categoryCheckbox) {
                        categoryCheckbox.checked = (allInCategory.length === checkedInCategory.length);
                    }
                }
            });
        });

        // Inicializar estado de checkboxes de categoría
        document.querySelectorAll('.select-all-category').forEach(function(categoryCheckbox) {
            var category = categoryCheckbox.getAttribute('data-category');
            var allInCategory = document.querySelectorAll('.category-' + category);
            var checkedInCategory = document.querySelectorAll('.category-' + category + ':checked');
            categoryCheckbox.checked = (allInCategory.length === checkedInCategory.length && allInCategory.length > 0);
        });
    </script>
</body>
</html>
