<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

require_admin();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(!isset($_POST['id_rol']) || empty($_POST['id_rol'])) {
        header("Location: roles.php?error=invalid_request");
        exit();
    }

    $id_rol = (int)$_POST['id_rol'];

    // Verificar que el rol existe
    $role_info = get_role_info($id_rol);
    if(!$role_info) {
        header("Location: roles.php?error=role_not_found");
        exit();
    }

    // Intentar eliminar el rol
    if(delete_role($id_rol)) {
        header("Location: roles.php?deleted=success");
        exit();
    } else {
        // Determinar el motivo del error
        if($id_rol <= 3) {
            header("Location: roles.php?error=cannot_delete_system_role");
        } elseif(count_users_by_role($id_rol) > 0) {
            header("Location: roles.php?error=role_has_users");
        } else {
            header("Location: roles.php?error=delete_failed");
        }
        exit();
    }
} else {
    // Si no es POST, redirigir
    header("Location: roles.php");
    exit();
}
?>
