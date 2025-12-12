<?php
/**
 * Script de Diagnóstico de Roles
 * Ejecutar este archivo desde el navegador para diagnosticar problemas con roles
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

// Estilo CSS inline
echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico de Roles</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; margin: 20px 0; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .ok { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .btn { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #45a049; }
        .btn-danger { background: #f44336; }
        .btn-danger:hover { background: #da190b; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico del Sistema de Roles</h1>
    <p><strong>Fecha:</strong> ' . date('Y-m-d H:i:s') . '</p>
';

try {
    $database = new Database();
    $db = $database->connect();

    // ============================================
    // 1. LISTAR TODOS LOS ROLES
    // ============================================
    echo '<div class="section">';
    echo '<h2>1. Roles en la Base de Datos</h2>';

    $stmt = $db->query('SELECT * FROM roles ORDER BY id_rol');
    $roles = $stmt->fetchAll();

    echo '<p><strong>Total de roles:</strong> ' . count($roles) . '</p>';

    if(count($roles) > 0) {
        echo '<table>';
        echo '<tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Creado</th><th>Actualizado</th></tr>';

        foreach($roles as $rol) {
            echo '<tr>';
            echo '<td>' . $rol['id_rol'] . '</td>';
            echo '<td><strong>' . htmlspecialchars($rol['nombre_rol']) . '</strong></td>';
            echo '<td>' . htmlspecialchars($rol['descripcion'] ?: '(vacía)') . '</td>';
            echo '<td>' . $rol['created_at'] . '</td>';
            echo '<td>' . $rol['updated_at'] . '</td>';
            echo '</tr>';
        }

        echo '</table>';
    } else {
        echo '<p class="warning">⚠️ No hay roles en la base de datos.</p>';
    }

    echo '</div>';

    // ============================================
    // 2. VERIFICAR ROLES DUPLICADOS
    // ============================================
    echo '<div class="section">';
    echo '<h2>2. Verificación de Duplicados</h2>';

    $stmt2 = $db->query('SELECT nombre_rol, COUNT(*) as count FROM roles GROUP BY nombre_rol HAVING count > 1');
    $duplicados = $stmt2->fetchAll();

    if(count($duplicados) > 0) {
        echo '<p class="error">❌ Se encontraron roles duplicados:</p>';
        echo '<table>';
        echo '<tr><th>Nombre del Rol</th><th>Cantidad de Duplicados</th></tr>';

        foreach($duplicados as $dup) {
            echo '<tr>';
            echo '<td class="error">' . htmlspecialchars($dup['nombre_rol']) . '</td>';
            echo '<td class="error">' . $dup['count'] . '</td>';
            echo '</tr>';
        }

        echo '</table>';
        echo '<p><a href="limpiar_duplicados.php" class="btn btn-danger" onclick="return confirm(\'¿Está seguro de eliminar los roles duplicados? Esta acción no se puede deshacer.\')">🗑️ Limpiar Duplicados</a></p>';
    } else {
        echo '<p class="ok">✅ No hay roles duplicados.</p>';
    }

    echo '</div>';

    // ============================================
    // 3. USUARIOS POR ROL
    // ============================================
    echo '<div class="section">';
    echo '<h2>3. Usuarios Asignados por Rol</h2>';

    $stmt3 = $db->query('
        SELECT r.id_rol, r.nombre_rol, COUNT(u.id_usuario) as user_count
        FROM roles r
        LEFT JOIN usuarios u ON r.id_rol = u.id_rol
        GROUP BY r.id_rol
        ORDER BY r.id_rol
    ');
    $users_per_role = $stmt3->fetchAll();

    echo '<table>';
    echo '<tr><th>ID</th><th>Nombre del Rol</th><th>Usuarios Asignados</th><th>Puede Eliminarse</th></tr>';

    foreach($users_per_role as $row) {
        $can_delete = ($row['id_rol'] > 3 && $row['user_count'] == 0);

        echo '<tr>';
        echo '<td>' . $row['id_rol'] . '</td>';
        echo '<td>' . htmlspecialchars($row['nombre_rol']) . '</td>';
        echo '<td>' . $row['user_count'] . '</td>';

        if($row['id_rol'] <= 3) {
            echo '<td class="warning">❌ Rol del Sistema</td>';
        } elseif($row['user_count'] > 0) {
            echo '<td class="warning">❌ Tiene usuarios asignados</td>';
        } else {
            echo '<td class="ok">✅ Sí</td>';
        }

        echo '</tr>';
    }

    echo '</table>';
    echo '</div>';

    // ============================================
    // 4. VERIFICAR INTEGRIDAD
    // ============================================
    echo '<div class="section">';
    echo '<h2>4. Verificación de Integridad</h2>';

    // Verificar roles del sistema
    $system_roles = ['Admin', 'Docente', 'Estudiante'];
    $missing_roles = [];

    foreach($system_roles as $role_name) {
        $stmt_check = $db->prepare('SELECT id_rol FROM roles WHERE nombre_rol = :name');
        $stmt_check->execute([':name' => $role_name]);

        if($stmt_check->rowCount() == 0) {
            $missing_roles[] = $role_name;
        }
    }

    if(count($missing_roles) > 0) {
        echo '<p class="error">❌ Faltan roles del sistema: ' . implode(', ', $missing_roles) . '</p>';
    } else {
        echo '<p class="ok">✅ Todos los roles del sistema están presentes.</p>';
    }

    // Verificar usuarios huérfanos
    $stmt_orphan = $db->query('
        SELECT COUNT(*) as count
        FROM usuarios u
        LEFT JOIN roles r ON u.id_rol = r.id_rol
        WHERE r.id_rol IS NULL
    ');
    $orphan_result = $stmt_orphan->fetch();

    if($orphan_result['count'] > 0) {
        echo '<p class="error">❌ Hay ' . $orphan_result['count'] . ' usuario(s) con roles inexistentes.</p>';
    } else {
        echo '<p class="ok">✅ No hay usuarios huérfanos.</p>';
    }

    echo '</div>';

    // ============================================
    // 5. TEST DE FUNCIONES PHP
    // ============================================
    echo '<div class="section">';
    echo '<h2>5. Test de Funciones PHP</h2>';

    // Test get_roles()
    $test_roles = get_roles();
    echo '<p><strong>get_roles():</strong> Retorna ' . count($test_roles) . ' roles ';
    echo (count($test_roles) == count($roles)) ? '<span class="ok">✅</span>' : '<span class="error">❌</span>';
    echo '</p>';

    // Test count_users_by_role() para cada rol
    echo '<p><strong>count_users_by_role():</strong></p>';
    echo '<ul>';
    foreach($test_roles as $rol) {
        $count = count_users_by_role($rol['id_rol']);
        echo '<li>Rol ' . htmlspecialchars($rol['nombre_rol']) . ': ' . $count . ' usuarios</li>';
    }
    echo '</ul>';

    echo '</div>';

} catch(Exception $e) {
    echo '<div class="section">';
    echo '<p class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div>';
}

echo '<div class="section">';
echo '<p><a href="roles.php" class="btn">← Volver a Gestión de Roles</a></p>';
echo '</div>';

echo '</body></html>';
?>
