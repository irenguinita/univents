<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM student WHERE user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

$stmtNotifs = $conn->prepare("
    SELECT e.title, e.start_datetime, e.venue, e.end_datetime, r.timestamp as rsvp_time, o.org_name,
           CASE 
               WHEN e.start_datetime BETWEEN NOW() AND NOW() + INTERVAL '1 day' THEN 'tomorrow'
               WHEN e.start_datetime BETWEEN NOW() AND NOW() + INTERVAL '7 days' THEN 'upcoming'
               WHEN e.end_datetime < NOW() THEN 'past'
               ELSE 'registered'
           END as notif_type
    FROM rsvp r
    JOIN event e ON r.event_id = e.event_id
    JOIN organization o ON e.organization_id = o.user_id
    WHERE r.student_id = ? AND r.rsvp_status != 'cancelled'
    ORDER BY e.start_datetime DESC
");
$stmtNotifs->execute([$user_id]);
$notifs = $stmtNotifs->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications - Univents</title>
    <link rel="stylesheet" href="dashboard-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .notif-list { max-width: 720px; display: flex; flex-direction: column; gap: 14px; margin-top: 20px; }
        .notif-card {
            background: white; border-radius: 16px; padding: 20px 24px;
            display: flex; align-items: flex-start; gap: 18px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06); position: relative;
            border-left: 4px solid transparent;
            transition: transform 0.2s;
        }
        .notif-card:hover { transform: translateX(4px); }
        .notif-card.tomorrow { border-left-color: #E74C3C; }
        .notif-card.upcoming { border-left-color: #E67E22; }
        .notif-card.registered { border-left-color: var(--teal); }
        .notif-card.past { border-left-color: #aaa; opacity: 0.75; }
        .notif-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
        .notif-icon.tomorrow { background: #FADBD8; }
        .notif-icon.upcoming { background: #FDEBD0; }
        .notif-icon.registered { background: #D5EFE9; }
        .notif-icon.past { background: #f0f0f0; }
        .notif-body { flex: 1; }
        .notif-body h4 { font-family: 'Montserrat'; font-size: 0.95rem; font-weight: 800; margin-bottom: 4px; }
        .notif-body p { color: #777; font-size: 0.85rem; margin-bottom: 2px; }
        .notif-badge { font-size: 0.7rem; font-weight: 800; padding: 3px 10px; border-radius: 20px; display: inline-block; margin-bottom: 8px; }
        .badge-tomorrow { background: #FADBD8; color: #C0392B; }
        .badge-upcoming { background: #FDEBD0; color: #E67E22; }
        .badge-registered { background: #D5EFE9; color: var(--teal); }
        .badge-past { background: #f0f0f0; color: #888; }
        .notif-time { font-size: 0.75rem; color: #bbb; font-weight: 600; flex-shrink: 0; margin-top: 2px; }
        .empty-state { text-align: center; padding: 60px 20px; color: #ccc; }
        .empty-state i { font-size: 3.5rem; margin-bottom: 16px; }
        .page-title { font-family: 'Montserrat'; font-size: 2.2rem; font-weight: 900; margin-bottom: 6px; }
        .page-sub { color: #888; font-size: 0.9rem; margin-bottom: 10px; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="user-avatar"><?= substr($student['name'] ?? 'U', 0, 1) ?></div>
            <div class="user-info">
                <h4><?= htmlspecialchars($student['name']) ?></h4>
                <p>STUDENT • <?= $student['year_level'] ?> YEAR</p>
            </div>
        </div>
        <nav class="side-nav">
            <p class="nav-label">MAIN</p>
            <a href="student_dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
            <a href="events.php"><i class='bx bx-calendar-event'></i> Browse Events</a>
            <a href="rsvps.php"><i class='bx bx-bookmark-heart'></i> My RSVPs</a>
            <p class="nav-label">ACTIVITY</p>
            <a href="notifications.php" class="active"><i class='bx bx-bell'></i> Notifications</a>
            <a href="reviews.php"><i class='bx bx-star'></i> Reviews</a>
            <p class="nav-label">ACCOUNT</p>
            <a href="edit_profile.php"><i class='bx bx-user-circle'></i> Edit Profile</a>
            <a href="logout.php"><i class='bx bx-log-out'></i> Log out</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="logo">Univents</div>
            <div class="top-links">
                <a href="events.php">EVENTS</a>
                <a href="rsvps.php">RSVPs</a>
            </div>
        </header>

        <div class="content-body">
            <h1 class="page-title"><i class='bx bx-bell' style="color:var(--teal);"></i> Notifications</h1>
            <p class="page-sub">Stay updated on your registered events and activities.</p>

            <div class="notif-list">
                <?php if(empty($notifs)): ?>
                <div class="empty-state">
                    <i class='bx bx-bell-off'></i>
                    <p style="font-size:1rem;font-weight:600;">No notifications yet.</p>
                    <p style="font-size:0.85rem;">Register for events to get updates here.</p>
                    <a href="events.php" style="color:var(--teal);font-weight:700;">Browse Events →</a>
                </div>
                <?php endif; ?>

                <?php foreach($notifs as $n):
                    $type = $n['notif_type'];
                    $icons = ['tomorrow'=>'🔔','upcoming'=>'📅','registered'=>'✅','past'=>'🏁'];
                    $labels = ['tomorrow'=>'EVENT TOMORROW','upcoming'=>'UPCOMING EVENT','registered'=>'REGISTERED','past'=>'PAST EVENT'];
                    $icon = $icons[$type] ?? '📌';
                    $label = $labels[$type] ?? 'EVENT';
                ?>
                <div class="notif-card <?= $type ?>">
                    <div class="notif-icon <?= $type ?>"><?= $icon ?></div>
                    <div class="notif-body">
                        <span class="notif-badge badge-<?= $type ?>"><?= $label ?></span>
                        <h4><?= htmlspecialchars($n['title']) ?></h4>
                        <p><i class='bx bx-buildings' style="font-size:0.8rem;"></i> <?= htmlspecialchars($n['org_name']) ?></p>
                        <p><i class='bx bx-map-pin' style="font-size:0.8rem;"></i> <?= htmlspecialchars($n['venue']) ?> &nbsp;•&nbsp; <?= date('M d, Y • g:i A', strtotime($n['start_datetime'])) ?></p>
                    </div>
                    <div class="notif-time">
                        <?php
                        $rsvpDt = new DateTime($n['rsvp_time']);
                        $now = new DateTime();
                        $diff = $now->diff($rsvpDt);
                        if ($diff->days >= 1) echo $diff->days . 'd ago';
                        elseif ($diff->h >= 1) echo $diff->h . 'h ago';
                        else echo 'just now';
                        ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>
