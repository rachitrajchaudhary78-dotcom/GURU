<?php
/**
 * Printable Event Pass / Ticket Receipt (Student)
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_student_login();

$studentId = $_SESSION['student_id'];
$regId = intval($_GET['reg_id'] ?? 0);

if ($regId <= 0) {
    die("Invalid registration ticket.");
}

// Fetch registration details
$ticket = fetch_one(
    "SELECT r.*, e.event_name, e.date, e.time, e.venue, e.building, e.room, e.organizer, s.fullname, s.student_id as roll_no, s.branch, c.name as category_name
     FROM registrations r
     JOIN events e ON r.event_id = e.id
     JOIN students s ON r.student_id = s.id
     JOIN categories c ON e.category_id = c.id
     WHERE r.id = ? AND r.student_id = ? AND r.status = 'approved'",
    [$regId, $studentId]
);

if (!$ticket) {
    die("Ticket not found or pending approval.");
}

$qrData = $ticket['ticket_code'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Entry Pass - <?= e($ticket['event_name']) ?></title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .ticket-card {
            border: 2px dashed #cbd5e1;
            border-radius: 1.5rem;
            background-color: #ffffff;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            max-width: 600px;
            margin: 3rem auto;
            overflow: hidden;
            position: relative;
        }
        .ticket-header {
            background: linear-gradient(135deg, #7c3aed 0%, #0ea5e9 100%);
            color: #ffffff;
            padding: 2rem;
            text-align: center;
        }
        .ticket-body {
            padding: 2.5rem;
        }
        .qr-sec {
            text-align: center;
            border-left: 2px dashed #e2e8f0;
            padding-left: 2rem;
        }
        @media (max-width: 575.98px) {
            .qr-sec {
                border-left: 0;
                border-top: 2px dashed #e2e8f0;
                padding-left: 0;
                padding-top: 2rem;
                margin-top: 2rem;
            }
        }
        .ticket-footer {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 1.5rem 2rem;
            text-align: center;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background-color: #ffffff;
            }
            .ticket-card {
                box-shadow: none;
                border: 2px solid #000000;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Action buttons -->
    <div class="text-center mt-4 no-print">
        <button onclick="window.print()" class="btn btn-primary px-4 py-2"><i class="bi bi-printer-fill me-2"></i>Print Pass</button>
        <button onclick="window.close()" class="btn btn-secondary px-4 py-2 ms-2"><i class="bi bi-x-circle me-2"></i>Close</button>
    </div>
    
    <!-- Pass Layout -->
    <div class="ticket-card">
        <!-- Header -->
        <div class="ticket-header">
            <h5 class="fw-bold mb-1 text-uppercase tracking-wider">Campus Event Entry Pass</h5>
            <h3 class="fw-bold mb-0 text-white"><?= e($ticket['event_name']) ?></h3>
            <span class="badge bg-white text-primary mt-2 px-3 py-1.5 rounded-pill"><?= e($ticket['category_name']) ?></span>
        </div>
        
        <!-- Body -->
        <div class="ticket-body">
            <div class="row g-4 align-items-center">
                <!-- Details -->
                <div class="col-sm-7">
                    <h6 class="fw-bold text-muted small text-uppercase mb-3">Participant Details</h6>
                    <div class="mb-2">
                        <strong>Name:</strong> <span class="text-secondary"><?= e($ticket['fullname']) ?></span>
                    </div>
                    <div class="mb-2">
                        <strong>Roll No:</strong> <span class="text-secondary"><?= e($ticket['roll_no']) ?></span>
                    </div>
                    <div class="mb-3">
                        <strong>Department:</strong> <span class="text-secondary"><?= e($ticket['branch']) ?></span>
                    </div>
                    
                    <h6 class="fw-bold text-muted small text-uppercase mb-3">Schedule & Location</h6>
                    <div class="mb-2">
                        <i class="bi bi-calendar-event me-2 text-primary"></i> <strong>Date:</strong> <?= date('d M Y', strtotime($ticket['date'])) ?>
                    </div>
                    <div class="mb-2">
                        <i class="bi bi-clock me-2 text-primary"></i> <strong>Time:</strong> <?= date('h:i A', strtotime($ticket['time'])) ?>
                    </div>
                    <div class="mb-0">
                        <i class="bi bi-geo-alt me-2 text-primary"></i> <strong>Venue:</strong> <?= e($ticket['venue']) ?> (<?= e($ticket['building']) ?>, Room <?= e($ticket['room']) ?>)
                    </div>
                </div>
                
                <!-- QR Code -->
                <div class="col-sm-5 qr-sec">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($qrData) ?>" alt="QR Code Ticket" class="img-fluid bg-light p-2 mb-2" style="width: 130px; height: 130px;">
                    <div class="small fw-semibold mt-1">Scan at Entrance</div>
                    <div class="text-muted small mt-1" style="font-size: 0.75rem;">ID: <?= e($qrData) ?></div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="ticket-footer">
            <div class="text-muted small mb-1"><strong>Organizer:</strong> <?= e($ticket['organizer']) ?></div>
            <div class="text-muted" style="font-size: 0.7rem;">Please bring a copy of this pass or keep it on your phone alongside your student ID. Gates close 10 mins prior to start.</div>
        </div>
    </div>
</div>

</body>
</html>
