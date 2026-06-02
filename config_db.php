<?php
/**
 * SOPHEA - Database Configuration
 * 
 * Database connection settings and credentials
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'sophea_db');
define('DB_USER', 'sopheadmin');     // Database user
define('DB_PASS', 'z*B4D5N#k59CIbs!'); // Database password
define('DB_CHARSET', 'utf8mb4');

// Email Configuration
define('ADMIN_EMAIL', 'admin@sophea.com.mx');  // Email to receive notifications
define('FROM_EMAIL', 'noreply@sophea.com.mx'); // Email sender
define('FROM_NAME', 'SOPHEA - Sistema de Contacto');

// Form Settings
define('ENABLE_EMAIL_NOTIFICATIONS', true);
define('ENABLE_DATABASE_STORAGE', true);
define('ENABLE_WHATSAPP_REDIRECT', true);

// Security Settings
define('ENABLE_CSRF_PROTECTION', true);
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
