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

// Fetch events the student attended (past events they RSVPd to) - eligible for review
$stmtAttended = $conn->prepare("
    SELECT e.event_id, e.title, e.venue, e.start_datetime, e.end_datetime, o.org_name,
           rv.review_id, rv.star_rating, rv.comment, rv.created_at
    FROM rsvp r
    JOIN event e ON r.event_id = e.event_id
    JOIN organization o ON e.organization_id = o.user_id
    LEFT JOIN review rv ON rv.event_id = e.event_id AND rv.student_id = ?
    WHERE r.student_id = ? AND e.end_datetime < NOW()
    ORDER BY e.start_datetime DESC
");
$stmtAttended->execute([$user_id, $user_id]);
$attended = $stmtAttended->fetchAll();

$message = "";

// Handle review submit
if (isset($_POST['submit_review'])) {
    $event_id = intval($_POST['event_id']);
    $star_rating = intval($_POST['star_rating']);
    $comment = trim($_POST['comment']);
    try {
        // Check if review exists
        $chk = $conn->prepare("SELECT review_id FROM review WHERE event_id = ? AND student_id = ?");
        $chk->execute([$event_id, $user_id]);
        if ($chk->fetch()) {
            $conn->prepare("UPDATE review SET star_rating=?, comment=?, created_at=NOW() WHERE event_id=? AND student_id=?")
                 ->execute([$star_rating, $comment, $event_id, $user_id]);
        } else {
            $conn->prepare("INSERT INTO review (event_id, student_id, star_rating, comment, created_at) VALUES (?,?,?,?,NOW())")
                 ->execute([$event_id, $user_id, $star_rating, $comment]);
        }
        $_SESSION['flash_msg'] = "Review submitted!";
        $_SESSION['flash_type'] = "success";
        header("Location: reviews.php");
        exit();
    } catch(PDOException $e) {
        $message = "<div class='toast error' style='position:static;animation:none;margin-bottom:20px;'><i class='bx bx-x-circle'></i> Error: " . $e->getMessage() . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Reviews - Univents</title>
    <link rel="stylesheet" href="dashboard-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .review-card { background: white; border-radius: 18px; padding: 24px; margin-bottom: 18px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
        .review-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; }
        .event-meta h4 { font-family: 'Montserrat'; font-size: 1rem; font-weight: 800; margin-bottom: 4px; }
        .event-meta p { color: #888; font-size: 0.82rem; }
        .star-display { color: #F1C40F; font-size: 1.1rem; }
        .star-input { display: flex; gap: 4px; margin-bottom: 12px; }
        .star-input input { display: none; }
        .star-input label { font-size: 1.6rem; cursor: pointer; color: #ddd; transition: color 0.15s; }
        .star-input input:checked ~ label,
        .star-input label:hover,
        .star-input label:hover ~ label { color: #F1C40F; }
        .star-input { flex-direction: row-reverse; }
        .star-input label:hover,
        .star-input label:hover ~ label { color: #F1C40F !important; }
        .star-input input:checked ~ label { color: #F1C40F !important; }
        .review-textarea { width: 100%; padding: 12px 14px; border: 1.5px solid #ddd; border-radius: 10px; font-family: 'Inter'; font-size: 0.9rem; resize: vertical; min-height: 80px; transition: border 0.2s; }
        .review-textarea:focus { border-color: var(--teal); outline: none; }
        .btn-review { background: var(--teal); color: white; border: none; padding: 10px 26px; border-radius: 10px; font-weight: 800; font-family: 'Montserrat'; font-size: 0.85rem; cursor: pointer; transition: 0.2s; }
        .btn-review:hover { opacity: 0.85; }
        .reviewed-badge { background: #D5EFE9; color: var(--teal); padding: 4px 12px; border-radius: 20px; font-size: 0.72rem; font-weight: 800; }
        .toast { position: fixed; top: 30px; right: 30px; z-index: 9999; padding: 18px 28px; border-radius: 14px; font-weight: 700; box-shadow: 0 8px 30px rgba(0,0,0,0.15); font-size: 0.95rem; display: flex; align-items: center; gap: 12px; animation: slideIn 0.4s ease, fadeOut 0.5s ease 3.5s forwards; }
        .toast.success { background: #D5F5E3; color: #1E8449; border: 1.5px solid #27AE60; }
        @keyframes slideIn { from { transform: translateX(120%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; pointer-events: none; } }
        .empty-state { text-align: center; padding: 60px 20px; color: #ccc; }
        .empty-state i { font-size: 3.5rem; margin-bottom: 16px; }
        .page-title { font-family: 'Montserrat'; font-size: 2.2rem; font-weight: 900; margin-bottom: 6px; }
        .page-sub { color: #888; font-size: 0.9rem; margin-bottom: 24px; }
        .existing-review { background: #F8FDF9; border: 1.5px solid #D5EFE9; border-radius: 10px; padding: 14px; margin-bottom: 14px; }
        .existing-review p { font-size: 0.9rem; color: #333; margin-top: 6px; font-style: italic; }
    </style>
</head>
<body>

<?php if(isset($_SESSION['flash_msg'])): ?>
<div class="toast success" id="toastMsg">
    <i class='bx bx-check-circle'></i> <?= htmlspecialchars($_SESSION['flash_msg']) ?>
</div>
<?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
<script>setTimeout(() => { const t = document.getElementById('toastMsg'); if(t) t.remove(); }, 4200);</script>
<?php endif; ?>

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
            <a href="notifications.php"><i class='bx bx-bell'></i> Notifications</a>
            <a href="reviews.php" class="active"><i class='bx bx-star'></i> Reviews</a>
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
            <h1 class="page-title"><i class='bx bx-star' style="color:var(--teal);"></i> My Reviews</h1>
            <p class="page-sub">Rate and review events you've attended. Your reviews help organizers improve.</p>

            <?= $message ?>

            <?php if(empty($attended)): ?>
            <div class="empty-state">
                <i class='bx bx-star'></i>
                <p style="font-size:1rem;font-weight:600;">No attended events yet.</p>
                <p style="font-size:0.85rem;">Reviews become available after events you've RSVPd to have ended.</p>
                <a href="events.php" style="color:var(--teal);font-weight:700;">Browse Events →</a>
            </div>
            <?php endif; ?>

            <?php foreach($attended as $ev): ?>
            <div class="review-card">
                <div class="review-card-header">
                    <div class="event-meta">
                        <h4><?= htmlspecialchars($ev['title']) ?></h4>
                        <p><i class='bx bx-buildings' style="font-size:0.8rem;"></i> <?= htmlspecialchars($ev['org_name']) ?>
                           &nbsp;•&nbsp; <?= date('M d, Y', strtotime($ev['start_datetime'])) ?>
                           &nbsp;•&nbsp; <?= htmlspecialchars($ev['venue']) ?></p>
                    </div>
                    <?php if($ev['review_id']): ?>
                        <span class="reviewed-badge">✓ REVIEWED</span>
                    <?php endif; ?>
                </div>

                <?php if($ev['review_id']): ?>
                <div class="existing-review">
                    <div class="star-display">
                        <?php for($i=1;$i<=5;$i++) echo $i <= $ev["star_rating"] ? '★' : '☆'; ?>
                        <span style="color:#888;font-size:0.8rem;margin-left:6px;"><?= $ev["star_rating"] ?>/5 &nbsp;•&nbsp; <?= date('M d, Y', strtotime($ev['created_at'])) ?></span>
                    </div>
                    <p>"<?= htmlspecialchars($ev['comment']) ?>"</p>
                </div>
                <details style="margin-top:10px;">
                    <summary style="cursor:pointer;color:var(--teal);font-weight:700;font-size:0.85rem;">Edit Review</summary>
                <?php endif; ?>

                <form method="POST" style="margin-top:<?= $ev['review_id'] ? '12px' : '0' ?>;">
                    <input type="hidden" name="event_id" value="<?= $ev['event_id'] ?>">
                    <div class="star-input">
                        <?php for($i=5;$i>=1;$i--): ?>
                        <input type="radio" name="star_rating" id="star<?= $ev['event_id'] ?>_<?= $i ?>" value="<?= $i ?>" <?= ($ev["star_rating"] == $i) ? 'checked' : '' ?> required>
                        <label for="star<?= $ev['event_id'] ?>_<?= $i ?>">★</label>
                        <?php endfor; ?>
                    </div>
                    <textarea name="comment" class="review-textarea" placeholder="Share your experience at this event..."><?= htmlspecialchars($ev['comment'] ?? '') ?></textarea>
                    <div style="margin-top:10px;">
                        <button type="submit" name="submit_review" class="btn-review">
                            <i class='bx bx-send'></i> <?= $ev['review_id'] ? 'Update Review' : 'Submit Review' ?>
                        </button>
                    </div>
                </form>

                <?php if($ev['review_id']): ?></details><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
</body>
</html>
