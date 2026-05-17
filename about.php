<?php
include 'db.php';
session_start();

$stmtStudents = $conn->query("SELECT COUNT(*) FROM student");
$student_count = $stmtStudents->fetchColumn();

$stmtEvents = $conn->query("SELECT COUNT(*) FROM event WHERE current_status = 'Upcoming'");
$event_count = $stmtEvents->fetchColumn();

$stmtOrgs = $conn->query("SELECT COUNT(*) FROM organization");
$org_count = $stmtOrgs->fetchColumn();

$is_logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>About Univents</title>
    <link rel="stylesheet" href="about-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        /* CSS Variables & Global Resets */
        :root {
            --cream: #F9F7F2;
            --coral: #F1948A;
            --dark: #0D1312;
            --teal: #4BA68D;
        }
        body { margin: 0; font-family: 'Inter', sans-serif; background: white; color: var(--dark); }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 5%; }
        
        /* Typography - Using the bold display font from your image[cite: 12] */
        .display-font { font-family: 'Montserrat', sans-serif; font-weight: 900; line-height: 0.9; text-transform: uppercase; }
        .section-label { color: var(--teal); font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 20px; }

        /* --- HEADER (Synchronized with index.php)[cite: 22] --- */
        header { padding: 20px 0; background: var(--cream); position: sticky; top: 0; z-index: 1000; }
        .nav-wrapper { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-family: 'Montserrat'; font-weight: 900; font-size: 1.8rem; color: #333; text-decoration: none; }
        nav ul { display: flex; list-style: none; gap: 30px; margin: 0; padding: 0; }
        nav ul li a { text-decoration: none; color: #666; font-weight: 600; font-size: 0.85rem; }
        nav ul li a.active { color: var(--teal); border-bottom: 2px solid var(--teal); padding-bottom: 5px; }

        /* --- HERO SECTION (Dark with Stats Grid)[cite: 13] --- */
        .about-hero { 
            background: #0D1312; 
            padding: 100px 0; 
            color: white; 
            position: relative; 
            overflow: hidden; 
        }
        .about-circle-purp { position: absolute; width: 600px; height: 600px; background: rgba(106, 90, 205, 0.15); border-radius: 50%; right: -150px; top: -100px; }
        .about-circle-gold { position: absolute; width: 300px; height: 300px; background: rgba(247, 220, 111, 0.05); border-radius: 50%; right: 200px; bottom: -50px; }
        .hero-flex { display: flex; justify-content: space-between; align-items: center; gap: 50px; }
        .hero-text { flex: 1; }
        .hero-text h1 { font-size: 4.5rem; margin: 20px 0; }
        .hero-text p { opacity: 0.8; line-height: 1.6; max-width: 450px; }
        
        /* Stats Box (Glassmorphism)[cite: 13] */
        .stats-glass-card { 
            background: rgba(255, 255, 255, 0.05); 
            backdrop-filter: blur(10px); 
            padding: 40px; 
            border-radius: 30px; 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 20px; 
            border: 1px solid rgba(255,255,255,0.1);
        }
        .stat-item { background: rgba(255,255,255,0.03); padding: 25px; border-radius: 20px; text-align: left; }
        .stat-num { font-family: 'Montserrat'; font-weight: 900; font-size: 1.8rem; display: block; }
        .stat-label { font-size: 0.7rem; opacity: 0.5; font-weight: 600; }

        /* --- PROBLEM SECTION (White background)[cite: 13] --- */
        .problem-section { padding: 100px 0; }
        .problem-grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 80px; align-items: start; }
        .problem-grid h2 { font-size: 3.5rem; margin-top: 10px; }
        .problem-content p { color: #555; line-height: 1.6; margin-bottom: 25px; }

        /* --- HUB SECTION (Cream background)[cite: 13] --- */
        .hub-section { background: var(--cream); padding: 100px 0; }
        .hub-section h2 { font-size: 3.5rem; margin-bottom: 60px; max-width: 800px; }
        .hub-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; }
        .hub-card { background: white; padding: 40px; border-radius: 25px; }
        .hub-card i { background: var(--cream); padding: 15px; border-radius: 12px; color: var(--teal); font-size: 1.5rem; margin-bottom: 25px; display: inline-block; }
        .hub-card h4 { font-family: 'Montserrat'; font-weight: 900; margin-bottom: 15px; }
        .hub-card p { font-size: 0.9rem; color: #777; line-height: 1.5; }

        /* --- TARGET USERS (White background)[cite: 13] --- */
        .target-section { padding: 100px 0; }
        .user-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 40px; }
        .user-card { background: #F4F0E8; padding: 40px 20px; border-radius: 20px; text-align: center; }
        .user-icon { width: 60px; height: 60px; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; color: white; font-family: 'Montserrat'; font-weight: 900; font-size: 1.5rem; }
    </style>
</head>
<body>

    <!-- Header synchronized with other pages[cite: 22] -->
    <header style="padding: 20px 0; background: #F9F7F2; position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid rgba(0,0,0,0.05);">
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 5%; display: flex; justify-content: space-between; align-items: center;">
            <a href="index.php" class="logo" style="font-family: 'Montserrat'; font-weight: 900; font-size: 1.8rem; color: #333; text-decoration: none;">Univents</a>
            <nav>
                <ul style="display: flex; list-style: none; gap: 40px; margin: 0; padding: 0;">
                    <li><a href="index.php" style="text-decoration: none; color: #666; font-weight: 600; font-size: 0.9rem;">HOME</a></li>
                    <li><a href="events.php" style="text-decoration: none; color: #666; font-weight: 600; font-size: 0.9rem;">EVENTS</a></li>
                    <li><a href="rsvps.php" style="text-decoration: none; color: #666; font-weight: 600; font-size: 0.9rem;">RSVPs</a></li>
                    <li><a href="about.php" class="active" style="text-decoration: none; color: #4BA68D; font-weight: 600; font-size: 0.9rem; border-bottom: 2px solid #4BA68D; padding-bottom: 5px;">ABOUT</a></li>
                </ul>
            </nav>
            <div class="nav-buttons" style="display: flex; align-items: center; gap: 25px;">
                <?php if (!$is_logged_in): ?>
                    <a href="register.php" class="btn-text" style="text-decoration: none; color: #333; font-weight: 700; font-size: 0.9rem;">SIGN-UP</a>
                    <a href="login.php" class="btn-primary-nav" style="background: #F1948A; color: white; padding: 12px 28px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 0.9rem;">LOG-IN</a>
                <?php else: ?>
                    <a href="logout.php" class="btn-primary-nav" style="background: #F1948A; color: white; padding: 12px 28px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 0.9rem;">LOGOUT</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- HERO SECTION[cite: 13] -->
    <section class="about-hero">
        <div class="about-circle-purp"></div>
        <div class="about-circle-gold"></div>
        <div class="container hero-flex">
            <div class="hero-text">
                <span class="section-label" style="color: var(--teal);">About UNIVENTS</span>
                <h1 class="display-font">BRINGING <br> CAMPUS LIFE <br> TOGETHER</h1>
                <p>UNIVENTS was built to solve a real frustration: students missing out on events because information is scattered, and organizers struggling to reach their audience. We fix both.</p>
            </div>
            <div class="stats-glass-card">
                <div class="stat-item">
                    <span class="stat-num" style="color: #F1948A;">4.2K</span>
                    <span class="stat-label">Registered Students</span>
                </div>
                <div class="stat-item">
                    <span class="stat-num" style="color: #76D7C4;">120+</span>
                    <span class="stat-label">Active Events</span>
                </div>
                <div class="stat-item">
                    <span class="stat-num" style="color: #5DADE2;">38</span>
                    <span class="stat-label">Partner Organizations</span>
                </div>
                <div class="stat-item">
                    <span class="stat-num" style="color: #F7DC6F;">4.6★</span>
                    <span class="stat-label">Average Event Rating</span>
                </div>
            </div>
        </div>
    </section>

    <!-- PROBLEM SECTION[cite: 13] -->
    <section class="problem-section">
        <div class="container problem-grid">
            <div>
                <span class="section-label" style="color: #F39C12;">The Problem</span>
                <h2 class="display-font">UNIVERSITY <br> PAIN POINT</h2>
            </div>
            <div class="problem-content">
                <p>Students often struggle to find information about upcoming events, activities, and opportunities on campus. Announcements are scattered across social media, posters, messaging groups, and word-of-mouth.</p>
                <p>Because of this fragmentation, many students miss out on events that could benefit them academically, socially, or professionally.</p>
                <p>At the same time, event organizers face difficulties promoting their events effectively. Even well-planned activities receive low attendance simply because students were unaware.</p>
            </div>
        </div>
    </section>

    <!-- HUB SECTION[cite: 13] -->
    <section class="hub-section">
        <div class="container">
            <span class="section-label">The Solution</span>
            <h2 class="display-font">A CENTRALIZED HUB FOR ALL UNIVERSITY EVENTS</h2>
            <div class="hub-cards">
                <div class="hub-card">
                    <i class='bx bx-grid-alt'></i>
                    <h4>Centralized Discovery</h4>
                    <p>All campus events in one place. Search, filter, and browse by category, organization, or date.</p>
                </div>
                <div class="hub-card">
                    <i class='bx bx-user-voice'></i>
                    <h4>Two Dashboards</h4>
                    <p>One platform, two experiences. Students get a participant view; organizers get management tools.</p>
                </div>
                <div class="hub-card">
                    <i class='bx bx-pulse'></i>
                    <h4>Live Insights</h4>
                    <p>Real-time RSVP tracking, attendance monitoring, and engagement analytics for organizers.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- TARGET USERS[cite: 13] -->
    <section class="target-section">
        <div class="container">
            <span class="section-label" style="color: #E74C3C;">The Team</span>
            <h2 class="display-font">TARGET USERS</h2>
            <div class="user-grid">
                <div class="user-card">
                    <div class="user-icon" style="background: #4BA68D;">S</div>
                    <h4 style="font-family: 'Montserrat'; font-weight: 900;">Students</h4>
                    <p style="font-size: 0.85rem; color: #777;">Discover, RSVP, and attend events that match their interests and schedule.</p>
                </div>
                <div class="user-card">
                    <div class="user-icon" style="background: #E68A6E;">O</div>
                    <h4 style="font-family: 'Montserrat'; font-weight: 900;">Organizations</h4>
                    <p style="font-size: 0.85rem; color: #777;">Create events, manage RSVPs, and reach the whole university community.</p>
                </div>
                <div class="user-card">
                    <div class="user-icon" style="background: #3498DB;">D</div>
                    <h4 style="font-family: 'Montserrat'; font-weight: 900;">Departments</h4>
                    <p style="font-size: 0.85rem; color: #777;">Host academic and professional events with built-in promotion tools.</p>
                </div>
                <div class="user-card">
                    <div class="user-icon" style="background: #9B59B6;">A</div>
                    <h4 style="font-family: 'Montserrat'; font-weight: 900;">Administrators</h4>
                    <p style="font-size: 0.85rem; color: #777;">Oversee platform activity, verify organizations, and monitor campus engagement.</p>
                </div>
            </div>
        </div>
    </section>

    <footer style="background: #0D1312; color: white; padding: 80px 0; margin-top: 50px;">
        <div class="container" style="display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr; gap: 50px;">
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                    <img src="logo_icon.png" alt="Logo" style="width: 40px; height: 40px;"> <!-- Verbatim reference to brand assets[cite: 13] -->
                    <h2 style="font-family: 'Montserrat'; font-size: 1.8rem; margin: 0; font-weight: 900;">Univents</h2>
                </div>
                <p style="opacity: 0.6; font-size: 0.9rem; line-height: 1.6; font-family: 'Inter', sans-serif;">
                    The smarter way to discover, RSVP, and manage university events on campus.
                </p>
            </div>
            
            <div>
                <h4 style="margin-bottom: 25px; font-family: 'Montserrat'; font-weight: 700; font-size: 1rem;">Platform</h4>
                <ul style="list-style: none; padding: 0; margin: 0; line-height: 2;">
                    <li><a href="events.php" style="color: white; text-decoration: none; opacity: 0.6; font-size: 0.9rem;">Browse Events</a></li>
                    <li><a href="rsvps.php" style="color: white; text-decoration: none; opacity: 0.6; font-size: 0.9rem;">My RSVPs</a></li>
                    <li><a href="#" style="color: white; text-decoration: none; opacity: 0.6; font-size: 0.9rem;">For Organizers</a></li>
                    <li><a href="#" style="color: white; text-decoration: none; opacity: 0.6; font-size: 0.9rem;">Reviews</a></li>
                </ul>
            </div>

            <div>
                <h4 style="margin-bottom: 25px; font-family: 'Montserrat'; font-weight: 700; font-size: 1rem;">Company</h4>
                <ul style="list-style: none; padding: 0; margin: 0; line-height: 2;">
                    <li><a href="about.php" style="color: white; text-decoration: none; opacity: 0.6; font-size: 0.9rem;">About</a></li>
                    <li><a href="#" style="color: white; text-decoration: none; opacity: 0.6; font-size: 0.9rem;">Contact</a></li>
                    <li><a href="#" style="color: white; text-decoration: none; opacity: 0.6; font-size: 0.9rem;">Privacy Policy</a></li>
                    <li><a href="#" style="color: white; text-decoration: none; opacity: 0.6; font-size: 0.9rem;">Terms</a></li>
                </ul>
            </div>

            <div>
                <h4 style="margin-bottom: 25px; font-family: 'Montserrat'; font-weight: 700; font-size: 1rem;">Support</h4>
                <ul style="list-style: none; padding: 0; margin: 0; line-height: 2;">
                    <li><a href="#" style="color: white; text-decoration: none; opacity: 0.6; font-size: 0.9rem;">Help Center</a></li>
                    <li><a href="#" style="color: white; text-decoration: none; opacity: 0.6; font-size: 0.9rem;">Report an Issue</a></li>
                    <li><a href="#" style="color: white; text-decoration: none; opacity: 0.6; font-size: 0.9rem;">Feedback</a></li>
                </ul>
            </div>
        </div>
    </footer>

</body>
</html>