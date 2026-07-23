<?php
/**
 * Browse Events with Search & Filters (Student)
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_student_login();

$studentId = $_SESSION['student_id'];
$student = fetch_one("SELECT * FROM students WHERE id = ?", [$studentId]);

// Fetch all categories for filter dropdown
$categories = fetch_all("SELECT * FROM categories ORDER BY name ASC");

// Parse search and filters
$search = sanitize_input($_GET['search'] ?? '');
$categoryFilter = intval($_GET['category'] ?? 0);
$timeline = sanitize_input($_GET['timeline'] ?? 'upcoming'); // upcoming, today, this_week, completed, all
$regStatus = sanitize_input($_GET['reg_status'] ?? 'all'); // all, open, closed

// Build dynamic SQL
$sql = "SELECT e.*, c.name as category_name 
        FROM events e 
        JOIN categories c ON e.category_id = c.id 
        WHERE 1=1";
$params = [];

// Apply Search
if (!empty($search)) {
    $sql .= " AND (e.event_name LIKE ? OR e.description LIKE ? OR e.venue LIKE ? OR e.organizer LIKE ?)";
    $searchWildcard = "%$search%";
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
}

// Apply Category Filter
if ($categoryFilter > 0) {
    $sql .= " AND e.category_id = ?";
    $params[] = $categoryFilter;
}

// Apply Timeline Filter
if ($timeline === 'today') {
    $sql .= " AND e.date = CURDATE()";
} elseif ($timeline === 'this_week') {
    $sql .= " AND YEARWEEK(e.date, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($timeline === 'upcoming') {
    $sql .= " AND e.date >= CURDATE() AND e.status = 'approved'";
} elseif ($timeline === 'completed') {
    $sql .= " AND (e.status = 'completed' OR e.date < CURDATE())";
}

// Apply Registration Status
if ($regStatus === 'open') {
    $sql .= " AND e.available_seats > 0 AND e.registration_deadline >= CURDATE() AND e.status = 'approved'";
} elseif ($regStatus === 'closed') {
    $sql .= " AND (e.available_seats = 0 OR e.registration_deadline < CURDATE() OR e.status != 'approved')";
}

// Sort order
$sql .= " ORDER BY e.date ASC";

$events = fetch_all($sql, $params);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
    <!-- Sidebar Navigation -->
    <div class="col-lg-3">
        <div class="glass-card p-4 mb-4">
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
                <a href="events.php" class="btn btn-light glass-card text-start py-2.5 border-0 text-primary fw-semibold"><i class="bi bi-search me-3"></i>Browse Events</a>
                <a href="my-events.php" class="btn btn-light glass-card text-start py-2.5 border-0"><i class="bi bi-calendar2-check me-3"></i>My Registrations</a>
                <a href="profile.php" class="btn btn-light glass-card text-start py-2.5 border-0"><i class="bi bi-person-gear me-3"></i>Profile Settings</a>
                <a href="../logout.php" class="btn btn-light glass-card text-start py-2.5 border-0 text-danger confirm-action" data-confirm-msg="Are you sure you want to log out?"><i class="bi bi-box-arrow-right me-3"></i>Logout</a>
            </div>
        </div>
        
        <!-- Filters Sidebar Widget -->
        <div class="glass-card p-4">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-sliders me-2 text-primary"></i>Filters</h5>
            
            <form action="events.php" method="GET">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Search</label>
                    <input type="text" name="search" class="form-control glass-input" placeholder="Event, venue, org..." value="<?= e($search) ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Category</label>
                    <select name="category" class="form-select glass-input">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Date & Timeline</label>
                    <select name="timeline" class="form-select glass-input">
                        <option value="all" <?= $timeline === 'all' ? 'selected' : '' ?>>All Events</option>
                        <option value="upcoming" <?= $timeline === 'upcoming' ? 'selected' : '' ?>>Upcoming Events</option>
                        <option value="today" <?= $timeline === 'today' ? 'selected' : '' ?>>Today's Events</option>
                        <option value="this_week" <?= $timeline === 'this_week' ? 'selected' : '' ?>>This Week</option>
                        <option value="completed" <?= $timeline === 'completed' ? 'selected' : '' ?>>Completed / History</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="form-label small fw-bold">Registration Status</label>
                    <select name="reg_status" class="form-select glass-input">
                        <option value="all" <?= $regStatus === 'all' ? 'selected' : '' ?>>All Slots</option>
                        <option value="open" <?= $regStatus === 'open' ? 'selected' : '' ?>>Registration Open</option>
                        <option value="closed" <?= $regStatus === 'closed' ? 'selected' : '' ?>>Registration Closed</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-premium-primary w-100 mb-2"><i class="bi bi-funnel me-2"></i>Apply Filters</button>
                <a href="events.php" class="btn btn-premium-secondary w-100">Clear Filters</a>
            </form>
        </div>
    </div>
    
    <!-- Main Content Area -->
    <div class="col-lg-9">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold text-dark mb-0">Browse Events (<?= count($events) ?>)</h4>
            <div class="small text-muted">Showing filter: <strong class="text-primary"><?= e(ucfirst($timeline)) ?></strong></div>
        </div>
        
        <div class="row g-4">
            <?php if (empty($events)): ?>
                <div class="col-12">
                    <div class="glass-card p-5 text-center">
                        <i class="bi bi-search display-4 text-muted mb-3"></i>
                        <h4 class="fw-bold text-dark">No Events Match Your Filters</h4>
                        <p class="text-muted mb-0">Try clearing some filters or search query to explore more events.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="glass-card h-100 overflow-hidden d-flex flex-column hover-card-lift">
                            <?php if ($event['event_banner']): ?>
                                <img src="<?= BASE_URL . e($event['event_banner']) ?>" alt="Event Banner" style="height: 150px; object-fit: cover; width: 100%;">
                            <?php else: ?>
                                <div class="bg-primary-subtle d-flex align-items-center justify-content-center" style="height: 150px;">
                                    <i class="bi bi-image text-primary fs-1"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="p-4 d-flex flex-column flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-light text-primary px-2.5 py-1 rounded-pill small"><?= e($event['category_name']) ?></span>
                                    <?php if ($event['available_seats'] > 0 && strtotime($event['registration_deadline']) >= time()): ?>
                                        <span class="badge bg-success-subtle text-success rounded-pill small">Open</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger rounded-pill small">Closed</span>
                                    <?php endif; ?>
                                </div>
                                
                                <h6 class="fw-bold text-dark mb-2"><?= e($event['event_name']) ?></h6>
                                <p class="text-muted small mb-3 flex-grow-1"><?= e(substr($event['description'], 0, 90)) ?>...</p>
                                
                                <div class="border-top pt-3 mt-auto">
                                    <div class="small text-muted mb-2">
                                        <i class="bi bi-calendar-event me-2 text-primary"></i><?= date('M d, Y', strtotime($event['date'])) ?>
                                    </div>
                                    <div class="small text-muted mb-3">
                                        <i class="bi bi-geo-alt me-2 text-primary"></i><?= e($event['venue']) ?>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="small fw-bold text-primary"><i class="bi bi-people-fill me-1"></i><?= e($event['available_seats']) ?> seats left</span>
                                        <a href="event-details.php?id=<?= $event['id'] ?>" class="btn btn-premium-primary btn-sm px-3 py-1">View Details</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
