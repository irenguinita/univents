<?php
include 'db.php';
session_start();

$search = $_GET['search'] ?? '';

$sql = "
    SELECT e.*, o.org_name, 
    (e.maximum_capacity - (SELECT COUNT(*) FROM rsvp r WHERE r.event_id = e.event_id)) as spots_left
    FROM event e
    JOIN organization o ON e.organization_id = o.user_id
";

$params = [];
if (!empty($search)) {
    $sql .= " WHERE e.title ILIKE ? OR o.org_name ILIKE ? OR e.description ILIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

$sql .= " ORDER BY e.start_datetime ASC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

$is_logged_in = isset($_SESSION['user_id']);
$role = $_SESSION['role'] ?? null;
$dashboard_link = ($role === 'org') ? 'org_dashboard.php' : 'student_dashboard.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - Univents</title>
    <link rel="stylesheet" href="about-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        header { padding: 20px 0; background: #F9F7F2; position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .nav-wrapper { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 5%; }
        .logo { font-family: 'Montserrat'; font-weight: 900; font-size: 1.8rem; color: #333; text-decoration: none; }
        nav ul { display: flex; list-style: none; gap: 40px; margin: 0; padding: 0; }
        nav ul li a { text-decoration: none; color: #666; font-weight: 600; font-size: 0.9rem; transition: 0.3s; }
        nav ul li a.active { color: #4BA68D; border-bottom: 2px solid #4BA68D; padding-bottom: 5px; }
        .nav-buttons { display: flex; align-items: center; gap: 25px; }
        .btn-text { text-decoration: none; color: #333; font-weight: 700; font-size: 0.9rem; }
        .btn-primary-nav { background: #F1948A; color: white; padding: 12px 28px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 0.9rem; }

        .events-hero { background: #326257; padding: 80px 0; color: white; position: relative; overflow: hidden; }
        .hero-circle-lg { position: absolute; width: 500px; height: 500px; background: rgba(255,255,255,0.08); border-radius: 50%; right: -100px; top: -50px; }
        .hero-circle-sm { position: absolute; width: 300px; height: 300px; background: rgba(0,0,0,0.15); border-radius: 50%; right: 50px; bottom: -100px; }
        .hero-title { font-family: 'Montserrat'; font-size: 4.5rem; line-height: 0.9; margin: 15px 0; font-weight: 900; }
        .hero-desc { max-width: 400px; opacity: 0.8; font-size: 0.9rem; margin-bottom: 30px; }
        .search-container { display: flex; gap: 10px; max-width: 600px; }
        .search-input { flex: 1; padding: 15px 25px; border-radius: 12px; border: none; background: rgba(255,255,255,0.2); color: white; outline: none; }
        .search-input::placeholder { color: rgb(114, 186, 169); }
        .btn-search { background: #E68A6E; border: none; padding: 0 30px; border-radius: 12px; color: white; font-weight: bold; cursor: pointer; }

        .filters { display: flex; gap: 12px; padding: 40px 0; flex-wrap: wrap; }
        .chip { background: white; padding: 10px 20px; border-radius: 50px; font-size: 0.8rem; font-weight: 600; color: #444; text-decoration: none; border: 1px solid #eee; }
        .chip.active { background: #446860; color: white; border-color: #446860; }

        .events-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px; padding-bottom: 80px; }
        .e-card { background: white; border-radius: 25px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.03); display: flex; flex-direction: column; transition: 0.3s; }
        .e-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .e-card-header { height: 160px; padding: 30px; display: flex; align-items: flex-end; }
        .cat-tag { background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 8px; color: white; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
        .e-card-body { padding: 30px; flex: 1; display: flex; flex-direction: column; }
        .e-org { font-size: 0.75rem; color: #888; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .e-org::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: #5DADE2; display: inline-block; }
        .e-title { font-family: 'Montserrat'; font-size: 1.4rem; margin: 15px 0; line-height: 1.2; flex: 1; font-weight: 900; }
        .e-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 0.8rem; color: #666; margin-bottom: 20px; }
        .e-card-footer { border-top: 1px solid #eee; padding-top: 20px; display: flex; justify-content: space-between; align-items: center; }
        .spots-label { color: #E67E22; font-weight: 700; font-size: 0.9rem; }
        .btn-view-details { background: #F1948A; color: white; padding: 12px 25px; border-radius: 12px; font-weight: 900; text-decoration: none; font-size: 0.8rem; transition: 0.2s; }
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
                        <li><a href="events.php" class="active">EVENTS</a></li>
                        <li><a href="rsvps.php">RSVPs</a></li>
                        <li><a href="about.php">ABOUT</a></li>
                    </ul>
                </nav>
                <div class="nav-buttons">
                    <a href="register.php" class="btn-text">SIGN-UP</a>
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

    <!-- Hero Section -->
    <section class="events-hero">
        <div class="hero-circle-lg"></div>
        <div class="hero-circle-sm"></div>
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 5%;">
            <span class="sub-label" style="color: #FAD7A0; font-weight: 700; text-transform: uppercase;">Campus Events</span>
            <h1 class="hero-title">WHAT'S HAPPENING <br> ON CAMPUS</h1>
            <p class="hero-desc">Browse, filter, and RSVP to events from all organizations across university.</p>
            <div class="search-container">
                <input type="text" class="search-input" placeholder="Search events, organizations, venue...">
                <button class="btn-search">Search</button>
            </div>
        </div>
    </section>

    <!-- Content Area -->
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 5%;">
        <div class="filters">
            <a href="#" class="chip active">All</a>
            <a href="#" class="chip">Technology</a>
            <a href="#" class="chip">Organization</a>
            <a href="#" class="chip">Hackathon</a>
            <a href="#" class="chip">Academic</a>
        </div>

        <div class="events-grid">
            <?php foreach($events as $index => $e): 
                $gradients = ['linear-gradient(135deg, #76D7C4, #48C9B0)', 'linear-gradient(135deg, #FAD7A0, #E67E22)', 'linear-gradient(135deg, #85929E, #34495E)'];
                $bg = $gradients[$index % count($gradients)];
            ?>
            <div class="e-card">
                <div class="e-card-header" style="background: <?= $bg ?>;">
                    <span class="cat-tag">Event</span>
                </div>
                <div class="e-card-body">
                    <span class="e-org"><?= strtoupper(htmlspecialchars($e['org_name'])) ?></span>
                    <h3 class="e-title"><?= htmlspecialchars($e['title']) ?></h3>
                    <div class="e-meta">
                        <span><i class='bx bx-calendar'></i> <?= date('M d', strtotime($e['start_datetime'])) ?></span>
                        <span><i class='bx bx-map-pin'></i> <?= htmlspecialchars($e['venue']) ?></span>
                    </div>
                    <div class="e-card-footer">
                        <span class="spots-label"><?= $e['spots_left'] ?> spots left</span>
                        <a href="view_event.php?id=<?= $e['event_id'] ?>" class="btn-view-details">View Details</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</body>
</html>