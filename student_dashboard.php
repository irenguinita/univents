<?php
include 'db.php';
session_start();

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Fetch Student Info
$stmt = $conn->prepare("SELECT * FROM student WHERE user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

// 2. Fetch Dynamic Stats
$stmtRSVP = $conn->prepare("SELECT COUNT(*) FROM rsvp r JOIN event e ON r.event_id = e.event_id WHERE r.student_id = ? AND e.start_datetime > NOW()");
$stmtRSVP->execute([$user_id]);
$rsvp_count = $stmtRSVP->fetchColumn();

$stmtAttended = $conn->prepare("SELECT COUNT(*) FROM rsvp r JOIN event e ON r.event_id = e.event_id WHERE r.student_id = ? AND e.end_datetime < NOW()");
$stmtAttended->execute([$user_id]);
$attended_count = $stmtAttended->fetchColumn();

$stmtFollowed = $conn->prepare("SELECT COUNT(DISTINCT e.organization_id) FROM rsvp r JOIN event e ON r.event_id = e.event_id WHERE r.student_id = ?");
$stmtFollowed->execute([$user_id]);
$followed_count = $stmtFollowed->fetchColumn();

$stmtRev = $conn->prepare("SELECT COUNT(*) FROM review WHERE student_id = ?");
$stmtRev->execute([$user_id]);
$review_count = $stmtRev->fetchColumn();

// 3. Fetch Upcoming RSVP List (Top 3)
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

// 4. Fetch Recommended Events
$stmtRec = $conn->prepare("
    SELECT e.*, o.org_name 
    FROM event e 
    JOIN organization o ON e.organization_id = o.user_id
    WHERE e.start_datetime > NOW() 
    AND e.event_id NOT IN (SELECT event_id FROM rsvp WHERE student_id = ?)
    LIMIT 2
");
$stmtRec->execute([$user_id]);
$recommended_events = $stmtRec->fetchAll();

// 5. Fetch "Following" List
$stmtFollowList = $conn->prepare("
    SELECT DISTINCT o.org_name, o.user_id 
    FROM organization o 
    JOIN event e ON o.user_id = e.organization_id 
    JOIN rsvp r ON e.event_id = r.event_id 
    WHERE r.student_id = ? LIMIT 3
");
$stmtFollowList->execute([$user_id]);
$follow_list = $stmtFollowList->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard - Univents</title>
    <link rel="stylesheet" href="dashboard-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>

    <div class="dashboard-container">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-user">
                <div class="user-avatar"><?php echo substr($student['name'] ?? 'U', 0, 1); ?></div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($student['name']); ?></h4>
                    <p>STUDENT • <?php echo $student['year_level']; ?> YEAR</p>
                </div>
            </div>

            <nav class="side-nav">
                <p class="nav-label">MAIN</p>
                <a href="student_dashboard.php" class="active"><i class='bx bxs-dashboard'></i> Dashboard</a>
                <a href="events.php"><i class='bx bx-calendar-event'></i> Browse Events</a>
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

        <main class="main-content">
            <header class="top-header">
                <div class="logo">Univents</div>
                <div class="top-links">
                    <a href="events.php">EVENTS</a>
                    <a href="#">RSVPs</a>
                </div>
            </header>

            <div class="content-body">
                <!-- SESSION MESSAGES -->
                <?php if(isset($_SESSION['msg'])): ?>
                    <div style="padding: 15px; background: <?php echo ($_SESSION['msg_type'] == 'success') ? '#D5F5E3' : '#FADBD8'; ?>; color: <?php echo ($_SESSION['msg_type'] == 'success') ? '#27AE60' : '#E74C3C'; ?>; border-radius: 10px; margin-bottom: 20px; font-weight: bold; text-align: center; border: 1px solid <?php echo ($_SESSION['msg_type'] == 'success') ? '#27AE60' : '#E74C3C'; ?>;">
                        <?php 
                            echo $_SESSION['msg']; 
                            unset($_SESSION['msg']); 
                            unset($_SESSION['msg_type']);
                        ?>
                    </div>
                <?php endif; ?>

                <!-- WELCOME SECTION (Cleaned up - not clickable) -->
                <div class="welcome-section">
                    <h1>Welcome, <span class="teal-text"><?php echo htmlspecialchars(explode(' ', $student['name'])[0]); ?>!</span></h1>
                    <p><?php echo date('l, F j'); ?> • You have <?php echo $rsvp_count; ?> upcoming RSVPs.</p>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card"><div class="dot orange"></div><h2><?php echo $rsvp_count; ?></h2><p>Upcoming RSVPs</p></div>
                    <div class="stat-card"><div class="dot teal"></div><h2><?php echo $attended_count; ?></h2><p>Events Attended</p></div>
                    <div class="stat-card"><div class="dot blue"></div><h2><?php echo $followed_count; ?></h2><p>Orgs Interacted</p></div>
                    <div class="stat-card"><div class="dot yellow"></div><h2><?php echo $review_count; ?></h2><p>Reviews Written</p></div>
                </div>

                <div class="dashboard-grid">
                    <div class="lists-column">
                        <div class="section-header">
                            <h3>Upcoming RSVPs</h3>
                            <a href="#">View all →</a>
                        </div>
                        
                        <?php if(empty($upcoming_rsvps)): ?>
                            <p style="color:#888; padding: 20px;">No upcoming RSVPs. Browse events to get started!</p>
                        <?php endif; ?>

                        <?php foreach($upcoming_rsvps as $event): ?>
                        <!-- CLICKABLE EVENT ROW -->
                        <div class="event-row" onclick="window.location='view_event.php?id=<?php echo $event['event_id']; ?>'" style="cursor:pointer;">
                            <div class="event-indicator"></div>
                            <div class="event-details">
                                <strong><?php echo htmlspecialchars($event['org_name']); ?></strong>
                                <h4><?php echo htmlspecialchars($event['title']); ?></h4>
                                <p><?php echo date('M d • g:i A', strtotime($event['start_datetime'])); ?> • <?php echo htmlspecialchars($event['venue']); ?></p>
                            </div>
                            <span class="status-badge confirmed">✓ <?php echo strtoupper($event['rsvp_status']); ?></span>
                        </div>
                        <?php endforeach; ?>

                        <div class="section-header" style="margin-top:40px;">
                            <h3>Recommended For You</h3>
                            <a href="events.php">Browse all →</a>
                        </div>
                        
                        <?php foreach($recommended_events as $rec): ?>
                        <!-- CLICKABLE RECOMMENDED ROW -->
                        <div class="rec-row" onclick="window.location='view_event.php?id=<?php echo $rec['event_id']; ?>'" style="cursor:pointer;">
                            <div class="rec-icon <?php echo ($rec['event_id'] % 2 == 0) ? 'purple' : 'orange'; ?>"></div>
                            <div class="rec-details">
                                <h4><?php echo htmlspecialchars($rec['title']); ?></h4>
                                <p><?php echo date('M d', strtotime($rec['start_datetime'])); ?> • <?php echo htmlspecialchars($rec['venue']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="widgets-column">
                        <div class="widget calendar-widget">
                            <div class="calendar-header"><strong><?php echo date('F Y'); ?></strong></div>
                            <div style="text-align:center; padding:20px; color:#ccc;"><i class='bx bx-calendar' style="font-size: 3rem;"></i><p>Calendar Sync Active</p></div>
                        </div>

                        <div class="widget following-widget">
                            <div class="section-header"><h3>Interacted Orgs</h3><a href="#">Manage</a></div>
                            <?php foreach($follow_list as $f): ?>
                            <div class="follow-item">
                                <div class="follow-icon blue"><?php echo substr($f['org_name'], 0, 1); ?></div>
                                <div><strong><?php echo htmlspecialchars($f['org_name']); ?></strong><p>University Partner</p></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>