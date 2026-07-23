<?php
/**
 * Authentication and session helper functions
 */

require_once __DIR__ . '/db.php';

/**
 * Check if admin is logged in
 */
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']) && $_SESSION['role'] === 'admin';
}

/**
 * Force admin login or redirect
 */
function require_admin_login() {
    if (!is_admin_logged_in()) {
        $_SESSION['redirect_message'] = "Please login as Admin to access this page.";
        $_SESSION['redirect_type'] = "danger";
        header("Location: " . BASE_URL . "login.php");
        exit();
    }
}

/**
 * Check if student is logged in
 */
function is_student_logged_in() {
    // If student status is blocked, log them out immediately
    if (isset($_SESSION['student_id']) && $_SESSION['role'] === 'student') {
        $student = fetch_one("SELECT status FROM students WHERE id = ?", [$_SESSION['student_id']]);
        if ($student && $student['status'] === 'blocked') {
            logout_user();
            return false;
        }
        return true;
    }
    return false;
}

/**
 * Force student login or redirect
 */
function require_student_login() {
    if (!is_student_logged_in()) {
        $_SESSION['redirect_message'] = "Please login as a Student to access this page.";
        $_SESSION['redirect_type'] = "danger";
        header("Location: " . BASE_URL . "login.php");
        exit();
    }
}

/**
 * Log in a user (student or admin)
 */
function login_user($userId, $usernameOrId, $email, $role, $fullname) {
    // Regenerate session ID to prevent Session Fixation
    session_regenerate_id(true);
    
    $_SESSION['role'] = $role;
    $_SESSION['fullname'] = $fullname;
    
    if ($role === 'admin') {
        $_SESSION['admin_id'] = $userId;
        $_SESSION['admin_username'] = $usernameOrId;
        $_SESSION['admin_email'] = $email;
        log_activity("Admin Logged In", "Admin: $usernameOrId successfully logged in.");
    } else {
        $_SESSION['student_id'] = $userId;
        $_SESSION['student_roll'] = $usernameOrId;
        $_SESSION['student_email'] = $email;
        log_activity("Student Logged In", "Student: $usernameOrId ($fullname) successfully logged in.");
    }
}

/**
 * Log out user and destroy session
 */
function logout_user() {
    log_activity("Logged Out", "User logged out.");
    
    // Clear cookies if remember me was active
    if (isset($_COOKIE['remember_user'])) {
        setcookie('remember_user', '', time() - 3600, BASE_URL);
    }
    
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Simulate sending email
 */
function simulate_email($toEmail, $subject, $body) {
    // We will save the simulated email to activity_logs or a local file so we can view it
    $logMsg = "Simulated Email to: $toEmail\nSubject: $subject\nBody:\n$body";
    log_activity("Simulated Email Sent", "To: $toEmail, Subject: $subject");
    
    // Save to simulated emails log in uploads directory (for visual display if wanted)
    $dir = __DIR__ . '/../uploads/simulated_emails/';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $file = $dir . 'email_' . time() . '_' . rand(1000, 9999) . '.txt';
    file_put_contents($file, $logMsg);
    
    return true;
}

/**
 * Simulate OTP Generation for Password Recovery
 */
function generate_otp_for_student($email) {
    $student = fetch_one("SELECT id, fullname FROM students WHERE email = ?", [$email]);
    if (!$student) {
        return false;
    }
    
    $otp = rand(100000, 999999);
    $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    query("UPDATE students SET otp = ?, otp_expiry = ? WHERE id = ?", [$otp, $expiry, $student['id']]);
    
    $emailBody = "Hello " . $student['fullname'] . ",\n\nYour OTP for resetting your Campus Guru password is: " . $otp . "\nThis OTP is valid for 15 minutes.\n\nIf you did not request this, please ignore this email.";
    simulate_email($email, "Password Reset OTP - Campus Guru", $emailBody);
    
    return $otp;
}
