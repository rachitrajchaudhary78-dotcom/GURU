<?php
/**
 * Submit Event Feedback (Student)
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_student_login();

$studentId = $_SESSION['student_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['redirect_message'] = "Security token validation failed.";
        $_SESSION['redirect_type'] = "danger";
        header("Location: dashboard.php");
        exit();
    }
    
    $eventId = intval($_POST['event_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 5);
    $comment = sanitize_input($_POST['comment'] ?? '');
    
    if ($eventId <= 0 || empty($comment)) {
        $_SESSION['redirect_message'] = "Feedback fields cannot be empty.";
        $_SESSION['redirect_type'] = "danger";
        header("Location: event-details.php?id=" . $eventId);
        exit();
    }
    
    // Check if student registered and attended (Present)
    $reg = fetch_one(
        "SELECT * FROM registrations WHERE student_id = ? AND event_id = ? AND status = 'approved' LIMIT 1",
        [$studentId, $eventId]
    );
    
    if (!$reg) {
        $_SESSION['redirect_message'] = "You must be registered for this event to leave feedback.";
        $_SESSION['redirect_type'] = "danger";
    } else {
        // Upsert feedback
        $existingFeed = fetch_one("SELECT id FROM feedback WHERE student_id = ? AND event_id = ?", [$studentId, $eventId]);
        if ($existingFeed) {
            query(
                "UPDATE feedback SET rating = ?, comment = ? WHERE id = ?",
                [$rating, $comment, $existingFeed['id']]
            );
        } else {
            query(
                "INSERT INTO feedback (student_id, event_id, rating, comment) VALUES (?, ?, ?, ?)",
                [$studentId, $eventId, $rating, $comment]
            );
        }
        
        log_activity("Submitted Feedback", "Student submitted feedback for event ID: $eventId.");
        $_SESSION['redirect_message'] = "Thank you! Your feedback has been recorded.";
        $_SESSION['redirect_type'] = "success";
    }
    
    header("Location: event-details.php?id=" . $eventId);
    exit();
} else {
    header("Location: dashboard.php");
    exit();
}
