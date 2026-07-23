<?php
/**
 * Campus Events Management System - Database Installer & Seeder
 * Run this file in your browser or CLI to set up the database structure and initial data.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$username = 'root';
$password = ''; // Default XAMPP password is empty

echo "<h2>Campus Events Management System Installer</h2>";

try {
    // 1. Connect to MySQL Server
    $pdo = new PDO("mysql:host=$host", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<p style='color:green;'>✓ Connected to MySQL server successfully.</p>";

    // 2. Read schema.sql content
    $sqlFile = __DIR__ . '/database/schema.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Schema file not found at: " . $sqlFile);
    }
    
    $sqlContent = file_get_contents($sqlFile);
    
    // 3. Execute the SQL schema script
    // PDO exec handles multiple queries separated by semicolons in most configurations,
    // but to be safe we can run the schema queries.
    $pdo->exec($sqlContent);
    echo "<p style='color:green;'>✓ Database `campus_events` and tables created/verified.</p>";

    // 4. Reconnect specifically to campus_events database
    $pdo = new PDO("mysql:host=$host;dbname=campus_events", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // 5. Check if we already have admins or categories seeded
    $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
    $adminCount = $stmt->fetchColumn();

    if ($adminCount == 0) {
        // Seed default Categories
        $categories = [
            ['Technical', 'Coding contests, hackathons, technical workshops'],
            ['Coding Contest', 'Competitive programming and algorithm challenges'],
            ['Hackathon', 'Time-limited software development events'],
            ['Workshop', 'Practical learning and skill development sessions'],
            ['Seminar', 'Informative lectures by industry experts'],
            ['Webinar', 'Online lectures and presentations'],
            ['Sports', 'Athletic and sporting competitions'],
            ['Cultural', 'Art, dance, drama, and music events'],
            ['Dance', 'Solo and group dancing competitions'],
            ['Music', 'Singing and instrumental performance events'],
            ['Drama', 'Plays, mime, and theatrical performances'],
            ['Photography', 'Photo contests and workshops'],
            ['Debate', 'Speaking and argumentation tournaments'],
            ['Quiz', 'General and technical quiz competitions'],
            ['Gaming', 'Console, PC, and mobile gaming tourneys'],
            ['Startup', 'Entrepreneurship pitches and mentoring sessions'],
            ['Placement', 'Mock interviews and recruitment preparation'],
            ['Other', 'Miscellaneous college events']
        ];

        $insCat = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        foreach ($categories as $cat) {
            $insCat->execute($cat);
        }
        echo "<p style='color:green;'>✓ Seeded " . count($categories) . " event categories.</p>";

        // Seed Admin Account
        $adminPassword = password_hash('admin123', PASSWORD_BCRYPT);
        $insAdmin = $pdo->prepare("INSERT INTO admins (username, password, email, fullname) VALUES (?, ?, ?, ?)");
        $insAdmin->execute(['admin', $adminPassword, 'admin@gmail.com', 'System Administrator']);
        echo "<p style='color:green;'>✓ Seeded Admin User (username: <b>admin</b>, password: <b>admin123</b>).</p>";

        // Seed Student Accounts
        $studentPassword = password_hash('student123', PASSWORD_BCRYPT);
        $insStudent = $pdo->prepare("INSERT INTO students (student_id, fullname, email, password, phone, branch, year, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insStudent->execute(['STU001', 'Alice Johnson', 'alice@gmail.com', $studentPassword, '9876543210', 'Computer Science', 3, 'active']);
        $insStudent->execute(['STU002', 'Bob Smith', 'bob@gmail.com', $studentPassword, '9876543211', 'Electronics', 2, 'active']);
        echo "<p style='color:green;'>✓ Seeded Student Users:<br>1. Roll: <b>STU001</b>, Email: <b>alice@gmail.com</b>, Pass: <b>student123</b><br>2. Roll: <b>STU002</b>, Email: <b>bob@gmail.com</b>, Pass: <b>student123</b></p>";

        // Seed Default Settings
        $insSettings = $pdo->prepare("INSERT INTO settings (site_name, site_email, site_phone, site_address) VALUES (?, ?, ?, ?)");
        $insSettings->execute([
            'Campus Guru',
            'organizer@gmail.com',
            '+1 (234) 567-8900',
            '123 University Ave, Science Campus, Building C'
        ]);
        echo "<p style='color:green;'>✓ Seeded Default Site Settings.</p>";

        // Seed Sample Announcements
        $insAnn = $pdo->prepare("INSERT INTO announcements (title, content, priority) VALUES (?, ?, ?)");
        $insAnn->execute([
            'Welcome to Campus Guru!',
            'We are thrilled to launch the new Campus Events Management System. Students can now register, view receipts, download certificates, and interact with event organizers easily.',
            'high'
        ]);
        $insAnn->execute([
            'Annual Hackathon Registration Open',
            'Register now for the National Level Hackathon 2026. Huge cash prizes are up for grabs! Registration ends soon.',
            'normal'
        ]);
        echo "<p style='color:green;'>✓ Seeded Sample Announcements.</p>";

        // Seed Sample Slider/Gallery
        // Find category CS/Coding Contest
        $stmt = $pdo->query("SELECT id FROM categories WHERE name = 'Coding Contest' LIMIT 1");
        $codingCatId = $stmt->fetchColumn();

        // Seed a sample event
        $insEvent = $pdo->prepare("INSERT INTO events (event_name, description, category_id, venue, building, room, date, time, organizer, faculty_coordinator, student_coordinator, max_participants, registration_deadline, rules, prizes, contact_details, status, available_seats) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Future Event 1
        $insEvent->execute([
            'ByteCraft Coding League',
            'An intense 3-hour competitive coding contest. Show off your programming skills in C++, Java, or Python and solve challenging algorithmic puzzles.',
            $codingCatId,
            'Programming Lab 1',
            'Science Block',
            'Room 402',
            date('Y-m-d', strtotime('+10 days')),
            '14:00:00',
            'Computer Science Department',
            'Dr. Sarah Connor',
            'John Doe (CS-3rd Year)',
            60,
            date('Y-m-d', strtotime('+8 days')),
            "1. Individual participation only.\n2. Use of external libraries or internet search is prohibited.\n3. Plagiarism leads to immediate disqualification.",
            "1st Prize: $200\n2nd Prize: $100\n3rd Prize: Goodies & T-shirt",
            "Email: cs.dept@campus.edu\nPhone: +1-456-789-012",
            'approved',
            60
        ]);

        // Future Event 2
        $stmtSport = $pdo->query("SELECT id FROM categories WHERE name = 'Sports' LIMIT 1");
        $sportCatId = $stmtSport->fetchColumn();
        $insEvent->execute([
            'Inter-College Badminton Tournament',
            'Annual badminton championship open to all students. Exciting matches, trophies, and medals await the winners.',
            $sportCatId,
            'Indoor Sports Complex',
            'Sports Wing',
            'Court A & B',
            date('Y-m-d', strtotime('+15 days')),
            '09:00:00',
            'Sports Committee',
            'Prof. Robert Davis',
            'Jane Smith (Mech-4th Year)',
            32,
            date('Y-m-d', strtotime('+12 days')),
            "1. Single-elimination format.\n2. Standard BWF rules apply.\n3. Players must bring their own rackets.",
            "Winner: Trophy & Gold Medal\nRunner-up: Silver Medal",
            "Email: sports@campus.edu\nPhone: +1-987-654-321",
            'approved',
            32
        ]);

        // Completed Event (for feedback and certificates)
        $insEvent->execute([
            'Advanced AI & ML Workshop',
            'A hands-on workshop covering deep learning foundations, neural networks, and applications using PyTorch.',
            $codingCatId,
            'Seminar Hall A',
            'Main Block',
            'Seminar Room 1',
            date('Y-m-d', strtotime('-5 days')),
            '10:00:00',
            'AI Club',
            'Dr. Alan Turing',
            'Charlie Brown (CS-4th Year)',
            100,
            date('Y-m-d', strtotime('-7 days')),
            "1. Laptop required.\n2. Basic python knowledge assumed.",
            "Certificate of completion for all attendees.",
            "Email: ai.club@campus.edu",
            'completed',
            0 // seats left
        ]);
        
        $completedEventId = $pdo->lastInsertId();

        // Register student 1 for completed event, make it approved and present
        $insReg = $pdo->prepare("INSERT INTO registrations (student_id, event_id, status, attendance, ticket_code, certificate_status) VALUES (?, ?, ?, ?, ?, ?)");
        $insReg->execute([1, $completedEventId, 'approved', 'present', 'TKT-' . uniqid(), 'generated']);
        $regId = $pdo->lastInsertId();

        // Create certificate for student 1
        $insCert = $pdo->prepare("INSERT INTO certificates (registration_id, certificate_code) VALUES (?, ?)");
        $insCert->execute([$regId, 'CERT-AI-WS-' . rand(1000, 9999)]);

        // Add feedback for completed event
        $insFeed = $pdo->prepare("INSERT INTO feedback (student_id, event_id, rating, comment) VALUES (?, ?, ?, ?)");
        $insFeed->execute([1, $completedEventId, 5, 'Absolutely phenomenal workshop! Dr. Alan explains concepts so clearly. Highly recommend it.']);

        // Log actions
        $insLog = $pdo->prepare("INSERT INTO activity_logs (user_id, user_role, action, details) VALUES (?, ?, ?, ?)");
        $insLog->execute([NULL, 'system', 'Database Seeded', 'Database populated with categories, admins, students, and sample events.']);

        echo "<p style='color:green;'>✓ Seeded Sample Events, Registrations, Certificates, and Feedbacks.</p>";
    } else {
        echo "<p style='color:orange;'>ℹ Database is already seeded. Seeding skipped.</p>";
    }

    echo "<h3 style='color:blue;'>🎉 Installation Completed Successfully!</h3>";
    echo "<p>You can now delete this file or navigate to <a href='index.php'>Homepage</a>.</p>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>✗ Database Error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Error: " . $e->getMessage() . "</p>";
}
