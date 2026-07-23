<?php
/**
 * Admin Panel - Manage Registrations & Mark Attendance
 */
require_once __DIR__ . '/admin_header.php';

$error = false;
$success = false;

// Handle Status Updates (Approve / Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['reg_id'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF security token mismatch.";
    } else {
        $regId = intval($_POST['reg_id']);
        $action = $_POST['action'];
        
        $reg = fetch_one("SELECT r.*, e.event_name, s.fullname, s.email FROM registrations r JOIN events e ON r.event_id = e.id JOIN students s ON r.student_id = s.id WHERE r.id = ?", [$regId]);
        
        if ($reg) {
            if ($action === 'approve') {
                if ($reg['status'] !== 'approved') {
                    // Update registration status to approved
                    query("UPDATE registrations SET status = 'approved' WHERE id = ?", [$regId]);
                    
                    log_activity("Approved Registration", "Registration ID: $regId approved for " . $reg['fullname']);
                    
                    // Send notification to student
                    query(
                        "INSERT INTO notifications (student_id, title, message) VALUES (?, ?, ?)",
                        [$reg['student_id'], 'Registration Approved!', 'Your registration for "' . $reg['event_name'] . '" has been approved. You can download your event pass now.']
                    );
                    
                    // Send simulated email
                    $body = "Hello " . $reg['fullname'] . ",\n\nYour registration for the event \"" . $reg['event_name'] . "\" has been APPROVED. Your entry pass is ready for download on the student portal.\n\nEnjoy the event!";
                    simulate_email($reg['email'], "Registration Approved - Campus Guru", $body);
                    
                    $success = "Registration for " . e($reg['fullname']) . " approved successfully.";
                }
            } elseif ($action === 'reject') {
                if ($reg['status'] !== 'rejected') {
                    // Start transaction to restore seat count
                    global $conn;
                    $conn->beginTransaction();
                    try {
                        query("UPDATE registrations SET status = 'rejected' WHERE id = ?", [$regId]);
                        query("UPDATE events SET available_seats = available_seats + 1 WHERE id = ?", [$reg['event_id']]);
                        $conn->commit();
                        
                        log_activity("Rejected Registration", "Registration ID: $regId rejected for " . $reg['fullname']);
                        
                        // Notify
                        query(
                            "INSERT INTO notifications (student_id, title, message) VALUES (?, ?, ?)",
                            [$reg['student_id'], 'Registration Rejected', 'Your registration for "' . $reg['event_name'] . '" has been rejected.']
                        );
                        
                        $success = "Registration for " . e($reg['fullname']) . " rejected and seat restored.";
                    } catch (Exception $e) {
                        $conn->rollBack();
                        $error = "Failed to reject: " . $e->getMessage();
                    }
                }
            }
        } else {
            $error = "Registration record not found.";
        }
    }
}

// Handle Attendance Updates
if (isset($_GET['attendance']) && isset($_GET['reg_id'])) {
    $regId = intval($_GET['reg_id']);
    $attn = sanitize_input($_GET['attendance']);
    
    $validAttn = ['present', 'absent', 'pending'];
    if (in_array($attn, $validAttn)) {
        $reg = fetch_one("SELECT r.*, s.fullname, e.event_name FROM registrations r JOIN students s ON r.student_id = s.id JOIN events e ON r.event_id = e.id WHERE r.id = ?", [$regId]);
        if ($reg) {
            query("UPDATE registrations SET attendance = ? WHERE id = ?", [$attn, $regId]);
            log_activity("Marked Attendance", "Attendance for " . $reg['fullname'] . " marked as " . ucfirst($attn) . " for: " . $reg['event_name']);
            $_SESSION['redirect_message'] = "Attendance marked as " . ucfirst($attn) . " for " . e($reg['fullname']) . ".";
            $_SESSION['redirect_type'] = "success";
        }
    }
    header("Location: registrations.php");
    exit();
}

// Fetch list with search & filters
$search = sanitize_input($_GET['search'] ?? '');
$statusFilter = sanitize_input($_GET['status'] ?? 'all');
$attnFilter = sanitize_input($_GET['attendance_filter'] ?? 'all');

$sql = "SELECT r.*, s.fullname, s.student_id as roll_no, s.branch, e.event_name, e.date 
        FROM registrations r 
        JOIN students s ON r.student_id = s.id 
        JOIN events e ON r.event_id = e.id 
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (s.fullname LIKE ? OR s.student_id LIKE ? OR e.event_name LIKE ?)";
    $searchWild = "%$search%";
    $params[] = $searchWild;
    $params[] = $searchWild;
    $params[] = $searchWild;
}

if ($statusFilter !== 'all') {
    $sql .= " AND r.status = ?";
    $params[] = $statusFilter;
}

if ($attnFilter !== 'all') {
    $sql .= " AND r.attendance = ?";
    $params[] = $attnFilter;
}

