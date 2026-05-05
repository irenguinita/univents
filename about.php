<?php
include 'db.php';
session_start();

// FETCH LIVE STATS FROM SUPABASE
// 1. Total Registered Students
$stmtStudents = $conn->query("SELECT COUNT(*) FROM student");
$student_count = $stmtStudents->fetchColumn();

// 2. Total Active Events
$stmtEvents = $conn->query("SELECT COUNT(*) FROM event WHERE current_status = 'Upcoming'");
$event_count = $stmtEvents->fetchColumn();

// 3. Total Partner Organizations
$stmtOrgs = $conn->query("SELECT COUNT(*) FROM organization");
$org_count = $stmtOrgs->fetchColumn();

$is_logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>About Univents - Bringing Campus Life Together</title>
    <link rel="stylesheet" href="about-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>

    <!-- NAVBAR (Same as index) -->
    <header>
        <div class="container nav-wrapper">
            <div class="logo">Univents</div>
            <nav>
                <ul>
                    <li><a href="index.php">HOME</a></li>
                    <li><a href="events.php">EVENTS</a></li>
                    <li><a href="rsvps.php">RSVPs</a></li>
                    <li><a href="about.php" class="active">ABOUT</a></li>
                </ul>
            </nav>
            <div class="nav-buttons">
                <?php if($is_logged_in): ?>
                    <a href="dashboard.php" class="btn-primary">DASHBOARD</a>
                <?php else: ?>
                    <a href="register.php" class="btn-text">SIGN-UP</a>
                    <a href="login.php" class="btn-primary">LOG-IN</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- HERO SECTION -->
    <section class="about-hero">
        <div class="container hero-grid">
            <div class="hero-content">
                <span class="sub-label teal">About UNIVENTS</span>
                <h1>BRINGING <br> CAMPUS LIFE <br> TOGETHER</h1>
                <p>UNIVENTS was built to solve a real frustration: students missing out on events because information is scattered, and organizers struggling to reach their audience. We fix both.</p>
            </div>
            
            <!-- Glassmorphism Stats Card -->
            <div class="glass-stats-container">
                <div class="glass-card">
                    <p class="card-label">Platform at a Glance</p>
                    <div class="stats-mini-grid">
                        <div class="mini-stat">
                            <h3 class="stat-pink"><?php echo number_format($student_count / 1000, 1); ?>K</h3>
                            <p>Registered Students</p>
                        </div>
                        <div class="mini-stat">
                            <h3 class="stat-teal"><?php echo $event_count; ?>+</h3>
                            <p>Active Events</p>
                        </div>
                        <div class="mini-stat">
                            <h3 class="stat-blue"><?php echo $org_count; ?></h3>
                            <p>Partner Organizations</p>
                        </div>
                        <div class="mini-stat">
                            <h3 class="stat-yellow">4.6★</h3>
                            <p>Average Event Rating</p>
                        </div>
                    </div>
                </div>
                <!-- Abstract Blobs -->
                <div class="blob-purple"></div>
                <div class="blob-orange"></div>
            </div>
        </div>
    </section>

    <!-- PAIN POINT SECTION -->
    <section class="pain-point">
        <div class="container split-grid">
            <div class="left-heading">
                <span class="sub-label orange">THE PROBLEM</span>
                <h2>UNIVERSITY <br> PAIN POINT</h2>
            </div>
            <div class="right-text">
                <p>Students often struggle to find information about upcoming events, activities, and opportunities on campus. Announcements are scattered across social media, posters, messaging groups, and word-of-mouth.</p>
                <p>Because of this fragmentation, many students miss out on events that could benefit them academically, socially, or professionally.</p>
                <p>At the same time, event organizers face difficulties promoting their events effectively. Even well-planned activities receive low attendance simply because students were unaware.</p>
            </div>
        </div>
    </section>

    <!-- HUB SECTION -->
    <section class="hub-section">
        <div class="container">
            <span class="sub-label orange">THE SOLUTION</span>
            <h2>A CENTRALIZED HUB <br> FOR ALL UNIVERSITY <br> EVENTS</h2>
            
            <div class="hub-cards">
                <div class="h-card">
                    <div class="icon-box">🔍</div>
                    <h4>Centralized Discovery</h4>
                    <p>All campus events in one place. Search, filter, and browse by category, organization, or date.</p>
                </div>
                <div class="h-card">
                    <div class="icon-box">📱</div>
                    <h4>Two Dashboards</h4>
                    <p>One platform, two experiences. Students get a participant view; organizers get management tools.</p>
                </div>
                <div class="h-card">
                    <div class="icon-box">📊</div>
                    <h4>Live Insights</h4>
                    <p>Real-time RSVP tracking, attendance monitoring, and engagement analytics for organizers.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- TARGET USERS -->
    <section class="target-users">
        <div class="container">
            <span class="sub-label orange">Target Users</span>
            <h2>TARGET USERS</h2>
            
            <div class="user-grid">
                <div class="u-card">
                    <div class="u-icon bg-blue">S</div>
                    <h4>Students</h4>
                    <p>Discover, RSVP, and attend events that match their interests and schedule.</p>
                </div>
                <div class="u-card">
                    <div class="u-icon bg-orange">O</div>
                    <h4>Organizations</h4>
                    <p>Create events, manage RSVPs, and reach the whole university community.</p>
                </div>
                <div class="u-card">
                    <div class="u-icon bg-teal">D</div>
                    <h4>Departments</h4>
                    <p>Host academic and professional events with built-in promotion tools.</p>
                </div>
                <div class="u-card">
                    <div class="u-icon bg-purple">A</div>
                    <h4>Administrators</h4>
                    <p>Oversee platform activity, verify organizations, and monitor campus engagement.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER (Same as index) -->
    <footer>
        <!-- Insert your footer HTML here -->
    </footer>

</body>
</html>