<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

require_login();

// Obtener información del usuario
$user_info = get_user_info($_SESSION['user_id']);

// Si es estudiante, obtener sus cursos
$mis_cursos = [];
if($_SESSION['id_rol'] == 3) {
    $mis_cursos = get_student_courses($_SESSION['user_id']);
}

// Verificar si el email está verificado
$email_no_verificado = isset($_SESSION['email_verificado']) && !$_SESSION['email_verificado'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo $_SESSION['nombre']; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="navbar">
        <div class="nav-brand">
            <h2>📚 Sistema de Clases</h2>
        </div>
        <div class="nav-links">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="courses.php">Cursos</a>
            <a href="profile.php">Mi Perfil</a>
            <a href="logout.php">Cerrar Sesión</a>
        </div>
    </div>

    <div class="dashboard-container">
        <div class="welcome-section">
            <h1>👋 Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']); ?>!</h1>
            <p style="color: #666; text-align: center;">
                Rol: <strong><?php echo htmlspecialchars($_SESSION['nombre_rol']); ?></strong>
            </p>
        </div>

        <?php if($email_no_verificado): ?>
            <div class="alert" style="background: #fff3cd; color: #856404; border-left: 4px solid #ffc107;">
                <strong>⚠️ Email no verificado:</strong>
                Tu correo electrónico aún no ha sido verificado.
                Por favor, revisa tu bandeja de entrada.
                <a href="resend_verification.php" style="color: #856404; text-decoration: underline;">
                    Reenviar correo de verificación
                </a>
            </div>
        <?php endif; ?>

        <?php if($_SESSION['id_rol'] == 3 && !empty($mis_cursos)): ?>
            <div class="table-container">
                <h2>📖 Mis Cursos (<?php echo count($mis_cursos); ?>)</h2>
                <table>
                    <thead>
                        <tr>
                            <th>CÓDIGO</th>
                            <th>CURSO</th>
                            <th>DOCENTE</th>
                            <th>CRÉDITOS</th>
                            <th>ESTADO</th>
                            <th>NOTA FINAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($mis_cursos as $curso): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($curso['codigo_curso']); ?></strong></td>
                            <td><?php echo htmlspecialchars($curso['nombre_curso']); ?></td>
                            <td><?php echo htmlspecialchars($curso['docente_nombre'] ?: 'Sin asignar'); ?></td>
                            <td style="text-align: center;"><?php echo $curso['creditos']; ?></td>
                            <td style="text-align: center;">
                                <?php
                                $estado_class = '';
                                switch($curso['estado']) {
                                    case 'Activo':
                                        $estado_class = 'status-active';
                                        break;
                                    case 'Completado':
                                        $estado_class = 'badge-success';
                                        break;
                                    default:
                                        $estado_class = 'status-inactive';
                                }
                                ?>
                                <span class="status-badge <?php echo $estado_class; ?>">
                                    <?php echo htmlspecialchars($curso['estado']); ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <?php echo $curso['nota_final'] ? number_format($curso['nota_final'], 2) : '-'; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="margin-top: 20px; text-align: center;">
                    <a href="courses.php">
                        <button class="btn btn-primary" style="width: auto;">Ver Más Cursos</button>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div class="info-card">
            <h2>ℹ️ Información de tu Cuenta</h2>
            
            <div class="info-row">
                <div class="info-label">Usuario:</div>
                <div class="info-value"><?php echo htmlspecialchars($user_info['username']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Nombre Completo:</div>
                <div class="info-value"><?php echo htmlspecialchars($user_info['nombre'] . ' ' . $user_info['apellido']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">DNI:</div>
                <div class="info-value"><?php echo htmlspecialchars($user_info['dni']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value"><?php echo htmlspecialchars($user_info['email']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Teléfono:</div>
                <div class="info-value"><?php echo htmlspecialchars($user_info['telefono'] ?: 'No registrado'); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Dirección:</div>
                <div class="info-value"><?php echo htmlspecialchars($user_info['direccion'] ?: 'No registrada'); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Rol:</div>
                <div class="info-value"><?php echo htmlspecialchars($user_info['nombre_rol']); ?></div>
            </div>
            
            <div class="info-row">
                <div class="info-label">Último Acceso:</div>
                <div class="info-value">
                    <?php 
                    if($user_info['ultimo_acceso']) {
                        echo date('d/m/Y H:i', strtotime($user_info['ultimo_acceso']));
                    } else {
                        echo 'Primer acceso';
                    }
                    ?>
                </div>
            </div>
            
            <div style="margin-top: 30px; text-align: center;">
                <a href="profile.php">
                    <button class="btn btn-primary" style="width: auto; padding: 12px 40px;">
                        Editar Perfil
                    </button>
                </a>
            </div>
        </div>
    </div>
</body>
</html>