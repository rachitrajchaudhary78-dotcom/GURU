<?php
/**
 * Admin Panel - Edit Campus Event
 */
require_once __DIR__ . '/admin_header.php';

$eventId = intval($_GET['id'] ?? 0);
if ($eventId <= 0) {
    header("Location: events.php");
    exit();
}

$error = false;
$success = false;

// Fetch current event details
$event = fetch_one("SELECT * FROM events WHERE id = ?", [$eventId]);
if (!$event) {
    $_SESSION['redirect_message'] = "Event not found.";
    $_SESSION['redirect_type'] = "danger";
    header("Location: events.php");
    exit();
}

// Fetch active categories
$categories = fetch_all("SELECT * FROM categories ORDER BY name ASC");

// Count current registrations
$regCount = fetch_one("SELECT COUNT(*) as count FROM registrations WHERE event_id = ? AND status = 'approved'", [$eventId])['count'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "CSRF security token mismatch.";
    } else {
        // Collect & Sanitize
        $eventName = sanitize_input($_POST['event_name'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $categoryId = intval($_POST['category_id'] ?? 0);
        $venue = sanitize_input($_POST['venue'] ?? '');
        $building = sanitize_input($_POST['building'] ?? '');
        $room = sanitize_input($_POST['room'] ?? '');
        $date = sanitize_input($_POST['date'] ?? '');
        $time = sanitize_input($_POST['time'] ?? '');
        $organizer = sanitize_input($_POST['organizer'] ?? '');
        $facultyCoordinator = sanitize_input($_POST['faculty_coordinator'] ?? '');
        $studentCoordinator = sanitize_input($_POST['student_coordinator'] ?? '');
        $maxParticipants = intval($_POST['max_participants'] ?? 0);
        $registrationDeadline = sanitize_input($_POST['registration_deadline'] ?? '');
        $rules = sanitize_input($_POST['rules'] ?? '');
        $prizes = sanitize_input($_POST['prizes'] ?? '');
        $contactDetails = sanitize_input($_POST['contact_details'] ?? '');
        $status = sanitize_input($_POST['status'] ?? 'pending');

        // Validations
        if (empty($eventName) || empty($description) || $categoryId <= 0 || empty($venue) || empty($date) || empty($time) || $maxParticipants <= 0 || empty($registrationDeadline)) {
            $error = "All marked mandatory fields (*) are required.";
        } elseif (strtotime($registrationDeadline) > strtotime($date)) {
            $error = "Registration deadline cannot be after the event date.";
        } elseif ($maxParticipants < $regCount) {
            $error = "Maximum participants cannot be less than the current approved registrations ($regCount).";
        } else {
            // Upload Banner
            $bannerPath = $event['event_banner'];
            if (isset($_FILES['event_banner']) && $_FILES['event_banner']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['event_banner']['tmp_name'];
                $fileName = $_FILES['event_banner']['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array($fileExtension, $allowedExtensions)) {
                    // Delete old banner
                    if ($bannerPath && file_exists(__DIR__ . '/../' . $bannerPath)) {
                        unlink(__DIR__ . '/../' . $bannerPath);
                    }
                    
                    $newFileName = 'banner_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
                    $uploadFileDir = __DIR__ . '/../uploads/event_banners/';
                    if (!is_dir($uploadFileDir)) {
                        mkdir($uploadFileDir, 0777, true);
                    }
                    $destPath = $uploadFileDir . $newFileName;
                    if (move_uploaded_file($fileTmpPath, $destPath)) {
                        $bannerPath = 'uploads/event_banners/' . $newFileName;
                    }
                }
            }

            // Calculate adjusted seats
            $maxDiff = $maxParticipants - $event['max_participants'];
            $newAvailableSeats = $event['available_seats'] + $maxDiff;

            try {
                query(
                    "UPDATE events 
                     SET event_name = ?, description = ?, category_id = ?, venue = ?, building = ?, room = ?, date = ?, time = ?, organizer = ?, faculty_coordinator = ?, student_coordinator = ?, max_participants = ?, registration_deadline = ?, event_banner = ?, rules = ?, prizes = ?, contact_details = ?, status = ?, available_seats = ? 
                     WHERE id = ?",
                    [$eventName, $description, $categoryId, $venue, $building, $room, $date, $time, $organizer, $facultyCoordinator, $studentCoordinator, $maxParticipants, $registrationDeadline, $bannerPath, $rules, $prizes, $contactDetails, $status, $newAvailableSeats, $eventId]
                );

                log_activity("Updated Event", "Admin modified details of event: $eventName.");
                
                $_SESSION['redirect_message'] = "Event details updated successfully!";
                $_SESSION['redirect_type'] = "success";
                header("Location: events.php");
                exit();
            } catch (PDOException $e) {
                $error = "Failed to update event details: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-dark mb-0">Edit Event: <?= e($event['event_name']) ?></h3>
    <a href="events.php" class="btn btn-premium-secondary btn-sm"><i class="bi bi-chevron-left me-1"></i>Back to List</a>
</div>

<div class="glass-card p-5">
    <?php if ($error): ?>
        <div class="alert alert-danger border-0 glass-card text-danger small py-2.5 mb-4">
            <i class="bi bi-exclamation-octagon-fill me-2"></i><?= e($error) ?>
        </div>
    <?php endif; ?>

    <form action="event-edit.php?id=<?= $eventId ?>" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        
        <div class="row g-4">
            <!-- Event Name -->
            <div class="col-md-8">
                <label class="form-label small fw-bold">Event Name *</label>
                <input type="text" name="event_name" class="form-control glass-input" value="<?= e($event['event_name']) ?>" required>
            </div>
            
            <!-- Category -->
            <div class="col-md-4">
                <label class="form-label small fw-bold">Category *</label>
                <select name="category_id" class="form-select glass-input" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $event['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Description -->
            <div class="col-12">
                <label class="form-label small fw-bold">Event Description *</label>
                <textarea name="description" rows="4" class="form-control glass-input" required><?= e($event['description']) ?></textarea>
            </div>
            
            <!-- Venue & Room -->
            <div class="col-md-4">
                <label class="form-label small fw-bold">Venue / Lab *</label>
                <input type="text" name="venue" class="form-control glass-input" value="<?= e($event['venue']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Building</label>
                <input type="text" name="building" class="form-control glass-input" value="<?= e($event['building']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Room</label>
                <input type="text" name="room" class="form-control glass-input" value="<?= e($event['room']) ?>">
            </div>
            
            <!-- Dates and Times -->
            <div class="col-md-4">
                <label class="form-label small fw-bold">Event Date *</label>
                <input type="date" name="date" class="form-control glass-input" value="<?= e($event['date']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Event Time *</label>
                <input type="time" name="time" class="form-control glass-input" value="<?= e($event['time']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Registration Deadline *</label>
                <input type="date" name="registration_deadline" class="form-control glass-input" value="<?= e($event['registration_deadline']) ?>" required>
            </div>
            
            <!-- Coordinators & crew -->
            <div class="col-md-4">
                <label class="form-label small fw-bold">Organizer / Department *</label>
                <input type="text" name="organizer" class="form-control glass-input" value="<?= e($event['organizer']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Faculty Coordinator Name *</label>
                <input type="text" name="faculty_coordinator" class="form-control glass-input" value="<?= e($event['faculty_coordinator']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Student Coordinator Name *</label>
                <input type="text" name="student_coordinator" class="form-control glass-input" value="<?= e($event['student_coordinator']) ?>" required>
            </div>
            
            <!-- Limit & status -->
            <div class="col-md-6">
                <label class="form-label small fw-bold">Maximum Participants (Seats) * (Approved: <?= $regCount ?>)</label>
                <input type="number" name="max_participants" class="form-control glass-input" min="<?= $regCount ?>" value="<?= e($event['max_participants']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold">Event Status</label>
                <select name="status" class="form-select glass-input">
                    <option value="pending" <?= $event['status'] === 'pending' ? 'selected' : '' ?>>Pending Approval</option>
                    <option value="approved" <?= $event['status'] === 'approved' ? 'selected' : '' ?>>Approved & Active (Signups Open)</option>
                    <option value="completed" <?= $event['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="rejected" <?= $event['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            
            <!-- Rules & Prizes -->
            <div class="col-md-6">
                <label class="form-label small fw-bold">Rules & Guidelines</label>
                <textarea name="rules" rows="3" class="form-control glass-input"><?= e($event['rules']) ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold">Prizes & Certificates</label>
                <textarea name="prizes" rows="3" class="form-control glass-input"><?= e($event['prizes']) ?></textarea>
            </div>
            
            <!-- Banner & Contact details -->
            <div class="col-md-6">
                <label class="form-label small fw-bold">Contact details</label>
                <textarea name="contact_details" rows="2" class="form-control glass-input"><?= e($event['contact_details']) ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label small fw-bold">Change Event Banner (Optional)</label>
                <input type="file" name="event_banner" class="form-control glass-input" accept="image/*">
                <?php if ($event['event_banner']): ?>
                    <div class="form-text small">Current Banner: <a href="<?= BASE_URL . e($event['event_banner']) ?>" target="_blank"><?= basename($event['event_banner']) ?></a></div>
                <?php endif; ?>
            </div>
        </div>
        
        <button type="submit" class="btn btn-premium-primary w-100 py-2.5 mt-4"><i class="bi bi-save me-2"></i>Update Event</button>
    </form>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
