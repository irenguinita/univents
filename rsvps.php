<?php
include 'db.php';
session_start();

$is_logged_in = isset($_SESSION['user_id']);
$uid = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;
$dashboard_link = ($role === 'org') ? 'org_dashboard.php' : 'student_dashboard.php';

$my_rsvps = [];
if ($is_logged_in) {
    $stmt = $conn->prepare("SELECT e.*, r.rsvp_status, o.org_name FROM event e JOIN rsvp r ON e.event_id = r.event_id JOIN organization o ON e.organization_id = o.user_id WHERE r.student_id = ?");
    $stmt->execute([$uid]);
    $my_rsvps = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My RSVPs - Univents</title>
    <link rel="stylesheet" href="about-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        header { padding: 20px 0; background: #F9F7F2; position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .nav-wrapper { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 5%; }
        .logo { font-family: 'Montserrat'; font-weight: 900; font-size: 1.8rem; color: #333; text-decoration: none; }
        nav ul { display: flex; list-style: none; gap: 40px; margin: 0; padding: 0; }
        nav ul li a { text-decoration: none; color: #666; font-weight: 600; font-size: 0.9rem; }
        nav ul li a.active { color: #4BA68D; border-bottom: 2px solid #4BA68D; padding-bottom: 5px; }
        .nav-buttons { display: flex; align-items: center; gap: 25px; }
        .btn-primary-nav { background: #F1948A; color: white; padding: 12px 28px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 0.9rem; }

        .rsvp-hero { background: #E68A6E; padding: 80px 0; color: white; position: relative; overflow: hidden; }
        .rsvp-circle-top { position: absolute; width: 450px; height: 450px; background: rgba(255,255,255,0.1); border-radius: 50%; right: -80px; top: -150px; }
        .rsvp-circle-bottom { position: absolute; width: 250px; height: 250px; background: rgba(80,40,40,0.1); border-radius: 50%; right: 100px; bottom: -80px; }
        .hero-title { font-family: 'Montserrat'; font-size: 4.5rem; line-height: 0.9; margin: 15px 0; font-weight: 900; }
        .hero-desc { max-width: 400px; opacity: 0.9; font-size: 0.9rem; margin-bottom: 30px; }

        /* Auth prompt section */
        .auth-prompt-section { 
            min-height: 60vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            text-align: center; 
            background: #F9F7F2;
        }
        .auth-prompt-box {
            background: white;
            padding: 60px 80px;
            border-radius: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.06);
        }
        .auth-prompt-box h2 {
            font-family: 'Montserrat';
            font-weight: 900;
            font-size: 2rem;
            color: #333;
            margin-bottom: 15px;
        }
        .auth-prompt-box p {
            color: #777;
            font-size: 1rem;
            margin-bottom: 35px;
        }
        .auth-prompt-btns { display: flex; gap: 15px; justify-content: center; }
        .btn-signup-prompt {
            background: white;
            color: #333;
            padding: 14px 32px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            border: 2px solid #eee;
            transition: all 0.2s;
        }
        .btn-login-prompt {
            background: #F1948A;
            color: white;
            padding: 14px 32px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .event-card-row:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08) !important;
            background-color: #fff;
        }
    </style>
</head>
<body style="background: #F9F7F2;">

    <!-- HEADER: Logged-out = full nav like about.php | Logged-in = back + logout -->
    <header>
        <div class="nav-wrapper">
            <a href="index.php" class="logo">Univents</a>

            <?php if (!$is_logged_in): ?>
                <!-- Full nav for guests -->
                <nav>
                    <ul>
                        <li><a href="index.php">HOME</a></li>
                        <li><a href="events.php">EVENTS</a></li>
                        <li><a href="rsvps.php" class="active">RSVPs</a></li>
                        <li><a href="about.php">ABOUT</a></li>
                    </ul>
                </nav>
                <div class="nav-buttons">
                    <a href="register.php" style="text-decoration: none; color: #333; font-weight: 700; font-size: 0.9rem;">SIGN-UP</a>
                    <a href="login.php" class="btn-primary-nav">LOG-IN</a>
                </div>
            <?php else: ?>
                <!-- Back + logout for logged-in users -->
                <div class="nav-buttons">
                    <a href="<?= $dashboard_link ?>" style="text-decoration: none; color: #326257; font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; gap: 6px;">
                        <i class='bx bx-arrow-back'></i> BACK TO DASHBOARD
                    </a>
                    <a href="logout.php" class="btn-primary-nav">LOGOUT</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <section class="rsvp-hero">
        <div class="rsvp-circle-top"></div>
        <div class="rsvp-circle-bottom"></div>
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 5%;">
            <span class="sub-label" style="color: rgba(255,255,255,0.8);">Campus Events</span>
            <h1 class="hero-title">WHAT'S HAPPENING <br> ON CAMPUS</h1>
            <p class="hero-desc">Track all your RSVPs, check-in statuses, and event history in one place.</p>
        </div>
    </section>

    <?php if (!$is_logged_in): ?>
        <!-- Auth prompt for guests -->
        <section class="auth-prompt-section">
            <div class="auth-prompt-box">
                <h2>READY TO JOIN THE ACTION?</h2>
                <p>Log in or sign up to RSVP to events and track your campus event history.</p>
                <div class="auth-prompt-btns">
                    <a href="register.php" class="btn-signup-prompt">SIGN UP FREE</a>
                    <a href="login.php" class="btn-login-prompt">LOG IN</a>
                </div>
            </div>
        </section>
    <?php else: ?>
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 80px 5%;">
            <h2 style="font-family: 'Montserrat'; font-weight: 900; font-size: 2.5rem; margin-bottom: 40px;">MY RSVPS</h2>
            
            <?php if(empty($my_rsvps)): ?>
                <p style="text-align: center; color: #777; font-size: 1.2rem;">You haven't RSVP'd to any events yet.</p>
            <?php else: ?>
                <?php foreach($my_rsvps as $r): ?>
                    <div class="event-card-row" 
                         onclick="window.location='view_event.php?id=<?= $r['event_id'] ?>'"
                         style="background: white; padding: 30px; border-radius: 20px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 5px 15px rgba(0,0,0,0.03); cursor: pointer; transition: all 0.3s ease;">
                        <div>
                            <strong style="color: #4BA68D; text-transform: uppercase; font-size: 0.8rem;"><?= htmlspecialchars($r['org_name']) ?></strong>
                            <h3 style="font-family: 'Montserrat'; font-weight: 900; margin: 5px 0; color: #333;"><?= htmlspecialchars($r['title']) ?></h3>
                            <p style="color: #777; font-size: 0.9rem;">
                                <i class='bx bx-calendar' style="vertical-align: middle;"></i> <?= date('M d, Y', strtotime($r['start_datetime'])) ?> 
                                <span style="margin: 0 10px;">•</span> 
                                <i class='bx bx-map' style="vertical-align: middle;"></i> <?= htmlspecialchars($r['venue']) ?>
                            </p>
                        </div>
                        <span style="padding: 8px 15px; border-radius: 8px; font-weight: 700; background: #D4EFDF; color: #1E8449;"><?= strtoupper($r['rsvp_status']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</body>
</html>