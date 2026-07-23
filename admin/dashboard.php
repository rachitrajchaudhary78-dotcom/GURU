<?php
/**
 * Admin Panel Dashboard (Analytics, Metrics & Charts)
 */
require_once __DIR__ . '/admin_header.php';

// 1. Fetch Stats Counts
$cntStudents = fetch_one("SELECT COUNT(*) as count FROM students")['count'] ?? 0;
$cntEvents = fetch_one("SELECT COUNT(*) as count FROM events")['count'] ?? 0;
$cntRegistrations = fetch_one("SELECT COUNT(*) as count FROM registrations")['count'] ?? 0;
$cntActiveEvents = fetch_one("SELECT COUNT(*) as count FROM events WHERE status = 'approved' AND date >= CURDATE()")['count'] ?? 0;
$cntCompletedEvents = fetch_one("SELECT COUNT(*) as count FROM events WHERE status = 'completed' OR date < CURDATE()")['count'] ?? 0;
$cntPendingEvents = fetch_one("SELECT COUNT(*) as count FROM events WHERE status = 'pending'")['count'] ?? 0;
$cntFeedback = fetch_one("SELECT COUNT(*) as count FROM feedback")['count'] ?? 0;
$cntCertificates = fetch_one("SELECT COUNT(*) as count FROM certificates")['count'] ?? 0;

// 2. Fetch Lists
$recentRegistrations = fetch_all(
    "SELECT r.*, s.fullname, s.student_id, e.event_name 
     FROM registrations r 
     JOIN students s ON r.student_id = s.id 
     JOIN events e ON r.event_id = e.id 
     ORDER BY r.id DESC LIMIT 5"
);

$recentStudents = fetch_all(
    "SELECT * FROM students ORDER BY id DESC LIMIT 5"
);

$pendingApprovals = fetch_all(
    "SELECT r.*, s.fullname, s.student_id, e.event_name, e.date 
     FROM registrations r 
     JOIN students s ON r.student_id = s.id 
     JOIN events e ON r.event_id = e.id 
     WHERE r.status = 'pending' 
     ORDER BY r.id ASC LIMIT 5"
);

$latestFeedback = fetch_all(
    "SELECT f.*, s.fullname, e.event_name 
     FROM feedback f 
     JOIN students s ON f.student_id = s.id 
     JOIN events e ON f.event_id = e.id 
     ORDER BY f.id DESC LIMIT 5"
);

// 3. Prepare Chart Data via Database Queries
// A. Category Distribution
$catData = fetch_all(
    "SELECT c.name, COUNT(e.id) as count 
     FROM categories c 
     LEFT JOIN events e ON e.category_id = c.id 
     GROUP BY c.id"
);
$catLabels = [];
$catValues = [];
foreach ($catData as $row) {
    if ($row['count'] > 0) {
        $catLabels[] = $row['name'];
        $catValues[] = intval($row['count']);
    }
}

// B. Monthly Registrations
$monthlyData = fetch_all(
    "SELECT DATE_FORMAT(registration_date, '%b %Y') as month, COUNT(id) as count 
     FROM registrations 
     GROUP BY YEAR(registration_date), MONTH(registration_date) 
     ORDER BY registration_date ASC LIMIT 12"
);
$monthLabels = [];
$monthValues = [];
foreach ($monthlyData as $row) {
    $monthLabels[] = $row['month'];
    $monthValues[] = intval($row['count']);
}

// C. Student Participation by Department/Branch
$branchData = fetch_all(
    "SELECT s.branch, COUNT(r.id) as count 
     FROM students s 
     JOIN registrations r ON s.id = r.student_id 
     GROUP BY s.branch"
);
$branchLabels = [];
$branchValues = [];
foreach ($branchData as $row) {
    $branchLabels[] = $row['branch'] ?? 'Other';
    $branchValues[] = intval($row['count']);
}

