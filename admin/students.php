<?php
/**
 * Admin Panel - Manage Students (List, Block/Unblock, Delete)
 */
require_once __DIR__ . '/admin_header.php';

$error = false;
$success = false;

// Handle Status Toggle (Block / Unblock)
if (isset($_GET['status']) && isset($_GET['student_id'])) {
    $targetStudentId = intval($_GET['student_id']);
    $status = sanitize_input($_GET['status']);
    
    if (in_array($status, ['active', 'blocked'])) {
        $stud = fetch_one("SELECT student_id, fullname FROM students WHERE id = ?", [$targetStudentId]);
        if ($stud) {
            query("UPDATE students SET status = ? WHERE id = ?", [$status, $targetStudentId]);
            log_activity("Updated Student Status", "Student " . $stud['student_id'] . " ($status) by Admin.");
            
            // If blocked, also destroy their session (handled automatically by auth.php check on next request)
            $_SESSION['redirect_message'] = "Student account " . e($stud['fullname']) . " status updated to " . ucfirst($status) . ".";
            $_SESSION['redirect_type'] = "success";
        }
    }
    header("Location: students.php");
    exit();
}

// Handle Delete Student
if (isset($_GET['delete'])) {
    $targetStudentId = intval($_GET['delete']);
    $stud = fetch_one("SELECT student_id, fullname, profile_pic FROM students WHERE id = ?", [$targetStudentId]);
    if ($stud) {
        // Delete profile pic if exists
        if ($stud['profile_pic'] && file_exists(__DIR__ . '/../' . $stud['profile_pic'])) {
            unlink(__DIR__ . '/../' . $stud['profile_pic']);
        }
        
        query("DELETE FROM students WHERE id = ?", [$targetStudentId]);
        log_activity("Deleted Student Account", "Student " . $stud['student_id'] . " deleted.");
        $_SESSION['redirect_message'] = "Student " . e($stud['fullname']) . " deleted successfully.";
        $_SESSION['redirect_type'] = "success";
    }
    header("Location: students.php");
    exit();
}

// Filter and Search
$search = sanitize_input($_GET['search'] ?? '');
$filterState = sanitize_input($_GET['status_filter'] ?? 'all');

$sql = "SELECT * FROM students WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (fullname LIKE ? OR student_id LIKE ? OR email LIKE ? OR branch LIKE ?)";
    $searchWild = "%$search%";
    $params[] = $searchWild;
    $params[] = $searchWild;
    $params[] = $searchWild;
    $params[] = $searchWild;
}

if ($filterState !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $filterState;
}

$sql .= " ORDER BY fullname ASC";
$students = fetch_all($sql, $params);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-dark mb-0">Manage Student Profiles</h3>
</div>

<!-- Search Widget -->
<div class="glass-card p-4 mb-4">
    <form action="students.php" method="GET" class="row g-3">
        <div class="col-md-6">
            <input type="text" name="search" class="form-control glass-input" placeholder="Search by roll, name, email, department..." value="<?= e($search) ?>">
        </div>
        <div class="col-md-3">
            <select name="status_filter" class="form-select glass-input">
                <option value="all" <?= $filterState === 'all' ? 'selected' : '' ?>>All Accounts</option>
                <option value="active" <?= $filterState === 'active' ? 'selected' : '' ?>>Active Accounts</option>
                <option value="blocked" <?= $filterState === 'blocked' ? 'selected' : '' ?>>Blocked Accounts</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-premium-secondary w-100"><i class="bi bi-search me-1"></i>Search</button>
        </div>
    </form>
</div>

<!-- Students list -->
<div class="glass-card p-4">
    <?php if (empty($students)): ?>
        <p class="text-muted text-center py-5">No students registered yet or matching criteria.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-glass mb-0">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Student Roll & Name</th>
                        <th>Email & Contact</th>
                        <th>Branch & Year</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                        <tr>
                            <td>
                                <?php if ($s['profile_pic']): ?>
                                    <img src="<?= BASE_URL . e($s['profile_pic']) ?>" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold text-center small" style="width: 40px; height: 40px;">
                                        <?= strtoupper(substr($s['fullname'], 0, 2)) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($s['fullname']) ?></div>
                                <small class="text-muted">Roll: <?= e($s['student_id']) ?></small>
                            </td>
                            <td class="small">
                                <div><i class="bi bi-envelope me-1"></i><?= e($s['email']) ?></div>
                                <div class="text-secondary"><i class="bi bi-telephone me-1"></i><?= e($s['phone'] ?? 'N/A') ?></div>
                            </td>
                            <td class="small">
                                <div><?= e($s['branch'] ?? 'Unassigned') ?></div>
                                <div class="text-secondary"><?= e($s['year']) ?><?= $s['year'] == 1 ? 'st' : ($s['year'] == 2 ? 'nd' : ($s['year'] == 3 ? 'rd' : 'th')) ?> Year</div>
                            </td>
                            <td>
                                <?php if ($s['status'] === 'active'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">Blocked</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-light glass-card border-0 btn-sm" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end border-0 glass-card shadow">
                                        <?php if ($s['status'] === 'active'): ?>
                                            <li><a class="dropdown-item py-1.5 text-danger small confirm-action" href="students.php?status=blocked&student_id=<?= $s['id'] ?>" data-confirm-msg="Are you sure you want to block this student? They won't be able to log in."><i class="bi bi-slash-circle me-2"></i>Block Student</a></li>
                                        <?php else: ?>
                                            <li><a class="dropdown-item py-1.5 text-success small" href="students.php?status=active&student_id=<?= $s['id'] ?>"><i class="bi bi-check-circle me-2"></i>Unblock Student</a></li>
                                        <?php endif; ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item py-1.5 text-danger small confirm-action" href="students.php?delete=<?= $s['id'] ?>" data-confirm-msg="Warning! Deleting this student account will delete all their registrations and feedbacks permanently. Proceed?"><i class="bi bi-trash me-2"></i>Delete Profile</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
