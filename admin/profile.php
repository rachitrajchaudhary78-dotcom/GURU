<?php
/**
 * Admin Profile Settings
 */
require_once __DIR__ . '/admin_header.php';

$error = false;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF security token mismatch.";
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_profile') {
            $fullname = sanitize_input($_POST['fullname'] ?? '');
            $email = sanitize_input($_POST['email'] ?? '');
            
            if (empty($fullname) || empty($email)) {
                $error = "Full Name and Email are required.";
            } else {
                // Verify email uniqueness (except current admin)
                $emailCheck = fetch_one("SELECT id FROM admins WHERE email = ? AND id != ? LIMIT 1", [$email, $adminId]);
                if ($emailCheck) {
                    $error = "This email is already in use by another admin.";
                } else {
                    query(
                        "UPDATE admins SET fullname = ?, email = ? WHERE id = ?",
                        [$fullname, $email, $adminId]
                    );
                    $_SESSION['fullname'] = $fullname;
                    $_SESSION['admin_email'] = $email;
                    
                    log_activity("Admin Profile Updated", "Admin updated fullname and email.");
                    $success = "Profile settings updated successfully!";
                    
                    // Fetch fresh info
                    $adminUser = fetch_one("SELECT * FROM admins WHERE id = ?", [$adminId]);
                }
            }
        } elseif ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $error = "Please fill in all password fields.";
            } elseif ($newPassword !== $confirmPassword) {
                $error = "New passwords do not match.";
            } elseif (strlen($newPassword) < 6) {
                $error = "New password must be at least 6 characters long.";
            } else {
                if (password_verify($currentPassword, $adminUser['password'])) {
                    $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
                    query("UPDATE admins SET password = ? WHERE id = ?", [$hashed, $adminId]);
                    
                    log_activity("Admin Password Changed", "Admin successfully changed password.");
                    $success = "Password changed successfully!";
                } else {
                    $error = "Incorrect current password.";
                }
            }
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-dark mb-0">Profile Settings</h3>
    <span class="badge bg-primary-subtle text-primary border px-3 py-2 rounded-pill"><i class="bi bi-person-gear me-2"></i>Account Config</span>
</div>

<div class="glass-card p-5">
    <?php if ($error): ?>
        <div class="alert alert-danger border-0 glass-card text-danger small py-2.5 mb-4">
            <i class="bi bi-exclamation-octagon-fill me-2"></i><?= e($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success border-0 glass-card text-success small py-2.5 mb-4">
            <i class="bi bi-check-circle-fill me-2"></i><?= e($success) ?>
        </div>
    <?php endif; ?>
    
    <div class="row g-5">
        <!-- Update Profile Details -->
        <div class="col-md-7 border-end">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-person me-2 text-primary"></i>Update Profile Details</h5>
            
            <form action="profile.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_profile">
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Username (Read-Only)</label>
                    <input type="text" class="form-control glass-input bg-light" value="<?= e($adminUser['username']) ?>" readonly disabled>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Full Name</label>
                    <input type="text" name="fullname" class="form-control glass-input" value="<?= e($adminUser['fullname']) ?>" required>
                </div>
                
                <div class="mb-4">
                    <label class="form-label small fw-bold">Email Address</label>
                    <input type="email" name="email" class="form-control glass-input" value="<?= e($adminUser['email']) ?>" required>
                </div>
                
                <button type="submit" class="btn btn-premium-primary w-100"><i class="bi bi-save me-2"></i>Save Changes</button>
            </form>
        </div>
        
        <!-- Change Password -->
        <div class="col-md-5">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-shield-lock me-2 text-primary"></i>Change Password</h5>
            
            <form action="profile.php" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="change_password">
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Current Password</label>
                    <input type="password" name="current_password" class="form-control glass-input" placeholder="••••••••" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">New Password</label>
                    <input type="password" name="new_password" class="form-control glass-input" placeholder="At least 6 chars" required>
                </div>
                
                <div class="mb-4">
                    <label class="form-label small fw-bold">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control glass-input" placeholder="Re-type new password" required>
                </div>
                
                <button type="submit" class="btn btn-premium-secondary w-100"><i class="bi bi-key-fill me-2"></i>Change Password</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
