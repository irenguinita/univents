<?php
include 'db.php';
session_start();

// Security: Redirect if not logged in as an organization
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'org') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Fetch Organization Info
$stmt = $conn->prepare("SELECT * FROM organization WHERE user_id = ?");
$stmt->execute([$user_id]);
$org = $stmt->fetch();

// 2. Fetch Stats for this Organization
// Total Events
$stmtTotal = $conn->prepare("SELECT COUNT(*) FROM event WHERE organization_id = ?");
$stmtTotal->execute([$user_id]);
$total_events = $stmtTotal->fetchColumn();

// Ongoing Events
$stmtOngoing = $conn->prepare("SELECT COUNT(*) FROM event WHERE organization_id = ? AND current_status = 'Ongoing'");
$stmtOngoing->execute([$user_id]);
$ongoing_events = $stmtOngoing->fetchColumn();

// Completed Events
$stmtCompleted = $conn->prepare("SELECT COUNT(*) FROM event WHERE organization_id = ? AND current_status = 'Completed'");
$stmtCompleted->execute([$user_id]);
$completed_events = $stmtCompleted->fetchColumn();

// Total Registrants (Sum of all RSVPs for all events hosted by this org)
$stmtRegs = $conn->prepare("
    SELECT COUNT(*) FROM rsvp r 
    JOIN event e ON r.event_id = e.event_id 
    WHERE e.organization_id = ?
");
$stmtRegs->execute([$user_id]);
$total_registrants = $stmtRegs->fetchColumn();

// 3. Fetch Organization's Upcoming Events
$stmtMyEvents = $conn->prepare("
    SELECT * FROM event 
    WHERE organization_id = ? AND start_datetime > NOW() 
    ORDER BY start_datetime ASC LIMIT 3
");
$stmtMyEvents->execute([$user_id]);
$my_events = $stmtMyEvents->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Organizer Dashboard - Univents</title>
    <link rel="stylesheet" href="dashboard-style.css"> <!-- Reuses the same CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>

    <div class="dashboard-container">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-user">
                <!-- Using first letter of Org Name -->
                <div class="user-avatar" style="background: #4BA68D;"><?php echo substr($org['org_name'], 0, 1); ?></div>
                <div class="user-info">
                    <h4><?php echo $org['org_name']; ?></h4>
                    <p>ORGANIZER • <?php echo $org['verification_status']; ?></p>
                </div>
            </div>

            <nav class="side-nav">
                <p class="nav-label">MAIN</p>
                <a href="#" class="active"><i class='bx bxs-dashboard'></i> Dashboard</a>
                
                <p class="nav-label">OPERATIONS</p>
                <a href="create_event.php"><i class='bx bx-plus-circle'></i> Create Event</a>
                <a href="#"><i class='bx bx-bar-chart-alt-2'></i> Summary</a>

                <p class="nav-label">ACCOUNT</p>
                <a href="#"><i class='bx bx-cog'></i> Settings</a>
                <a href="logout.php"><i class='bx bx-log-out'></i> Log out</a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <header class="top-header">
                <div class="logo">Univents</div>
                <div class="top-links">
                    <a href="#">EVENTS</a>
                    <a href="#">RSVPs</a>
                </div>
            </header>

            <div class="content-body">
                <div class="welcome-section">
                    <h1>Welcome, <span class="teal-text"><?php echo $org['org_name']; ?>!</span></h1>
                    <p>Manage your events and track registrations in real-time.</p>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="dot orange"></div>
                        <h2><?php echo $total_events; ?></h2>
                        <p>Total Events</p>
                    </div>
                    <div class="stat-card">
                        <div class="dot teal"></div>
                        <h2><?php echo $ongoing_events; ?></h2>
                        <p>Ongoing Events</p>
                    </div>
                    <div class="stat-card">
                        <div class="dot blue"></div>
                        <h2><?php echo $completed_events; ?></h2>
                        <p>Completed Events</p>
                    </div>
                    <div class="stat-card">
                        <div class="dot yellow"></div>
                        <h2><?php echo $total_registrants; ?></h2>
                        <p>Total Registrants</p>
                    </div>
                </div>

                <!-- Dashboard Layout -->
                <div class="dashboard-grid">
                    <!-- Left: My Events -->
                    <div class="lists-column">
                        <div class="section-header">
                            <h3>My Upcoming Events</h3>
                            <a href="#">View all →</a>
                        </div>
                        
                        <?php if(empty($my_events)): ?>
                            <p style="color:#888; padding: 20px;">You haven't posted any events yet.</p>
                        <?php endif; ?>

                        <?php foreach($my_events as $event): ?>
                        <div class="event-row">
                            <div class="event-indicator" style="background: #E68A6E;"></div>
                            <div class="event-details">
                                <strong>Status: <?php echo $event['current_status']; ?></strong>
                                <h4><?php echo $event['title']; ?></h4>
                                <p><?php echo date('M d, Y', strtotime($event['start_datetime'])); ?> • <?php echo $event['venue']; ?></p>
                            </div>
                            <a href="manage_event.php?id=<?php echo $event['event_id']; ?>" class="status-badge confirmed" style="text-decoration:none;">
                                MANAGE
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Right Column -->
                    <div class="widgets-column">
                         <div class="widget calendar-widget">
                            <div class="calendar-header">
                                <strong>March 2026</strong>
                                <div class="cal-nav"><span>‹</span><span>›</span></div>
                            </div>
                            <!-- Mockup of the calendar image -->
                            <div style="background: #f9f9f9; height: 200px; display: flex; align-items: center; justify-content: center; border-radius: 10px; color: #ccc;">
                                Calendar Widget Area
                            </div>
                        </div>

                        <div class="widget following-widget">
                            <div class="section-header">
                                <h3>Following</h3>
                                <a href="#">Manage</a>
                            </div>
                            <!-- This could represent Orgs you collaborate with or just a placeholder for now -->
                            <div class="follow-item">
                                <div class="follow-icon blue">G</div>
                                <div><strong>GDG on Campus</strong><p>Partner Organization</p></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

</body>
</html>