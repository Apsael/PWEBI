<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

require_login();
require_permission('curso.create');

$error = '';
$is_edit = false;
$course_info = null;
$id_curso = null;

// Verificar si es edición
if(isset($_GET['id']) && !empty($_GET['id'])) {
    $is_edit = true;
    $id_curso = (int)$_GET['id'];
    $course_info = get_course_info($id_curso);

    if(!$course_info) {
        header("Location: courses.php?error=course_not_found");
        exit();
    }
}

// Obtener docentes disponibles
$docentes = get_available_teachers();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo_curso = sanitize_input($_POST['codigo_curso']);
    $nombre_curso = sanitize_input($_POST['nombre_curso']);
    $descripcion = sanitize_input($_POST['descripcion']);
    $id_docente = !empty($_POST['id_docente']) ? (int)$_POST['id_docente'] : null;
    $creditos = (int)$_POST['creditos'];
    $horas_semanales = (int)$_POST['horas_semanales'];
    $capacidad_maxima = (int)$_POST['capacidad_maxima'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    $fecha_inicio = !empty($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : null;
    $fecha_fin = !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null;

    if(empty($codigo_curso) || empty($nombre_curso)) {
        $error = "El código y nombre del curso son obligatorios";
    } else {
        $exclude_id = $is_edit ? $id_curso : null;
        if(course_code_exists($codigo_curso, $exclude_id)) {
            $error = "Ya existe un curso con ese código";
        } else {
            $data = [
                'codigo_curso' => $codigo_curso,
                'nombre_curso' => $nombre_curso,
                'descripcion' => $descripcion,
                'id_docente' => $id_docente,
                'creditos' => $creditos,
                'horas_semanales' => $horas_semanales,
                'capacidad_maxima' => $capacidad_maxima,
                'activo' => $activo,
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin
            ];

            if($is_edit) {
                if(update_course($id_curso, $data)) {
                    header("Location: courses.php?updated=success");
                    exit();
                } else {
                    $error = "Error al actualizar el curso";
                }
            } else {
                if(create_course($data)) {
                    header("Location: courses.php?created=success");
                    exit();
                } else {
                    $error = "Error al crear el curso";
                }
            }
        }
    }
}

// Valores por defecto
$codigo_curso = $course_info['codigo_curso'] ?? '';
$nombre_curso = $course_info['nombre_curso'] ?? '';
$descripcion = $course_info['descripcion'] ?? '';
$id_docente = $course_info['id_docente'] ?? '';
$creditos = $course_info['creditos'] ?? 3;
$horas_semanales = $course_info['horas_semanales'] ?? 4;
$capacidad_maxima = $course_info['capacidad_maxima'] ?? 30;
$activo = $course_info['activo'] ?? true;
$fecha_inicio = $course_info['fecha_inicio'] ?? '';
$fecha_fin = $course_info['fecha_fin'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Editar Curso' : 'Nuevo Curso'; ?> - Sistema de Clases</title>
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
            <?php endif; ?>
            <a href="courses.php" class="active">Cursos</a>
            <a href="profile.php">Mi Perfil</a>
            <a href="logout.php">Cerrar Sesión</a>
        </div>
    </div>

    <div class="dashboard-container">
        <div class="container" style="max-width: 800px;">
            <h1><?php echo $is_edit ? '✏️ Editar Curso' : '➕ Nuevo Curso'; ?></h1>

            <?php if($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="codigo_curso">Código del Curso *</label>
                        <input type="text" id="codigo_curso" name="codigo_curso" required
                               value="<?php echo htmlspecialchars($codigo_curso); ?>"
                               placeholder="Ej: WEB201">
                    </div>

                    <div class="form-group">
                        <label for="creditos">Créditos *</label>
                        <input type="number" id="creditos" name="creditos" required min="1" max="10"
                               value="<?php echo $creditos; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="nombre_curso">Nombre del Curso *</label>
                    <input type="text" id="nombre_curso" name="nombre_curso" required
                           value="<?php echo htmlspecialchars($nombre_curso); ?>"
                           placeholder="Ej: Desarrollo Web">
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="4"
                              placeholder="Describe el contenido y objetivos del curso..."><?php echo htmlspecialchars($descripcion); ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="id_docente">Docente</label>
                        <select id="id_docente" name="id_docente">
                            <option value="">Sin asignar</option>
                            <?php foreach($docentes as $docente): ?>
                                <option value="<?php echo $docente['id_usuario']; ?>"
                                        <?php echo ($id_docente == $docente['id_usuario']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($docente['nombre_completo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="horas_semanales">Horas Semanales *</label>
                        <input type="number" id="horas_semanales" name="horas_semanales" required min="1" max="40"
                               value="<?php echo $horas_semanales; ?>">
                    </div>

                    <div class="form-group">
                        <label for="capacidad_maxima">Capacidad Máxima *</label>
                        <input type="number" id="capacidad_maxima" name="capacidad_maxima" required min="1" max="100"
                               value="<?php echo $capacidad_maxima; ?>">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="fecha_inicio">Fecha de Inicio</label>
                        <input type="date" id="fecha_inicio" name="fecha_inicio"
                               value="<?php echo $fecha_inicio; ?>">
                    </div>

                    <div class="form-group">
                        <label for="fecha_fin">Fecha de Fin</label>
                        <input type="date" id="fecha_fin" name="fecha_fin"
                               value="<?php echo $fecha_fin; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" name="activo" id="activo" value="1"
                               <?php echo $activo ? 'checked' : ''; ?>
                               style="width: auto; margin-right: 10px;">
                        <span>Curso Activo</span>
                    </label>
                    <small style="color: #666; display: block; margin-top: 5px;">
                        Los cursos inactivos no estarán disponibles para inscripción
                    </small>
                </div>

                <div style="margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $is_edit ? 'Guardar Cambios' : 'Crear Curso'; ?>
                    </button>
                    <a href="courses.php">
                        <button type="button" class="btn btn-secondary">Cancelar</button>
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
