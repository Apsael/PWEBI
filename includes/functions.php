<?php
require_once __DIR__ . '/../config/database.php';

// Sanitizar entrada de datos
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Verificar si el usuario existe por username
function user_exists($username) {
    $database = new Database();
    $db = $database->connect();
    
    $query = "SELECT id_usuario FROM usuarios WHERE username = :username";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    return $stmt->rowCount() > 0;
}

// Verificar si el email existe
function email_exists($email) {
    $database = new Database();
    $db = $database->connect();
    
    $query = "SELECT id_persona FROM personas WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    return $stmt->rowCount() > 0;
}

// Verificar si el DNI existe
function dni_exists($dni) {
    $database = new Database();
    $db = $database->connect();
    
    $query = "SELECT id_persona FROM personas WHERE dni = :dni";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':dni', $dni);
    $stmt->execute();
    
    return $stmt->rowCount() > 0;
}

// Crear nuevo usuario
function create_user($data) {
    $database = new Database();
    $db = $database->connect();

    try {
        $db->beginTransaction();

        // Insertar persona
        $query_persona = "INSERT INTO personas (nombre, apellido, dni, email, telefono, direccion)
                         VALUES (:nombre, :apellido, :dni, :email, :telefono, :direccion)";
        $stmt_persona = $db->prepare($query_persona);
        $stmt_persona->execute([
            ':nombre' => $data['nombre'],
            ':apellido' => $data['apellido'],
            ':dni' => $data['dni'],
            ':email' => $data['email'],
            ':telefono' => $data['telefono'],
            ':direccion' => $data['direccion']
        ]);

        $id_persona = $db->lastInsertId();

        // Insertar usuario (rol por defecto: Estudiante = 3)
        $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $query_usuario = "INSERT INTO usuarios (id_persona, id_rol, username, password_hash)
                         VALUES (:id_persona, 3, :username, :password_hash)";
        $stmt_usuario = $db->prepare($query_usuario);
        $stmt_usuario->execute([
            ':id_persona' => $id_persona,
            ':username' => $data['username'],
            ':password_hash' => $password_hash
        ]);

        $id_usuario = $db->lastInsertId();

        $db->commit();

        // Generar y enviar token de verificación de email
        $token = create_verification_token($id_usuario);
        if($token) {
            send_verification_email($data['email'], $data['nombre'], $token);
        }

        return true;

    } catch(Exception $e) {
        $db->rollBack();
        return false;
    }
}

// Autenticar usuario (por username o email)
function authenticate_user($login, $password) {
    $database = new Database();
    $db = $database->connect();

    // Buscar por username o email
    $query = "SELECT u.id_usuario, u.username, u.password_hash, u.id_rol, u.activo, u.email_verificado,
                     p.nombre, p.apellido, p.email, r.nombre_rol, r.activo as rol_activo
              FROM usuarios u
              INNER JOIN personas p ON u.id_persona = p.id_persona
              INNER JOIN roles r ON u.id_rol = r.id_rol
              WHERE u.username = :login_user OR p.email = :login_email";

    $stmt = $db->prepare($query);
    $stmt->execute([':login_user' => $login, ':login_email' => $login]);

    if($stmt->rowCount() > 0) {
        $user = $stmt->fetch();

        // Verificar que el usuario esté activo, el rol esté activo y la contraseña sea correcta
        if($user['activo'] == 1 && $user['rol_activo'] == 1 && password_verify($password, $user['password_hash'])) {
            // Actualizar último acceso
            $update_query = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = :id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':id', $user['id_usuario']);
            $update_stmt->execute();

            return $user;
        }
    }

    return false;
}

// Obtener información completa del usuario
function get_user_info($id_usuario) {
    $database = new Database();
    $db = $database->connect();
    
    $query = "SELECT u.*, p.nombre, p.apellido, p.dni, p.email, p.telefono, p.direccion, r.nombre_rol
              FROM usuarios u
              INNER JOIN personas p ON u.id_persona = p.id_persona
              INNER JOIN roles r ON u.id_rol = r.id_rol
              WHERE u.id_usuario = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id_usuario);
    $stmt->execute();
    
    return $stmt->fetch();
}

// Obtener todos los usuarios (solo Admin)
function get_all_users() {
    $database = new Database();
    $db = $database->connect();

    $query = "SELECT u.id_usuario, u.username, u.activo, u.ultimo_acceso, u.email_verificado,
                     p.nombre, p.apellido, p.dni, p.email, p.telefono,
                     r.nombre_rol, r.activo as rol_activo
              FROM usuarios u
              INNER JOIN personas p ON u.id_persona = p.id_persona
              INNER JOIN roles r ON u.id_rol = r.id_rol
              ORDER BY u.id_usuario DESC";

    $stmt = $db->prepare($query);
    $stmt->execute();

    return $stmt->fetchAll();
}