// D. Attendance stats
$attnData = fetch_all(
    "SELECT attendance, COUNT(id) as count 
     FROM registrations 
     GROUP BY attendance"
);
$attnLabels = ['Present', 'Absent', 'Pending'];
$attnValues = [0, 0, 0];
foreach ($attnData as $row) {
    if ($row['attendance'] === 'present') $attnValues[0] = intval($row['count']);
    elseif ($row['attendance'] === 'absent') $attnValues[1] = intval($row['count']);
    else $attnValues[2] = intval($row['count']);
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-dark mb-0">System Overview & Analytics</h3>
    <span class="badge bg-primary-subtle text-primary border px-3 py-2 rounded-pill"><i class="bi bi-clock me-2"></i>Live Server Stats</span>
</div>

<!-- Stats Cards Grid -->
<div class="row g-3 mb-4">
    <!-- Total Students -->
    <div class="col-xl-3 col-sm-6">
        <div class="glass-card p-3 stat-card-purple d-flex align-items-center">
            <div class="p-3 rounded-circle bg-white shadow-sm text-primary me-3">
                <i class="bi bi-people-fill fs-4"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0 text-dark"><?= $cntStudents ?></h4>
                <small class="text-muted">Total Students</small>
            </div>
        </div>
    </div>
    
    <!-- Total Registrations -->
    <div class="col-xl-3 col-sm-6">
        <div class="glass-card p-3 stat-card-blue d-flex align-items-center">
            <div class="p-3 rounded-circle bg-white shadow-sm text-info me-3">
                <i class="bi bi-check2-all fs-4"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0 text-dark"><?= $cntRegistrations ?></h4>
                <small class="text-muted">Total Registrations</small>
            </div>
        </div>
    </div>
    
    <!-- Active Events -->
    <div class="col-xl-3 col-sm-6">
        <div class="glass-card p-3 stat-card-green d-flex align-items-center">
            <div class="p-3 rounded-circle bg-white shadow-sm text-success me-3">
                <i class="bi bi-calendar2-check-fill fs-4"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0 text-dark"><?= $cntActiveEvents ?></h4>
                <small class="text-muted">Active Events</small>
            </div>
        </div>
    </div>
    
    <!-- Pending Approvals -->
    <div class="col-xl-3 col-sm-6">
        <div class="glass-card p-3 stat-card-red d-flex align-items-center">
            <div class="p-3 rounded-circle bg-white shadow-sm text-danger me-3">
                <i class="bi bi-exclamation-circle-fill fs-4"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0 text-dark"><?= $cntPendingEvents ?></h4>
                <small class="text-muted">Pending Events</small>
            </div>
        </div>
    </div>
</div>

<!-- Secondary Stats Row -->
<div class="row g-3 mb-5">
    <div class="col-md-4">
        <div class="glass-card p-3 bg-white d-flex align-items-center">
            <div class="p-2.5 rounded bg-primary-subtle text-primary me-3">
                <i class="bi bi-award-fill fs-5"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-0 text-dark"><?= $cntCertificates ?></h5>
                <small class="text-muted">Certificates Generated</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass-card p-3 bg-white d-flex align-items-center">
            <div class="p-2.5 rounded bg-info-subtle text-info me-3">
                <i class="bi bi-chat-heart-fill fs-5"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-0 text-dark"><?= $cntFeedback ?></h5>
                <small class="text-muted">Feedback Received</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass-card p-3 bg-white d-flex align-items-center">
            <div class="p-2.5 rounded bg-success-subtle text-success me-3">
                <i class="bi bi-check-circle-fill fs-5"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-0 text-dark"><?= $cntCompletedEvents ?></h5>
                <small class="text-muted">Completed Events</small>
            </div>
        </div>
    </div>
</div>

<!-- Charts Grid -->
<div class="row g-4 mb-5">
    <div class="col-lg-6">
        <div class="glass-card p-4">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-graph-up me-2 text-primary"></i>Monthly Registrations</h5>
            <canvas id="chartMonthly" style="max-height: 250px;"></canvas>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="glass-card p-4">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-pie-chart me-2 text-primary"></i>Event Categories</h5>
            <canvas id="chartCategories" style="max-height: 250px;"></canvas>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="glass-card p-4">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-bar-chart me-2 text-primary"></i>Student Participation (Branch)</h5>
            <canvas id="chartParticipation" style="max-height: 250px;"></canvas>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="glass-card p-4">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-pie-chart-fill me-2 text-primary"></i>Attendance Statistics</h5>
            <canvas id="chartAttendance" style="max-height: 250px;"></canvas>
        </div>
    </div>
</div>

<!-- Recent Submissions and Approvals lists -->
<div class="row g-4 mb-4">
    <!-- Pending Approvals -->
    <div class="col-lg-6">
        <div class="glass-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-hourglass me-2 text-warning"></i>Pending Registrations</h5>
                <a href="registrations.php" class="btn btn-light glass-card border-0 btn-sm text-primary">View All</a>
            </div>
            
            <?php if (empty($pendingApprovals)): ?>
                <p class="text-muted small py-4 text-center">No registrations waiting for approval.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-glass small mb-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Event</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingApprovals as $pend): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= e($pend['fullname']) ?></div>
                                        <small class="text-muted"><?= e($pend['student_id']) ?></small>
                                    </td>
                                    <td><?= e($pend['event_name']) ?></td>
                                    <td>
                                        <form action="registrations.php" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="reg_id" value="<?= $pend['id'] ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-success btn-sm p-1 px-2 mb-1" title="Approve"><i class="bi bi-check-lg"></i></button>
                                            <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm p-1 px-2 mb-1" title="Reject"><i class="bi bi-x-lg"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Latest Feedback -->
    <div class="col-lg-6">
        <div class="glass-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-chat-left-heart me-2 text-info"></i>Latest Reviews</h5>
                <a href="feedback.php" class="btn btn-light glass-card border-0 btn-sm text-primary">View All</a>
            </div>
            
            <?php if (empty($latestFeedback)): ?>
                <p class="text-muted small py-4 text-center">No feedbacks received yet.</p>
            <?php else: ?>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($latestFeedback as $fb): ?>
                        <div class="p-3 bg-white rounded shadow-sm border-start border-primary border-3 small">
                            <div class="d-flex justify-content-between">
                                <strong><?= e($fb['fullname']) ?></strong>
                                <span class="text-warning"><?= str_repeat('★', $fb['rating']) ?></span>
                            </div>
                            <small class="text-muted block d-block mb-1">On: <em><?= e($fb['event_name']) ?></em></small>
                            <p class="mb-0 text-secondary italic">"<?= e($fb['comment']) ?>"</p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart Configurations JavaScript -->
