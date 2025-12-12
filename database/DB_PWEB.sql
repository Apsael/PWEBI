-- ==========================================
-- BASE DE DATOS COMPLETA: DB_PWEB
-- Sistema de Gestión de Clases (LMS)
-- Versión: 2.1 - Con login por email, permisos y estado de roles
-- ==========================================

DROP DATABASE IF EXISTS DB_PWEB;
CREATE DATABASE DB_PWEB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE DB_PWEB;

-- ==========================================
-- TABLA: PERSONAS
-- ==========================================
CREATE TABLE personas (
    id_persona INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    dni VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    telefono VARCHAR(20),
    direccion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dni (dni),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABLA: ROLES (CON CAMPO ACTIVO)
-- ==========================================
CREATE TABLE roles (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre_rol VARCHAR(50) UNIQUE NOT NULL,
    descripcion VARCHAR(255),
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nombre_rol (nombre_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABLA: PERMISOS
-- ==========================================
CREATE TABLE permisos (
    id_permiso INT AUTO_INCREMENT PRIMARY KEY,
    clave_permiso VARCHAR(50) UNIQUE NOT NULL,
    descripcion VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABLA PIVOTE: ROL_PERMISOS
-- ==========================================
CREATE TABLE rol_permisos (
    id_rol INT,
    id_permiso INT,
    PRIMARY KEY (id_rol, id_permiso),
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol) ON DELETE CASCADE,
    FOREIGN KEY (id_permiso) REFERENCES permisos(id_permiso) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABLA: USUARIOS (CON VERIFICACIÓN DE EMAIL)
-- ==========================================
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    id_persona INT NOT NULL,
    id_rol INT NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    email_verificado BOOLEAN DEFAULT FALSE,
    fecha_verificacion TIMESTAMP NULL,
    ultimo_acceso TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_persona) REFERENCES personas(id_persona) ON DELETE CASCADE,
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol) ON DELETE RESTRICT,
    INDEX idx_username (username),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABLA: EMAIL_VERIFICATION_TOKENS (CORREGIDA)
-- ==========================================
CREATE TABLE email_verification_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME NOT NULL,
    usado BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_usuario (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABLA: CURSOS
-- ==========================================
CREATE TABLE cursos (
    id_curso INT AUTO_INCREMENT PRIMARY KEY,
    codigo_curso VARCHAR(20) UNIQUE NOT NULL,
    nombre_curso VARCHAR(150) NOT NULL,
    descripcion TEXT,
    id_docente INT,
    creditos INT DEFAULT 3,
    horas_semanales INT DEFAULT 4,
    capacidad_maxima INT DEFAULT 30,
    activo BOOLEAN DEFAULT TRUE,
    fecha_inicio DATE,
    fecha_fin DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_docente) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    INDEX idx_codigo (codigo_curso),
    INDEX idx_docente (id_docente),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABLA: INSCRIPCIONES A CURSOS
-- ==========================================
CREATE TABLE inscripciones_cursos (
    id_inscripcion INT AUTO_INCREMENT PRIMARY KEY,
    id_curso INT NOT NULL,
    id_estudiante INT NOT NULL,
    fecha_inscripcion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('Activo', 'Completado', 'Retirado', 'Reprobado') DEFAULT 'Activo',
    nota_final DECIMAL(5,2),
    asistencias INT DEFAULT 0,
    observaciones TEXT,
    FOREIGN KEY (id_curso) REFERENCES cursos(id_curso) ON DELETE CASCADE,
    FOREIGN KEY (id_estudiante) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    UNIQUE KEY unique_inscripcion (id_curso, id_estudiante),
    INDEX idx_curso (id_curso),
    INDEX idx_estudiante (id_estudiante),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABLA: MATERIALES DE CURSO
-- ==========================================
CREATE TABLE materiales_curso (
    id_material INT AUTO_INCREMENT PRIMARY KEY,
    id_curso INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT,
    tipo_material ENUM('PDF', 'Video', 'Enlace', 'Archivo', 'Otro') DEFAULT 'Archivo',
    ruta_archivo VARCHAR(255),
    url_externa VARCHAR(500),
    fecha_publicacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (id_curso) REFERENCES cursos(id_curso) ON DELETE CASCADE,
    INDEX idx_curso (id_curso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABLA: ADMINISTRATIVOS
-- ==========================================
CREATE TABLE administrativos (
    id_persona INT PRIMARY KEY,
    codigo_administrativo VARCHAR(20) UNIQUE NOT NULL,
    cargo VARCHAR(50),
    area VARCHAR(100),
    salario DECIMAL(10, 2),
    fecha_contratacion DATE,
    FOREIGN KEY (id_persona) REFERENCES personas(id_persona) ON DELETE CASCADE,
    INDEX idx_codigo (codigo_administrativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABLA: ESTUDIANTES
-- ==========================================
CREATE TABLE estudiantes (
    id_persona INT PRIMARY KEY,
    codigo_estudiante VARCHAR(20) UNIQUE NOT NULL,
    carrera VARCHAR(100),
    semestre VARCHAR(20),
    tipo_plan ENUM('Basic', 'Pro', 'Premium') NOT NULL DEFAULT 'Basic',
    FOREIGN KEY (id_persona) REFERENCES personas(id_persona) ON DELETE CASCADE,
    INDEX idx_codigo (codigo_estudiante)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABLA: ACTIVE_SESSIONS
-- ==========================================
CREATE TABLE active_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(128) NOT NULL,
    device_info VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABLA: SESSION_LOGS
-- ==========================================
CREATE TABLE session_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    device VARCHAR(255) DEFAULT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- TABLA: PASSWORD_RESET_TOKENS
-- Tokens para recuperación de contraseña
-- ==========================================
CREATE TABLE password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME NOT NULL,
    usado BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_usuario (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- DATOS INICIALES: ROLES
-- ==========================================
INSERT INTO roles (nombre_rol, descripcion, activo) VALUES
('Admin', 'Administrador del sistema con acceso completo', TRUE),
('Docente', 'Profesor con acceso a gestión académica', TRUE),
('Estudiante', 'Estudiante con acceso limitado', TRUE);

-- ==========================================
-- DATOS INICIALES: PERMISOS COMPLETOS
-- ==========================================
INSERT INTO permisos (clave_permiso, descripcion) VALUES
-- Permisos de usuarios
('user.create', 'Crear usuarios'),
('user.read', 'Ver usuarios'),
('user.update', 'Actualizar usuarios'),
('user.delete', 'Eliminar usuarios'),
-- Permisos de cursos
('curso.create', 'Crear cursos'),
('curso.read', 'Ver cursos'),
('curso.update', 'Actualizar cursos'),
('curso.delete', 'Eliminar cursos'),
('curso.enroll', 'Inscribirse a cursos'),
('curso.material', 'Gestionar materiales de curso'),
-- Permisos de roles
('role.create', 'Crear roles'),
('role.read', 'Ver roles'),
('role.update', 'Actualizar roles'),
('role.delete', 'Eliminar roles'),
-- Permisos de reportes
('report.view', 'Ver reportes'),
('report.export', 'Exportar reportes');

-- ==========================================
-- ASIGNAR PERMISOS A ROLES
-- ==========================================

-- ADMIN (id_rol = 1): TODOS LOS PERMISOS
INSERT INTO rol_permisos (id_rol, id_permiso) VALUES
-- Usuarios
(1, 1), (1, 2), (1, 3), (1, 4),
-- Cursos (incluye inscripción y materiales)
(1, 5), (1, 6), (1, 7), (1, 8), (1, 9), (1, 10),
-- Roles
(1, 11), (1, 12), (1, 13), (1, 14),
-- Reportes
(1, 15), (1, 16);

-- DOCENTE (id_rol = 2): Gestión de cursos y materiales
INSERT INTO rol_permisos (id_rol, id_permiso) VALUES
(2, 2),  -- user.read (ver usuarios/estudiantes)
(2, 5),  -- curso.create
(2, 6),  -- curso.read
(2, 7),  -- curso.update
(2, 10), -- curso.material (gestionar materiales)
(2, 15); -- report.view (ver reportes de sus cursos)

-- ESTUDIANTE (id_rol = 3): Ver cursos e inscribirse
INSERT INTO rol_permisos (id_rol, id_permiso) VALUES
(3, 6),  -- curso.read (ver cursos disponibles)
(3, 9);  -- curso.enroll (inscribirse a cursos)

-- ==========================================
-- USUARIO ADMIN POR DEFECTO
-- Usuario: admin
-- Contraseña: admin123
-- Hash generado con: password_hash('admin123', PASSWORD_BCRYPT)
-- ==========================================
INSERT INTO personas (nombre, apellido, dni, email, telefono, direccion)
VALUES ('Administrador', 'Sistema', '00000000', 'admin@sistema.com', '00000000', 'Sistema');

INSERT INTO usuarios (id_persona, id_rol, username, password_hash, activo, email_verificado)
VALUES (1, 1, 'admin', '$2y$10$9j3Q5zryg6csvl2KXEDfyuocfNSoVwac5mIJWTFJhJdXz9qfb2RVG', 1, TRUE);
-- Contraseña: admin123
-- Email verificado por defecto

-- ==========================================
-- DATOS DE EJEMPLO: CURSOS
-- ==========================================
INSERT INTO cursos (codigo_curso, nombre_curso, descripcion, creditos, horas_semanales, capacidad_maxima, activo, fecha_inicio, fecha_fin) VALUES
('PROG101', 'Introducción a la Programación', 'Conceptos básicos de programación usando Python. Aprende variables, condicionales, bucles y funciones.', 4, 6, 30, TRUE, '2025-01-15', '2025-05-15'),
('WEB201', 'Desarrollo Web', 'HTML, CSS, JavaScript y frameworks modernos. Crea sitios web responsive y aplicaciones interactivas.', 4, 6, 25, TRUE, '2025-01-15', '2025-05-15'),
('BD301', 'Bases de Datos', 'Diseño e implementación de bases de datos relacionales. MySQL, SQL avanzado y normalización.', 3, 4, 30, TRUE, '2025-01-15', '2025-05-15'),
('REDES401', 'Redes de Computadoras', 'Fundamentos de redes, protocolos TCP/IP, seguridad y administración de redes.', 3, 4, 20, TRUE, '2025-01-15', '2025-05-15');

-- ==========================================
-- BASE DE DATOS COMPLETADA
-- ==========================================
-- CREDENCIALES DE ACCESO:
-- Usuario: admin (o email: admin@sistema.com)
-- Contraseña: admin123
-- ==========================================
-- FUNCIONALIDADES IMPLEMENTADAS:
-- - Login con username o email
-- - Verificación de email con tokens
-- - Sistema de roles con estado activo/inactivo
-- - Sistema de permisos granulares asignables a roles
-- - Gestión completa de usuarios, roles y cursos
-- ==========================================
-- Para iniciar el sistema, simplemente ejecuta este archivo SQL
-- y luego accede a http://localhost/ActI
-- ==========================================