// Actualizar perfil de usuario
function update_profile($id_usuario, $data) {
    $database = new Database();
    $db = $database->connect();
    
    try {
        $db->beginTransaction();
        
        // Actualizar persona
        $query_persona = "UPDATE personas p
                         INNER JOIN usuarios u ON p.id_persona = u.id_persona
                         SET p.nombre = :nombre,
                             p.apellido = :apellido,
                             p.telefono = :telefono,
                             p.direccion = :direccion
                         WHERE u.id_usuario = :id_usuario";
        
        $stmt = $db->prepare($query_persona);
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':apellido' => $data['apellido'],
            ':telefono' => $data['telefono'],
            ':direccion' => $data['direccion'],
            ':id_usuario' => $id_usuario
        ]);
        
        $db->commit();
        return true;
        
    } catch(Exception $e) {
        $db->rollBack();
        return false;
    }
}

// Eliminar usuario completamente (incluyendo persona y tokens de verificación)
function delete_user($id_usuario) {
    $database = new Database();
    $db = $database->connect();

    // No permitir eliminar al admin principal
    if($id_usuario == 1) {
        return false;
    }

    try {
        $db->beginTransaction();

        // Obtener id_persona del usuario
        $query_persona = "SELECT id_persona FROM usuarios WHERE id_usuario = :id";
        $stmt_persona = $db->prepare($query_persona);
        $stmt_persona->bindParam(':id', $id_usuario);
        $stmt_persona->execute();
        $result = $stmt_persona->fetch();

        if(!$result) {
            $db->rollBack();
            return false;
        }

        $id_persona = $result['id_persona'];

        // Eliminar tokens de verificación de email
        $query_tokens = "DELETE FROM email_verification_tokens WHERE id_usuario = :id";
        $stmt_tokens = $db->prepare($query_tokens);
        $stmt_tokens->bindParam(':id', $id_usuario);
        $stmt_tokens->execute();

        // Eliminar inscripciones a cursos
        $query_inscripciones = "DELETE FROM inscripciones_cursos WHERE id_estudiante = :id";
        $stmt_inscripciones = $db->prepare($query_inscripciones);
        $stmt_inscripciones->bindParam(':id', $id_usuario);
        $stmt_inscripciones->execute();

        // Eliminar usuario
        $query_usuario = "DELETE FROM usuarios WHERE id_usuario = :id";
        $stmt_usuario = $db->prepare($query_usuario);
        $stmt_usuario->bindParam(':id', $id_usuario);
        $stmt_usuario->execute();

        // Eliminar persona
        $query_delete_persona = "DELETE FROM personas WHERE id_persona = :id_persona";
        $stmt_delete_persona = $db->prepare($query_delete_persona);
        $stmt_delete_persona->bindParam(':id_persona', $id_persona);
        $stmt_delete_persona->execute();

        $db->commit();
        return true;

    } catch(Exception $e) {
        $db->rollBack();
        error_log("Error deleting user: " . $e->getMessage());
        return false;
    }
}

// Obtener roles
function get_roles() {
    $database = new Database();
    $db = $database->connect();

    $query = "SELECT * FROM roles ORDER BY id_rol";
    $stmt = $db->prepare($query);
    $stmt->execute();

    return $stmt->fetchAll();
}

// Obtener información de un rol específico
function get_role_info($id_rol) {
    $database = new Database();
    $db = $database->connect();

    $query = "SELECT * FROM roles WHERE id_rol = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id_rol);
    $stmt->execute();

    return $stmt->fetch();
}

// Verificar si el nombre del rol ya existe
function role_name_exists($nombre_rol, $exclude_id = null) {
    $database = new Database();
    $db = $database->connect();

    if ($exclude_id !== null) {
        $query = "SELECT id_rol FROM roles WHERE nombre_rol = :nombre_rol AND id_rol != :exclude_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nombre_rol', $nombre_rol, PDO::PARAM_STR);
        $stmt->bindParam(':exclude_id', $exclude_id, PDO::PARAM_INT);
    } else {
        $query = "SELECT id_rol FROM roles WHERE nombre_rol = :nombre_rol";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nombre_rol', $nombre_rol, PDO::PARAM_STR);
    }

    $stmt->execute();
    return $stmt->rowCount() > 0;
}

