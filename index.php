<?php
/**
 * Campus Events Management System - Homepage
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Handle Contact Form Submission (Feedback)
$feedbackSuccess = false;
$feedbackError = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $name = sanitize_input($_POST['name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $comment = sanitize_input($_POST['message'] ?? '');
    $rating = intval($_POST['rating'] ?? 5);
    
    if (empty($name) || empty($email) || empty($comment)) {
        $feedbackError = "All fields are required.";
    } else {
        // If logged in as student, use student_id. If guest, map to student with id=1 or keep it null.
        // To be safe, we will fetch student matching email or insert feedback.
        $student = fetch_one("SELECT id FROM students WHERE email = ? LIMIT 1", [$email]);
        $studentId = $student ? $student['id'] : 1; // Fallback to Alice (first student) if guest
        
        // Find a completed event to attach to, or use any event
        $event = fetch_one("SELECT id FROM events ORDER BY id DESC LIMIT 1");
        $eventId = $event ? $event['id'] : 1;

        query(
            "INSERT INTO feedback (student_id, event_id, rating, comment) VALUES (?, ?, ?, ?)",
            [$studentId, $eventId, $rating, $comment]
        );
        $feedbackSuccess = "Thank you! Your feedback/message has been received.";
    }
}

// Fetch stats
$statStudents = fetch_one("SELECT COUNT(*) as count FROM students")['count'] ?? 0;
$statEvents = fetch_one("SELECT COUNT(*) as count FROM events")['count'] ?? 0;
$statRegistrations = fetch_one("SELECT COUNT(*) as count FROM registrations")['count'] ?? 0;
$statActive = fetch_one("SELECT COUNT(*) as count FROM events WHERE status = 'approved' AND date >= CURDATE()")['count'] ?? 0;

// Fetch upcoming events
$upcomingEvents = fetch_all("SELECT e.*, c.name as category_name FROM events e JOIN categories c ON e.category_id = c.id WHERE e.status = 'approved' AND e.date >= CURDATE() ORDER BY e.date ASC LIMIT 6");

// Fetch announcements
$announcements = fetch_all("SELECT * FROM announcements ORDER BY priority DESC, id DESC LIMIT 4");

// Fetch settings
$settings = fetch_one("SELECT * FROM settings ORDER BY id DESC LIMIT 1");
$siteName = $settings['site_name'] ?? 'Campus Guru';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="py-5 text-center bg-transparent mt-3">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-6 text-start">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill mb-3">Empowering Campus Engagement</span>
                <h1 class="display-4 fw-bold text-dark mb-3" style="font-family: 'Outfit', sans-serif;">
                    Manage & Discover <br>
                    <span class="text-primary" style="background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Campus Events</span> Seamlessly
                </h1>
                <p class="lead text-muted mb-4">Register for hackathons, coding contests, sports meets, and cultural festivals. Download participation certificates and verify seats in real-time.</p>
                <div class="d-flex gap-3">
                    <?php if (is_student_logged_in()): ?>
                        <a href="<?= BASE_URL ?>student/events.php" class="btn btn-premium-primary btn-lg"><i class="bi bi-search me-2"></i>Explore Events</a>
                        <a href="<?= BASE_URL ?>student/dashboard.php" class="btn btn-premium-secondary btn-lg"><i class="bi bi-speedometer2 me-2"></i>My Dashboard</a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>register.php" class="btn btn-premium-primary btn-lg"><i class="bi bi-rocket-takeoff me-2"></i>Get Started</a>
                        <a href="<?= BASE_URL ?>login.php" class="btn btn-premium-secondary btn-lg"><i class="bi bi-box-arrow-in-right me-2"></i>Sign In</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6 mt-5 mt-lg-0">
                <div class="glass-card p-4 position-relative overflow-hidden" style="border-radius: 2rem;">
                    <div class="position-absolute top-0 start-0 w-100 h-100 bg-primary opacity-10" style="filter: blur(100px);"></div>
                    <img src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=800&auto=format&fit=crop" class="img-fluid rounded-4 shadow-sm" alt="Campus Event Illustration" style="max-height: 400px; object-fit: cover;">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-5">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-md-3 col-sm-6">
                <div class="glass-card p-4 hover-card-lift">
                    <div class="d-inline-flex p-3 rounded-circle bg-primary-subtle text-primary mb-3">
                        <i class="bi bi-people-fill fs-3"></i>
                    </div>
                    <h3 class="fw-bold mb-0 text-dark"><?= e($statStudents) ?></h3>
                    <p class="text-muted small mb-0">Registered Students</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="glass-card p-4 hover-card-lift">
                    <div class="d-inline-flex p-3 rounded-circle bg-info-subtle text-info mb-3">
                        <i class="bi bi-calendar-event-fill fs-3"></i>
                    </div>
                    <h3 class="fw-bold mb-0 text-dark"><?= e($statEvents) ?></h3>
                    <p class="text-muted small mb-0">Total Events Created</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="glass-card p-4 hover-card-lift">
                    <div class="d-inline-flex p-3 rounded-circle bg-success-subtle text-success mb-3">
                        <i class="bi bi-check2-all fs-3"></i>
                    </div>
                    <h3 class="fw-bold mb-0 text-dark"><?= e($statRegistrations) ?></h3>
                    <p class="text-muted small mb-0">Event Registrations</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="glass-card p-4 hover-card-lift">
                    <div class="d-inline-flex p-3 rounded-circle bg-warning-subtle text-warning mb-3">
                        <i class="bi bi-hourglass-split fs-3"></i>
                    </div>
                    <h3 class="fw-bold mb-0 text-dark"><?= e($statActive) ?></h3>
                    <p class="text-muted small mb-0">Active Events</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section id="about" class="py-5 bg-white-50">
    <div class="container my-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <img src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1?q=80&w=800&auto=format&fit=crop" class="img-fluid rounded-4 shadow-lg" alt="College Campus">
            </div>
            <div class="col-lg-6">
                <h2 class="fw-bold text-dark mb-3" style="font-family: 'Outfit', sans-serif;">About Our Institution & Events</h2>
                <p class="text-muted lead">Our college stands at the forefront of academic and extra-curricular excellence. We believe in holistic student development driven by dynamic experiences, coding bootcamps, and cultural expressions.</p>
                <p class="text-muted mb-4">Through <b><?= e($siteName) ?></b>, we bring transparency and simplicity to event organisation. Faculty coordinators, student leaders, and participants connect seamlessly in one responsive portal.</p>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-patch-check-fill text-success fs-3 me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-0">Verified Profiles</h6>
                                <small class="text-muted">Strict roll number matching</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-award-fill text-primary fs-3 me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-0">E-Certificates</h6>
                                <small class="text-muted">Directly download printable passes</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Upcoming Events Section -->
<section id="events" class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <span class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill mb-2">Happening Soon</span>
                <h2 class="fw-bold text-dark mb-0" style="font-family: 'Outfit', sans-serif;">Featured Upcoming Events</h2>
            </div>
            <a href="<?= BASE_URL ?>student/events.php" class="btn btn-premium-secondary"><i class="bi bi-calendar-range me-2"></i>View All</a>
        </div>
        
        <div class="row g-4">
            <?php if (empty($upcomingEvents)): ?>
                <div class="col-12 text-center py-5">
                    <div class="glass-card p-5">
                        <i class="bi bi-calendar-x fs-1 text-muted mb-3"></i>
                        <h4 class="fw-bold">No Upcoming Events Found</h4>
                        <p class="text-muted mb-0">Check back later or browse completed archives.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($upcomingEvents as $event): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="glass-card h-100 overflow-hidden d-flex flex-column hover-card-lift">
                            <?php if ($event['event_banner']): ?>
                                <img src="<?= BASE_URL . e($event['event_banner']) ?>" alt="Event Banner" style="height: 180px; object-fit: cover; width: 100%;">
                            <?php else: ?>
                                <div class="bg-primary-subtle d-flex align-items-center justify-content-center" style="height: 180px;">
                                    <i class="bi bi-image text-primary fs-1"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="p-4 d-flex flex-column flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-light text-primary px-2.5 py-1.5 rounded-pill"><?= e($event['category_name']) ?></span>
                                    <small class="text-danger fw-semibold"><i class="bi bi-people-fill me-1"></i><?= e($event['available_seats']) ?>/<?= e($event['max_participants']) ?> Seats</small>
                                </div>
                                <h5 class="fw-bold text-dark mb-2"><?= e($event['event_name']) ?></h5>
                                <p class="text-muted small mb-3 flex-grow-1"><?= e(substr($event['description'], 0, 120)) ?>...</p>
                                
                                <div class="border-top pt-3 mt-3">
                                    <div class="row g-2 mb-3 text-muted small">
                                        <div class="col-6"><i class="bi bi-calendar3 text-primary me-2"></i><?= date('M d, Y', strtotime($event['date'])) ?></div>
                                        <div class="col-6 text-end"><i class="bi bi-geo-alt-fill text-primary me-1"></i><?= e($event['venue']) ?></div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted"><i class="bi bi-clock me-1"></i><?= date('h:i A', strtotime($event['time'])) ?></small>
                                        <a href="<?= BASE_URL ?>student/event-details.php?id=<?= $event['id'] ?>" class="btn btn-premium-primary btn-sm">Register & Details</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Gallery Section -->
<section id="gallery" class="py-5 bg-white-50">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-info-subtle text-info px-3 py-1 rounded-pill mb-2">Glimpses of Success</span>
            <h2 class="fw-bold text-dark" style="font-family: 'Outfit', sans-serif;">Event Gallery</h2>
            <p class="text-muted">A look back at our recent hackathons, cultural festivals, and sporting achievements.</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="glass-card overflow-hidden hover-card-lift">
                    <img src="https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?q=80&w=600&auto=format&fit=crop" class="img-fluid rounded-top" alt="Coding Session">
                    <div class="p-3"><h6 class="fw-bold mb-0">Hackathon Pitch 2026</h6></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card overflow-hidden hover-card-lift">
                    <img src="https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?q=80&w=600&auto=format&fit=crop" class="img-fluid rounded-top" alt="Music Festival">
                    <div class="p-3"><h6 class="fw-bold mb-0">Cultural Band Night</h6></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card overflow-hidden hover-card-lift">
                    <img src="https://images.unsplash.com/photo-1505373877841-8d25f7d46678?q=80&w=600&auto=format&fit=crop" class="img-fluid rounded-top" alt="Sports Meet">
                    <div class="p-3"><h6 class="fw-bold mb-0">Seminar and Panel Discussions</h6></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-dark" style="font-family: 'Outfit', sans-serif;">What Students & Faculty Say</h2>
            <p class="text-muted">Feedback from the campus community about the event registration and certificate generation flows.</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6">
                <div class="glass-card p-4">
                    <p class="text-muted italic">"The OTP password recovery and instant printable event passes saved me so much hassle. I could secure my badminton court slot in under 10 seconds!"</p>
                    <div class="d-flex align-items-center mt-3">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">AJ</div>
                        <div class="ms-3">
                            <h6 class="fw-bold mb-0">Alice Johnson</h6>
                            <small class="text-muted">CS Student</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="glass-card p-4">
                    <p class="text-muted italic">"Managing registration spreadsheets and generating participation certificates manually used to take days. Now, Campus Guru generates them instantly!"</p>
                    <div class="d-flex align-items-center mt-3">
                        <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">RC</div>
                        <div class="ms-3">
                            <h6 class="fw-bold mb-0">Dr. Sarah Connor</h6>
                            <small class="text-muted">Faculty Coordinator</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Announcements & FAQ Row -->
<section class="py-5 bg-white-50">
    <div class="container">
        <div class="row g-5">
            <!-- Announcements -->
            <div id="announcements" class="col-lg-6">
                <h3 class="fw-bold text-dark mb-4" style="font-family: 'Outfit', sans-serif;"><i class="bi bi-megaphone-fill text-primary me-2"></i>Latest Announcements</h3>
                <div class="d-flex flex-column gap-3">
                    <?php if (empty($announcements)): ?>
                        <div class="glass-card p-4 text-center text-muted">
                            No announcements posted yet.
                        </div>
                    <?php else: ?>
                        <?php foreach ($announcements as $ann): ?>
                            <div class="glass-card p-4 <?= $ann['priority'] === 'high' ? 'border-primary bg-primary-subtle' : '' ?>">
                                <div class="d-flex justify-content-between mb-2">
                                    <h6 class="fw-bold text-dark mb-0"><?= e($ann['title']) ?></h6>
                                    <span class="badge bg-white text-secondary"><?= date('M d, Y', strtotime($ann['created_at'])) ?></span>
                                </div>
                                <p class="text-muted small mb-0"><?= nl2br(e($ann['content'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- FAQ Section -->
            <div id="faq" class="col-lg-6">
                <h3 class="fw-bold text-dark mb-4" style="font-family: 'Outfit', sans-serif;"><i class="bi bi-question-circle-fill text-primary me-2"></i>Frequently Asked Questions</h3>
                <div class="accordion accordion-flush" id="faqAccordion">
                    <div class="accordion-item bg-transparent border-0 mb-2 glass-card overflow-hidden">
                        <h2 class="accordion-header" id="faq-h1">
                            <button class="accordion-button bg-transparent fw-semibold text-dark shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq-c1">
                                How do I register for an event?
                            </button>
                        </h2>
                        <div id="faq-c1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted small bg-white-50">
                                Log in using your Student credentials, search for the event, check the countdown timer and live seat availability, then click "Register".
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item bg-transparent border-0 mb-2 glass-card overflow-hidden">
                        <h2 class="accordion-header" id="faq-h2">
                            <button class="accordion-button bg-transparent collapsed fw-semibold text-dark shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq-c2">
                                Where do I get my participation certificate?
                            </button>
                        </h2>
                        <div id="faq-c2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted small bg-white-50">
                                Once an event ends and the coordinator marks your attendance as "Present", the administrator will generate the certificate. You can then download it as a PDF or high-quality printable image from the 'My Registrations' page.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item bg-transparent border-0 mb-2 glass-card overflow-hidden">
                        <h2 class="accordion-header" id="faq-h3">
                            <button class="accordion-button bg-transparent collapsed fw-semibold text-dark shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#faq-c3">
                                Can I cancel my registration?
                            </button>
                        </h2>
                        <div id="faq-c3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted small bg-white-50">
                                Yes, you can cancel your registration anytime before the deadline. This frees up the seat for another student instantly.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Form Section -->
<section id="contact" class="py-5">
    <div class="container py-4">
        <div class="row g-5">
            <div class="col-lg-5">
                <span class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill mb-2">Get in touch</span>
                <h2 class="fw-bold text-dark mb-3" style="font-family: 'Outfit', sans-serif;">Need Help? Contact Organizers</h2>
                <p class="text-muted">Have a query regarding prizes, scheduling, or registrations? Send us a direct message and the system administrator or coordinator will get back to you.</p>
                <div class="mt-4">
                    <div class="d-flex mb-3">
                        <i class="bi bi-envelope-open-fill text-primary fs-4 me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-0">Email Support</h6>
                            <small class="text-muted">imgransh@gmail.com</small>
                        </div>
                    </div>
                    <div class="d-flex">
                        <i class="bi bi-clock-fill text-primary fs-4 me-3"></i>
                        <div>
                            <h6 class="fw-bold mb-0">Office Hours</h6>
                            <small class="text-muted">Mon - Fri: 9:00 AM - 5:00 PM</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-7">
                <div class="glass-card p-4">
                    <h5 class="fw-bold text-dark mb-4">Send a Message</h5>
                    
                    <?php if ($feedbackSuccess): ?>
                        <div class="alert alert-success border-0 glass-card text-success mb-4">
                            <i class="bi bi-check-circle-fill me-2"></i><?= e($feedbackSuccess) ?>
                        </div>
                    <?php elseif ($feedbackError): ?>
                        <div class="alert alert-danger border-0 glass-card text-danger mb-4">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($feedbackError) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="#contact" method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Full Name</label>
                                <input type="text" name="name" class="form-control glass-input" placeholder="Your name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Email Address</label>
                                <input type="email" name="email" class="form-control glass-input" placeholder="you@example.com" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Message / Feedback</label>
                                <textarea name="message" rows="4" class="form-control glass-input" placeholder="Type your message here..." required></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Rating (if sharing feedback for a past event)</label>
                                <select name="rating" class="form-select glass-input">
                                    <option value="5">⭐⭐⭐⭐⭐ (Excellent)</option>
                                    <option value="4">⭐⭐⭐⭐ (Good)</option>
                                    <option value="3">⭐⭐⭐ (Average)</option>
                                    <option value="2">⭐⭐ (Fair)</option>
                                    <option value="1">⭐ (Poor)</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="contact_submit" class="btn btn-premium-primary w-100"><i class="bi bi-send-fill me-2"></i>Send Message</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
