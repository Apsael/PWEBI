<?php
/**
 * Script para Limpiar Roles Duplicados
 * ADVERTENCIA: Este script eliminará roles duplicados dejando solo el más antiguo
 */

require_once 'config/database.php';
require_once 'includes/session.php';

// Solo admin puede ejecutar este script
require_admin();

$database = new Database();
$db = $database->connect();

echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Limpiar Roles Duplicados</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; margin: 20px 0; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .ok { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .btn { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { background: #45a049; }
    </style>
</head>
<body>
    <h1>🧹 Limpieza de Roles Duplicados</h1>';

try {
    // Buscar duplicados
    $stmt = $db->query('
        SELECT nombre_rol, COUNT(*) as count, GROUP_CONCAT(id_rol ORDER BY id_rol) as ids
        FROM roles
        GROUP BY nombre_rol
        HAVING count > 1
    ');
    $duplicados = $stmt->fetchAll();

    if(count($duplicados) > 0) {
        echo '<div class="section">';
        echo '<p class="warning">Se encontraron ' . count($duplicados) . ' rol(es) duplicado(s):</p>';
        echo '<ul>';

        $cleaned = 0;

        foreach($duplicados as $dup) {
            $ids = explode(',', $dup['ids']);
            $keep_id = $ids[0]; // Mantener el primero (más antiguo)
            $delete_ids = array_slice($ids, 1); // Eliminar el resto

            echo '<li><strong>' . htmlspecialchars($dup['nombre_rol']) . '</strong> (IDs: ' . $dup['ids'] . ')<br>';
            echo 'Manteniendo ID: ' . $keep_id . '<br>';
            echo 'Eliminando IDs: ' . implode(', ', $delete_ids) . '<br>';

            // Verificar si los duplicados tienen usuarios asignados
            foreach($delete_ids as $del_id) {
                $check_users = $db->prepare('SELECT COUNT(*) as count FROM usuarios WHERE id_rol = :id');
                $check_users->execute([':id' => $del_id]);
                $user_count = $check_users->fetch()['count'];

                if($user_count > 0) {
                    // Reasignar usuarios al rol que vamos a mantener
                    echo 'Reasignando ' . $user_count . ' usuario(s) del rol ' . $del_id . ' al rol ' . $keep_id . '...<br>';
                    $update_users = $db->prepare('UPDATE usuarios SET id_rol = :keep_id WHERE id_rol = :del_id');
                    $update_users->execute([':keep_id' => $keep_id, ':del_id' => $del_id]);
                }

                // Eliminar el rol duplicado
                $delete_role = $db->prepare('DELETE FROM roles WHERE id_rol = :id');
                if($delete_role->execute([':id' => $del_id])) {
                    echo '<span class="ok">✅ Rol ID ' . $del_id . ' eliminado</span><br>';
                    $cleaned++;
                } else {
                    echo '<span class="error">❌ Error al eliminar rol ID ' . $del_id . '</span><br>';
                }
            }

            echo '</li>';
        }

        echo '</ul>';
        echo '<p class="ok">✅ Limpieza completada. Se eliminaron ' . $cleaned . ' rol(es) duplicado(s).</p>';
        echo '</div>';

    } else {
        echo '<div class="section">';
        echo '<p class="ok">✅ No se encontraron roles duplicados. La base de datos está limpia.</p>';
        echo '</div>';
    }

} catch(Exception $e) {
    echo '<div class="section">';
    echo '<p class="error">❌ Error durante la limpieza: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div>';
}

echo '<div class="section">';
echo '<p><a href="diagnostico_roles.php" class="btn">Ver Diagnóstico</a>';
echo '<a href="roles.php" class="btn">Volver a Roles</a></p>';
echo '</div>';

echo '</body></html>';
?>