// Crear nuevo rol (retorna el ID del rol creado o false en caso de error)
function create_role($nombre_rol, $descripcion, $activo = true) {
    $database = new Database();
    $db = $database->connect();

    try {
        $query = "INSERT INTO roles (nombre_rol, descripcion, activo) VALUES (:nombre_rol, :descripcion, :activo)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nombre_rol', $nombre_rol);
        $stmt->bindParam(':descripcion', $descripcion, $descripcion === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(':activo', $activo, PDO::PARAM_BOOL);
        $stmt->execute();

        return $db->lastInsertId();
    } catch(Exception $e) {
        error_log("Error creating role: " . $e->getMessage());
        return false;
    }
}

// Actualizar rol
function update_role($id_rol, $nombre_rol, $descripcion, $activo = true) {
    $database = new Database();
    $db = $database->connect();

    try {
        $query = "UPDATE roles SET nombre_rol = :nombre_rol, descripcion = :descripcion, activo = :activo WHERE id_rol = :id_rol";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id_rol', $id_rol, PDO::PARAM_INT);
        $stmt->bindParam(':nombre_rol', $nombre_rol);
        $stmt->bindParam(':descripcion', $descripcion, $descripcion === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(':activo', $activo, PDO::PARAM_BOOL);
        $stmt->execute();

        return true;
    } catch(Exception $e) {
        error_log("Error updating role: " . $e->getMessage());
        return false;
    }
}

// Eliminar rol
function delete_role($id_rol) {
    $database = new Database();
    $db = $database->connect();

    // No permitir eliminar roles básicos del sistema (Admin, Docente, Estudiante)
    if($id_rol <= 3) {
        error_log("Intento de eliminar rol del sistema: ID $id_rol");
        return false;
    }

    // Verificar si hay usuarios con este rol
    $check_query = "SELECT COUNT(*) as count FROM usuarios WHERE id_rol = :id_rol";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':id_rol', $id_rol, PDO::PARAM_INT);
    $check_stmt->execute();
    $result = $check_stmt->fetch();

    if((int)$result['count'] > 0) {
        error_log("No se puede eliminar rol ID $id_rol: tiene {$result['count']} usuarios asignados");
        return false;
    }

    try {
        $query = "DELETE FROM roles WHERE id_rol = :id_rol";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id_rol', $id_rol, PDO::PARAM_INT);
        $success = $stmt->execute();

        if($success) {
            error_log("Rol ID $id_rol eliminado exitosamente");
        } else {
            error_log("Error al ejecutar DELETE para rol ID $id_rol");
        }

        return $success;
    } catch(Exception $e) {
        error_log("Excepción al eliminar rol ID $id_rol: " . $e->getMessage());
        return false;
    }
}

// Contar usuarios por rol
function count_users_by_role($id_rol) {
    $database = new Database();
    $db = $database->connect();

    $query = "SELECT COUNT(*) as count FROM usuarios WHERE id_rol = :id_rol";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_rol', $id_rol, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch();

    return (int)$result['count'];
}

// ==========================================
// FUNCIONES DE VERIFICACIÓN DE EMAIL
// ==========================================

// Generar token único de verificación
function generate_verification_token() {
    return bin2hex(random_bytes(32));
}

// Crear token de verificación para usuario
function create_verification_token($id_usuario) {
    $database = new Database();
    $db = $database->connect();

    try {
        // Generar token
        $token = generate_verification_token();

        // El token expira en 24 horas
        $fecha_expiracion = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $query = "INSERT INTO email_verification_tokens (id_usuario, token, fecha_expiracion)
                  VALUES (:id_usuario, :token, :fecha_expiracion)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':id_usuario' => $id_usuario,
            ':token' => $token,
            ':fecha_expiracion' => $fecha_expiracion
        ]);

        return $token;
    } catch(Exception $e) {
        error_log("Error creating verification token: " . $e->getMessage());
        return false;
    }
}

// Enviar correo de verificación
function send_verification_email($email, $nombre, $token) {
    // Cargar configuración de email
    require_once __DIR__ . '/../config/email.php';

    // Construir URL de verificación
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $verification_url = "$protocol://$host/ActI/verify_email.php?token=$token";

    // Construir contenido HTML del email
    $subject = "Verifica tu cuenta - " . SMTP_FROM_NAME;
    $message = build_verification_email_html($nombre, $verification_url);

    // Si SMTP está deshabilitado, usar modo desarrollo
    if(!SMTP_ENABLED) {
        return send_verification_email_dev_mode($email, $nombre, $verification_url);
    }

    // SMTP habilitado: Usar PHPMailer
    try {
        // Cargar PHPMailer
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURITY === 'ssl'
            ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        // Debug (solo si está configurado)
        $mail->SMTPDebug = SMTP_DEBUG;

        // Remitente y destinatario
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email, $nombre);

        // Contenido del email
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = "Hola $nombre, verifica tu cuenta visitando: $verification_url";

        $mail->send();

        // Log de éxito
        $log_dir = __DIR__ . '/../logs';
        if(!is_dir($log_dir)) {
            mkdir($log_dir, 0777, true);
        }
        $log_file = $log_dir . '/email_sent.log';
        $log_message = date('Y-m-d H:i:s') . " | Email enviado exitosamente a: $email\n";
        file_put_contents($log_file, $log_message, FILE_APPEND);

        return true;

    } catch (PHPMailer\PHPMailer\Exception $e) {
        // Error de PHPMailer - registrar y usar modo desarrollo como fallback
        error_log("PHPMailer Error: " . $e->getMessage());

        $log_dir = __DIR__ . '/../logs';
        if(!is_dir($log_dir)) {
            mkdir($log_dir, 0777, true);
        }
        $log_file = $log_dir . '/email_errors.log';
        $log_message = date('Y-m-d H:i:s') . " | Error enviando a $email: " . $e->getMessage() . "\n";
        file_put_contents($log_file, $log_message, FILE_APPEND);

        // Fallback a modo desarrollo para no bloquear al usuario
        return send_verification_email_dev_mode($email, $nombre, $verification_url);
    }
}

