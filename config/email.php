<?php
/**
 * CONFIGURACIÓN DE EMAIL SMTP
 *
 * Configura aquí los datos de tu servidor SMTP para enviar correos reales.
 *
 * OPCIONES RECOMENDADAS:
 *
 * 1. GMAIL (necesitas contraseña de aplicación):
 *    - Host: smtp.gmail.com
 *    - Puerto: 587
 *    - Seguridad: tls
 *    - Usuario: tu-email@gmail.com
 *    - Contraseña: (contraseña de aplicación de 16 caracteres)
 *    - Cómo obtener contraseña de aplicación:
 *      https://myaccount.google.com/apppasswords
 *
 * 2. OUTLOOK/HOTMAIL:
 *    - Host: smtp.office365.com
 *    - Puerto: 587
 *    - Seguridad: tls
 *    - Usuario: tu-email@outlook.com
 *    - Contraseña: tu contraseña normal
 *
 * 3. MAILTRAP (para pruebas - https://mailtrap.io):
 *    - Host: sandbox.smtp.mailtrap.io
 *    - Puerto: 587
 *    - Seguridad: tls
 *    - Usuario: (el que te da mailtrap)
 *    - Contraseña: (la que te da mailtrap)
 */

// ============================================
// CONFIGURACIÓN SMTP - MODIFICA ESTOS VALORES
// ============================================

define('SMTP_ENABLED', true);  // true para usar SMTP, false para modo desarrollo

define('SMTP_HOST', 'smtp.gmail.com');     // Servidor SMTP
define('SMTP_PORT', 587);                   // Puerto (587 para TLS, 465 para SSL)
define('SMTP_SECURITY', 'tls');             // 'tls' o 'ssl'

define('SMTP_USER', 'diego7u7.42@gmail.com');  // Tu email
define('SMTP_PASS', 'vbvecblyaddspfwm'); // Contraseña de aplicación (16 caracteres sin espacios)

define('SMTP_FROM_EMAIL', 'diego7u7.42@gmail.com');  // Email que aparece como remitente
define('SMTP_FROM_NAME', 'Sistema de Clases');     // Nombre del remitente

// ============================================
// MODO DEBUG (solo para desarrollo)
// ============================================
define('SMTP_DEBUG', 0);  // 0 = sin debug, 1 = errores, 2 = mensajes, 3 = todo
