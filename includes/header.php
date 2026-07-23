<?php
/**
 * Global Header Template
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// Get active site name from settings
$siteSettings = fetch_one("SELECT site_name, logo FROM settings ORDER BY id DESC LIMIT 1");
$siteName = $siteSettings['site_name'] ?? 'Campus Guru';
$siteLogo = $siteSettings['logo'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($siteName) ?> - Campus Events Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom Glassmorphic CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark-mode');
        }
    </script>
</head>
<body>
    <canvas id="three-bg"></canvas>

    <!-- Dynamic Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top glass-navbar py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center fw-bold text-gradient" href="<?= BASE_URL ?>index.php" style="font-family: 'Outfit', sans-serif; font-size: 1.5rem; background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                <i class="bi bi-grid-1x2-fill me-2 text-primary" style="-webkit-text-fill-color: initial;"></i>
                <?= e($siteName) ?>
            </a>
            <button class="navbar-expand-lg navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item me-2">
                        <button id="darkModeToggle" class="btn btn-link nav-link px-2 border-0" aria-label="Toggle Theme">
                            <i class="bi bi-moon-stars-fill fs-5 text-secondary"></i>
                        </button>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="<?= BASE_URL ?>index.php">Home</a>
                    </li>
                    
                    <?php if (is_student_logged_in()): ?>
                        <li class="nav-item">
                            <a class="nav-link px-3" href="<?= BASE_URL ?>student/events.php">Browse Events</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-3" href="<?= BASE_URL ?>student/dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle px-3 btn btn-light glass-card border-0 py-2 ms-2 d-flex align-items-center" href="#" id="studentDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-2 text-primary"></i>
                                <?= e($_SESSION['fullname']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end border-0 glass-card shadow" aria-labelledby="studentDropdown">
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>student/profile.php"><i class="bi bi-person me-2"></i>Edit Profile</a></li>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>student/my-events.php"><i class="bi bi-calendar-check me-2"></i>My Registrations</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item py-2 text-danger" href="<?= BASE_URL ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    <?php elseif (is_admin_logged_in()): ?>
                        <li class="nav-item">
                            <a class="nav-link px-3" href="<?= BASE_URL ?>admin/dashboard.php">Admin Dashboard</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle px-3 btn btn-light glass-card border-0 py-2 ms-2 d-flex align-items-center" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-shield-lock-fill me-2 text-primary"></i>
                                <?= e($_SESSION['fullname']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end border-0 glass-card shadow" aria-labelledby="adminDropdown">
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>admin/profile.php"><i class="bi bi-person me-2"></i>Profile Settings</a></li>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>admin/settings.php"><i class="bi bi-gear me-2"></i>System Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item py-2 text-danger" href="<?= BASE_URL ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link px-3" href="<?= BASE_URL ?>index.php#about">About</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-3" href="<?= BASE_URL ?>index.php#gallery">Gallery</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-3" href="<?= BASE_URL ?>index.php#faq">FAQ</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-3" href="<?= BASE_URL ?>index.php#contact">Contact</a>
                        </li>
                        <li class="nav-item ms-2">
                            <a class="btn btn-premium-primary" href="<?= BASE_URL ?>login.php">Login / Sign Up</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container py-4">
        <!-- System level global messages -->
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
    </div>