// Modo desarrollo: guardar enlace en archivo y sesión
function send_verification_email_dev_mode($email, $nombre, $verification_url) {
    $log_dir = __DIR__ . '/../logs';
    if(!is_dir($log_dir)) {
        mkdir($log_dir, 0777, true);
    }

    $log_file = $log_dir . '/verification_links.txt';
    $log_message = "\n" . date('Y-m-d H:i:s') . " | Usuario: $nombre | Email: $email\n";
    $log_message .= "Enlace de verificación: $verification_url\n";
    $log_message .= str_repeat('-', 80) . "\n";

    file_put_contents($log_file, $log_message, FILE_APPEND);

    // Guardar en sesión para mostrar al usuario
    if(session_status() == PHP_SESSION_ACTIVE) {
        $_SESSION['verification_url'] = $verification_url;
    }

    return true;
}

// Construir HTML del email de verificación
function build_verification_email_html($nombre, $verification_url) {
    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
            .button { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Bienvenido al Sistema de Clases</h1>
            </div>
            <div class='content'>
                <p>Hola <strong>$nombre</strong>,</p>
                <p>Gracias por registrarte en nuestro sistema de clases. Para completar tu registro, por favor verifica tu dirección de correo electrónico haciendo clic en el siguiente botón:</p>
                <p style='text-align: center;'>
                    <a href='$verification_url' class='button'>Verificar mi correo</a>
                </p>
                <p>O copia y pega este enlace en tu navegador:</p>
                <p style='word-break: break-all; background: white; padding: 10px; border-radius: 3px;'>$verification_url</p>
                <p><strong>Este enlace expirará en 24 horas.</strong></p>
                <p>Si no creaste esta cuenta, puedes ignorar este correo.</p>
            </div>
            <div class='footer'>
                <p>&copy; 2025 Sistema de Clases. Todos los derechos reservados.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

// Verificar token de email
function verify_email_token($token) {
    $database = new Database();
    $db = $database->connect();

    try {
        // Buscar token válido
        $query = "SELECT * FROM email_verification_tokens
                  WHERE token = :token
                  AND usado = FALSE
                  AND fecha_expiracion > NOW()";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $token_data = $stmt->fetch();
            $id_usuario = $token_data['id_usuario'];

            // Iniciar transacción
            $db->beginTransaction();

            // Marcar usuario como verificado
            $update_user = "UPDATE usuarios SET email_verificado = TRUE, fecha_verificacion = NOW()
                           WHERE id_usuario = :id_usuario";
            $stmt_user = $db->prepare($update_user);
            $stmt_user->bindParam(':id_usuario', $id_usuario);
            $stmt_user->execute();

            // Marcar token como usado
            $update_token = "UPDATE email_verification_tokens SET usado = TRUE
                            WHERE id = :id";
            $stmt_token = $db->prepare($update_token);
            $stmt_token->bindParam(':id', $token_data['id']);
            $stmt_token->execute();

            $db->commit();
            return true;
        }

        return false;
    } catch(Exception $e) {
        if(isset($db)) {
            $db->rollBack();
        }
        error_log("Error verifying email token: " . $e->getMessage());
        return false;
    }
}

// Verificar si el email del usuario está verificado
function is_email_verified($id_usuario) {
    $database = new Database();
    $db = $database->connect();

    $query = "SELECT email_verificado FROM usuarios WHERE id_usuario = :id_usuario";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_usuario', $id_usuario);
    $stmt->execute();

    if($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        return (bool)$user['email_verificado'];
    }

    return false;
}

// Marcar email como verificado manualmente (solo admin)
function verify_email_manually($id_usuario) {
    $database = new Database();
    $db = $database->connect();

    try {
        $query = "UPDATE usuarios SET email_verificado = TRUE, fecha_verificacion = NOW()
                  WHERE id_usuario = :id_usuario";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        return $stmt->execute();
    } catch(Exception $e) {
        error_log("Error verifying email manually: " . $e->getMessage());
        return false;
    }
}

// ==========================================
// FUNCIONES DE PERMISOS
// ==========================================

// Verificar si un usuario tiene un permiso específico
function user_has_permission($id_usuario, $clave_permiso) {
    $database = new Database();
    $db = $database->connect();

    $query = "SELECT COUNT(*) as count
              FROM usuarios u
              INNER JOIN rol_permisos rp ON u.id_rol = rp.id_rol
              INNER JOIN permisos p ON rp.id_permiso = p.id_permiso
              WHERE u.id_usuario = :id_usuario AND p.clave_permiso = :clave_permiso";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_usuario', $id_usuario);
    $stmt->bindParam(':clave_permiso', $clave_permiso);
    $stmt->execute();

    $result = $stmt->fetch();
    return (int)$result['count'] > 0;
}

// Verificar si un rol tiene un permiso específico
function role_has_permission($id_rol, $clave_permiso) {
    $database = new Database();
    $db = $database->connect();

    $query = "SELECT COUNT(*) as count
              FROM rol_permisos rp
              INNER JOIN permisos p ON rp.id_permiso = p.id_permiso
              WHERE rp.id_rol = :id_rol AND p.clave_permiso = :clave_permiso";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_rol', $id_rol);
    $stmt->bindParam(':clave_permiso', $clave_permiso);
    $stmt->execute();

    $result = $stmt->fetch();
    return (int)$result['count'] > 0;
}

// Obtener todos los permisos de un usuario
function get_user_permissions($id_usuario) {
    $database = new Database();
    $db = $database->connect();

    $query = "SELECT DISTINCT p.clave_permiso, p.descripcion
              FROM usuarios u
              INNER JOIN rol_permisos rp ON u.id_rol = rp.id_rol
              INNER JOIN permisos p ON rp.id_permiso = p.id_permiso
              WHERE u.id_usuario = :id_usuario
              ORDER BY p.clave_permiso";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_usuario', $id_usuario);
    $stmt->execute();

    return $stmt->fetchAll();
}

// Obtener todos los permisos de un rol
function get_role_permissions($id_rol) {
    $database = new Database();
    $db = $database->connect();

    $query = "SELECT p.id_permiso, p.clave_permiso, p.descripcion
              FROM rol_permisos rp
              INNER JOIN permisos p ON rp.id_permiso = p.id_permiso
              WHERE rp.id_rol = :id_rol
              ORDER BY p.clave_permiso";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_rol', $id_rol);
    $stmt->execute();

    return $stmt->fetchAll();
}

// Obtener todos los permisos disponibles
function get_all_permissions() {
    $database = new Database();
    $db = $database->connect();

    $query = "SELECT * FROM permisos ORDER BY clave_permiso";
    $stmt = $db->prepare($query);
    $stmt->execute();

    return $stmt->fetchAll();
}

// Obtener permisos agrupados por categoría
function get_permissions_grouped() {
    $permissions = get_all_permissions();
    $grouped = [];

    foreach($permissions as $perm) {
        // Obtener categoría del permiso (antes del punto)
        $parts = explode('.', $perm['clave_permiso']);
        $category = ucfirst($parts[0]);

        if(!isset($grouped[$category])) {
            $grouped[$category] = [];
        }
        $grouped[$category][] = $perm;
    }

    return $grouped;
}

// Asignar permisos a un rol
function assign_permissions_to_role($id_rol, $permission_ids) {
    $database = new Database();
    $db = $database->connect();

    try {
        $db->beginTransaction();

        // Eliminar permisos actuales del rol
        $delete_query = "DELETE FROM rol_permisos WHERE id_rol = :id_rol";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->bindParam(':id_rol', $id_rol, PDO::PARAM_INT);
        $delete_stmt->execute();

        // Insertar nuevos permisos
        if(!empty($permission_ids)) {
            $insert_query = "INSERT INTO rol_permisos (id_rol, id_permiso) VALUES (:id_rol, :id_permiso)";
            $insert_stmt = $db->prepare($insert_query);

            foreach($permission_ids as $id_permiso) {
                $insert_stmt->execute([
                    ':id_rol' => $id_rol,
                    ':id_permiso' => (int)$id_permiso
                ]);
            }
        }

        $db->commit();
        return true;
    } catch(Exception $e) {
        $db->rollBack();
        error_log("Error assigning permissions to role: " . $e->getMessage());
        return false;
    }
}

// Obtener IDs de permisos de un rol
function get_role_permission_ids($id_rol) {
    $database = new Database();
    $db = $database->connect();

    $query = "SELECT id_permiso FROM rol_permisos WHERE id_rol = :id_rol";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_rol', $id_rol, PDO::PARAM_INT);
    $stmt->execute();

    $ids = [];
    while($row = $stmt->fetch()) {
        $ids[] = $row['id_permiso'];
    }

    return $ids;
}

// Obtener solo roles activos
function get_active_roles() {
    $database = new Database();
    $db = $database->connect();

    $query = "SELECT * FROM roles WHERE activo = TRUE ORDER BY id_rol";
    $stmt = $db->prepare($query);
    $stmt->execute();

    return $stmt->fetchAll();
}

// ==========================================
// FUNCIONES DE GESTIÓN DE CURSOS
// ==========================================

// Obtener todos los cursos
function get_all_courses($active_only = false) {
    $database = new Database();
    $db = $database->connect();

    $query = "SELECT c.*, u.username as docente_username,
                     CONCAT(p.nombre, ' ', p.apellido) as docente_nombre,
                     (SELECT COUNT(*) FROM inscripciones_cursos WHERE id_curso = c.id_curso) as estudiantes_inscritos
              FROM cursos c
              LEFT JOIN usuarios u ON c.id_docente = u.id_usuario
              LEFT JOIN personas p ON u.id_persona = p.id_persona";

    if($active_only) {
        $query .= " WHERE c.activo = TRUE";
    }

    $query .= " ORDER BY c.created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute();

    return $stmt->fetchAll();
}

// Obtener información de un curso específico
function get_course_info($id_curso) {
    $database = new Database();
    $db = $database->connect();

    $query = "SELECT c.*, u.username as docente_username,
                     CONCAT(p.nombre, ' ', p.apellido) as docente_nombre,
                     (SELECT COUNT(*) FROM inscripciones_cursos WHERE id_curso = c.id_curso) as estudiantes_inscritos
              FROM cursos c
              LEFT JOIN usuarios u ON c.id_docente = u.id_usuario
              LEFT JOIN personas p ON u.id_persona = p.id_persona
              WHERE c.id_curso = :id_curso";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_curso', $id_curso, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetch();
}

// Crear nuevo curso
function create_course($data) {
    $database = new Database();
    $db = $database->connect();

    try {
        $query = "INSERT INTO cursos (codigo_curso, nombre_curso, descripcion, id_docente,
                                     creditos, horas_semanales, capacidad_maxima, activo,
                                     fecha_inicio, fecha_fin)
                  VALUES (:codigo_curso, :nombre_curso, :descripcion, :id_docente,
                         :creditos, :horas_semanales, :capacidad_maxima, :activo,
                         :fecha_inicio, :fecha_fin)";

        $stmt = $db->prepare($query);
        $stmt->execute([
            ':codigo_curso' => $data['codigo_curso'],
            ':nombre_curso' => $data['nombre_curso'],
            ':descripcion' => $data['descripcion'],
            ':id_docente' => $data['id_docente'] ?: null,
            ':creditos' => $data['creditos'] ?: 3,
            ':horas_semanales' => $data['horas_semanales'] ?: 4,
            ':capacidad_maxima' => $data['capacidad_maxima'] ?: 30,
            ':activo' => $data['activo'] ?? true,
            ':fecha_inicio' => $data['fecha_inicio'] ?: null,
            ':fecha_fin' => $data['fecha_fin'] ?: null
        ]);

        return $db->lastInsertId();
    } catch(Exception $e) {
        error_log("Error creating course: " . $e->getMessage());
        return false;
    }
}

