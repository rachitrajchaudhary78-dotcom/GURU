<?php
/**
 * Admin Panel - Manage Events (List, Delete, Quick Status updates)
 */
require_once __DIR__ . '/admin_header.php';

// Handle Delete Action
if (isset($_GET['delete'])) {
    $delId = intval($_GET['delete']);
    // Fetch banner first to delete file
    $evt = fetch_one("SELECT event_name, event_banner FROM events WHERE id = ?", [$delId]);
    if ($evt) {
        if ($evt['event_banner'] && file_exists(__DIR__ . '/../' . $evt['event_banner'])) {
            unlink(__DIR__ . '/../' . $evt['event_banner']);
        }
        query("DELETE FROM events WHERE id = ?", [$delId]);
        log_activity("Deleted Event", "Admin deleted event: " . $evt['event_name']);
        $_SESSION['redirect_message'] = "Event deleted successfully.";
        $_SESSION['redirect_type'] = "success";
    }
    header("Location: events.php");
    exit();
}

// Handle Status Change Shortcuts
if (isset($_GET['action']) && isset($_GET['id'])) {
    $actId = intval($_GET['id']);
    $action = sanitize_input($_GET['action']);
    
    $validActions = ['approve', 'reject', 'complete'];
    if (in_array($action, $validActions)) {
        $status = 'pending';
        if ($action === 'approve') $status = 'approved';
        elseif ($action === 'reject') $status = 'rejected';
        elseif ($action === 'complete') $status = 'completed';
        
        query("UPDATE events SET status = ? WHERE id = ?", [$status, $actId]);
        
        $evtName = fetch_one("SELECT event_name FROM events WHERE id = ?", [$actId])['event_name'] ?? 'Unknown';
        log_activity("Updated Event Status", "Event: $evtName status set to $status.");
        
        // Notify registered students of event completion/status if approved
        if ($status === 'approved' || $status === 'completed') {
            $regs = fetch_all("SELECT student_id FROM registrations WHERE event_id = ? AND status = 'approved'", [$actId]);
            $title = ($status === 'approved') ? "Event Approved!" : "Event Completed!";
            $msg = ($status === 'approved') 
                ? "The event \"$evtName\" has been approved by admin. Get ready!" 
                : "The event \"$evtName\" is marked as completed. You can submit feedback and download certificates now.";
            
            foreach ($regs as $r) {
                query("INSERT INTO notifications (student_id, title, message) VALUES (?, ?, ?)", [$r['student_id'], $title, $msg]);
            }
        }

        $_SESSION['redirect_message'] = "Event status updated to " . ucfirst($status) . ".";
        $_SESSION['redirect_type'] = "success";
    }
    header("Location: events.php");
    exit();
}

// Fetch lists with optional filters
$filterStatus = sanitize_input($_GET['status'] ?? 'all');
$search = sanitize_input($_GET['search'] ?? '');

$sql = "SELECT e.*, c.name as category_name 
        FROM events e 
        JOIN categories c ON e.category_id = c.id 
        WHERE 1=1";
$params = [];

if ($filterStatus !== 'all') {
    $sql .= " AND e.status = ?";
    $params[] = $filterStatus;
}
if (!empty($search)) {
    $sql .= " AND (e.event_name LIKE ? OR e.venue LIKE ? OR e.organizer LIKE ?)";
    $searchWild = "%$search%";
    $params[] = $searchWild;
    $params[] = $searchWild;
    $params[] = $searchWild;
}

$sql .= " ORDER BY e.date ASC, e.time ASC";
$events = fetch_all($sql, $params);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-dark mb-0">Manage Campus Events</h3>
    <a href="event-create.php" class="btn btn-premium-primary"><i class="bi bi-plus-circle me-2"></i>Create New Event</a>
</div>

<!-- Search & Filter Controls -->
<div class="glass-card p-4 mb-4">
    <form action="events.php" method="GET" class="row g-3">
        <div class="col-md-5">
            <input type="text" name="search" class="form-control glass-input" placeholder="Search by name, venue, organizer..." value="<?= e($search) ?>">
        </div>
        <div class="col-md-4">
            <select name="status" class="form-select glass-input">
                <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>All Statuses</option>
                <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending Approval</option>
                <option value="approved" <?= $filterStatus === 'approved' ? 'selected' : '' ?>>Approved / Upcoming</option>
                <option value="completed" <?= $filterStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="rejected" <?= $filterStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-premium-secondary w-100"><i class="bi bi-search me-2"></i>Filter List</button>
        </div>
    </form>
</div>

<!-- Events Table -->
<div class="glass-card p-4">
    <?php if (empty($events)): ?>
        <p class="text-muted text-center py-5">No events found matching current criteria.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-glass mb-0">
                <thead>
                    <tr>
                        <th>Banner</th>
                        <th>Event Details</th>
                        <th>Category</th>
                        <th>DateTime & Venue</th>
                        <th>Seats (Left/Max)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $evt): ?>
                        <tr>
                            <td>
                                <?php if ($evt['event_banner']): ?>
                                    <img src="<?= BASE_URL . e($evt['event_banner']) ?>" alt="Banner" class="rounded" style="width: 70px; height: 45px; object-fit: cover;">
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border py-2"><i class="bi bi-image"></i></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($evt['event_name']) ?></div>
                                <small class="text-muted">Org: <?= e($evt['organizer']) ?></small>
                            </td>
                            <td class="small"><?= e($evt['category_name']) ?></td>
                            <td class="small">
                                <div><?= date('M d, Y', strtotime($evt['date'])) ?> @ <?= date('h:i A', strtotime($evt['time'])) ?></div>
                                <div class="text-secondary"><i class="bi bi-geo-alt me-1"></i><?= e($evt['venue']) ?></div>
                            </td>
                            <td class="small text-center">
                                <strong><?= e($evt['available_seats']) ?></strong> / <?= e($evt['max_participants']) ?>
                            </td>
                            <td>
                                <?php if ($evt['status'] === 'approved'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Approved</span>
                                <?php elseif ($evt['status'] === 'completed'): ?>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill">Completed</span>
                                <?php elseif ($evt['status'] === 'rejected'): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">Rejected</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-light glass-card border-0 btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end border-0 glass-card shadow">
                                        <li><a class="dropdown-item py-1.5" href="event-edit.php?id=<?= $evt['id'] ?>"><i class="bi bi-pencil me-2"></i>Edit Event</a></li>
                                        <?php if ($evt['status'] === 'pending'): ?>
                                            <li><a class="dropdown-item py-1.5 text-success" href="events.php?action=approve&id=<?= $evt['id'] ?>"><i class="bi bi-check2-circle me-2"></i>Approve Event</a></li>
                                            <li><a class="dropdown-item py-1.5 text-danger" href="events.php?action=reject&id=<?= $evt['id'] ?>"><i class="bi bi-x-circle me-2"></i>Reject Event</a></li>
                                        <?php endif; ?>
                                        <?php if ($evt['status'] === 'approved'): ?>
                                            <li><a class="dropdown-item py-1.5 text-info" href="events.php?action=complete&id=<?= $evt['id'] ?>"><i class="bi bi-calendar2-check me-2"></i>Mark Completed</a></li>
                                        <?php endif; ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item py-1.5 text-danger confirm-action" href="events.php?delete=<?= $evt['id'] ?>" data-confirm-msg="Are you sure you want to delete this event? This will delete all registrations!"><i class="bi bi-trash me-2"></i>Delete Event</a></li>
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
