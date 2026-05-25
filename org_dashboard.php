<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'org') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM organization WHERE user_id = ?");
$stmt->execute([$user_id]);
$org = $stmt->fetch();

$stmtTotal = $conn->prepare("SELECT COUNT(*) FROM event WHERE organization_id = ?");
$stmtTotal->execute([$user_id]);
$total_events = $stmtTotal->fetchColumn();

$stmtOngoing = $conn->prepare("SELECT COUNT(*) FROM event WHERE organization_id = ? AND current_status = 'Ongoing'");
$stmtOngoing->execute([$user_id]);
$ongoing_events = $stmtOngoing->fetchColumn();

$stmtCompleted = $conn->prepare("SELECT COUNT(*) FROM event WHERE organization_id = ? AND current_status = 'Completed'");
$stmtCompleted->execute([$user_id]);
$completed_events = $stmtCompleted->fetchColumn();

$stmtRegs = $conn->prepare("SELECT COUNT(*) FROM rsvp r JOIN event e ON r.event_id = e.event_id WHERE e.organization_id = ?");
$stmtRegs->execute([$user_id]);
$total_registrants = $stmtRegs->fetchColumn();

$stmtCalDates = $conn->prepare("SELECT TO_CHAR(start_datetime,'YYYY-MM-DD') as edate FROM event WHERE organization_id = ?");
$stmtCalDates->execute([$user_id]);
$orgEventDates = $stmtCalDates->fetchAll(PDO::FETCH_COLUMN);

$stmtUpcoming = $conn->prepare("SELECT * FROM event WHERE organization_id = ? AND start_datetime > NOW() ORDER BY start_datetime ASC");
$stmtUpcoming->execute([$user_id]);
$tab_upcoming = $stmtUpcoming->fetchAll();

$stmtFinished = $conn->prepare("SELECT * FROM event WHERE organization_id = ? AND end_datetime < NOW() ORDER BY start_datetime DESC");
$stmtFinished->execute([$user_id]);
$tab_finished = $stmtFinished->fetchAll();

$stmtCancelled = $conn->prepare("SELECT * FROM event WHERE organization_id = ? AND current_status = 'Cancelled' ORDER BY start_datetime DESC");
$stmtCancelled->execute([$user_id]);
$tab_cancelled = $stmtCancelled->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Organizer Dashboard - Univents</title>
    <link rel="stylesheet" href="dashboard-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        /* Toast */
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

        /* Tabs */
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

        /* Delete modal */
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
    </style>
</head>
<body>

<!-- Toast -->
<?php if(isset($_SESSION['flash_msg'])): ?>
<div class="toast <?= $_SESSION['flash_type'] === 'success' ? 'success' : 'error' ?>" id="toastMsg">
    <i class='bx <?= $_SESSION['flash_type'] === 'success' ? 'bx-check-circle' : 'bx-x-circle' ?>'></i>
    <?= htmlspecialchars($_SESSION['flash_msg']) ?>
</div>
<?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
<script>setTimeout(() => { const t = document.getElementById('toastMsg'); if(t) t.remove(); }, 4200);</script>
<?php endif; ?>