// Actualizar curso
function update_course($id_curso, $data) {
    $database = new Database();
    $db = $database->connect();

    try {
        $query = "UPDATE cursos SET
                    codigo_curso = :codigo_curso,
                    nombre_curso = :nombre_curso,
                    descripcion = :descripcion,
                    id_docente = :id_docente,
                    creditos = :creditos,
                    horas_semanales = :horas_semanales,
                    capacidad_maxima = :capacidad_maxima,
                    activo = :activo,
                    fecha_inicio = :fecha_inicio,
                    fecha_fin = :fecha_fin
                  WHERE id_curso = :id_curso";

        $stmt = $db->prepare($query);
        $stmt->execute([
            ':id_curso' => $id_curso,
            ':codigo_curso' => $data['codigo_curso'],
            ':nombre_curso' => $data['nombre_curso'],
            ':descripcion' => $data['descripcion'],
            ':id_docente' => $data['id_docente'] ?: null,
            ':creditos' => $data['creditos'] ?: 3,
            ':horas_semanales' => $data['horas_semanales'] ?: 4,
            ':capacidad_maxima' => $data['capacidad_maxima'] ?: 30,
            ':activo' => $data['activo'] ?? true,
            ':fecha_inicio' => $data['fecha_inicio'] ?: null,
            ':fecha_fin' => $data['fecha_fin'] ?: null
        ]);

        return true;
    } catch(Exception $e) {
        error_log("Error updating course: " . $e->getMessage());
        return false;
    }
}

