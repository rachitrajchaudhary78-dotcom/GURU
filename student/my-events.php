<?php
/**
 * Student's Registered Events & Certificates
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_student_login();

$studentId = $_SESSION['student_id'];
$student = fetch_one("SELECT * FROM students WHERE id = ?", [$studentId]);

// Fetch registrations
$registrations = fetch_all(
    "SELECT r.*, e.event_name, e.date, e.time, e.venue, e.status as event_status, c.name as category_name, cert.certificate_code
     FROM registrations r 
     JOIN events e ON r.event_id = e.id 
     JOIN categories c ON e.category_id = c.id
     LEFT JOIN certificates cert ON r.id = cert.registration_id
     WHERE r.student_id = ? 
     ORDER BY e.date DESC",
    [$studentId]
);

$upcomingRegs = [];
$completedRegs = [];

foreach ($registrations as $reg) {
    $eventDate = strtotime($reg['date']);
    $isPast = ($eventDate < strtotime(date('Y-m-d')) || $reg['event_status'] === 'completed');
    
    if ($isPast) {
        $completedRegs[] = $reg;
    } else {
        $upcomingRegs[] = $reg;
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
                <a href="my-events.php" class="btn btn-light glass-card text-start py-2.5 border-0 text-primary fw-semibold"><i class="bi bi-calendar2-check me-3"></i>My Registrations</a>
                <a href="profile.php" class="btn btn-light glass-card text-start py-2.5 border-0"><i class="bi bi-person-gear me-3"></i>Profile Settings</a>
                <a href="../logout.php" class="btn btn-light glass-card text-start py-2.5 border-0 text-danger confirm-action" data-confirm-msg="Are you sure you want to log out?"><i class="bi bi-box-arrow-right me-3"></i>Logout</a>
            </div>
        </div>
    </div>
    
    <!-- main panel -->
    <div class="col-lg-9">
        <h4 class="fw-bold text-dark mb-4"><i class="bi bi-calendar2-check me-2 text-primary"></i>My Event Registrations</h4>
        
        <!-- Tab Navigation -->
        <ul class="nav nav-pills mb-4 d-flex gap-2" id="eventTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="btn btn-premium-primary px-4 py-2 active" id="upcoming-tab" data-bs-toggle="pill" data-bs-target="#upcoming" type="button" role="tab"><i class="bi bi-hourglass-split me-2"></i>Upcoming Events (<?= count($upcomingRegs) ?>)</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="btn btn-premium-secondary px-4 py-2" id="completed-tab" data-bs-toggle="pill" data-bs-target="#completed" type="button" role="tab"><i class="bi bi-check2-circle me-2"></i>Completed History (<?= count($completedRegs) ?>)</button>
            </li>
        </ul>
        
        <div class="tab-content" id="eventTabsContent">
            <!-- Tab 1: Upcoming -->
            <div class="tab-pane fade show active" id="upcoming" role="tabpanel">
                <div class="glass-card p-4">
                    <?php if (empty($upcomingRegs)): ?>
                        <div class="text-center py-5 text-muted small">
                            <i class="bi bi-ticket-detailed display-4 mb-2 d-block"></i>
                            No upcoming registrations. Find exciting events happening soon.
                            <a href="events.php" class="btn btn-premium-primary btn-sm mt-3 d-block mx-auto" style="max-width: 150px;">Browse Events</a>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($upcomingRegs as $reg): ?>
                                <div class="col-md-6">
                                    <div class="glass-card p-4 border-start border-primary border-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="badge bg-light text-primary rounded-pill small"><?= e($reg['category_name']) ?></span>
                                            
                                            <?php if ($reg['status'] === 'approved'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small">Approved</span>
                                            <?php elseif ($reg['status'] === 'rejected'): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill small">Rejected</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill small">Pending Approval</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <h6 class="fw-bold text-dark mb-1"><?= e($reg['event_name']) ?></h6>
                                        <div class="text-muted small mb-3">
                                            <div><i class="bi bi-calendar3 text-primary me-2"></i><?= date('M d, Y', strtotime($reg['date'])) ?></div>
                                            <div class="mt-1"><i class="bi bi-geo-alt text-primary me-2"></i><?= e($reg['venue']) ?></div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                            <a href="event-details.php?id=<?= $reg['event_id'] ?>" class="btn btn-light glass-card border-0 btn-sm text-primary"><i class="bi bi-eye me-1"></i>Details</a>
                                            
                                            <?php if ($reg['status'] === 'approved'): ?>
                                                <a href="receipt.php?reg_id=<?= $reg['id'] ?>" target="_blank" class="btn btn-premium-primary btn-sm"><i class="bi bi-ticket-perforated me-1"></i>Get Pass</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Tab 2: Completed -->
            <div class="tab-pane fade" id="completed" role="tabpanel">
                <div class="glass-card p-4">
                    <?php if (empty($completedRegs)): ?>
                        <div class="text-center py-5 text-muted small">
                            <i class="bi bi-calendar-x display-4 mb-2 d-block"></i>
                            No past events history recorded.
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($completedRegs as $reg): ?>
                                <div class="col-md-6">
                                    <div class="glass-card p-4 border-start border-secondary border-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="badge bg-light text-secondary rounded-pill small"><?= e($reg['category_name']) ?></span>
                                            
                                            <?php if ($reg['attendance'] === 'present'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small">Attended</span>
                                            <?php elseif ($reg['attendance'] === 'absent'): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill small">Absent</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border rounded-pill small">Concluded</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <h6 class="fw-bold text-dark mb-1"><?= e($reg['event_name']) ?></h6>
                                        <div class="text-muted small mb-3">
                                            <div><i class="bi bi-calendar-check me-2 text-secondary"></i><?= date('M d, Y', strtotime($reg['date'])) ?></div>
                                            <div class="mt-1"><i class="bi bi-award me-2 text-secondary"></i>Status: <?= e($reg['attendance']) ?></div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                            <a href="event-details.php?id=<?= $reg['event_id'] ?>" class="btn btn-light glass-card border-0 btn-sm text-primary"><i class="bi bi-chat-left-text me-1"></i>Feedback / Info</a>
                                            
                                            <?php if ($reg['attendance'] === 'present' && $reg['certificate_status'] === 'generated'): ?>
                                                <a href="certificate.php?reg_id=<?= $reg['id'] ?>" target="_blank" class="btn btn-premium-primary btn-sm bg-gradient-success"><i class="bi bi-download me-1"></i>Certificate</a>
                                            <?php elseif ($reg['attendance'] === 'present'): ?>
                                                <span class="text-muted small italic" data-bs-toggle="tooltip" title="Admin is preparing certificates"><i class="bi bi-hourglass-split me-1"></i>Certificate Pending</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Basic pill toggle switching class to support active look
    $('#eventTabs button').on('click', function (e) {
        e.preventDefault();
        $('#eventTabs button').removeClass('active btn-premium-primary').addClass('btn-premium-secondary');
        $(this).addClass('active btn-premium-primary').removeClass('btn-premium-secondary');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