<?php if(isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
<div class="toast success" id="toastMsg">
    <i class='bx bx-check-circle'></i> Event deleted successfully.
</div>
<script>setTimeout(() => { const t = document.getElementById('toastMsg'); if(t) t.remove(); }, 4200);</script>
<?php endif; ?>

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteModal" style="display:none;">
    <div class="modal-box">
        <div class="modal-icon">🗑️</div>
        <h3>Delete This Event?</h3>
        <p>You're about to permanently delete:</p>
        <div class="modal-event-name" id="deleteEventName"></div>
        <p style="color:#E74C3C;font-weight:600;margin-bottom:24px;">This cannot be undone. Are you sure?</p>
        <div class="modal-actions">
            <button class="btn-cancel-modal" onclick="document.getElementById('deleteModal').style.display='none'">No, Keep It</button>
            <a id="deleteEventLink" href="#" class="btn-confirm-danger">Yes, Delete It</a>
        </div>
    </div>
</div>

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
            <a href="org_dashboard.php" class="active"><i class='bx bxs-dashboard'></i> Dashboard</a>
            <p class="nav-label">OPERATIONS</p>
            <a href="create_event.php"><i class='bx bx-plus-circle'></i> Create Event</a>
            <a href="org_summary.php"><i class='bx bx-bar-chart-alt-2'></i> Analytics</a>
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
                <a href="org_summary.php">ANALYTICS</a>
            </div>
        </header>

        <div class="content-body">
            <div class="welcome-section">
                <h1>Welcome, <span class="teal-text"><?= htmlspecialchars($org['org_name']) ?>!</span></h1>
                <p>Manage your events and track registrations in real-time.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card"><div class="dot orange"></div><h2><?= $total_events ?></h2><p>Total Events</p></div>
                <div class="stat-card"><div class="dot teal"></div><h2><?= $ongoing_events ?></h2><p>Ongoing Events</p></div>
                <div class="stat-card"><div class="dot blue"></div><h2><?= $completed_events ?></h2><p>Completed Events</p></div>
                <div class="stat-card"><div class="dot yellow"></div><h2><?= $total_registrants ?></h2><p>Total Registrants</p></div>
            </div>

            <div class="dashboard-grid">
                <div class="lists-column">

                    <!-- ── EVENT TABS ── -->
                    <div class="tab-bar-dash">
                        <button class="tab-btn-dash active" onclick="switchOrgTab('upcoming', this)">
                            UPCOMING <span class="tab-count-dash"><?= count($tab_upcoming) ?></span>
                        </button>
                        <button class="tab-btn-dash" onclick="switchOrgTab('finished', this)">
                            FINISHED <span class="tab-count-dash"><?= count($tab_finished) ?></span>
                        </button>
                        <button class="tab-btn-dash" onclick="switchOrgTab('cancelled', this)">
                            CANCELLED <span class="tab-count-dash"><?= count($tab_cancelled) ?></span>
                        </button>
                    </div>

                    <!-- Upcoming -->
                    <div class="tab-panel-dash active" id="orgTab-upcoming">
                        <?php if(empty($tab_upcoming)): ?>
                            <div class="empty-dash">No upcoming events. <a href="create_event.php" style="color:var(--teal);font-weight:700;">Create one →</a></div>
                        <?php endif; ?>
                        <?php foreach($tab_upcoming as $event): ?>
                        <div class="event-row">
                            <div class="event-indicator" style="background:#E68A6E;"></div>
                            <div class="event-details" style="flex:1;">
                                <strong>Status: <?= htmlspecialchars($event['current_status']) ?></strong>
                                <h4><?= htmlspecialchars($event['title']) ?></h4>
                                <p><?= date('M d, Y', strtotime($event['start_datetime'])) ?> • <?= htmlspecialchars($event['venue']) ?></p>
                            </div>
                            <div style="display:flex;gap:8px;align-items:center;">
                                <a href="manage_event.php?id=<?= $event['event_id'] ?>" class="status-badge confirmed" style="text-decoration:none;">MANAGE</a>
                                <button onclick="showDeleteModal(<?= $event['event_id'] ?>, '<?= addslashes(htmlspecialchars($event['title'])) ?>')"
                                    style="background:#FADBD8;color:#C0392B;border:none;padding:5px 12px;border-radius:7px;font-weight:700;font-size:0.75rem;cursor:pointer;">
                                    DELETE
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Finished -->
                    <div class="tab-panel-dash" id="orgTab-finished">
                        <?php if(empty($tab_finished)): ?>
                            <div class="empty-dash">No finished events yet.</div>
                        <?php endif; ?>
                        <?php foreach($tab_finished as $event): ?>
                        <div class="event-row" style="opacity:0.8;">
                            <div class="event-indicator" style="background:#3498DB;"></div>
                            <div class="event-details" style="flex:1;">
                                <strong>FINISHED</strong>
                                <h4><?= htmlspecialchars($event['title']) ?></h4>
                                <p><?= date('M d, Y', strtotime($event['start_datetime'])) ?> • <?= htmlspecialchars($event['venue']) ?></p>
                            </div>
                            <a href="manage_event.php?id=<?= $event['event_id'] ?>" class="status-badge" style="text-decoration:none;background:#EBF5FB;color:#2E86C1;">VIEW</a>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Cancelled -->
                    <div class="tab-panel-dash" id="orgTab-cancelled">
                        <?php if(empty($tab_cancelled)): ?>
                            <div class="empty-dash">No cancelled events.</div>
                        <?php endif; ?>
                        <?php foreach($tab_cancelled as $event): ?>
                        <div class="event-row" style="opacity:0.65;">
                            <div class="event-indicator" style="background:#E74C3C;"></div>
                            <div class="event-details" style="flex:1;">
                                <strong>CANCELLED</strong>
                                <h4><?= htmlspecialchars($event['title']) ?></h4>
                                <p><?= date('M d, Y', strtotime($event['start_datetime'])) ?> • <?= htmlspecialchars($event['venue']) ?></p>
                            </div>
                            <span class="status-badge" style="background:#FADBD8;color:#C0392B;">CANCELLED</span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                </div>

                <div class="widgets-column">
                    <div class="widget" style="padding:0;">
                        <?php include 'calendar_widget.php'; renderCalendarWidget($orgEventDates); ?>
                    </div>

                    <div class="widget following-widget">
                        <div class="section-header">
                            <h3>Quick Actions</h3>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:12px;margin-top:10px;">
                            <a href="create_event.php" style="display:flex;align-items:center;gap:12px;padding:14px;background:#F2EDE4;border-radius:12px;text-decoration:none;color:#333;font-weight:600;font-size:0.9rem;">
                                <i class='bx bx-plus-circle' style="font-size:1.3rem;color:var(--teal);"></i> Create New Event
                            </a>
                            <a href="org_summary.php" style="display:flex;align-items:center;gap:12px;padding:14px;background:#F2EDE4;border-radius:12px;text-decoration:none;color:#333;font-weight:600;font-size:0.9rem;">
                                <i class='bx bx-bar-chart-alt-2' style="font-size:1.3rem;color:#E68A6E;"></i> View Analytics
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
function switchOrgTab(name, btn) {
    document.querySelectorAll('.tab-panel-dash').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn-dash').forEach(b => b.classList.remove('active'));
    document.getElementById('orgTab-' + name).classList.add('active');
    btn.classList.add('active');
}

function showDeleteModal(id, name) {
    document.getElementById('deleteEventName').textContent = name;
    document.getElementById('deleteEventLink').href = 'delete_event.php?id=' + id;
    document.getElementById('deleteModal').style.display = 'flex';
}

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>
</body>
</html>
