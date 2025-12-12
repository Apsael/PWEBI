<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

require_admin();

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_usuario'])) {
    $id_usuario = (int)$_POST['id_usuario'];
    
    // No permitir eliminar al admin principal
    if($id_usuario == 1) {
        header("Location: admin_dashboard.php?error=cannot_delete_admin");
        exit();
    }
    
    if(delete_user($id_usuario)) {
        header("Location: admin_dashboard.php?deleted=success");
    } else {
        header("Location: admin_dashboard.php?error=delete_failed");
    }
} else {
    header("Location: admin_dashboard.php");
}
exit();
?>