<script>
$(document).ready(function() {
    // 1. Monthly Registrations
    var ctxMonthly = document.getElementById('chartMonthly').getContext('2d');
    new Chart(ctxMonthly, {
        type: 'line',
        data: {
            labels: <?= json_encode($monthLabels) ?>,
            datasets: [{
                label: 'Registrations',
                data: <?= json_encode($monthValues) ?>,
                borderColor: '#7c3aed',
                backgroundColor: 'rgba(124, 58, 237, 0.05)',
                borderWidth: 3,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // 2. Category Distribution
    var ctxCat = document.getElementById('chartCategories').getContext('2d');
    new Chart(ctxCat, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($catLabels) ?>,
            datasets: [{
                data: <?= json_encode($catValues) ?>,
                backgroundColor: ['#7c3aed', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#ec4899', '#6366f1']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // 3. Branch/Department Participation
    var ctxPart = document.getElementById('chartParticipation').getContext('2d');
    new Chart(ctxPart, {
        type: 'bar',
        data: {
            labels: <?= json_encode($branchLabels) ?>,
            datasets: [{
                data: <?= json_encode($branchValues) ?>,
                backgroundColor: '#0ea5e9',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // 4. Attendance Stats
    var ctxAttn = document.getElementById('chartAttendance').getContext('2d');
    new Chart(ctxAttn, {
        type: 'pie',
        data: {
            labels: <?= json_encode($attnLabels) ?>,
            datasets: [{
                data: <?= json_encode($attnValues) ?>,
                backgroundColor: ['#10b981', '#ef4444', '#f59e0b']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
});
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
