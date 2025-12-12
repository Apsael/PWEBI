<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

require_login();
require_permission('curso.enroll');

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_curso = (int)$_POST['id_curso'];
    $id_estudiante = $_SESSION['user_id'];

    $result = enroll_student($id_curso, $id_estudiante);

    if($result === true) {
        header("Location: courses.php?enrolled=success");
    } else {
        header("Location: courses.php?error=enrollment_failed");
    }
    exit();
}

header("Location: courses.php");
exit();
?>
