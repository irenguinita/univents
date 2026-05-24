<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'org') {
    header("Location: login.php");
    exit();
}

$org_id = $_SESSION['user_id'];

$stmtOrg = $conn->prepare("SELECT * FROM organization WHERE user_id = ?");
$stmtOrg->execute([$org_id]);
$org = $stmtOrg->fetch();

// stats
$totalManaged = $conn->prepare("SELECT COUNT(*) FROM event WHERE organization_id = ?");
$totalManaged->execute([$org_id]);
$total_events = $totalManaged->fetchColumn();

$totalReg = $conn->prepare("SELECT COUNT(*) FROM rsvp r JOIN event e ON r.event_id = e.event_id WHERE e.organization_id = ?");
$totalReg->execute([$org_id]);
$total_registrants = $totalReg->fetchColumn();

$avgRatingStmt = $conn->prepare("SELECT AVG(rv.star_rating) FROM review rv JOIN event e ON rv.event_id = e.event_id WHERE e.organization_id = ?");
$avgRatingStmt->execute([$org_id]);
$global_avg = $avgRatingStmt->fetchColumn();

$stmt = $conn->prepare("
    SELECT e.event_id, e.title, e.start_datetime, e.venue, e.current_status,
           e.maximum_capacity,
           COALESCE(AVG(rv.star_rating), 0) as avg_rating,
           COUNT(DISTINCT rv.review_id) as review_count,
           COUNT(DISTINCT r.student_id) as attendance
    FROM event e
    LEFT JOIN review rv ON rv.event_id = e.event_id
    LEFT JOIN rsvp r ON r.event_id = e.event_id
    WHERE e.organization_id = ?
    GROUP BY e.event_id
    ORDER BY e.start_datetime DESC
");
$stmt->execute([$org_id]);
$analytics = $stmt->fetchAll();

$selected_event_id = $_GET['event_id'] ?? null;
$selected_event = null;
$selected_reviews = [];
if ($selected_event_id) {
    $evStmt = $conn->prepare("SELECT * FROM event WHERE event_id = ? AND organization_id = ?");
    $evStmt->execute([$selected_event_id, $org_id]);
    $selected_event = $evStmt->fetch();
    if ($selected_event) {
        $revStmt = $conn->prepare("
            SELECT rv.*, s.name as student_name, s.department
            FROM review rv JOIN student s ON rv.student_id = s.user_id
            WHERE rv.event_id = ?
            ORDER BY rv.created_at DESC
        ");
        $revStmt->execute([$selected_event_id]);
        $selected_reviews = $revStmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics - Univents</title>
    <link rel="stylesheet" href="dashboard-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .analytics-table { width: 100%; border-collapse: collapse; background: white; border-radius: 18px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
        .analytics-table thead { background: #f9f9f9; }
        .analytics-table th { text-align: left; padding: 16px 20px; color: #888; font-size: 0.78rem; font-weight: 800; letter-spacing: 0.5px; border-bottom: 2px solid #eee; }
        .analytics-table td { padding: 18px 20px; border-bottom: 1px solid #f5f5f5; font-size: 0.88rem; vertical-align: middle; }
        .analytics-table tr:last-child td { border-bottom: none; }
        .analytics-table tr:hover td { background: #f9fffe; }
        .star-rating { color: #F1C40F; font-size: 0.85rem; }
        .btn-view-reviews { background: #F2EDE4; color: var(--teal); border: 1.5px solid var(--teal); padding: 7px 16px; border-radius: 8px; font-weight: 700; font-size: 0.78rem; cursor: pointer; text-decoration: none; display: inline-block; transition: 0.2s; }
        .btn-view-reviews:hover { background: var(--teal); color: white; }
        .capacity-bar-wrap { background: #eee; border-radius: 20px; height: 6px; width: 80px; display: inline-block; vertical-align: middle; margin-left: 8px; }
        .capacity-bar { background: var(--teal); height: 6px; border-radius: 20px; }
        .reviews-section { background: white; border-radius: 18px; padding: 28px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); margin-top: 28px; }
        .review-item { padding: 16px 0; border-bottom: 1px solid #f5f5f5; display: flex; gap: 16px; }
        .review-item:last-child { border-bottom: none; }
        .reviewer-avatar { width: 40px; height: 40px; background: var(--teal); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 1rem; flex-shrink: 0; }
        .review-body h5 { font-family: 'Montserrat'; font-size: 0.88rem; font-weight: 800; margin-bottom: 3px; }
        .review-body .stars { color: #F1C40F; font-size: 0.85rem; margin-bottom: 5px; }
        .review-body p { color: #555; font-size: 0.85rem; font-style: italic; }
        .review-date { color: #bbb; font-size: 0.75rem; font-weight: 600; }
        .page-title { font-family: 'Montserrat'; font-size: 2.2rem; font-weight: 900; margin-bottom: 6px; }
        .section-title { font-family: 'Montserrat'; font-weight: 800; font-size: 1.1rem; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="user-avatar" style="background: var(--teal);"><?= substr($org['org_name'], 0, 1) ?></div>
            <div class="user-info">
                <h4><?= htmlspecialchars($org['org_name']) ?></h4>
                <p>ORGANIZER • <?= $org['verification_status'] ?></p>
            </div>
        </div>
        <nav class="side-nav">
            <p class="nav-label">MAIN</p>
            <a href="org_dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
            <a href="events.php"><i class='bx bx-globe'></i> Public View</a>
            <p class="nav-label">OPERATIONS</p>
            <a href="create_event.php"><i class='bx bx-plus-circle'></i> Create Event</a>
            <a href="org_summary.php" class="active"><i class='bx bx-bar-chart-alt-2'></i> Analytics</a>
            <p class="nav-label">ACCOUNT</p>
            <a href="edit_profile.php"><i class='bx bx-user-circle'></i> Edit Profile</a>
            <a href="logout.php"><i class='bx bx-log-out'></i> Log out</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="logo">Univents</div>
            <div class="top-links">
                <a href="org_dashboard.php">DASHBOARD</a>
                <a href="events.php">EVENTS</a>
            </div>
        </header>

        <div class="content-body">
            <h1 class="page-title">Analytics</h1>

            <div class="stats-grid">
                <div class="stat-card"><div class="dot orange"></div><h2><?= $total_events ?></h2><p>Total Events</p></div>
                <div class="stat-card"><div class="dot teal"></div>
                    <h2><?= $global_avg > 0 ? number_format($global_avg, 1) : '—' ?></h2>
                    <p>Avg. Rating</p>
                    <?php if($global_avg > 0): ?>
                    <div style="color:#F1C40F;font-size:0.8rem;margin-top:4px;">
                        <?php for($i=1;$i<=5;$i++) echo $i <= round($global_avg) ? '★' : '☆'; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="stat-card"><div class="dot blue"></div><h2><?= $total_registrants ?></h2><p>Total Registrants</p></div>
                <div class="stat-card"><div class="dot yellow"></div>
                    <h2><?= array_sum(array_column($analytics, 'review_count')) ?></h2>
                    <p>Total Reviews</p>
                </div>
            </div>

            <h3 class="section-title">Event Analytics</h3>
            <table class="analytics-table">
                <thead>
                    <tr>
                        <th>EVENT TITLE</th>
                        <th>DATE</th>
                        <th>ATTENDANCE</th>
                        <th>AVG. RATING</th>
                        <th>REVIEWS</th>
                        <th>STATUS</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($analytics as $row): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['title']) ?></strong><br>
                            <span style="color:#aaa;font-size:0.75rem;"><?= htmlspecialchars($row['venue']) ?></span>
                        </td>
                        <td style="color:#888;"><?= date('M d, Y', strtotime($row['start_datetime'])) ?></td>
                        <td>
                            <?= $row['attendance'] ?> / <?= $row['maximum_capacity'] ?>
                            <div class="capacity-bar-wrap">
                                <div class="capacity-bar" style="width:<?= $row['maximum_capacity'] > 0 ? min(100, round($row['attendance']/$row['maximum_capacity']*100)) : 0 ?>%"></div>
                            </div>
                        </td>
                        <td>
                            <?php if($row['avg_rating'] > 0): ?>
                            <span class="star-rating">
                                <?php for($i=1;$i<=5;$i++) echo $i <= round($row['avg_rating']) ? '★' : '☆'; ?>
                            </span>
                            <strong style="margin-left:4px;"><?= number_format($row['avg_rating'],1) ?></strong>
                            <?php else: ?>
                            <span style="color:#ccc;font-size:0.8rem;">No ratings yet</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $row['review_count'] ?> review<?= $row['review_count'] != 1 ? 's' : '' ?></td>
                        <td>
                            <span style="background:<?= $row['current_status']==='Upcoming'?'#D5EFE9':($row['current_status']==='Ongoing'?'#FDEBD0':'#f0f0f0') ?>;
                                color:<?= $row['current_status']==='Upcoming'?'var(--teal)':($row['current_status']==='Ongoing'?'#E67E22':'#888') ?>;
                                padding:4px 10px;border-radius:20px;font-size:0.72rem;font-weight:800;">
                                <?= $row['current_status'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if($row['review_count'] > 0): ?>
                            <a href="org_summary.php?event_id=<?= $row['event_id'] ?>" class="btn-view-reviews">
                                View Reviews
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($analytics)): ?>
                    <tr><td colspan="7" style="text-align:center;padding:40px;color:#ccc;">No events yet. <a href="create_event.php" style="color:var(--teal);font-weight:700;">Create one →</a></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if($selected_event && $selected_reviews): ?>
            <div class="reviews-section">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                    <h3 class="section-title" style="margin-bottom:0;">Reviews: <?= htmlspecialchars($selected_event['title']) ?></h3>
                    <a href="org_summary.php" style="color:#888;font-size:0.85rem;font-weight:700;text-decoration:none;">✕ Close</a>
                </div>
                <?php foreach($selected_reviews as $rv): ?>
                <div class="review-item">
                    <div class="reviewer-avatar"><?= substr($rv['student_name'], 0, 1) ?></div>
                    <div class="review-body" style="flex:1;">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                            <div>
                                <h5><?= htmlspecialchars($rv['student_name']) ?></h5>
                                <div class="stars"><?php for($i=1;$i<=5;$i++) echo $i<=$rv["star_rating"]?'★':'☆'; ?></div>
                            </div>
                            <span class="review-date"><?= date('M d, Y', strtotime($rv['created_at'])) ?></span>
                        </div>
                        <p>"<?= htmlspecialchars($rv['comment']) ?>"</p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php elseif($selected_event_id && !$selected_reviews): ?>
            <div class="reviews-section" style="text-align:center;color:#ccc;padding:40px;">
                <i class='bx bx-comment-x' style="font-size:2.5rem;"></i>
                <p style="margin-top:10px;">No reviews for this event yet.</p>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
