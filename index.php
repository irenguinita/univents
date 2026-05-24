<?php
session_start();
include 'db.php';

$is_logged_in = isset($_SESSION['user_id']);
$role = $_SESSION['role'] ?? null;
$dashboard_link = ($role === 'org') ? 'org_dashboard.php' : 'student_dashboard.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Univents - Smarter Events Start Here</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@800;900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>

        html, body {
            height: 100%;
            margin: 0;
        }
        body {
            display: flex;
            flex-direction: column;
            background: #F9F7F2; 
        }
        main {
            flex: 1 0 auto; 
            display: flex;
            flex-direction: column;
        }
        .how-it-works {
            flex-grow: 1; 
            background: #FFFFFF;
            padding: 100px 0;
        }
        footer {
            flex-shrink: 0;
        }

        /* Restoration of Hero spacing and layout */
        .hero { padding: 80px 0; background: #F9F7F2; }
        .hero-grid { 
            display: grid; 
            grid-template-columns: 1.2fr 1fr; 
            gap: 50px; 
            align-items: center; 
        }
    </style>
</head>
<body>

    <!-- NAVIGATION -->
    <header style="padding: 20px 0; background: #F9F7F2; position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid rgba(0,0,0,0.05);">
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 5%; display: flex; justify-content: space-between; align-items: center;">
            <a href="index.php" class="logo" style="font-family: 'Montserrat'; font-weight: 900; font-size: 1.8rem; color: #333; text-decoration: none; display:flex; align-items:center; gap:10px;">
                <img src="logo.png" alt="Univents Logo" style="height:38px; width:auto; object-fit:contain;" onerror="this.style.display='none'">
                Univents
            </a>
            <nav>
                <ul style="display: flex; list-style: none; gap: 40px; margin: 0; padding: 0;">
                    <li><a href="index.php" style="text-decoration: none; color: #4BA68D; font-weight: 600; font-size: 0.9rem; border-bottom: 2px solid #4BA68D; padding-bottom: 5px;">HOME</a></li>
                    <li><a href="events.php" style="text-decoration: none; color: #666; font-weight: 600; font-size: 0.9rem;">EVENTS</a></li>
                    <li><a href="rsvps.php" style="text-decoration: none; color: #666; font-weight: 600; font-size: 0.9rem;">RSVPs</a></li>
                    <li><a href="about.php" style="text-decoration: none; color: #666; font-weight: 600; font-size: 0.9rem;">ABOUT</a></li>
                </ul>
            </nav>
            <div class="nav-buttons" style="display: flex; align-items: center; gap: 25px;">
                <?php if (!$is_logged_in): ?>
                    <a href="register.php" style="text-decoration: none; color: #333; font-weight: 700; font-size: 0.9rem;">SIGN-UP</a>
                    <a href="login.php" style="background: #F1948A; color: white; padding: 12px 28px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 0.9rem;">LOG-IN</a>
                <?php else: ?>
                    <a href="<?= $dashboard_link ?>" style="text-decoration: none; color: #333; font-weight: 700; font-size: 0.9rem;">DASHBOARD</a>
                    <a href="logout.php" style="background: #F1948A; color: white; padding: 12px 28px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 0.9rem;">LOGOUT</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main>

        <!-- HERO (With Original Papers restored) -->
        <section class="hero">
            <div class="container hero-grid" style="max-width: 1200px; margin: 0 auto; padding: 0 5%;">
                <div class="hero-text">
                    <span class="badge">University Event Hub &bull; Live</span>
                    <h1>SMARTER <br> EVENTS <br> <span class="teal-text">START HERE</span></h1>
                    <p>An all-in-one platform where students discover and register for campus events — while organizers stay in control.</p>
                    <a href="events.php" class="btn-dark">Explore Events <span>→</span></a>
                    <div class="stats">
                        <div class="stat-item"><h3>120+</h3><p>Active Events</p></div>
                        <div class="stat-item"><h3>4.2K</h3><p>Students Joined</p></div>
                        <div class="stat-item"><h3>38</h3><p>Organizers</p></div>
                    </div>
                </div>

                <div class="hero-visual">
                    <div class="paper p1">
                        <img src="paper1.png" alt="Event Card 1">
                    </div>
                    <div class="paper p2">
                        <img src="paper2.png" alt="Event Card 2">
                    </div>
                    <div class="paper p3">
                        <img src="paper3.png" alt="Event Card 3">
                    </div>
                </div>
            </div>
        </section>

        <!-- HOW IT WORKS (With Original Glass Circles restored) -->
        <section class="how-it-works">
            <div class="container works-grid" style="max-width: 1200px; margin: 0 auto; padding: 0 5%;">
                <div class="works-info">
                    <span class="sub-label">HOW IT WORKS</span>
                    <h2>BUILT FOR <br> STUDENTS <br> &amp; ORGANIZERS</h2>
                    <p>Two powerful dashboards — one platform. Everything you need to participate in or manage campus events.</p>

                    <div class="step">
                        <div class="step-num">01</div>
                        <div class="step-body">
                            <h4>Create an Account</h4>
                            <p>Sign up with your institutional email. Choose student or organizer and get instant access.</p>
                        </div>
                    </div>

                    <div class="step">
                        <div class="step-num">02</div>
                        <div class="step-body">
                            <h4>Discover &amp; RSVP</h4>
                            <p>Browse upcoming events filtered by category, org, or date. RSVP in seconds.</p>
                        </div>
                    </div>

                    <div class="step">
                        <div class="step-num">03</div>
                        <div class="step-body">
                            <h4>Track &amp; Engage</h4>
                            <p>Students track attendance live. Students leave reviews and follow their favorite organizations.</p>
                        </div>
                    </div>
                </div>

                <div class="works-visual">
                    <div class="glass-circle gc-1"></div>
                    <div class="glass-circle gc-2"></div>
                    <div class="glass-circle gc-3"></div>
                    <img src="dashboard_preview.png" alt="Dashboard Preview" class="laptop-img">
                </div>
            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <footer style="background: #0D1312; color: white; padding: 80px 0;">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 5%; display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr; gap: 50px;">
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                    <img src="logo.png" alt="Univents Logo" style="height: 36px; width: auto; object-fit: contain;" onerror="this.style.display='none'">
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