<?php
/**
 * Admin Panel Header template
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin_login();

$adminId = $_SESSION['admin_id'];
$adminUser = fetch_one("SELECT * FROM admins WHERE id = ?", [$adminId]);

// Get active site name from settings
$siteSettings = fetch_one("SELECT site_name FROM settings ORDER BY id DESC LIMIT 1");
$siteName = $siteSettings['site_name'] ?? 'Campus Guru';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?= e($siteName) ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark-mode');
        }
    </script>
</head>
<body class="bg-light">

    <!-- Top Navigation Bar for Mobile Toggles -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top d-lg-none glass-navbar py-2 px-3">
        <div class="container-fluid">
            <button class="btn btn-outline-primary border-0 me-2" id="sidebarToggle">
                <i class="bi bi-list fs-3"></i>
            </button>
            <a class="navbar-brand fw-bold text-primary" href="<?= BASE_URL ?>admin/dashboard.php">
                <i class="bi bi-grid-1x2-fill me-2"></i><?= e($siteName) ?> Admin
            </a>
            <button id="darkModeToggle" class="btn btn-link nav-link px-2 border-0 ms-auto me-3 d-lg-none theme-toggle-btn" aria-label="Toggle Theme">
                <i class="bi bi-moon-stars-fill fs-5 text-secondary"></i>
            </button>
            <div class="dropdown">
                <a class="btn btn-light rounded-circle shadow-sm p-1" href="#" id="mobProfileDropdown" role="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-fill fs-5 px-1.5 text-primary"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end border-0 glass-card shadow" aria-labelledby="mobProfileDropdown">
                    <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>admin/profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 text-danger confirm-action" href="<?= BASE_URL ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar Navigation -->
    <aside class="admin-sidebar shadow-sm">
        <div class="p-4 border-bottom text-center">
            <h4 class="fw-bold text-primary mb-1" style="font-family: 'Outfit', sans-serif;"><i class="bi bi-grid-1x2-fill me-2"></i><?= e($siteName) ?></h4>
            <span class="badge bg-primary-subtle text-primary border rounded-pill small">Admin Dashboard</span>
        </div>
        
        <div class="py-3 d-flex flex-column h-100 overflow-y-auto" style="max-height: calc(100vh - 120px);">
            <a href="<?= BASE_URL ?>admin/dashboard.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i>Dashboard
            </a>
            <a href="<?= BASE_URL ?>admin/events.php" class="sidebar-link <?= in_array(basename($_SERVER['PHP_SELF']), ['events.php', 'event-create.php', 'event-edit.php']) ? 'active' : '' ?>">
                <i class="bi bi-calendar3"></i>Manage Events
            </a>
            <a href="<?= BASE_URL ?>admin/registrations.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'registrations.php' ? 'active' : '' ?>">
                <i class="bi bi-ticket-perforated"></i>Registrations
            </a>
            <a href="<?= BASE_URL ?>admin/students.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'students.php' ? 'active' : '' ?>">
                <i class="bi bi-people"></i>Manage Students
            </a>
            <a href="<?= BASE_URL ?>admin/categories.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : '' ?>">
                <i class="bi bi-tags"></i>Event Categories
            </a>
            <a href="<?= BASE_URL ?>admin/certificates.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'certificates.php' ? 'active' : '' ?>">
                <i class="bi bi-award"></i>Certificates
            </a>
            <a href="<?= BASE_URL ?>admin/feedback.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'feedback.php' ? 'active' : '' ?>">
                <i class="bi bi-chat-left-heart"></i>Feedbacks
            </a>
            <a href="<?= BASE_URL ?>admin/announcements.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'announcements.php' ? 'active' : '' ?>">
                <i class="bi bi-megaphone"></i>Announcements
            </a>
            <a href="<?= BASE_URL ?>admin/notifications.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : '' ?>">
                <i class="bi bi-bell"></i>Notifications
            </a>
            <a href="<?= BASE_URL ?>admin/reports.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-bar-graph"></i>Reports
            </a>
            <a href="<?= BASE_URL ?>admin/settings.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : '' ?>">
                <i class="bi bi-sliders2"></i>System Settings
            </a>
            <a href="<?= BASE_URL ?>admin/logs.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'logs.php' ? 'active' : '' ?>">
                <i class="bi bi-clock-history"></i>Activity Logs
            </a>
            <a href="<?= BASE_URL ?>admin/profile.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>">
                <i class="bi bi-person-gear"></i>Admin Profile
            </a>
            
            <div class="mt-auto border-top pt-2 mb-4">
                <a href="#" id="darkModeToggle" class="sidebar-link theme-toggle-btn">
                    <i class="bi bi-moon-stars-fill"></i><span>Toggle Theme</span>
                </a>
                <a href="<?= BASE_URL ?>logout.php" class="sidebar-link text-danger confirm-action" data-confirm-msg="Are you sure you want to logout?">
                    <i class="bi bi-box-arrow-right text-danger"></i>Logout
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Workspace Container -->
    <main class="admin-content">
        <!-- Notification Messages -->
        <?php if (isset($_SESSION['redirect_message'])): ?>
            <div class="alert alert-<?= e($_SESSION['redirect_type'] ?? 'info') ?> alert-dismissible fade show glass-card border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i>
                <?= e($_SESSION['redirect_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php 
            unset($_SESSION['redirect_message']);
            unset($_SESSION['redirect_type']);
            ?>
        <?php endif; ?>
