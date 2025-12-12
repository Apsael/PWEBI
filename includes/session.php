<?php
// Iniciar sesión segura
if (session_status() == PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400,
        'cookie_secure' => false, // Cambiar a true si usas HTTPS
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict'
    ]);
}

// Verificar si el usuario está logueado
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Verificar si el usuario es admin
function is_admin() {
    return isset($_SESSION['id_rol']) && $_SESSION['id_rol'] == 1;
}

// Redirigir si no está logueado
function require_login() {
    if(!is_logged_in()) {
        header("Location: index.php");
        exit();
    }
}

// Redirigir si no es admin
function require_admin() {
    require_login();
    if(!is_admin()) {
        header("Location: dashboard.php");
        exit();
    }
}

// Cerrar sesión
function logout_user() {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

// Verificar si el usuario tiene un permiso específico
function has_permission($clave_permiso) {
    if(!is_logged_in()) {
        return false;
    }

    require_once __DIR__ . '/functions.php';
    return user_has_permission($_SESSION['user_id'], $clave_permiso);
}

// Requerir un permiso específico (redirigir si no lo tiene)
function require_permission($clave_permiso, $redirect = 'dashboard.php') {
    require_login();

    if(!has_permission($clave_permiso)) {
        header("Location: $redirect");
        exit();
    }
}

// Verificar si el usuario tiene alguno de los permisos especificados
function has_any_permission($claves_permisos) {
    if(!is_logged_in()) {
        return false;
    }

    foreach($claves_permisos as $clave) {
        if(has_permission($clave)) {
            return true;
        }
    }

    return false;
}

// Verificar si el usuario tiene todos los permisos especificados
function has_all_permissions($claves_permisos) {
    if(!is_logged_in()) {
        return false;
    }

    foreach($claves_permisos as $clave) {
        if(!has_permission($clave)) {
            return false;
        }
    }

    return true;
}
?>