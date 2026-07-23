<?php
/**
 * Password Recovery (OTP Simulation)
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$error = false;
$success = false;
$step = 1; // 1: Enter Email, 2: Enter OTP & New Password

if (isset($_SESSION['reset_email'])) {
    $step = 2;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF security token mismatch.";
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'request_otp') {
            $email = sanitize_input($_POST['email'] ?? '');
            if (empty($email)) {
                $error = "Please enter your registered email address.";
            } else {
                $otp = generate_otp_for_student($email);
                if ($otp) {
                    $_SESSION['reset_email'] = $email;
                    $success = "A simulated OTP has been sent to your email. Check system activity logs or uploads/simulated_emails.";
                    $step = 2;
                } else {
                    $error = "This email address is not registered in our system.";
                }
            }
        } elseif ($action === 'reset_password') {
            $email = $_SESSION['reset_email'] ?? '';
            $otpInput = sanitize_input($_POST['otp'] ?? '');
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (empty($otpInput) || empty($newPassword) || empty($confirmPassword)) {
                $error = "Please complete all fields.";
            } elseif ($newPassword !== $confirmPassword) {
                $error = "Passwords do not match.";
            } elseif (strlen($newPassword) < 6) {
                $error = "Password must be at least 6 characters long.";
            } else {
                // Verify OTP
                $student = fetch_one(
                    "SELECT id, otp, otp_expiry FROM students WHERE email = ? LIMIT 1",
                    [$email]
                );
                
                if ($student) {
                    $currentTime = date('Y-m-d H:i:s');
                    if ($student['otp'] !== $otpInput) {
                        $error = "Invalid OTP code.";
                    } elseif ($currentTime > $student['otp_expiry']) {
                        $error = "OTP code has expired. Please request a new one.";
                    } else {
                        // Success! Update password
                        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
                        query(
                            "UPDATE students SET password = ?, otp = NULL, otp_expiry = NULL WHERE id = ?",
                            [$hashed, $student['id']]
                        );
                        
                        // Log
                        log_activity("Password Reset Successful", "Student: " . $email . " successfully reset password.");
                        
                        unset($_SESSION['reset_email']);
                        $_SESSION['redirect_message'] = "Password reset successful! You can now log in with your new password.";
                        $_SESSION['redirect_type'] = "success";
                        header("Location: " . BASE_URL . "login.php");
                        exit();
                    }
                } else {
                    $error = "Student record not found.";
                }
            }
        }
    }
}

// Cancel reset process
if (isset($_GET['cancel'])) {
    unset($_SESSION['reset_email']);
    header("Location: " . BASE_URL . "login.php");
    exit();
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center my-5">
    <div class="col-lg-5 col-md-8">
        <div class="glass-card p-5">
            <div class="text-center mb-4">
                <i class="bi bi-key text-primary display-4"></i>
                <h3 class="fw-bold mt-2 text-dark">Password Recovery</h3>
                <p class="text-muted small">Simulate secure OTP verification and reset</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger border-0 glass-card text-danger text-center small py-2.5 mb-4">
                    <i class="bi bi-exclamation-octagon-fill me-2"></i><?= e($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success border-0 glass-card text-success text-center small py-2.5 mb-4">
                    <i class="bi bi-check-circle-fill me-2"></i><?= e($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($step === 1): ?>
                <!-- Step 1: Request OTP -->
                <form action="forgot-password.php" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="request_otp">
                    
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Enter Registered Email</label>
                        <input type="email" name="email" class="form-control glass-input" placeholder="e.g. alice@gmail.com" required>
                        <div class="form-text small text-muted">A 6-digit OTP will be simulated and sent.</div>
                    </div>
                    
                    <button type="submit" class="btn btn-premium-primary w-100 py-2.5 mb-3"><i class="bi bi-send me-2"></i>Send Verification OTP</button>
                    
                    <div class="text-center">
                        <a href="login.php" class="text-secondary small text-decoration-none"><i class="bi bi-chevron-left me-1"></i>Back to Sign In</a>
                    </div>
                </form>
            <?php else: ?>
                <!-- Step 2: Verify OTP & Change Password -->
                <div class="alert alert-info border-0 glass-card text-info text-start small mb-4">
                    <strong>Developer Tip:</strong> Since email sending is simulated, find the OTP in the activity logs database table or in <code>/uploads/simulated_emails/</code>.
                </div>
                
                <form action="forgot-password.php" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reset_password">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Verification OTP</label>
                        <input type="text" name="otp" class="form-control glass-input text-center fs-5 fw-bold" placeholder="6-digit OTP" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">New Password</label>
                        <input type="password" name="new_password" class="form-control glass-input" placeholder="At least 6 characters" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control glass-input" placeholder="Re-type new password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-premium-primary w-100 py-2.5 mb-3"><i class="bi bi-shield-check me-2"></i>Reset Password</button>
                    
                    <div class="text-center d-flex justify-content-between">
                        <a href="forgot-password.php?cancel=1" class="text-danger small text-decoration-none"><i class="bi bi-x-circle me-1"></i>Cancel</a>
                        <span class="text-muted small">Target: <?= e($_SESSION['reset_email'] ?? '') ?></span>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