$sql .= " ORDER BY r.id DESC";
$registrations = fetch_all($sql, $params);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-dark mb-0">Event Registrations & Attendance</h3>
</div>

<!-- Search & Filters -->
<div class="glass-card p-4 mb-4">
    <form action="registrations.php" method="GET" class="row g-3">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control glass-input" placeholder="Student name, Roll, or Event..." value="<?= e($search) ?>">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select glass-input">
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Statuses</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending Approval</option>
                <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="attendance_filter" class="form-select glass-input">
                <option value="all" <?= $attnFilter === 'all' ? 'selected' : '' ?>>All Attendance</option>
                <option value="present" <?= $attnFilter === 'present' ? 'selected' : '' ?>>Present</option>
                <option value="absent" <?= $attnFilter === 'absent' ? 'selected' : '' ?>>Absent</option>
                <option value="pending" <?= $attnFilter === 'pending' ? 'selected' : '' ?>>Pending Check-in</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-premium-primary w-100"><i class="bi bi-search me-1"></i>Search</button>
        </div>
    </form>
</div>

<!-- Registrations List -->
<div class="glass-card p-4">
    <?php if ($error): ?>
        <div class="alert alert-danger border-0 glass-card text-danger small py-2 mb-3">
            <?= e($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success border-0 glass-card text-success small py-2 mb-3">
            <?= e($success) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($registrations)): ?>
        <p class="text-muted text-center py-5">No registrations found matching selection.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-glass mb-0">
                <thead>
                    <tr>
                        <th>Student Roll & Name</th>
                        <th>Event Title</th>
                        <th>Reg Date</th>
                        <th>Status</th>
                        <th>Attendance</th>
                        <th>Quick Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $reg): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?= e($reg['fullname']) ?></div>
                                <small class="text-muted"><?= e($reg['roll_no']) ?> (<?= e($reg['branch']) ?>)</small>
                            </td>
                            <td class="small">
                                <div class="fw-semibold"><?= e($reg['event_name']) ?></div>
                                <small class="text-secondary"><?= date('M d, Y', strtotime($reg['date'])) ?></small>
                            </td>
                            <td class="small text-muted"><?= date('M d, Y', strtotime($reg['registration_date'])) ?></td>
                            <td>
                                <?php if ($reg['status'] === 'approved'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Approved</span>
                                <?php elseif ($reg['status'] === 'rejected'): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">Rejected</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($reg['attendance'] === 'present'): ?>
                                    <span class="badge bg-success text-white rounded-pill">Present</span>
                                <?php elseif ($reg['attendance'] === 'absent'): ?>
                                    <span class="badge bg-danger text-white rounded-pill">Absent</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border rounded-pill">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($reg['status'] === 'pending'): ?>
                                    <form action="registrations.php" method="POST" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="reg_id" value="<?= $reg['id'] ?>">
                                        <button type="submit" name="action" value="approve" class="btn btn-success btn-sm py-1 px-2.5 small" title="Approve"><i class="bi bi-check-circle-fill me-1"></i>Approve</button>
                                        <button type="submit" name="action" value="reject" class="btn btn-outline-danger btn-sm py-1 px-2.5 small" title="Reject"><i class="bi bi-x-circle-fill me-1"></i>Reject</button>
                                    </form>
                                <?php elseif ($reg['status'] === 'approved'): ?>
                                    <div class="dropdown d-inline">
                                        <button class="btn btn-light glass-card border-0 btn-sm dropdown-toggle py-1 px-2.5" type="button" data-bs-toggle="dropdown">
                                            Mark Attendance
                                        </button>
                                        <ul class="dropdown-menu border-0 glass-card shadow">
                                            <li><a class="dropdown-item py-1.5 small text-success" href="registrations.php?attendance=present&reg_id=<?= $reg['id'] ?>"><i class="bi bi-check2-circle me-2"></i>Present</a></li>
                                            <li><a class="dropdown-item py-1.5 small text-danger" href="registrations.php?attendance=absent&reg_id=<?= $reg['id'] ?>"><i class="bi bi-x-octagon me-2"></i>Absent</a></li>
                                            <li><a class="dropdown-item py-1.5 small text-muted" href="registrations.php?attendance=pending&reg_id=<?= $reg['id'] ?>"><i class="bi bi-hourglass me-2"></i>Reset (Pending)</a></li>
                                        </ul>
                                    </div>
                                    <form action="registrations.php" method="POST" class="d-inline ms-1">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="reg_id" value="<?= $reg['id'] ?>">
                                        <button type="submit" name="action" value="reject" class="btn btn-outline-danger btn-sm py-1 px-2.5 small confirm-action" data-confirm-msg="Are you sure you want to revoke/reject this approved registration?"><i class="bi bi-x-circle me-1"></i>Revoke</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
