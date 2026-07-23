<?php
/**
 * Student Dashboard
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_student_login();

$studentId = $_SESSION['student_id'];

// Fetch student profile details
$student = fetch_one("SELECT * FROM students WHERE id = ?", [$studentId]);

// Calculate Profile Completion Percentage
$completionScore = 0;
$fieldsToCheck = ['fullname', 'email', 'phone', 'branch', 'year', 'profile_pic'];
foreach ($fieldsToCheck as $f) {
    if (!empty($student[$f])) {
        $completionScore += (100 / count($fieldsToCheck));
    }
}
$completionScore = round($completionScore);

// Fetch notifications (recent 5)
$notifications = fetch_all(
    "SELECT * FROM notifications WHERE student_id = ? OR student_id IS NULL ORDER BY id DESC LIMIT 5",
    [$studentId]
);

// Fetch registrations
$registrations = fetch_all(
    "SELECT r.*, e.event_name, e.date, e.time, e.venue, c.name as category_name 
     FROM registrations r 
     JOIN events e ON r.event_id = e.id 
     JOIN categories c ON e.category_id = c.id
     WHERE r.student_id = ? 
     ORDER BY e.date ASC",
    [$studentId]
);

// Compute Attendance statistics
$totalAttended = 0;
$totalAbsent = 0;
$totalPending = 0;
foreach ($registrations as $r) {
    if ($r['attendance'] === 'present') $totalAttended++;
    elseif ($r['attendance'] === 'absent') $totalAbsent++;
    else $totalPending++;
}

// Fetch general upcoming events (not registered yet)
$allUpcoming = fetch_all(
    "SELECT e.*, c.name as category_name 
     FROM events e 
     JOIN categories c ON e.category_id = c.id 
     WHERE e.status = 'approved' AND e.date >= CURDATE() 
       AND e.id NOT IN (SELECT event_id FROM registrations WHERE student_id = ?) 
     ORDER BY e.date ASC LIMIT 3",
    [$studentId]
);

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
                <a href="dashboard.php" class="btn btn-light glass-card text-start py-2.5 border-0 text-primary fw-semibold"><i class="bi bi-speedometer2 me-3"></i>Dashboard</a>
                <a href="events.php" class="btn btn-light glass-card text-start py-2.5 border-0"><i class="bi bi-search me-3"></i>Browse Events</a>
                <a href="my-events.php" class="btn btn-light glass-card text-start py-2.5 border-0"><i class="bi bi-calendar2-check me-3"></i>My Registrations</a>
                <a href="profile.php" class="btn btn-light glass-card text-start py-2.5 border-0"><i class="bi bi-person-gear me-3"></i>Profile Settings</a>
                <a href="../logout.php" class="btn btn-light glass-card text-start py-2.5 border-0 text-danger confirm-action" data-confirm-msg="Are you sure you want to log out?"><i class="bi bi-box-arrow-right me-3"></i>Logout</a>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="col-lg-9">
        <!-- Welcome Card -->
        <div class="glass-card p-4 mb-4 position-relative overflow-hidden" style="border-left: 5px solid var(--primary-color);">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h3 class="fw-bold text-dark mb-1">Hello, <?= e($student['fullname']) ?>!</h3>
                    <p class="text-muted small mb-0">Welcome to your student control center. Track your upcoming events, download passes, and claim certificates.</p>
                </div>
                <div class="col-md-4 mt-3 mt-md-0">
                    <div class="small fw-semibold mb-1">Profile Completion: <?= $completionScore ?>%</div>
                    <div class="progress" style="height: 8px; border-radius: 4px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $completionScore ?>%;" aria-valuenow="<?= $completionScore ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stats Summary cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="glass-card p-3 stat-card-purple d-flex align-items-center">
                    <div class="p-3 rounded-circle bg-white shadow-sm text-primary me-3">
                        <i class="bi bi-ticket-perforated-fill fs-4"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0 text-dark"><?= count($registrations) ?></h4>
                        <small class="text-muted">Total Registrations</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card p-3 stat-card-green d-flex align-items-center">
                    <div class="p-3 rounded-circle bg-white shadow-sm text-success me-3">
                        <i class="bi bi-person-check-fill fs-4"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0 text-dark"><?= $totalAttended ?></h4>
                        <small class="text-muted">Events Attended</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card p-3 stat-card-blue d-flex align-items-center">
                    <div class="p-3 rounded-circle bg-white shadow-sm text-info me-3">
                        <i class="bi bi-clock-history fs-4"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0 text-dark"><?= $totalPending ?></h4>
                        <small class="text-muted">Pending/Upcoming</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row g-4 mb-4">
            <!-- Registered / Active Events -->
            <div class="col-md-8">
                <div class="glass-card p-4 h-100">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-calendar-event me-2 text-primary"></i>My Registered Events</h5>
                    <?php if (empty($registrations)): ?>
                        <div class="text-center py-5 text-muted small">
                            <i class="bi bi-calendar-x display-4 mb-2 d-block"></i>
                            You are not registered for any events yet.
                            <a href="events.php" class="btn btn-premium-primary btn-sm d-block mt-3 mx-auto" style="max-width: 150px;">Find Events</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-glass mb-0">
                                <thead>
                                    <tr>
                                        <th>Event Name</th>
                                        <th>Date & Venue</th>
                                        <th>Approval</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($registrations as $reg): ?>
                                        <tr>
                                            <td class="fw-bold text-dark small"><?= e($reg['event_name']) ?></td>
                                            <td class="small text-muted">
                                                <div><?= date('M d, Y', strtotime($reg['date'])) ?></div>
                                                <div class="text-secondary"><i class="bi bi-geo-alt me-1"></i><?= e($reg['venue']) ?></div>
                                            </td>
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
                                                <a href="event-details.php?id=<?= $reg['event_id'] ?>" class="btn btn-light btn-sm glass-card border-0 text-primary py-1"><i class="bi bi-eye"></i></a>
                                                <?php if ($reg['status'] === 'approved'): ?>
                                                    <a href="receipt.php?reg_id=<?= $reg['id'] ?>" target="_blank" class="btn btn-light btn-sm glass-card border-0 text-secondary py-1" data-bs-toggle="tooltip" title="Download Event Pass"><i class="bi bi-ticket-detailed"></i></a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Calendar Widget -->
            <div class="col-md-4">
                <div class="glass-card p-4 h-100 text-center">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-calendar3 me-2 text-primary"></i>Event Calendar</h5>
                    <div class="p-3 bg-white rounded shadow-sm">
                        <!-- Render a mini-calendar for the current month -->
                        <?php
                        $month = date('n');
                        $year = date('Y');
                        $numDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                        $firstDay = date('N', strtotime("$year-$month-01"));
                        
                        // Fetch days in this month where user has registered events
                        $registeredDays = [];
                        foreach ($registrations as $r) {
                            $rDate = strtotime($r['date']);
                            if (date('n', $rDate) == $month && date('Y', $rDate) == $year) {
                                $registeredDays[] = intval(date('j', $rDate));
                            }
                        }
                        ?>
                        <div class="fw-bold text-dark mb-2"><?= date('F Y') ?></div>
                        <div class="row g-1 fw-semibold text-muted small border-bottom pb-1 mb-1">
                            <div class="col" style="width: 14%;">M</div>
                            <div class="col" style="width: 14%;">T</div>
                            <div class="col" style="width: 14%;">W</div>
                            <div class="col" style="width: 14%;">T</div>
                            <div class="col" style="width: 14%;">F</div>
                            <div class="col" style="width: 14%;">S</div>
                            <div class="col" style="width: 14%;">S</div>
                        </div>
                        <div class="row g-1 small">
                            <?php 
                            // Empty blocks before first day
                            for ($i = 1; $i < $firstDay; $i++) {
                                echo '<div class="col" style="width: 14%;"></div>';
                            }
                            // Days of month
                            for ($day = 1; $day <= $numDays; $day++) {
                                $isEvent = in_array($day, $registeredDays);
                                $isToday = ($day == date('j'));
                                
                                $class = "col p-1 ";
                                $style = "width: 14%; border-radius: 4px;";
                                
                                if ($isEvent) {
                                    $class .= "bg-primary text-white fw-bold";
                                } elseif ($isToday) {
                                    $class .= "bg-info text-white";
                                } else {
                                    $class .= "text-dark";
                                }
                                
                                echo "<div class='$class' style='$style'>$day</div>";
                                
                                // Break to next line if Sunday
                                if (($day + $firstDay - 1) % 7 == 0) {
                                    echo '</div><div class="row g-1 small mt-1">';
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-3"><span class="badge bg-primary me-1">&nbsp;</span> Registered Event Day</small>
                </div>
            </div>
        </div>
        
        <div class="row g-4">
            <!-- Recent Notifications -->
            <div class="col-md-6">
                <div class="glass-card p-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-bell me-2 text-primary"></i>Recent Notifications</h5>
                    <?php if (empty($notifications)): ?>
                        <p class="text-muted small">No new notifications.</p>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($notifications as $notif): ?>
                                <div class="p-3 rounded bg-white shadow-sm border-start border-primary border-3 small">
                                    <div class="fw-bold text-dark mb-1"><?= e($notif['title']) ?></div>
                                    <p class="text-muted mb-0"><?= e($notif['message']) ?></p>
                                    <small class="text-secondary block mt-1 text-end d-block" style="font-size: 0.75rem;"><?= date('M d, h:i A', strtotime($notif['created_at'])) ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recommendations / Other Events -->
            <div class="col-md-6">
                <div class="glass-card p-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-arrow-right-circle me-2 text-primary"></i>Recommended Events</h5>
                    <?php if (empty($allUpcoming)): ?>
                        <p class="text-muted small">No other events currently open.</p>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($allUpcoming as $eItem): ?>
                                <div class="d-flex align-items-center justify-content-between p-2.5 rounded bg-white shadow-sm">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1 small"><?= e($eItem['event_name']) ?></h6>
                                        <small class="text-muted"><i class="bi bi-calendar3 me-1"></i><?= date('M d', strtotime($eItem['date'])) ?> | <?= e($eItem['venue']) ?></small>
                                    </div>
                                    <a href="event-details.php?id=<?= $eItem['id'] ?>" class="btn btn-premium-primary btn-sm px-2 py-1"><i class="bi bi-arrow-right"></i></a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
