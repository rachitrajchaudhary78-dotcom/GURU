<?php
/**
 * Student Registration Page
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (is_admin_logged_in() || is_student_logged_in()) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$error = false;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF security token mismatch.";
    } else {
        $role = sanitize_input($_POST['role'] ?? 'student');
        $fullname = sanitize_input($_POST['fullname'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($role === 'admin') {
            $username = sanitize_input($_POST['username'] ?? '');
            if (empty($username) || empty($fullname) || empty($email) || empty($password)) {
                $error = "All mandatory fields are required.";
            } elseif ($password !== $confirmPassword) {
                $error = "Passwords do not match.";
            } elseif (strlen($password) < 6) {
                $error = "Password must be at least 6 characters long.";
            } else {
                // Check for existing admin ID or Email
                $existing = fetch_one("SELECT id FROM admins WHERE username = ? OR email = ? LIMIT 1", [$username, $email]);
                if ($existing) {
                    $error = "Username or Email is already registered as Admin.";
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    try {
                        query(
                            "INSERT INTO admins (username, password, email, fullname) VALUES (?, ?, ?, ?)",
                            [$username, $hashedPassword, $email, $fullname]
                        );
                        $_SESSION['redirect_message'] = "Admin registration successful! You can now log in.";
                        $_SESSION['redirect_type'] = "success";
                        header("Location: " . BASE_URL . "login.php");
                        exit();
                    } catch (PDOException $e) {
                        $error = "Admin registration failed: " . $e->getMessage();
                    }
                }
            }
        } else {
            // Student registration
            $studentId = sanitize_input($_POST['student_id'] ?? '');
            $phone = sanitize_input($_POST['phone'] ?? '');
            $branch = sanitize_input($_POST['branch'] ?? '');
            $year = intval($_POST['year'] ?? 1);

            if (empty($studentId) || empty($fullname) || empty($email) || empty($password)) {
                $error = "All mandatory fields are required.";
            } elseif ($password !== $confirmPassword) {
                $error = "Passwords do not match.";
            } elseif (strlen($password) < 6) {
                $error = "Password must be at least 6 characters long.";
            } else {
                // Check for existing student ID or Email
                $existing = fetch_one("SELECT id FROM students WHERE student_id = ? OR email = ? LIMIT 1", [$studentId, $email]);
                if ($existing) {
                    $error = "Student ID or Email is already registered.";
                } else {
                    // Profile Pic Upload handling
                    $profilePicPath = null;
                    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
                        $fileTmpPath = $_FILES['profile_pic']['tmp_name'];
                        $fileName = $_FILES['profile_pic']['name'];
                        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
                        if (in_array($fileExtension, $allowedExtensions)) {
                            $newFileName = 'profile_' . $studentId . '_' . time() . '.' . $fileExtension;
                            $uploadFileDir = __DIR__ . '/uploads/profile_pics/';
                            
                            if (!is_dir($uploadFileDir)) {
                                mkdir($uploadFileDir, 0777, true);
                            }
                            
                            $destPath = $uploadFileDir . $newFileName;
                            if (move_uploaded_file($fileTmpPath, $destPath)) {
                                $profilePicPath = 'uploads/profile_pics/' . $newFileName;
                            }
                        }
                    }

                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    
                    try {
                        query(
                            "INSERT INTO students (student_id, fullname, email, password, phone, branch, year, profile_pic) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                            [$studentId, $fullname, $email, $hashedPassword, $phone, $branch, $year, $profilePicPath]
                        );

                        // Add welcome notification
                        $newStudent = fetch_one("SELECT id FROM students WHERE student_id = ?", [$studentId]);
                        if ($newStudent) {
                            query(
                                "INSERT INTO notifications (student_id, title, message) VALUES (?, ?, ?)",
                                [$newStudent['id'], 'Welcome to Campus Guru!', 'Your student registration was successful. Browse upcoming events and sign up today!']
                            );
                        }

                        $_SESSION['redirect_message'] = "Registration successful! You can now log in.";
                        $_SESSION['redirect_type'] = "success";
                        header("Location: " . BASE_URL . "login.php");
                        exit();
                    } catch (PDOException $e) {
                        $error = "Registration failed: " . $e->getMessage();
                    }
                }
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center my-4">
    <div class="col-lg-6 col-md-10">
        <div class="glass-card p-5">
            <div class="text-center mb-4">
                <i class="bi bi-person-plus text-primary display-4"></i>
                <h3 class="fw-bold mt-2 text-dark" id="regTitle">Student Sign Up</h3>
                <p class="text-muted small" id="regSubtitle">Register to participate in campus contests & workshops</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger border-0 glass-card text-danger text-center small py-2.5 mb-4">
                    <i class="bi bi-exclamation-octagon-fill me-2"></i><?= e($error) ?>
                </div>
            <?php endif; ?>
            
            <form action="register.php" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                
                <!-- Role Selection -->
                <div class="mb-4">
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
                
                <div class="row g-3">
                    <div class="col-md-6" id="idWrapper">
                        <label class="form-label small fw-bold" id="idLabel">Student ID / Roll No *</label>
                        <input type="text" name="student_id" id="idInput" class="form-control glass-input" placeholder="e.g. STU101" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Full Name *</label>
                        <input type="text" name="fullname" class="form-control glass-input" placeholder="Your full name" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Email Address *</label>
                        <input type="email" name="email" class="form-control glass-input" placeholder="e.g. name@gmail.com" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Phone Number</label>
                        <input type="text" name="phone" class="form-control glass-input" placeholder="10-digit number">
                    </div>
                    
                    <div class="col-md-6 student-only">
                        <label class="form-label small fw-bold">Department / Branch</label>
                        <select name="branch" id="branchSelect" class="form-select glass-input">
                            <option value="Computer Science">Computer Science</option>
                            <option value="Electronics">Electronics</option>
                            <option value="Mechanical">Mechanical</option>
                            <option value="Civil">Civil</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Electrical">Electrical</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-6 student-only">
                        <label class="form-label small fw-bold">Current Academic Year</label>
                        <select name="year" id="yearSelect" class="form-select glass-input">
                            <option value="1">1st Year (Freshman)</option>
                            <option value="2">2nd Year (Sophomore)</option>
                            <option value="3">3rd Year (Junior)</option>
                            <option value="4">4th Year (Senior)</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Password *</label>
                        <input type="password" name="password" class="form-control glass-input" placeholder="At least 6 characters" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Confirm Password *</label>
                        <input type="password" name="confirm_password" class="form-control glass-input" placeholder="Verify password" required>
                    </div>
                    
                    <div class="col-12 student-only">
                        <label class="form-label small fw-bold">Profile Picture</label>
                        <input type="file" name="profile_pic" id="picInput" class="form-control glass-input" accept="image/*">
                        <div class="form-text small text-muted">Allowed extensions: JPG, JPEG, PNG, WEBP</div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-premium-primary w-100 py-2.5 mt-4 mb-3"><i class="bi bi-person-check me-2"></i>Create Account</button>
                
                <div class="text-center">
                    <p class="small text-muted mb-0">Already registered? <a href="login.php" class="text-primary text-decoration-none fw-bold">Sign In Here</a></p>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('input[name="role"]').change(function() {
        if ($(this).val() === 'admin') {
            $('#regTitle').text('Admin Sign Up');
            $('#regSubtitle').text('Register to manage campus contests, workshops, and events');
            $('#idLabel').text('Username *');
            $('#idInput').attr('placeholder', 'e.g. admin_user');
            $('#idInput').attr('name', 'username');
            $('.student-only').slideUp();
        } else {
            $('#regTitle').text('Student Sign Up');
            $('#regSubtitle').text('Register to participate in campus contests & workshops');
            $('#idLabel').text('Student ID / Roll No *');
            $('#idInput').attr('placeholder', 'e.g. STU101');
            $('#idInput').attr('name', 'student_id');
            $('.student-only').slideDown();
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