// Eliminar curso
function delete_course($id_curso) {
    $database = new Database();
    $db = $database->connect();

    // Verificar si hay estudiantes inscritos
    $check_query = "SELECT COUNT(*) as count FROM inscripciones_cursos WHERE id_curso = :id_curso";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':id_curso', $id_curso, PDO::PARAM_INT);
    $check_stmt->execute();
    $result = $check_stmt->fetch();

    if((int)$result['count'] > 0) {
        return false;
    }

    $query = "DELETE FROM cursos WHERE id_curso = :id_curso";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_curso', $id_curso, PDO::PARAM_INT);

    return $stmt->execute();
}

// Verificar si el código de curso ya existe
function course_code_exists($codigo_curso, $exclude_id = null) {
    $database = new Database();
    $db = $database->connect();

    if ($exclude_id !== null) {
        $query = "SELECT id_curso FROM cursos WHERE codigo_curso = :codigo_curso AND id_curso != :exclude_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':codigo_curso', $codigo_curso);
        $stmt->bindParam(':exclude_id', $exclude_id, PDO::PARAM_INT);
    } else {
        $query = "SELECT id_curso FROM cursos WHERE codigo_curso = :codigo_curso";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':codigo_curso', $codigo_curso);
    }

    $stmt->execute();
    return $stmt->rowCount() > 0;
}

