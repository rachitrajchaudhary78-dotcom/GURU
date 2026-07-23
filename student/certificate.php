<?php
/**
 * Student Certificate Viewer / Printer
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_student_login();

$studentId = $_SESSION['student_id'];
$regId = intval($_GET['reg_id'] ?? 0);

if ($regId <= 0) {
    die("Invalid certificate target.");
}

// Fetch registration & certificate details
$cert = fetch_one(
    "SELECT c.*, r.attendance, r.status as reg_status, e.event_name, e.date, e.organizer, e.faculty_coordinator, s.fullname, s.student_id as roll_no
     FROM certificates c
     JOIN registrations r ON c.registration_id = r.id
     JOIN events e ON r.event_id = e.id
     JOIN students s ON r.student_id = s.id
     WHERE r.id = ? AND r.student_id = ? AND r.attendance = 'present'",
    [$regId, $studentId]
);

if (!$cert) {
    die("Certificate not found or not yet generated. Please verify attendance and status.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - <?= e($cert['event_name']) ?></title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700&family=Playfair+Display:ital,wght@0,600;1,400&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #e2e8f0;
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            padding: 0;
        }
        .cert-container {
            background-color: #ffffff;
            width: 1056px; /* Landscape Letter Width */
            height: 816px; /* Landscape Letter Height */
            padding: 50px;
            margin: 3rem auto;
            position: relative;
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            box-sizing: border-box;
            border: 20px solid #ffffff;
            outline: 2px solid #cbd5e1;
        }
        .cert-border {
            border: 8px double #7c3aed;
            height: 100%;
            padding: 40px;
            box-sizing: border-box;
            position: relative;
            background: radial-gradient(circle, rgba(255,255,255,1) 70%, rgba(243,232,255,0.3) 100%);
        }
        .cert-title {
            font-family: 'Cinzel', serif;
            color: #1e1b4b;
            font-size: 2.75rem;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 0.5rem;
        }
        .cert-subtitle {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 1.5rem;
            color: #6d28d9;
            margin-bottom: 2.5rem;
        }
        .cert-name {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 2.25rem;
            color: #1e293b;
            border-bottom: 2px solid #ddd;
            display: inline-block;
            padding: 0 2rem 0.25rem 2rem;
            margin-bottom: 1.5rem;
        }
        .cert-text {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            line-height: 1.8;
            color: #475569;
            margin: 0 auto;
            max-width: 700px;
        }
        .cert-footer {
            margin-top: 5rem;
        }
        .cert-sign-line {
            border-top: 1px solid #94a3b8;
            width: 200px;
            margin: 0 auto;
            padding-top: 0.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
        }
        .cert-seal {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #7c3aed 0%, #0ea5e9 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: bold;
            font-size: 1.1rem;
            box-shadow: 0 5px 15px rgba(124,58,237,0.3);
            margin: 0 auto;
            position: relative;
        }
        .cert-seal::after {
            content: '';
            position: absolute;
            width: 82px;
            height: 82px;
            border: 2px dashed rgba(255,255,255,0.6);
            border-radius: 50%;
        }
        .cert-code {
            position: absolute;
            bottom: 15px;
            right: 15px;
            font-size: 0.75rem;
            color: #94a3b8;
            font-weight: 500;
        }
        .no-print-area {
            text-align: center;
            margin-top: 2rem;
            margin-bottom: 2rem;
        }
        @media print {
            body {
                background-color: #ffffff;
                margin: 0;
                padding: 0;
            }
            .no-print-area {
                display: none !important;
            }
            .cert-container {
                box-shadow: none;
                margin: 0;
                border: 0;
                outline: 0;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

<div class="no-print-area">
    <button onclick="window.print()" class="btn btn-primary px-4 py-2"><i class="bi bi-printer-fill me-2"></i>Print / Download PDF</button>
    <button onclick="window.close()" class="btn btn-secondary px-4 py-2 ms-2"><i class="bi bi-x-circle me-2"></i>Close</button>
</div>

<div class="cert-container shadow">
    <div class="cert-border text-center">
        <!-- Seal/Icon top -->
        <div class="mb-4">
            <i class="bi bi-award-fill text-primary" style="font-size: 3rem;"></i>
        </div>
        
        <div class="cert-title">Certificate of Achievement</div>
        <div class="cert-subtitle">is proudly presented to</div>
        
        <div class="cert-name"><?= e($cert['fullname']) ?></div>
        
        <div class="cert-text">
            for active and successful participation in the event <strong class="text-dark">"<?= e($cert['event_name']) ?>"</strong>, 
            organized by the <span class="text-secondary"><?= e($cert['organizer']) ?></span>, completed on 
            <strong class="text-dark"><?= date('d F Y', strtotime($cert['date'])) ?></strong>. 
            We commend their dedication, competitive spirit, and academic pursuit.
        </div>
        
        <!-- Footer Signatures -->
        <div class="row cert-footer">
            <div class="col-4">
                <div class="cert-sign-line mt-4">
                    <?= e($cert['faculty_coordinator']) ?><br>
                    <span class="text-muted small">Faculty Coordinator</span>
                </div>
            </div>
            
            <div class="col-4">
                <div class="cert-seal">
                    SEAL
                </div>
            </div>
            
            <div class="col-4">
                <div class="cert-sign-line mt-4">
                    College Principal<br>
                    <span class="text-muted small">Academic Dean</span>
                </div>
            </div>
        </div>
        
        <!-- Certificate Code -->
        <div class="cert-code">
            Verification ID: <?= e($cert['certificate_code']) ?>
        </div>
    </div>
</div>

</body>
</html>
