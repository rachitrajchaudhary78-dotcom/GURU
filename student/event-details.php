<?php
/**
 * Event Details, Countdown & Registration Action (Student)
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_student_login();

$studentId = $_SESSION['student_id'];
$eventId = intval($_GET['id'] ?? 0);

if ($eventId <= 0) {
    header("Location: events.php");
    exit();
}

// Fetch event and category details
$event = fetch_one(
    "SELECT e.*, c.name as category_name 
     FROM events e 
     JOIN categories c ON e.category_id = c.id 
     WHERE e.id = ?",
    [$eventId]
);

if (!$event) {
    $_SESSION['redirect_message'] = "Event not found.";
    $_SESSION['redirect_type'] = "danger";
    header("Location: events.php");
    exit();
}

// Check if student is already registered for this event
$registration = fetch_one(
    "SELECT * FROM registrations WHERE student_id = ? AND event_id = ?",
    [$studentId, $eventId]
);

$error = false;
$success = false;

// Handle Registration / Cancellation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF security token mismatch.";
    } else {
        $action = $_POST['action'];
        
        if ($action === 'register') {
            // Checks
            if ($registration) {
                $error = "You are already registered for this event.";
            } elseif ($event['status'] !== 'approved') {
                $error = "Registration is not open for this event.";
            } elseif ($event['available_seats'] <= 0) {
                $error = "No seats available. The event is full.";
            } elseif (strtotime($event['registration_deadline']) < strtotime(date('Y-m-d'))) {
                $error = "The registration deadline has passed.";
            } else {
                // Register
                $ticketCode = 'TKT-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
                
                // Start a transaction to ensure atomic seat count update
                global $conn;
                $conn->beginTransaction();
                
                try {
                    query(
                        "INSERT INTO registrations (student_id, event_id, status, ticket_code) VALUES (?, ?, 'pending', ?)",
                        [$studentId, $eventId, $ticketCode]
                    );
                    query(
                        "UPDATE events SET available_seats = available_seats - 1 WHERE id = ?",
                        [$eventId]
                    );
                    
                    $conn->commit();
                    
                    log_activity("Registered for Event", "Student registered for event: " . $event['event_name'] . " (Ticket: $ticketCode).");
                    
                    // Create confirmation notification
                    query(
                        "INSERT INTO notifications (student_id, title, message) VALUES (?, ?, ?)",
                        [$studentId, 'Event Registration Pending', 'Your registration for "' . $event['event_name'] . '" is pending admin approval.']
                    );
                    
                    $success = "Registration submitted successfully! Wait for Administrator approval.";
                    
                    // Reload data
                    $registration = fetch_one("SELECT * FROM registrations WHERE student_id = ? AND event_id = ?", [$studentId, $eventId]);
                    $event = fetch_one("SELECT e.*, c.name as category_name FROM events e JOIN categories c ON e.category_id = c.id WHERE e.id = ?", [$eventId]);
                } catch (Exception $e) {
                    $conn->rollBack();
                    $error = "Registration failed. Please try again: " . $e->getMessage();
                }
            }
        } elseif ($action === 'cancel') {
            if (!$registration) {
                $error = "You are not registered for this event.";
            } else {
                // Start Transaction
                global $conn;
                $conn->beginTransaction();
                
                try {
                    query(
                        "DELETE FROM registrations WHERE id = ?",
                        [$registration['id']]
                    );
                    // Only restore seat if event wasn't already completed/started
                    if ($event['status'] === 'approved') {
                        query(
                            "UPDATE events SET available_seats = available_seats + 1 WHERE id = ?",
                            [$eventId]
                        );
                    }
                    
                    $conn->commit();
                    
                    log_activity("Cancelled Event Registration", "Student cancelled registration for: " . $event['event_name']);
                    
                    $success = "Your registration has been cancelled successfully.";
                    $registration = null;
                    $event = fetch_one("SELECT e.*, c.name as category_name FROM events e JOIN categories c ON e.category_id = c.id WHERE e.id = ?", [$eventId]);
                } catch (Exception $e) {
                    $conn->rollBack();
                    $error = "Cancellation failed: " . $e->getMessage();
                }
            }
        }
    }
}

// Check if event is completed (history)
$isCompleted = ($event['status'] === 'completed' || strtotime($event['date']) < strtotime(date('Y-m-d')));

// Fetch feedback submitted by this student
$feedback = null;
if ($isCompleted) {
    $feedback = fetch_one("SELECT * FROM feedback WHERE student_id = ? AND event_id = ?", [$studentId, $eventId]);
}

// Generate URL for QR check-in
$qrData = "http://" . $_SERVER['HTTP_HOST'] . BASE_URL . "student/event-details.php?id=" . $eventId;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row g-4">
    <!-- Back & Basic Title Header -->
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <a href="events.php" class="btn btn-premium-secondary btn-sm"><i class="bi bi-arrow-left me-2"></i>Back to Events</a>
            <span class="badge bg-light text-primary border px-3 py-1.5 rounded-pill fw-bold"><?= e($event['category_name']) ?></span>
        </div>
        <h2 class="fw-bold text-dark mt-2" style="font-family: 'Outfit', sans-serif;"><?= e($event['event_name']) ?></h2>
    </div>
    
    <!-- Left Column: Image, Details, Rules, Prizes -->
    <div class="col-lg-8">
        <div class="glass-card overflow-hidden p-0 mb-4">
            <?php if ($event['event_banner']): ?>
                <img src="<?= BASE_URL . e($event['event_banner']) ?>" alt="Event Banner" class="img-fluid w-100" style="max-height: 380px; object-fit: cover;">
            <?php else: ?>
                <div class="bg-primary-subtle d-flex align-items-center justify-content-center text-primary" style="height: 280px;">
                    <i class="bi bi-image display-2"></i>
                </div>
            <?php endif; ?>
            
            <div class="p-4">
                <h5 class="fw-bold mb-3">Event Description</h5>
                <p class="text-muted"><?= nl2br(e($event['description'])) ?></p>
            </div>
        </div>
        
        <!-- Rules & Prizes -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="glass-card p-4 h-100">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-list-task text-primary me-2"></i>Event Rules</h5>
                    <p class="text-muted small mb-0"><?= !empty($event['rules']) ? nl2br(e($event['rules'])) : 'No specific rules listed.' ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="glass-card p-4 h-100">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-trophy text-primary me-2"></i>Prizes & Rewards</h5>
                    <p class="text-muted small mb-0"><?= !empty($event['prizes']) ? nl2br(e($event['prizes'])) : 'Participation certificates for all candidates.' ?></p>
                </div>
            </div>
        </div>
        
        <!-- Feedback Form (If Completed) -->
        <?php if ($isCompleted && $registration && $registration['attendance'] === 'present'): ?>
            <div class="glass-card p-4 mb-4">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-chat-left-heart text-primary me-2"></i>Submit Event Feedback</h5>
                
                <?php if ($feedback): ?>
                    <div class="p-3 bg-light rounded text-muted small">
                        <div class="fw-bold text-dark mb-1">Your Rating: <?= str_repeat('⭐', $feedback['rating']) ?></div>
                        <p class="mb-1">"<?= e($feedback['comment']) ?>"</p>
                        <?php if ($feedback['reply']): ?>
                            <div class="mt-2 p-2 bg-white rounded border-start border-primary border-3">
                                <strong>Organizer Reply:</strong> <?= e($feedback['reply']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <form action="feedback.php" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="event_id" value="<?= $eventId ?>">
                        
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Rating</label>
                                <select name="rating" class="form-select glass-input">
                                    <option value="5">⭐⭐⭐⭐⭐ (Excellent)</option>
                                    <option value="4">⭐⭐⭐⭐ (Good)</option>
                                    <option value="3">⭐⭐⭐ (Average)</option>
                                    <option value="2">⭐⭐ (Fair)</option>
                                    <option value="1">⭐ (Poor)</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small fw-bold">Comment / Suggestions</label>
                                <input type="text" name="comment" class="form-control glass-input" placeholder="What did you like or want improved?" required>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-premium-primary btn-sm"><i class="bi bi-send-fill me-2"></i>Submit Feedback</button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Right Column: Registration Card, Dates, Contacts -->
    <div class="col-lg-4">
        <!-- Status & Registration Actions -->
        <div class="glass-card p-4 mb-4">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Registration Status</h5>
            
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
            
            <!-- Details of Slots -->
            <div class="d-flex justify-content-between mb-2 small text-muted">
                <span>Available Seats:</span>
                <span class="fw-bold text-dark"><?= e($event['available_seats']) ?> / <?= e($event['max_participants']) ?></span>
            </div>
            
            <div class="d-flex justify-content-between mb-4 small text-muted">
                <span>Deadline:</span>
                <span class="fw-bold text-danger"><?= date('M d, Y', strtotime($event['registration_deadline'])) ?></span>
            </div>
            
            <?php if ($isCompleted): ?>
                <div class="alert alert-secondary text-center small border-0 mb-0 py-2.5">
                    <i class="bi bi-calendar-x-fill me-1"></i> This event has concluded.
                </div>
            <?php else: ?>
                <!-- Countdown Timer -->
                <div class="mb-4 text-center">
                    <small class="text-muted d-block mb-1">Time Remaining to Register:</small>
                    <h5 class="fw-bold text-primary" data-countdown="<?= $event['registration_deadline'] . ' 23:59:59' ?>">
                        Calculating...
                    </h5>
                </div>
                
                <form action="event-details.php?id=<?= $eventId ?>" method="POST">
                    <?= csrf_field() ?>
                    <?php if ($registration): ?>
                        <div class="alert alert-info text-center small border-0 py-2.5 mb-3">
                            <i class="bi bi-check-circle-fill me-1"></i> You are Registered! <br>
                            Status: <strong class="text-capitalize"><?= e($registration['status']) ?></strong>
                        </div>
                        <input type="hidden" name="action" value="cancel">
                        <button type="submit" class="btn btn-danger w-100 py-2 confirm-action" data-confirm-msg="Are you sure you want to cancel this registration?"><i class="bi bi-x-circle me-2"></i>Cancel Registration</button>
                    <?php else: ?>
                        <input type="hidden" name="action" value="register">
                        <?php if ($event['available_seats'] > 0 && strtotime($event['registration_deadline']) >= strtotime(date('Y-m-d'))): ?>
                            <button type="submit" class="btn btn-premium-primary w-100 py-2"><i class="bi bi-calendar-check me-2"></i>Register Now</button>
                        <?php else: ?>
                            <button type="button" class="btn btn-secondary w-100 py-2" disabled><i class="bi bi-slash-circle me-2"></i>Registration Closed</button>
                        <?php endif; ?>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- QR Code Ticket Simulation Card (If Registered & Approved) -->
        <?php if ($registration && $registration['status'] === 'approved'): ?>
            <div class="glass-card p-4 mb-4 text-center">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-qr-code text-primary me-2"></i>Your Entry Pass</h5>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=<?= urlencode($registration['ticket_code']) ?>" alt="Ticket QR Code" class="img-thumbnail bg-white p-2 mb-3" style="width: 140px; height: 140px;">
                <div class="small text-muted mb-3">Ticket Code: <strong class="text-dark"><?= e($registration['ticket_code']) ?></strong></div>
                <a href="receipt.php?reg_id=<?= $registration['id'] ?>" target="_blank" class="btn btn-premium-secondary btn-sm w-100"><i class="bi bi-printer me-2"></i>Print Event Pass</a>
            </div>
        <?php endif; ?>
        
        <!-- Venue & Coordinators details -->
        <div class="glass-card p-4">
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">Event Schedule & Crew</h5>
            
            <div class="mb-3 small">
                <i class="bi bi-geo-alt-fill text-primary me-2"></i><strong>Venue:</strong><br>
                <span class="text-muted ms-4"><?= e($event['venue']) ?>, <?= e($event['building']) ?> - Room <?= e($event['room']) ?></span>
            </div>
            
            <div class="mb-3 small">
                <i class="bi bi-clock-fill text-primary me-2"></i><strong>Time:</strong><br>
                <span class="text-muted ms-4"><?= date('h:i A', strtotime($event['time'])) ?> on <?= date('d M Y', strtotime($event['date'])) ?></span>
            </div>
            
            <div class="mb-3 small">
                <i class="bi bi-person-fill-check text-primary me-2"></i><strong>Faculty Coordinator:</strong><br>
                <span class="text-muted ms-4"><?= e($event['faculty_coordinator']) ?></span>
            </div>
            
            <div class="mb-3 small">
                <i class="bi bi-people-fill text-primary me-2"></i><strong>Student Coordinator:</strong><br>
                <span class="text-muted ms-4"><?= e($event['student_coordinator']) ?></span>
            </div>
            
            <div class="small">
                <i class="bi bi-telephone-inbound-fill text-primary me-2"></i><strong>Contact Details:</strong><br>
                <span class="text-muted ms-4"><?= nl2br(e($event['contact_details'])) ?></span>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