// Obtener docentes disponibles
function get_available_teachers() {
    $database = new Database();
    $db = $database->connect();

    $query = "SELECT u.id_usuario, u.username, CONCAT(p.nombre, ' ', p.apellido) as nombre_completo
              FROM usuarios u
              INNER JOIN personas p ON u.id_persona = p.id_persona
              WHERE u.id_rol = 2 AND u.activo = TRUE
              ORDER BY p.nombre, p.apellido";

    $stmt = $db->prepare($query);
    $stmt->execute();

    return $stmt->fetchAll();
}

// Inscribir estudiante a curso
function enroll_student($id_curso, $id_estudiante) {
    $database = new Database();
    $db = $database->connect();

    try {
        // Verificar capacidad del curso
        $course = get_course_info($id_curso);
        if($course['estudiantes_inscritos'] >= $course['capacidad_maxima']) {
            return false;
        }

        // Verificar si ya está inscrito
        $check_query = "SELECT id_inscripcion FROM inscripciones_cursos
                       WHERE id_curso = :id_curso AND id_estudiante = :id_estudiante";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([
            ':id_curso' => $id_curso,
            ':id_estudiante' => $id_estudiante
        ]);

        if($check_stmt->rowCount() > 0) {
            return false; // Ya está inscrito
        }

        $query = "INSERT INTO inscripciones_cursos (id_curso, id_estudiante) VALUES (:id_curso, :id_estudiante)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':id_curso' => $id_curso,
            ':id_estudiante' => $id_estudiante
        ]);

        return true;
    } catch(Exception $e) {
        error_log("Error enrolling student: " . $e->getMessage());
        return false;
    }
}

// Obtener cursos de un estudiante
function get_student_courses($id_estudiante) {
    $database = new Database();
    $db = $database->connect();

    $query = "SELECT c.*, ic.estado, ic.nota_final, ic.fecha_inscripcion,
                     CONCAT(p.nombre, ' ', p.apellido) as docente_nombre
              FROM inscripciones_cursos ic
              INNER JOIN cursos c ON ic.id_curso = c.id_curso
              LEFT JOIN usuarios u ON c.id_docente = u.id_usuario
              LEFT JOIN personas p ON u.id_persona = p.id_persona
              WHERE ic.id_estudiante = :id_estudiante
              ORDER BY ic.fecha_inscripcion DESC";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_estudiante', $id_estudiante, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

// ==========================================
// FUNCIONES DE RECUPERACIÓN DE CONTRASEÑA
// ==========================================

// Obtener usuario por email
function get_user_by_email($email) {
    $database = new Database();
    $db = $database->connect();

    $query = "SELECT u.id_usuario, u.username, u.activo, p.nombre, p.apellido, p.email
              FROM usuarios u
              INNER JOIN personas p ON u.id_persona = p.id_persona
              WHERE p.email = :email";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if($stmt->rowCount() > 0) {
        return $stmt->fetch();
    }

    return false;
}

// Crear token de recuperación de contraseña
function create_password_reset_token($id_usuario) {
    $database = new Database();
    $db = $database->connect();

    try {
        // Invalidar tokens anteriores del usuario
        $invalidate_query = "UPDATE password_reset_tokens SET usado = TRUE WHERE id_usuario = :id_usuario AND usado = FALSE";
        $invalidate_stmt = $db->prepare($invalidate_query);
        $invalidate_stmt->bindParam(':id_usuario', $id_usuario);
        $invalidate_stmt->execute();

        // Generar nuevo token
        $token = bin2hex(random_bytes(32));

        // El token expira en 1 hora
        $fecha_expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $query = "INSERT INTO password_reset_tokens (id_usuario, token, fecha_expiracion)
                  VALUES (:id_usuario, :token, :fecha_expiracion)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':id_usuario' => $id_usuario,
            ':token' => $token,
            ':fecha_expiracion' => $fecha_expiracion
        ]);

        return $token;
    } catch(Exception $e) {
        error_log("Error creating password reset token: " . $e->getMessage());
        return false;
    }
}

// Verificar token de recuperación de contraseña
function verify_password_reset_token($token) {
    $database = new Database();
    $db = $database->connect();

    $query = "SELECT prt.*, u.username, p.nombre, p.email
              FROM password_reset_tokens prt
              INNER JOIN usuarios u ON prt.id_usuario = u.id_usuario
              INNER JOIN personas p ON u.id_persona = p.id_persona
              WHERE prt.token = :token
              AND prt.usado = FALSE
              AND prt.fecha_expiracion > NOW()";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->execute();

    if($stmt->rowCount() > 0) {
        return $stmt->fetch();
    }

    return false;
}

