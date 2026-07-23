<?php
/**
 * Edit Profile & Change Password (Student)
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_student_login();

$studentId = $_SESSION['student_id'];
$error = false;
$success = false;

// Fetch current details
$student = fetch_one("SELECT * FROM students WHERE id = ?", [$studentId]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF security token mismatch.";
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_profile') {
            $fullname = sanitize_input($_POST['fullname'] ?? '');
            $email = sanitize_input($_POST['email'] ?? '');
            $phone = sanitize_input($_POST['phone'] ?? '');
            $branch = sanitize_input($_POST['branch'] ?? '');
            $year = intval($_POST['year'] ?? 1);
            
            if (empty($fullname) || empty($email)) {
                $error = "Full Name and Email are required.";
            } else {
                // Verify email uniqueness (except current student)
                $emailCheck = fetch_one("SELECT id FROM students WHERE email = ? AND id != ? LIMIT 1", [$email, $studentId]);
                if ($emailCheck) {
                    $error = "This email address is already in use by another student.";
                } else {
                    // Handle Profile Pic Upload
                    $profilePicPath = $student['profile_pic'];
                    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
                        $fileTmpPath = $_FILES['profile_pic']['tmp_name'];
                        $fileName = $_FILES['profile_pic']['name'];
                        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        
                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
                        if (in_array($fileExtension, $allowedExtensions)) {
                            // Delete old profile picture if exists
                            if ($profilePicPath && file_exists(__DIR__ . '/../' . $profilePicPath)) {
                                unlink(__DIR__ . '/../' . $profilePicPath);
                            }
                            
                            $newFileName = 'profile_' . $student['student_id'] . '_' . time() . '.' . $fileExtension;
                            $uploadFileDir = __DIR__ . '/../uploads/profile_pics/';
                            if (!is_dir($uploadFileDir)) {
                                mkdir($uploadFileDir, 0777, true);
                            }
                            $destPath = $uploadFileDir . $newFileName;
                            if (move_uploaded_file($fileTmpPath, $destPath)) {
                                $profilePicPath = 'uploads/profile_pics/' . $newFileName;
                            }
                        }
                    }
                    
                    // Update Database
                    query(
                        "UPDATE students SET fullname = ?, email = ?, phone = ?, branch = ?, year = ?, profile_pic = ? WHERE id = ?",
                        [$fullname, $email, $phone, $branch, $year, $profilePicPath, $studentId]
                    );
                    
                    // Refresh session variables
                    $_SESSION['fullname'] = $fullname;
                    $_SESSION['student_email'] = $email;
                    
                    log_activity("Updated Profile", "Student: " . $student['student_id'] . " updated profile details.");
                    
                    $success = "Profile details updated successfully!";
                    // Fetch fresh info
                    $student = fetch_one("SELECT * FROM students WHERE id = ?", [$studentId]);
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
                // Verify current password
                if (password_verify($currentPassword, $student['password'])) {
                    $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
                    query("UPDATE students SET password = ? WHERE id = ?", [$hashed, $studentId]);
                    
                    log_activity("Password Changed", "Student: " . $student['student_id'] . " changed password.");
                    $success = "Password changed successfully!";
                } else {
                    $error = "Incorrect current password.";
                }
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
    <!-- Sidebar Navigation -->
    <div class="col-lg-3">
        <div class="glass-card p-4">
            <div class="text-center mb-4 pb-3 border-bottom">
                <?php if ($student['profile_pic']): ?>
                    <img src="<?= BASE_URL . e($student['profile_pic']) ?>" class="rounded-circle shadow-sm border" alt="Profile Picture" style="width: 90px; height: 90px; object-fit: cover;">
                <?php else: ?>
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center fw-bold border shadow-sm fs-2" style="width: 90px; height: 90px;">
                        <?= strtoupper(substr($student['fullname'], 0, 2)) ?>
                    </div>
                <?php endif; ?>
                <h5 class="fw-bold mt-3 mb-1 text-dark"><?= e($student['fullname']) ?></h5>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill small"><?= e($student['student_id']) ?></span>
            </div>
            
            <div class="d-flex flex-column gap-2">
                <a href="dashboard.php" class="btn btn-light glass-card text-start py-2.5 border-0"><i class="bi bi-speedometer2 me-3"></i>Dashboard</a>
                <a href="events.php" class="btn btn-light glass-card text-start py-2.5 border-0"><i class="bi bi-search me-3"></i>Browse Events</a>
                <a href="my-events.php" class="btn btn-light glass-card text-start py-2.5 border-0"><i class="bi bi-calendar2-check me-3"></i>My Registrations</a>
                <a href="profile.php" class="btn btn-light glass-card text-start py-2.5 border-0 text-primary fw-semibold"><i class="bi bi-person-gear me-3"></i>Profile Settings</a>
                <a href="../logout.php" class="btn btn-light glass-card text-start py-2.5 border-0 text-danger confirm-action" data-confirm-msg="Are you sure you want to log out?"><i class="bi bi-box-arrow-right me-3"></i>Logout</a>
            </div>
        </div>
    </div>
    
    <!-- Profile Edit Forms -->
    <div class="col-lg-9">
        <div class="glass-card p-5">
            <h4 class="fw-bold text-dark mb-4"><i class="bi bi-person-gear me-2 text-primary"></i>Profile Settings</h4>
            
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
                <!-- Update Details -->
                <div class="col-md-7 border-end">
                    <h5 class="fw-bold text-dark mb-3">Update Details</h5>
                    
                    <form action="profile.php" method="POST" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Full Name</label>
                            <input type="text" name="fullname" class="form-control glass-input" value="<?= e($student['fullname']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Email Address</label>
                            <input type="email" name="email" class="form-control glass-input" value="<?= e($student['email']) ?>" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <label class="form-label small fw-bold">Phone Number</label>
                                <input type="text" name="phone" class="form-control glass-input" value="<?= e($student['phone']) ?>">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-bold">Branch</label>
                                <select name="branch" class="form-select glass-input">
                                    <option value="Computer Science" <?= $student['branch'] === 'Computer Science' ? 'selected' : '' ?>>Computer Science</option>
                                    <option value="Electronics" <?= $student['branch'] === 'Electronics' ? 'selected' : '' ?>>Electronics</option>
                                    <option value="Mechanical" <?= $student['branch'] === 'Mechanical' ? 'selected' : '' ?>>Mechanical</option>
                                    <option value="Civil" <?= $student['branch'] === 'Civil' ? 'selected' : '' ?>>Civil</option>
                                    <option value="Information Technology" <?= $student['branch'] === 'Information Technology' ? 'selected' : '' ?>>Information Technology</option>
                                    <option value="Electrical" <?= $student['branch'] === 'Electrical' ? 'selected' : '' ?>>Electrical</option>
                                    <option value="Other" <?= $student['branch'] === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <label class="form-label small fw-bold">Academic Year</label>
                                <select name="year" class="form-select glass-input">
                                    <option value="1" <?= $student['year'] == 1 ? 'selected' : '' ?>>1st Year</option>
                                    <option value="2" <?= $student['year'] == 2 ? 'selected' : '' ?>>2nd Year</option>
                                    <option value="3" <?= $student['year'] == 3 ? 'selected' : '' ?>>3rd Year</option>
                                    <option value="4" <?= $student['year'] == 4 ? 'selected' : '' ?>>4th Year</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-bold">Profile Picture</label>
                                <input type="file" name="profile_pic" class="form-control glass-input" accept="image/*">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-premium-primary w-100 mt-2"><i class="bi bi-save me-2"></i>Save Profile</button>
                    </form>
                </div>
                
                <!-- Change Password -->
                <div class="col-md-5">
                    <h5 class="fw-bold text-dark mb-3">Change Password</h5>
                    
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
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control glass-input" placeholder="Verify password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-premium-secondary w-100 mt-3"><i class="bi bi-shield-lock me-2"></i>Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
