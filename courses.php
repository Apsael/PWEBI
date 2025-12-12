<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

require_login();

// Verificar permisos
$can_manage = has_any_permission(['curso.create', 'curso.update', 'curso.delete']);
$can_view = has_permission('curso.read');

if(!$can_view && !$can_manage) {
    header("Location: dashboard.php");
    exit();
}

// Obtener todos los cursos (admin/docente) o solo activos (estudiante)
$cursos = $can_manage ? get_all_courses() : get_all_courses(true);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Cursos - Sistema de Clases</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="navbar">
        <div class="nav-brand">
            <h2>📚 Sistema de Clases</h2>
        </div>
        <div class="nav-links">
            <?php if(is_admin()): ?>
                <a href="admin_dashboard.php">Usuarios</a>
                <a href="roles.php">Roles</a>
            <?php else: ?>
                <a href="dashboard.php">Dashboard</a>
            <?php endif; ?>
            <a href="courses.php" class="active">Cursos</a>
            <a href="profile.php">Mi Perfil</a>
            <a href="logout.php">Cerrar Sesión</a>
        </div>
    </div>

    <div class="dashboard-container">
        <div class="welcome-section">
            <h1>📖 Gestión de Cursos</h1>
            <p style="color: #666; text-align: center;">
                <?php if($can_manage): ?>
                    Administra los cursos del sistema
                <?php else: ?>
                    Explora y matricúlate en los cursos disponibles
                <?php endif; ?>
            </p>
        </div>

        <?php if(isset($_GET['created']) && $_GET['created'] == 'success'): ?>
            <div class="alert alert-success">Curso creado correctamente.</div>
        <?php endif; ?>

        <?php if(isset($_GET['updated']) && $_GET['updated'] == 'success'): ?>
            <div class="alert alert-success">Curso actualizado correctamente.</div>
        <?php endif; ?>

        <?php if(isset($_GET['deleted']) && $_GET['deleted'] == 'success'): ?>
            <div class="alert alert-success">Curso eliminado correctamente.</div>
        <?php endif; ?>

        <?php if(isset($_GET['enrolled']) && $_GET['enrolled'] == 'success'): ?>
            <div class="alert alert-success">Te has inscrito al curso exitosamente.</div>
        <?php endif; ?>

        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php
                    if($_GET['error'] == 'course_full') {
                        echo "El curso ha alcanzado su capacidad máxima.";
                    } elseif($_GET['error'] == 'already_enrolled') {
                        echo "Ya estás inscrito en este curso.";
                    } else {
                        echo "Error al procesar la solicitud.";
                    }
                ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Lista de Cursos (<?php echo count($cursos); ?>)</h2>
                <?php if(has_permission('curso.create')): ?>
                    <a href="course_form.php">
                        <button class="btn btn-primary">Nuevo Curso</button>
                    </a>
                <?php endif; ?>
            </div>

            <?php if(empty($cursos)): ?>
                <div class="alert" style="background: #f8f9fa; color: #666;">
                    No hay cursos disponibles en este momento.
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>CÓDIGO</th>
                            <th>NOMBRE DEL CURSO</th>
                            <th>DOCENTE</th>
                            <th>CRÉDITOS</th>
                            <th>INSCRITOS</th>
                            <th>ESTADO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($cursos as $curso): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($curso['codigo_curso']); ?></strong></td>
                            <td><?php echo htmlspecialchars($curso['nombre_curso']); ?></td>
                            <td><?php echo htmlspecialchars($curso['docente_nombre'] ?: 'Sin asignar'); ?></td>
                            <td style="text-align: center;"><?php echo $curso['creditos']; ?></td>
                            <td style="text-align: center;">
                                <span class="status-badge <?php echo $curso['estudiantes_inscritos'] > 0 ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $curso['estudiantes_inscritos']; ?> / <?php echo $curso['capacidad_maxima']; ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <?php if($curso['activo']): ?>
                                    <span class="status-badge status-active">Activo</span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td style="white-space: nowrap;">
                                <a href="course_detail.php?id=<?php echo $curso['id_curso']; ?>">
                                    <button class="btn btn-info">Ver</button>
                                </a>

                                <?php if(has_permission('curso.update')): ?>
                                    <a href="course_form.php?id=<?php echo $curso['id_curso']; ?>">
                                        <button class="btn btn-warning">Editar</button>
                                    </a>
                                <?php endif; ?>

                                <?php if(has_permission('curso.enroll') && $_SESSION['id_rol'] == 3 && $curso['activo']): ?>
                                    <form method="POST" action="enroll_course.php" style="display: inline;">
                                        <input type="hidden" name="id_curso" value="<?php echo $curso['id_curso']; ?>">
                                        <button type="submit" class="btn btn-primary"
                                                <?php echo ($curso['estudiantes_inscritos'] >= $curso['capacidad_maxima']) ? 'disabled' : ''; ?>>
                                            Inscribirse
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if(has_permission('curso.delete')): ?>
                                    <form method="POST" action="delete_course.php" style="display: inline;"
                                          onsubmit="return confirm('¿Estás seguro de que deseas eliminar el curso \'<?php echo htmlspecialchars($curso['nombre_curso']); ?>\'?');">
                                        <input type="hidden" name="id_curso" value="<?php echo $curso['id_curso']; ?>">
                                        <button type="submit" class="btn btn-danger"
                                                <?php echo ($curso['estudiantes_inscritos'] > 0) ? 'disabled title="Este curso tiene estudiantes inscritos"' : ''; ?>>
                                            Eliminar
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
