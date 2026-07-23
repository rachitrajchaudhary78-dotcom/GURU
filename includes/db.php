<?php
/**
 * Database connection and query helper functions
 */

require_once __DIR__ . '/../config/config.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

/**
 * Escape output to prevent XSS (Cross-Site Scripting)
 */
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Execute a query using prepared statements
 * Returns the PDOStatement object or false
 */
function query($sql, $params = []) {
    global $conn;
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        // In production, log error instead of printing
        error_log("Database Query Error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Execute a query and fetch a single row
 */
function fetch_one($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt ? $stmt->fetch() : null;
}

/**
 * Execute a query and fetch all rows
 */
function fetch_all($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt ? $stmt->fetchAll() : [];
}

/**
 * Verify CSRF Token to prevent Cross-Site Request Forgery
 */
function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate CSRF hidden input field for forms
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e($_SESSION['csrf_token'] ?? '') . '">';
}

/**
 * Log activity to database for auditing
 */
function log_activity($action, $details = null) {
    $userId = null;
    $role = 'system';
    
    if (isset($_SESSION['admin_id'])) {
        $userId = $_SESSION['admin_id'];
        $role = 'admin';
    } elseif (isset($_SESSION['student_id'])) {
        $userId = $_SESSION['student_id'];
        $role = 'student';
    }
    
    query(
        "INSERT INTO activity_logs (user_id, user_role, action, details) VALUES (?, ?, ?, ?)",
        [$userId, $role, $action, $details]
    );
}

/**
 * Sanitize simple text inputs
 */
function sanitize_input($data) {
    return trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8'));
}
