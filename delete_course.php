<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

require_login();
require_permission('curso.delete');

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_curso = (int)$_POST['id_curso'];

    if(delete_course($id_curso)) {
        header("Location: courses.php?deleted=success");
    } else {
        header("Location: courses.php?error=cannot_delete");
    }
    exit();
}

header("Location: courses.php");
exit();
?>
