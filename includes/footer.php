<?php
/**
 * Global Footer Template
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';

// Fetch settings
$settings = fetch_one("SELECT * FROM settings ORDER BY id DESC LIMIT 1");
$siteName = $settings['site_name'] ?? 'Campus Guru';
$siteEmail = $settings['site_email'] ?? 'imgransh@gmail.com';
$sitePhone = $settings['site_phone'] ?? '+91 9693650729';
$siteAddress = $settings['site_address'] ?? 'Skit college jaipur';

// Fetch recent announcements
$recentAnnouncements = fetch_all("SELECT * FROM announcements ORDER BY id DESC LIMIT 3");
?>
    </div> <!-- Close main container if opened in sub-pages, but normally we open-close properly -->
    
    <footer class="bg-white border-top py-5 mt-auto glass-card border-0 rounded-0 shadow-lg">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <h5 class="fw-bold text-primary mb-3" style="font-family: 'Outfit', sans-serif;"><i class="bi bi-grid-1x2-fill me-2"></i><?= e($siteName) ?></h5>
                    <p class="text-muted small">A modern, integrated solution for organizing, managing, and participating in campus activities, coding contests, hackathons, and cultural events.</p>
                    <p class="text-muted small mb-0">&copy; <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved.</p>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <h5 class="fw-bold mb-3" style="font-family: 'Outfit', sans-serif;">Latest Announcements</h5>
                    <ul class="list-unstyled small">
                        <?php if (empty($recentAnnouncements)): ?>
                            <li class="text-muted">No announcements yet.</li>
                        <?php else: ?>
                            <?php foreach ($recentAnnouncements as $ann): ?>
                                <li class="mb-2 pb-2 border-bottom border-light">
                                    <span class="badge bg-light text-primary me-1"><?= date('M d', strtotime($ann['created_at'])) ?></span>
                                    <a href="<?= BASE_URL ?>index.php#announcements" class="text-decoration-none text-secondary fw-semibold"><?= e($ann['title']) ?></a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="col-lg-4 col-md-12">
                    <h5 class="fw-bold mb-3" style="font-family: 'Outfit', sans-serif;">Contact & Support</h5>
                    <p class="text-muted small mb-1"><i class="bi bi-geo-alt-fill text-primary me-2"></i><?= e($siteAddress) ?></p>
                    <p class="text-muted small mb-1"><i class="bi bi-envelope-fill text-primary me-2"></i><?= e($siteEmail) ?></p>
                    <p class="text-muted small mb-1"><i class="bi bi-telephone-fill text-primary me-2"></i><?= e($sitePhone) ?></p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Three.js CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <!-- Background 3D script -->
    <script src="<?= BASE_URL ?>js/three-bg.js"></script>
    <!-- Custom JS -->
    <script src="<?= BASE_URL ?>js/main.js"></script>
</body>
</html>
