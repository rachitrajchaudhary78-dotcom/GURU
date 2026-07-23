<?php
/**
 * Logout Page
 */
require_once __DIR__ . '/includes/auth.php';

logout_user();

$_SESSION['redirect_message'] = "You have been logged out successfully.";
$_SESSION['redirect_type'] = "info";

header("Location: " . BASE_URL . "index.php");
exit();
