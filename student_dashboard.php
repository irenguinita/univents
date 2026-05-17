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

$stmtRSVP = $conn->prepare("SELECT COUNT(*) FROM rsvp r JOIN event e ON r.event_id = e.event_id WHERE r.student_id = ? AND e.start_datetime >= CURRENT_DATE");
$stmtRSVP->execute([$user_id]);
$rsvp_count = $stmtRSVP->fetchColumn();

$stmtAttended = $conn->prepare("SELECT COUNT(*) FROM rsvp r JOIN event e ON r.event_id = e.event_id WHERE r.student_id = ? AND e.end_datetime < NOW()");
$stmtAttended->execute([$user_id]);
$attended_count = $stmtAttended->fetchColumn();

$stmtFollowed = $conn->prepare("SELECT COUNT(DISTINCT e.organization_id) FROM rsvp r JOIN event e ON r.event_id = e.event_id WHERE r.student_id = ?");
$stmtFollowed->execute([$user_id]);
$followed_count = $stmtFollowed->fetchColumn();

$stmtRev = $conn->prepare("SELECT COUNT(*) FROM review WHERE student_id = ?");
$stmtRev->execute([$user_id]);
$review_count = $stmtRev->fetchColumn();

$now = new DateTime();

// Upcoming RSVPs
$stmtEvents = $conn->prepare("
    SELECT e.*, r.rsvp_status, o.org_name 
    FROM event e 
    JOIN rsvp r ON e.event_id = r.event_id 
    JOIN organization o ON e.organization_id = o.user_id
    WHERE r.student_id = ? AND e.start_datetime >= CURRENT_DATE
    ORDER BY e.start_datetime ASC
");
$stmtEvents->execute([$user_id]);
$upcoming_rsvps = $stmtEvents->fetchAll();

// Recommended Events
$stmtRec = $conn->prepare("
    SELECT e.*, o.org_name 
    FROM event e 
    JOIN organization o ON e.organization_id = o.user_id
    WHERE e.start_datetime > NOW() 
    AND e.event_id NOT IN (SELECT event_id FROM rsvp WHERE student_id = ?)
    LIMIT 3
");
$stmtRec->execute([$user_id]);
$recommended_events = $stmtRec->fetchAll();

// Following list
$stmtFollowList = $conn->prepare("
    SELECT DISTINCT o.org_name, o.user_id 
    FROM organization o 
    JOIN event e ON o.user_id = e.organization_id 
    JOIN rsvp r ON e.event_id = r.event_id 
    WHERE r.student_id = ? LIMIT 3
");
$stmtFollowList->execute([$user_id]);
$follow_list = $stmtFollowList->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard - Univents</title>
    <link rel="stylesheet" href="dashboard-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>

        .toast {
            position: fixed; top: 30px; right: 30px; z-index: 9999;
            padding: 18px 28px; border-radius: 14px; font-weight: 700;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15); font-size: 0.95rem;
            display: flex; align-items: center; gap: 12px;
            animation: slideIn 0.4s ease, fadeOut 0.5s ease 3.5s forwards;
            max-width: 360px;
        }
        .toast.success { background: #D5F5E3; color: #1E8449; border: 1.5px solid #27AE60; }
        .toast.error   { background: #FADBD8; color: #C0392B; border: 1.5px solid #E74C3C; }
        @keyframes slideIn { from { transform: translateX(120%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; pointer-events: none; } }


        .tab-bar-dash { display: flex; gap: 6px; margin-bottom: 20px; border-bottom: 2px solid #eee; }
        .tab-btn-dash {
            padding: 10px 20px; background: none; border: none; cursor: pointer;
            font-family: 'Montserrat'; font-weight: 800; font-size: 0.78rem;
            color: #aaa; letter-spacing: 0.5px; position: relative; bottom: -2px;
            border-bottom: 3px solid transparent; transition: 0.2s;
        }
        .tab-btn-dash.active { color: var(--teal); border-bottom-color: var(--teal); }
        .tab-count-dash { background: #eee; color: #888; border-radius: 20px; padding: 1px 7px; font-size: 0.7rem; margin-left: 5px; }
        .tab-btn-dash.active .tab-count-dash { background: #D5EFE9; color: var(--teal); }
        .tab-panel-dash { display: none; }
        .tab-panel-dash.active { display: block; }

        .empty-dash { padding: 30px 20px; color: #ccc; text-align: center; font-size: 0.9rem; }

        /* Modal */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.45); display: flex; align-items: center; justify-content: center; z-index: 8888; }
        .modal-box { background: white; border-radius: 24px; padding: 44px; max-width: 460px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.2); text-align: center; animation: popIn 0.3s ease; }
        @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .modal-box h3 { font-family: 'Montserrat'; font-size: 1.5rem; margin-bottom: 10px; }
        .modal-box p { color: #777; font-size: 0.9rem; margin-bottom: 8px; }
        .modal-icon { font-size: 2.5rem; margin-bottom: 16px; }
        .modal-event-name { font-family: 'Montserrat'; font-weight: 900; font-size: 1.05rem; color: #333; margin: 12px 0 24px; }
        .modal-actions { display: flex; gap: 14px; justify-content: center; }
        .btn-cancel-modal { background: #f0f0f0; color: #555; border: none; padding: 13px 28px; border-radius: 10px; cursor: pointer; font-weight: 700; }
        .btn-confirm-danger { background: #E74C3C; color: white; border: none; padding: 13px 28px; border-radius: 10px; cursor: pointer; font-weight: 700; text-decoration: none; display: inline-block; }

        .btn-cancel-rsvp { background: #FADBD8; color: #C0392B; border: none; padding: 6px 14px; border-radius: 7px; cursor: pointer; font-weight: 700; font-size: 0.75rem; margin-left: auto; }
    </style>
</head>
<body>


<?php if(isset($_SESSION['msg'])): ?>
<div class="toast <?= $_SESSION['msg_type'] === 'success' ? 'success' : 'error' ?>" id="toastMsg">
    <i class='bx <?= $_SESSION['msg_type'] === 'success' ? 'bx-check-circle' : 'bx-x-circle' ?>'></i>
    <?= htmlspecialchars($_SESSION['msg']) ?>
</div>
<?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
<script>setTimeout(() => { const t = document.getElementById('toastMsg'); if(t) t.remove(); }, 4200);</script>
<?php endif; ?>


<div class="modal-overlay" id="cancelModal" style="display:none;">
    <div class="modal-box">
        <div class="modal-icon">🎟️</div>
        <h3>Cancel Your RSVP?</h3>
        <p>You're about to cancel your RSVP for:</p>
        <div class="modal-event-name" id="cancelEventName"></div>
        <p style="color:#E74C3C;font-weight:600;margin-bottom:24px;">Are you sure you want to cancel?</p>
        <div class="modal-actions">
            <button class="btn-cancel-modal" onclick="document.getElementById('cancelModal').style.display='none'">No, Keep It</button>
            <a id="cancelRsvpLink" href="#" class="btn-confirm-danger">Yes, Cancel RSVP</a>
        </div>
    </div>
</div>

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
            <a href="student_dashboard.php" class="active"><i class='bx bxs-dashboard'></i> Dashboard</a>
            <a href="events.php"><i class='bx bx-calendar-event'></i> Browse Events</a>
            <a href="rsvps.php"><i class='bx bx-bookmark-heart'></i> My RSVPs</a>
            <p class="nav-label">ACTIVITY</p>
            <a href="#"><i class='bx bx-bell'></i> Notifications</a>
            <a href="#"><i class='bx bx-star'></i> Reviews</a>
            <a href="#"><i class='bx bx-group'></i> Organizations</a>
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
            <div class="welcome-section">
                <h1>Welcome, <span class="teal-text"><?= htmlspecialchars(explode(' ', $student['name'])[0]) ?>!</span></h1>
                <p><?= date('l, F j') ?> • You have <?= count($upcoming_rsvps) ?> upcoming RSVPs.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card"><div class="dot orange"></div><h2><?= count($upcoming_rsvps) ?></h2><p>Upcoming RSVPs</p></div>
                <div class="stat-card"><div class="dot teal"></div><h2><?= $attended_count ?></h2><p>Events Attended</p></div>
                <div class="stat-card"><div class="dot blue"></div><h2><?= $followed_count ?></h2><p>Orgs Interacted</p></div>
                <div class="stat-card"><div class="dot yellow"></div><h2><?= $review_count ?></h2><p>Reviews Written</p></div>
            </div>

            <div class="dashboard-grid">
                <div class="lists-column">


                    <div class="tab-bar-dash">
                        <button class="tab-btn-dash active" onclick="switchDashTab('upcoming', this)">
                            UPCOMING <span class="tab-count-dash"><?= count($upcoming_rsvps) ?></span>
                        </button>
                        <button class="tab-btn-dash" onclick="switchDashTab('recommended', this)">
                            RECOMMENDED <span class="tab-count-dash"><?= count($recommended_events) ?></span>
                        </button>
                    </div>

                    <!-- Upcoming RSVPs Panel -->
                    <div class="tab-panel-dash active" id="dashTab-upcoming">
                        <?php if(empty($upcoming_rsvps)): ?>
                            <div class="empty-dash">No upcoming RSVPs. <a href="events.php" style="color:var(--teal);font-weight:700;">Browse events →</a></div>
                        <?php endif; ?>

                        <?php foreach($upcoming_rsvps as $event): ?>
                        <div class="event-row">
                            <div class="event-indicator"></div>
                            <div class="event-details" onclick="window.location='view_event.php?id=<?= $event['event_id'] ?>'" style="cursor:pointer;flex:1;">
                                <strong><?= htmlspecialchars($event['org_name']) ?></strong>
                                <h4><?= htmlspecialchars($event['title']) ?></h4>
                                <p><?= date('M d • g:i A', strtotime($event['start_datetime'])) ?> • <?= htmlspecialchars($event['venue']) ?></p>
                            </div>
                            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
                                <span class="status-badge confirmed">✓ <?= strtoupper($event['rsvp_status']) ?></span>
                                <button class="btn-cancel-rsvp"
                                    onclick="showCancelModal(<?= $event['event_id'] ?>, '<?= addslashes(htmlspecialchars($event['title'])) ?>')">
                                    Cancel
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Recommended Panel -->
                    <div class="tab-panel-dash" id="dashTab-recommended">
                        <?php if(empty($recommended_events)): ?>
                            <div class="empty-dash">No recommendations right now.</div>
                        <?php endif; ?>
                        <?php foreach($recommended_events as $rec): ?>
                        <div class="rec-row" onclick="window.location='view_event.php?id=<?= $rec['event_id'] ?>'" style="cursor:pointer;">
                            <div class="rec-icon <?= ($rec['event_id'] % 2 == 0) ? 'purple' : 'orange' ?>"></div>
                            <div class="rec-details">
                                <h4><?= htmlspecialchars($rec['title']) ?></h4>
                                <p><?= date('M d', strtotime($rec['start_datetime'])) ?> • <?= htmlspecialchars($rec['venue']) ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                </div>

                <div class="widgets-column">
                    <div class="widget calendar-widget">
                        <div class="calendar-header"><strong><?= date('F Y') ?></strong></div>
                        <div style="text-align:center;padding:20px;color:#ccc;">
                            <i class='bx bx-calendar' style="font-size:3rem;"></i>
                            <p>Calendar Sync Active</p>
                        </div>
                    </div>

                    <div class="widget following-widget">
                        <div class="section-header"><h3>Interacted Orgs</h3><a href="#">Manage</a></div>
                        <?php foreach($follow_list as $f): ?>
                        <div class="follow-item">
                            <div class="follow-icon blue"><?= substr($f['org_name'], 0, 1) ?></div>
                            <div><strong><?= htmlspecialchars($f['org_name']) ?></strong><p>University Partner</p></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
function switchDashTab(name, btn) {
    document.querySelectorAll('.tab-panel-dash').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn-dash').forEach(b => b.classList.remove('active'));
    document.getElementById('dashTab-' + name).classList.add('active');
    btn.classList.add('active');
}

function showCancelModal(eventId, eventName) {
    document.getElementById('cancelEventName').textContent = eventName;
    document.getElementById('cancelRsvpLink').href = 'cancel_rsvp.php?id=' + eventId;
    document.getElementById('cancelModal').style.display = 'flex';
}

document.getElementById('cancelModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>
</body>
</html>
