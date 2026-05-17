<?php
include 'db.php';
session_start();

$is_logged_in = isset($_SESSION['user_id']);
$uid  = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;
$dashboard_link = ($role === 'org') ? 'org_dashboard.php' : 'student_dashboard.php';

$my_rsvps = [];
if ($is_logged_in) {
    $stmt = $conn->prepare("SELECT e.*, r.rsvp_status, o.org_name FROM event e JOIN rsvp r ON e.event_id = r.event_id JOIN organization o ON e.organization_id = o.user_id WHERE r.student_id = ? ORDER BY e.start_datetime ASC");
    $stmt->execute([$uid]);
    $my_rsvps = $stmt->fetchAll();
}

// Separate into tabs by event date/status
$now = new DateTime();
$upcoming  = array_filter($my_rsvps, fn($r) => new DateTime($r['start_datetime']) > $now);
$finished  = array_filter($my_rsvps, fn($r) => new DateTime($r['end_datetime']) < $now);
$cancelled = array_filter($my_rsvps, fn($r) => strtolower($r['rsvp_status']) === 'cancelled');
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

        /* Tabs */
        .tab-bar { display: flex; gap: 8px; margin-bottom: 36px; border-bottom: 2px solid #eee; padding-bottom: 0; }
        .tab-btn {
            padding: 14px 28px; background: none; border: none; cursor: pointer;
            font-family: 'Montserrat'; font-weight: 800; font-size: 0.85rem;
            color: #aaa; letter-spacing: 0.5px; position: relative; bottom: -2px;
            border-bottom: 3px solid transparent; transition: 0.2s;
        }
        .tab-btn:hover { color: #555; }
        .tab-btn.active { color: #E68A6E; border-bottom-color: #E68A6E; }
        .tab-count { background: #eee; color: #888; border-radius: 20px; padding: 2px 9px; font-size: 0.75rem; margin-left: 7px; }
        .tab-btn.active .tab-count { background: #FCE8E4; color: #E68A6E; }

        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* Auth prompt */
        .auth-prompt-section { min-height: 60vh; display: flex; align-items: center; justify-content: center; text-align: center; background: #F9F7F2; }
        .auth-prompt-box { background: white; padding: 60px 80px; border-radius: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.06); }
        .auth-prompt-box h2 { font-family: 'Montserrat'; font-weight: 900; font-size: 2rem; color: #333; margin-bottom: 15px; }
        .auth-prompt-box p { color: #777; font-size: 1rem; margin-bottom: 35px; }
        .auth-prompt-btns { display: flex; gap: 15px; justify-content: center; }
        .btn-signup-prompt { background: white; color: #333; padding: 14px 32px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 0.9rem; border: 2px solid #eee; }
        .btn-login-prompt { background: #F1948A; color: white; padding: 14px 32px; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 0.9rem; }

        /* RSVP card row */
        .event-card-row {
            background: white; padding: 30px; border-radius: 20px; margin-bottom: 20px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.03); cursor: pointer; transition: all 0.3s ease;
        }
        .event-card-row:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
        .card-actions { display: flex; flex-direction: column; align-items: flex-end; gap: 12px; }

        /* Status badges */
        .badge { padding: 7px 16px; border-radius: 8px; font-weight: 700; font-size: 0.78rem; }
        .badge-confirmed { background: #D4EFDF; color: #1E8449; }
        .badge-finished  { background: #EBF5FB; color: #2E86C1; }
        .badge-cancelled { background: #FADBD8; color: #C0392B; }

        .btn-cancel-rsvp {
            background: #FADBD8; color: #C0392B; border: none; padding: 8px 18px;
            border-radius: 8px; cursor: pointer; font-weight: 700; font-size: 0.8rem; transition: 0.2s;
        }
        .btn-cancel-rsvp:hover { background: #E74C3C; color: white; }

        .empty-state { text-align: center; padding: 60px 20px; color: #bbb; }
        .empty-state i { font-size: 3rem; display: block; margin-bottom: 12px; }

        /* Toast */
        .toast {
            position: fixed; top: 30px; right: 30px; z-index: 9999;
            padding: 18px 28px; border-radius: 14px; font-weight: 700;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15); font-size: 0.95rem;
            display: flex; align-items: center; gap: 12px;
            animation: slideIn 0.4s ease, fadeOut 0.5s ease 3.5s forwards;
        }
        .toast.success { background: #D5F5E3; color: #1E8449; border: 1.5px solid #27AE60; }
        .toast.error   { background: #FADBD8; color: #C0392B; border: 1.5px solid #E74C3C; }
        @keyframes slideIn { from { transform: translateX(120%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; pointer-events: none; } }

        /* Modal */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.45); display: flex; align-items: center; justify-content: center; z-index: 8888; }
        .modal-box { background: white; border-radius: 24px; padding: 44px; max-width: 460px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.2); text-align: center; animation: popIn 0.3s ease; }
        @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .modal-box h3 { font-family: 'Montserrat'; font-size: 1.5rem; margin-bottom: 10px; }
        .modal-box p { color: #777; font-size: 0.9rem; margin-bottom: 8px; }
        .modal-icon { font-size: 2.5rem; margin-bottom: 16px; }
        .modal-event-name { font-family: 'Montserrat'; font-weight: 900; font-size: 1.1rem; color: #333; margin: 12px 0 24px; }
        .modal-actions { display: flex; gap: 14px; justify-content: center; }
        .btn-cancel-modal { background: #f0f0f0; color: #555; border: none; padding: 13px 28px; border-radius: 10px; cursor: pointer; font-weight: 700; }
        .btn-confirm-danger { background: #E74C3C; color: white; border: none; padding: 13px 28px; border-radius: 10px; cursor: pointer; font-weight: 700; text-decoration: none; display: inline-block; }
    </style>
</head>
<body style="background: #F9F7F2;">

<!-- Toast -->
<?php if(isset($_SESSION['msg'])): ?>
<div class="toast <?= $_SESSION['msg_type'] === 'success' ? 'success' : 'error' ?>" id="toastMsg">
    <i class='bx <?= $_SESSION['msg_type'] === 'success' ? 'bx-check-circle' : 'bx-x-circle' ?>'></i>
    <?= htmlspecialchars($_SESSION['msg']) ?>
</div>
<?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
<script>setTimeout(() => { const t = document.getElementById('toastMsg'); if(t) t.remove(); }, 4200);</script>
<?php endif; ?>

<!-- Cancel RSVP Confirmation Modal -->
<div class="modal-overlay" id="cancelModal" style="display:none;">
    <div class="modal-box">
        <div class="modal-icon">🎟️</div>
        <h3>Cancel Your RSVP?</h3>
        <p>You're about to cancel your RSVP for:</p>
        <div class="modal-event-name" id="cancelEventName"></div>
        <p style="color:#E74C3C; font-weight:600; margin-bottom:24px;">Are you sure you want to cancel?</p>
        <div class="modal-actions">
            <button class="btn-cancel-modal" onclick="document.getElementById('cancelModal').style.display='none'">No, Keep It</button>
            <a id="cancelRsvpLink" href="#" class="btn-confirm-danger">Yes, Cancel RSVP</a>
        </div>
    </div>
</div>

    <header>
        <div class="nav-wrapper">
            <a href="index.php" class="logo">Univents</a>
            <?php if (!$is_logged_in): ?>
                <nav>
                    <ul>
                        <li><a href="index.php">HOME</a></li>
                        <li><a href="events.php">EVENTS</a></li>
                        <li><a href="rsvps.php" class="active">RSVPs</a></li>
                        <li><a href="about.php">ABOUT</a></li>
                    </ul>
                </nav>
                <div class="nav-buttons">
                    <a href="register.php" style="text-decoration:none;color:#333;font-weight:700;font-size:0.9rem;">SIGN-UP</a>
                    <a href="login.php" class="btn-primary-nav">LOG-IN</a>
                </div>
            <?php else: ?>
                <div class="nav-buttons">
                    <a href="<?= $dashboard_link ?>" style="text-decoration:none;color:#326257;font-weight:700;font-size:0.9rem;display:flex;align-items:center;gap:6px;">
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
        <div style="max-width:1200px;margin:0 auto;padding:0 5%;">
            <span style="color:rgba(255,255,255,0.8); font-weight:700; text-transform:uppercase; font-size:0.85rem;">My Events</span>
            <h1 class="hero-title">MY RSVPS</h1>
            <p class="hero-desc">Track all your RSVPs, check-in statuses, and event history in one place.</p>
        </div>
    </section>

    <?php if (!$is_logged_in): ?>
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
        <div style="max-width:1200px;margin:0 auto;padding:60px 5%;">

            <!-- TAB BAR -->
            <div class="tab-bar">
                <button class="tab-btn active" onclick="switchTab('upcoming', this)">
                    UPCOMING <span class="tab-count"><?= count($upcoming) ?></span>
                </button>
                <button class="tab-btn" onclick="switchTab('finished', this)">
                    FINISHED <span class="tab-count"><?= count($finished) ?></span>
                </button>
                <button class="tab-btn" onclick="switchTab('cancelled', this)">
                    CANCELLED <span class="tab-count"><?= count($cancelled) ?></span>
                </button>
            </div>

            <!-- UPCOMING TAB -->
            <div class="tab-panel active" id="tab-upcoming">
                <?php if(empty($upcoming)): ?>
                    <div class="empty-state"><i class='bx bx-calendar-x'></i>No upcoming RSVPs. <a href="events.php" style="color:#E68A6E;font-weight:700;">Browse events →</a></div>
                <?php else: ?>
                    <?php foreach($upcoming as $r): ?>
                    <div class="event-card-row" onclick="window.location='view_event.php?id=<?= $r['event_id'] ?>'">
                        <div>
                            <strong style="color:#4BA68D;text-transform:uppercase;font-size:0.8rem;"><?= htmlspecialchars($r['org_name']) ?></strong>
                            <h3 style="font-family:'Montserrat';font-weight:900;margin:5px 0;color:#333;"><?= htmlspecialchars($r['title']) ?></h3>
                            <p style="color:#777;font-size:0.9rem;">
                                <i class='bx bx-calendar' style="vertical-align:middle;"></i> <?= date('M d, Y', strtotime($r['start_datetime'])) ?>
                                <span style="margin:0 8px;">•</span>
                                <i class='bx bx-map' style="vertical-align:middle;"></i> <?= htmlspecialchars($r['venue']) ?>
                            </p>
                        </div>
                        <div class="card-actions" onclick="event.stopPropagation()">
                            <span class="badge badge-confirmed">✓ <?= strtoupper($r['rsvp_status']) ?></span>
                            <button class="btn-cancel-rsvp"
                                onclick="showCancelModal(<?= $r['event_id'] ?>, '<?= addslashes(htmlspecialchars($r['title'])) ?>')">
                                Cancel RSVP
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- FINISHED TAB -->
            <div class="tab-panel" id="tab-finished">
                <?php if(empty($finished)): ?>
                    <div class="empty-state"><i class='bx bx-check-circle'></i>No finished events yet.</div>
                <?php else: ?>
                    <?php foreach($finished as $r): ?>
                    <div class="event-card-row" onclick="window.location='view_event.php?id=<?= $r['event_id'] ?>'">
                        <div>
                            <strong style="color:#4BA68D;text-transform:uppercase;font-size:0.8rem;"><?= htmlspecialchars($r['org_name']) ?></strong>
                            <h3 style="font-family:'Montserrat';font-weight:900;margin:5px 0;color:#333;"><?= htmlspecialchars($r['title']) ?></h3>
                            <p style="color:#777;font-size:0.9rem;">
                                <i class='bx bx-calendar' style="vertical-align:middle;"></i> <?= date('M d, Y', strtotime($r['start_datetime'])) ?>
                                <span style="margin:0 8px;">•</span>
                                <i class='bx bx-map' style="vertical-align:middle;"></i> <?= htmlspecialchars($r['venue']) ?>
                            </p>
                        </div>
                        <span class="badge badge-finished">✓ ATTENDED</span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- CANCELLED TAB -->
            <div class="tab-panel" id="tab-cancelled">
                <?php if(empty($cancelled)): ?>
                    <div class="empty-state"><i class='bx bx-x-circle'></i>No cancelled RSVPs.</div>
                <?php else: ?>
                    <?php foreach($cancelled as $r): ?>
                    <div class="event-card-row" style="opacity:0.7;" onclick="window.location='view_event.php?id=<?= $r['event_id'] ?>'">
                        <div>
                            <strong style="color:#4BA68D;text-transform:uppercase;font-size:0.8rem;"><?= htmlspecialchars($r['org_name']) ?></strong>
                            <h3 style="font-family:'Montserrat';font-weight:900;margin:5px 0;color:#333;"><?= htmlspecialchars($r['title']) ?></h3>
                            <p style="color:#777;font-size:0.9rem;">
                                <i class='bx bx-calendar' style="vertical-align:middle;"></i> <?= date('M d, Y', strtotime($r['start_datetime'])) ?>
                            </p>
                        </div>
                        <span class="badge badge-cancelled">✕ CANCELLED</span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    <?php endif; ?>

<script>
function switchTab(name, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
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
