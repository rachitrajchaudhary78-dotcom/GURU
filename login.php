<?php
/**
 * Login Screen
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_admin_logged_in()) {
    header("Location: " . BASE_URL . "admin/dashboard.php");
    exit();
} elseif (is_student_logged_in()) {
    header("Location: " . BASE_URL . "student/dashboard.php");
    exit();
}

$error = false;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF security token mismatch. Please try again.";
    } else {
        $loginInput = sanitize_input($_POST['login_input'] ?? ''); // Student ID (Roll) or Email/Username
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'student';
        
        if (empty($loginInput) || empty($password)) {
            $error = "Please fill in all fields.";
        } else {
            if ($role === 'admin') {
                // Find admin by username or email
                $admin = fetch_one("SELECT * FROM admins WHERE username = ? OR email = ? LIMIT 1", [$loginInput, $loginInput]);
                if ($admin && password_verify($password, $admin['password'])) {
                    login_user($admin['id'], $admin['username'], $admin['email'], 'admin', $admin['fullname']);
                    
                    // Optional Remember Me handling
                    if (isset($_POST['remember_me'])) {
                        setcookie('remember_user', 'admin|' . $admin['username'], time() + 86400 * 30, BASE_URL);
                    }
                    
                    header("Location: " . BASE_URL . "admin/dashboard.php");
                    exit();
                } else {
                    $error = "Invalid admin credentials.";
                }
            } else {
                // Find student by student_id (Roll No) or email
                $student = fetch_one("SELECT * FROM students WHERE student_id = ? OR email = ? LIMIT 1", [$loginInput, $loginInput]);
                
                if ($student) {
                    if ($student['status'] === 'blocked') {
                        $error = "Your account has been blocked by the Administrator. Please contact support.";
                    } elseif (password_verify($password, $student['password'])) {
                        login_user($student['id'], $student['student_id'], $student['email'], 'student', $student['fullname']);
                        
                        // Optional Remember Me handling
                        if (isset($_POST['remember_me'])) {
                            setcookie('remember_user', 'student|' . $student['student_id'], time() + 86400 * 30, BASE_URL);
                        }
                        
                        header("Location: " . BASE_URL . "student/dashboard.php");
                        exit();
                    } else {
                        $error = "Invalid student credentials.";
                    }
                } else {
                    $error = "Student record not found.";
                }
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center my-5">
    <div class="col-lg-5 col-md-8">
        <div class="glass-card p-5">
            <div class="text-center mb-4">
                <i class="bi bi-shield-lock text-primary display-4"></i>
                <h3 class="fw-bold mt-2 text-dark">Welcome Back</h3>
                <p class="text-muted small">Access your Campus Events account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger border-0 glass-card text-danger text-center small py-2.5 mb-4">
                    <i class="bi bi-exclamation-octagon-fill me-2"></i><?= e($error) ?>
                </div>
            <?php endif; ?>
            
            <form action="login.php" method="POST">
                <?= csrf_field() ?>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Select Role</label>
                    <div class="d-flex gap-3">
                        <div class="form-check flex-grow-1 glass-card p-2 text-center rounded">
                            <input class="form-check-input ms-0 me-2" type="radio" name="role" id="roleStudent" value="student" checked>
                            <label class="form-check-label fw-semibold" for="roleStudent">
                                Student
                            </label>
                        </div>
                        <div class="form-check flex-grow-1 glass-card p-2 text-center rounded">
                            <input class="form-check-input ms-0 me-2" type="radio" name="role" id="roleAdmin" value="admin">
                            <label class="form-check-label fw-semibold" for="roleAdmin">
                                Admin
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold" id="inputLabel">Roll Number or Email</label>
                    <input type="text" name="login_input" class="form-control glass-input" placeholder="e.g. STU001 or alice@gmail.com" required>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <label class="form-label small fw-bold">Password</label>
                        <a href="forgot-password.php" class="text-primary text-decoration-none small">Forgot Password?</a>
                    </div>
                    <input type="password" name="password" class="form-control glass-input" placeholder="••••••••" required>
                </div>
                
                <div class="form-check mb-4 text-start">
                    <input class="form-check-input glass-input" type="checkbox" name="remember_me" id="rememberMe">
                    <label class="form-check-label small text-muted" for="rememberMe">
                        Remember me on this device
                    </label>
                </div>
                
                <button type="submit" class="btn btn-premium-primary w-100 py-2.5 mb-3"><i class="bi bi-box-arrow-in-right me-2"></i>Sign In</button>
                
                <div class="text-center mt-3">
                    <p class="small text-muted mb-0">Don't have a student account? <a href="register.php" class="text-primary text-decoration-none fw-bold">Sign Up Now</a></p>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('input[name="role"]').change(function() {
        if ($(this).val() === 'admin') {
            $('#inputLabel').text('Username or Email');
            $('input[name="login_input"]').attr('placeholder', 'e.g. admin or admin@gmail.com');
        } else {
            $('#inputLabel').text('Roll Number or Email');
            $('input[name="login_input"]').attr('placeholder', 'e.g. STU001 or alice@gmail.com');
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
