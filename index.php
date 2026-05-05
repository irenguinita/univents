<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Univents - Smarter Events Start Here</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>

    <!-- NAVIGATION -->
    <header>
        <div class="container nav-wrapper">
            <div class="logo">
                <span>Univents</span>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php" class="active">HOME</a></li>
                    <li><a href="events.php">EVENTS</a></li>
                    <li><a href="rsvps.php">RSVPs</a></li>
                    <li><a href="about.php">ABOUT</a></li>
                </ul>
            </nav>
            <div class="nav-buttons">
                <a href="register.php" class="btn-text">SIGN-UP</a>
                <a href="login.php" class="btn-primary login-btn">LOG-IN</a>
            </div>
        </div>
    </header>

    <main>
        <!-- HERO SECTION -->
        <section class="hero">
            <div class="container hero-grid">
                <div class="hero-text">
                    <span class="badge">University Event Hub • Live</span>
                    <h1>SMARTER <br> EVENTS <br> <span class="teal-text">START HERE</span></h1>
                    <p>An all-in-one platform where students effortlessly discover and register for campus events — while organizers stay in full control with live attendance insights.</p>
                    
                    <a href="events.php" class="btn-dark">Explore Events <span>→</span></a>

                    <div class="stats">
                        <div class="stat-item">
                            <h3>120+</h3>
                            <p>Active Events</p>
                        </div>
                        <div class="stat-item">
                            <h3>4.2K</h3>
                            <p>Students Joined</p>
                        </div>
                        <div class="stat-item">
                            <h3>38</h3>
                            <p>Organizations</p>
                        </div>
                    </div>
                </div>

                <!-- OVERLAPPING PAPERS AREA -->
                <div class="hero-visual">
                    <img src="paper1.png" alt="Paper 1" class="paper p1">
                    <img src="paper2.png" alt="Paper 2" class="paper p2">
                    <img src="paper3.png" alt="Paper 3" class="paper p3">
                </div>
            </div>
        </section>

        <!-- HOW IT WORKS SECTION -->
        <section class="how-it-works">
            <div class="container works-grid">
                <div class="works-info">
                    <span class="sub-label">HOW IT WORKS</span>
                    <h2>BUILT FOR <br> STUDENTS <br> & ORGANIZERS</h2>
                    <p>Two powerful dashboards — one platform. Everything you need to participate in or manage campus events.</p>

                    <div class="steps">
                        <div class="step">
                            <div class="step-num step-1">01</div>
                            <div class="step-content">
                                <h4>Create an Account</h4>
                                <p>Sign up with your institutional email. Choose student or organizer and get instant access.</p>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-num step-2">02</div>
                            <div class="step-content">
                                <h4>Discover & RSVP</h4>
                                <p>Browse upcoming events filtered by category, org, or date. RSVP in seconds.</p>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-num step-3">03</div>
                            <div class="step-content">
                                <h4>Track & Engage</h4>
                                <p>Organizers monitor attendance live. Students leave reviews and follow their favorite organizations.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LAPTOP VISUAL WITH BLOBS -->
                <div class="works-visual">
                    <div class="blob b-pink"></div>
                    <div class="blob b-blue"></div>
                    <div class="blob b-teal"></div>
                    <img src="dashboard_preview.png" alt="Dashboard Preview" class="laptop-img">
                </div>
            </div>
        </section>
    </main>

    <!-- FOOTER -->
    <footer>
        <div class="container footer-grid">
            <div class="footer-brand">
                <div class="logo-footer"><span>Univents</span></div>
                <p>The smarter way to discover, RSVP, and manage university events on campus.</p>
            </div>
            <div class="footer-links">
                <h4>Platform</h4>
                <a href="#">Browse Events</a>
                <a href="#">My RSVPs</a>
                <a href="#">For Organizers</a>
                <a href="#">Reviews</a>
            </div>
            <div class="footer-links">
                <h4>Company</h4>
                <a href="#">About</a>
                <a href="#">Contact</a>
                <a href="#">Privacy Policy</a>
                <a href="#">Terms</a>
            </div>
            <div class="footer-links">
                <h4>Support</h4>
                <a href="#">Help Center</a>
                <a href="#">Report an Issue</a>
                <a href="#">Feedback</a>
            </div>
        </div>
    </footer>

</body>
</html>