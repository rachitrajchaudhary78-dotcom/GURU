<?php
/**
 * Core Configuration Settings
 */

// Define application root constants
if (!defined('BASE_URL')) {
    define('BASE_URL', '/campus_guru/');
}

// Session settings - Secure & modern
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    
    // Cookie lifetime (optional - 1 day)
    // ini_set('session.cookie_lifetime', 86400);

    // If HTTPS is active, use secure cookies
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    
    session_start();
}

// Timezone setup
date_default_timezone_set('Asia/Kolkata');

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'campus_events');

// Error reporting settings (Development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CSRF Protection Initialization
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
