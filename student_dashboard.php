<?php
include 'db.php';
session_start();

// Security: Redirect if not logged in as a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Fetch Student Info
$stmt = $conn->prepare("SELECT * FROM student WHERE user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

// 2. Fetch Stats (Counts)
// Count RSVPs
$stmtRSVP = $conn->prepare("SELECT COUNT(*) FROM rsvp WHERE student_id = ?");
$stmtRSVP->execute([$user_id]);
$rsvp_count = $stmtRSVP->fetchColumn();

// Count Reviews
$stmtRev = $conn->prepare("SELECT COUNT(*) FROM review WHERE student_id = ?");
$stmtRev->execute([$user_id]);
$review_count = $stmtRev->fetchColumn();

// 3. Fetch Upcoming RSVPs
$stmtEvents = $conn->prepare("
    SELECT e.*, r.rsvp_status, o.org_name 
    FROM event e 
    JOIN rsvp r ON e.event_id = r.event_id 
    JOIN organization o ON e.organization_id = o.user_id
    WHERE r.student_id = ? AND e.start_datetime > NOW()
    ORDER BY e.start_datetime ASC LIMIT 3
");
$stmtEvents->execute([$user_id]);
$upcoming_rsvps = $stmtEvents->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard - Univents</title>
    <link rel="stylesheet" href="dashboard-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Using boxicons for the sidebar icons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>

    <div class="dashboard-container">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-user">
                <div class="user-avatar"><?php echo substr($student['name'], 0, 1); ?></div>
                <div class="user-info">
                    <h4><?php echo $student['name']; ?></h4>
                    <p>STUDENT • <?php echo $student['year_level']; ?>RD YEAR</p>
                </div>
            </div>

            <nav class="side-nav">
                <p class="nav-label">MAIN</p>
                <a href="#" class="active"><i class='bx bxs-dashboard'></i> Dashboard</a>
                <a href="#"><i class='bx bx-calendar-event'></i> Browse Events</a>
                <a href="#"><i class='bx bx-bookmark-heart'></i> My RSVPs</a>

                <p class="nav-label">ACTIVITY</p>
                <a href="#"><i class='bx bx-bell'></i> Notifications</a>
                <a href="#"><i class='bx bx-star'></i> Reviews</a>
                <a href="#"><i class='bx bx-group'></i> Organizations</a>

                <p class="nav-label">ACCOUNT</p>
                <a href="#"><i class='bx bx-cog'></i> Settings</a>
                <a href="logout.php"><i class='bx bx-log-out'></i> Log out</a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <!-- Top Header -->
            <header class="top-header">
                <div class="logo">Univents</div>
                <div class="top-links">
                    <a href="#">EVENTS</a>
                    <a href="#">RSVPs</a>
                </div>
            </header>

            <div class="content-body">
                <div class="welcome-section">
                    <h1>Welcome, <span class="teal-text"><?php echo explode(' ', $student['name'])[0]; ?>!</span></h1>
                    <p>Friday, March 6 • You have <?php echo $rsvp_count; ?> upcoming RSVPs this week.</p>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="dot orange"></div>
                        <h2><?php echo $rsvp_count; ?></h2>
                        <p>Upcoming RSVPs</p>
                    </div>
                    <div class="stat-card">
                        <div class="dot teal"></div>
                        <h2>12</h2>
                        <p>Events Attended</p>
                    </div>
                    <div class="stat-card">
                        <div class="dot blue"></div>
                        <h2>5</h2>
                        <p>Orgs Followed</p>
                    </div>
                    <div class="stat-card">
                        <div class="dot yellow"></div>
                        <h2><?php echo $review_count; ?></h2>
                        <p>Reviews Written</p>
                    </div>
                </div>

                <!-- Dashboard Layout: 2 Columns -->
                <div class="dashboard-grid">
                    <!-- Left: Lists -->
                    <div class="lists-column">
                        <div class="section-header">
                            <h3>Upcoming RSVPs</h3>
                            <a href="#">View all →</a>
                        </div>
                        
                        <?php foreach($upcoming_rsvps as $event): ?>
                        <div class="event-row">
                            <div class="event-indicator"></div>
                            <div class="event-details">
                                <strong><?php echo $event['org_name']; ?></strong>
                                <h4><?php echo $event['title']; ?></h4>
                                <p><?php echo date('M d, Y • g:i A', strtotime($event['start_datetime'])); ?> • <?php echo $event['venue']; ?></p>
                            </div>
                            <span class="status-badge <?php echo strtolower($event['rsvp_status']); ?>">
                                ✓ <?php echo strtoupper($event['rsvp_status']); ?>
                            </span>
                        </div>
                        <?php endforeach; ?>

                        <div class="section-header" style="margin-top:40px;">
                            <h3>Recommended For You</h3>
                            <a href="#">Browse all →</a>
                        </div>
                        <!-- Mock data for recommendations -->
                        <div class="rec-row">
                            <div class="rec-icon purple"></div>
                            <div class="rec-details">
                                <h4>Research Colloquium: AI in Education</h4>
                                <p>Mar 25 • Faculty Hall A • CS Department</p>
                            </div>
                        </div>
                        <div class="rec-row">
                            <div class="rec-icon orange"></div>
                            <div class="rec-details">
                                <h4>CulturFest 2026: Roots & Routes</h4>
                                <p>Apr 1 • Main Quad • Cultural Society</p>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Widgets -->
                    <div class="widgets-column">
                        <div class="widget calendar-widget">
                            <div class="calendar-header">
                                <strong>March 2026</strong>
                                <div class="cal-nav"><span>‹</span><span>›</span></div>
                            </div>
                            <img src="calendar-placeholder.png" alt="Calendar" style="width:100%; border-radius:10px;">
                        </div>

                        <div class="widget following-widget">
                            <div class="section-header">
                                <h3>Following</h3>
                                <a href="#">Manage</a>
                            </div>
                            <div class="follow-item">
                                <div class="follow-icon blue">G</div>
                                <div><strong>GDG on Campus</strong><p>3 upcoming events</p></div>
                            </div>
                            <div class="follow-item">
                                <div class="follow-icon green">A</div>
                                <div><strong>ACM Student Chapter</strong><p>1 upcoming event</p></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

</body>
</html>