// Restablecer contraseña
function reset_password($token, $new_password) {
    $database = new Database();
    $db = $database->connect();

    try {
        // Verificar token válido
        $token_data = verify_password_reset_token($token);
        if(!$token_data) {
            return false;
        }

        $db->beginTransaction();

        // Actualizar contraseña
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $update_password = "UPDATE usuarios SET password_hash = :password_hash WHERE id_usuario = :id_usuario";
        $stmt_password = $db->prepare($update_password);
        $stmt_password->execute([
            ':password_hash' => $password_hash,
            ':id_usuario' => $token_data['id_usuario']
        ]);

        // Marcar token como usado
        $update_token = "UPDATE password_reset_tokens SET usado = TRUE WHERE id = :id";
        $stmt_token = $db->prepare($update_token);
        $stmt_token->bindParam(':id', $token_data['id']);
        $stmt_token->execute();

        $db->commit();
        return true;
    } catch(Exception $e) {
        if(isset($db)) {
            $db->rollBack();
        }
        error_log("Error resetting password: " . $e->getMessage());
        return false;
    }
}

// Enviar correo de recuperación de contraseña
function send_password_reset_email($email, $nombre, $token) {
    require_once __DIR__ . '/../config/email.php';

    // Construir URL de reset
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $reset_url = "$protocol://$host/ActI/reset_password.php?token=$token";

    $subject = "Recuperar contraseña - " . SMTP_FROM_NAME;
    $message = build_password_reset_email_html($nombre, $reset_url);

    // Si SMTP está deshabilitado, usar modo desarrollo
    if(!SMTP_ENABLED) {
        return send_password_reset_email_dev_mode($email, $nombre, $reset_url);
    }

    // SMTP habilitado: Usar PHPMailer
    try {
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURITY === 'ssl'
            ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->SMTPDebug  = SMTP_DEBUG;

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email, $nombre);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = "Hola $nombre, restablece tu contraseña visitando: $reset_url";

        $mail->send();

        // Log de éxito
        $log_dir = __DIR__ . '/../logs';
        if(!is_dir($log_dir)) {
            mkdir($log_dir, 0777, true);
        }
        $log_file = $log_dir . '/email_sent.log';
        $log_message = date('Y-m-d H:i:s') . " | Email de recuperación enviado a: $email\n";
        file_put_contents($log_file, $log_message, FILE_APPEND);

        return true;

    } catch (PHPMailer\PHPMailer\Exception $e) {
        error_log("PHPMailer Error (password reset): " . $e->getMessage());

        $log_dir = __DIR__ . '/../logs';
        if(!is_dir($log_dir)) {
            mkdir($log_dir, 0777, true);
        }
        $log_file = $log_dir . '/email_errors.log';
        $log_message = date('Y-m-d H:i:s') . " | Error enviando reset a $email: " . $e->getMessage() . "\n";
        file_put_contents($log_file, $log_message, FILE_APPEND);

        // Fallback a modo desarrollo
        return send_password_reset_email_dev_mode($email, $nombre, $reset_url);
    }
}

// Modo desarrollo: guardar enlace de reset en archivo y sesión
function send_password_reset_email_dev_mode($email, $nombre, $reset_url) {
    $log_dir = __DIR__ . '/../logs';
    if(!is_dir($log_dir)) {
        mkdir($log_dir, 0777, true);
    }

    $log_file = $log_dir . '/password_reset_links.txt';
    $log_message = "\n" . date('Y-m-d H:i:s') . " | Usuario: $nombre | Email: $email\n";
    $log_message .= "Enlace de recuperación: $reset_url\n";
    $log_message .= str_repeat('-', 80) . "\n";

    file_put_contents($log_file, $log_message, FILE_APPEND);

    // Guardar en sesión para mostrar al usuario
    if(session_status() == PHP_SESSION_ACTIVE) {
        $_SESSION['password_reset_url'] = $reset_url;
    }

    return true;
}

// Construir HTML del email de recuperación
function build_password_reset_email_html($nombre, $reset_url) {
    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
            .button { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
            .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Recuperar Contraseña</h1>
            </div>
            <div class='content'>
                <p>Hola <strong>$nombre</strong>,</p>
                <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta. Si no realizaste esta solicitud, puedes ignorar este correo.</p>
                <p>Para restablecer tu contraseña, haz clic en el siguiente botón:</p>
                <p style='text-align: center;'>
                    <a href='$reset_url' class='button'>Restablecer Contraseña</a>
                </p>
                <p>O copia y pega este enlace en tu navegador:</p>
                <p style='word-break: break-all; background: white; padding: 10px; border-radius: 3px;'>$reset_url</p>
                <div class='warning'>
                    <strong>⚠️ Importante:</strong> Este enlace expirará en 1 hora por motivos de seguridad.
                </div>
                <p>Si no solicitaste restablecer tu contraseña, tu cuenta sigue siendo segura. Puedes ignorar este correo.</p>
            </div>
            <div class='footer'>
                <p>&copy; 2025 Sistema de Clases. Todos los derechos reservados.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}
?>