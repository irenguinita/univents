<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'org') {
    header("Location: login.php");
    exit();
}

$org_id = $_SESSION['user_id'];
$event_id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM event WHERE event_id = ? AND organization_id = ?");
$stmt->execute([$event_id, $org_id]);
$event = $stmt->fetch();

if (!$event) {
    die("Event not found or access denied.");
}

$stmtReg = $conn->prepare("
    SELECT s.user_id AS student_id, s.name, s.department, r.timestamp 
    FROM student s 
    JOIN rsvp r ON s.user_id = r.student_id 
    WHERE r.event_id = ?
    ORDER BY r.timestamp DESC
");
$stmtReg->execute([$event_id]);
$registrants = $stmtReg->fetchAll();

if (isset($_POST['confirmed_update'])) {
    $sql = "UPDATE event SET title = ?, description = ?, venue = ?, maximum_capacity = ?, start_datetime = ?, end_datetime = ? WHERE event_id = ?";
    $conn->prepare($sql)->execute([$_POST['title'], $_POST['desc'], $_POST['venue'], $_POST['cap'], $_POST['start_datetime'], $_POST['end_datetime'], $event_id]);
    $_SESSION['flash_msg'] = "Event updated successfully!";
    $_SESSION['flash_type'] = "success";
    header("Location: manage_event.php?id=$event_id");
    exit();
}

$review_mode = isset($_POST['review_update']);
$review_data = $review_mode ? $_POST : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Event - Univents</title>
    <link rel="stylesheet" href="dashboard-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .manage-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px; margin-top: 30px; }
        .white-card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .registrants-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .registrants-table th { text-align: left; border-bottom: 2px solid #eee; padding: 10px; color: #888; font-size: 0.8rem; font-family: 'Montserrat', sans-serif; }
        .registrants-table td { padding: 15px 10px; border-bottom: 1px solid #f9f9f9; font-size: 0.9rem; font-family: 'Inter', sans-serif; }
        .btn-edit { background: #5D6D7E; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-family: 'Inter', sans-serif; font-weight: 600; }
        .btn-delete { background: #E74C3C; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; margin-left: 10px; font-family: 'Inter', sans-serif; font-weight: 600; }
        .edit-form input, .edit-form textarea {
            width: 100%; padding: 12px 14px; margin-bottom: 15px;
            border: 1.5px solid #eee; border-radius: 10px;
            font-size: 0.95rem; font-family: 'Inter', sans-serif;
            background: #fdfdfd; transition: border-color 0.2s;
            box-sizing: border-box;
        }
        .edit-form input:focus, .edit-form textarea:focus { outline: none; border-color: #4BA68D; background: white; }
        .edit-form label { font-weight: 600; font-size: 0.82rem; color: #555; display: block; margin-bottom: 6px; font-family: 'Inter', sans-serif; }

        /* Toast message */
        .toast {
            position: fixed; top: 30px; right: 30px; z-index: 9999;
            padding: 18px 28px; border-radius: 14px; font-weight: 700;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15); font-size: 0.95rem;
            display: flex; align-items: center; gap: 12px;
            animation: slideIn 0.4s ease, fadeOut 0.5s ease 3.5s forwards;
            max-width: 360px; font-family: 'Inter', sans-serif;
        }
        .toast.success { background: #D5F5E3; color: #1E8449; border: 1.5px solid #27AE60; }
        .toast.error   { background: #FADBD8; color: #C0392B; border: 1.5px solid #E74C3C; }
        .toast i { font-size: 1.3rem; }
        @keyframes slideIn { from { transform: translateX(120%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; pointer-events: none; } }

        /* Review/Confirm modal overlay */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.45);
            display: flex; align-items: center; justify-content: center;
            z-index: 8888; animation: fadeInBg 0.3s ease;
        }
        @keyframes fadeInBg { from { opacity: 0; } to { opacity: 1; } }
        .modal-box {
            background: white; border-radius: 24px; padding: 40px 44px;
            max-width: 520px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            animation: popIn 0.3s ease;
        }
        @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .modal-box h3 { font-family: 'Montserrat', sans-serif; font-size: 1.5rem; margin-bottom: 8px; font-weight: 700; }
        .modal-box p.modal-sub { color: #888; font-size: 0.9rem; margin-bottom: 25px; font-family: 'Inter', sans-serif; }
        .review-table { width: 100%; border-collapse: collapse; margin-bottom: 28px; }
        .review-table td { padding: 10px 0; border-bottom: 1px solid #f0f0f0; font-size: 0.9rem; font-family: 'Inter', sans-serif; }
        .review-table td:first-child { color: #888; font-weight: 600; width: 38%; }
        .review-table td:last-child { color: #333; font-weight: 500; }
        .modal-actions { display: flex; gap: 14px; justify-content: flex-end; }
        .btn-cancel-modal { background: #f0f0f0; color: #555; border: none; padding: 13px 28px; border-radius: 10px; cursor: pointer; font-weight: 700; font-size: 0.9rem; font-family: 'Inter', sans-serif; }
        .btn-confirm-modal { background: #4BA68D; color: white; border: none; padding: 13px 28px; border-radius: 10px; cursor: pointer; font-weight: 700; font-size: 0.9rem; font-family: 'Inter', sans-serif; }
        .btn-confirm-danger { background: #E74C3C; color: white; border: none; padding: 13px 28px; border-radius: 10px; cursor: pointer; font-weight: 700; font-size: 0.9rem; font-family: 'Inter', sans-serif; }
        .modal-icon { font-size: 2.5rem; margin-bottom: 16px; }
        .white-card h3 { font-family: 'Montserrat', sans-serif; font-weight: 700; }
    </style>
</head>
<body>

<!-- Toast Notification -->
<?php if (isset($_SESSION['flash_msg'])): ?>
<div class="toast <?= $_SESSION['flash_type'] === 'success' ? 'success' : 'error' ?>" id="toastMsg">
    <i class='bx <?= $_SESSION['flash_type'] === 'success' ? 'bx-check-circle' : 'bx-x-circle' ?>'></i>
    <?= htmlspecialchars($_SESSION['flash_msg']) ?>
</div>
<?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
<script>setTimeout(() => { const t = document.getElementById('toastMsg'); if(t) t.remove(); }, 4200);</script>
<?php endif; ?>

<div class="dashboard-container">
    <aside class="sidebar">
        <div class="sidebar-user">
            <?php
            $stmtOrg2 = $conn->prepare("SELECT * FROM organization WHERE user_id = ?");
            $stmtOrg2->execute([$org_id]);
            $org2 = $stmtOrg2->fetch();
            ?>
            <div class="user-avatar" style="background: var(--teal);"><?= substr($org2['org_name'] ?? 'O', 0, 1) ?></div>
            <div class="user-info">
                <h4><?= htmlspecialchars($org2['org_name'] ?? 'Organizer') ?></h4>
                <p>ORGANIZER • <?= $org2['verification_status'] ?? '' ?></p>
            </div>
        </div>
        <nav class="side-nav">
            <p class="nav-label">MAIN</p>
            <a href="org_dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
            <a href="events.php"><i class='bx bx-globe'></i> Public View</a>
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
            <div class="logo" style="font-family:'Montserrat', sans-serif; font-weight:900; font-size:1.8rem; color:#333;">Univents</div>
            <a href="org_dashboard.php" style="text-decoration:none; color:var(--teal); font-weight:700; font-family:'Inter', sans-serif;">← Back to Dashboard</a>
        </header>

        <div class="content-body">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h1 style="font-family:'Montserrat', sans-serif;">Manage: <span class="teal-text"><?= htmlspecialchars($event['title']) ?></span></h1>
                <div>
                    <button class="btn-edit" onclick="document.getElementById('editSection').scrollIntoView({behavior:'smooth'})">Edit Details</button>
                    <button class="btn-delete" onclick="showDeleteModal(<?= $event['event_id'] ?>)">Delete Event</button>
                </div>
            </div>

            <div class="manage-grid">
                <!-- LIST OF REGISTRANTS -->
                <div class="white-card">
                    <h3>Registrants (<?= count($registrants) ?>)</h3>
                    <table class="registrants-table">
                        <thead>
                            <tr><th>Student Name</th><th>Dept</th><th>RSVP Date</th><th style="width:70px;"></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($registrants as $reg): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($reg['name']) ?></strong></td>
                                <td><?= htmlspecialchars($reg['department']) ?></td>
                                <td style="color:#aaa; font-size:0.8rem;"><?= date('M d, H:i', strtotime($reg['timestamp'])) ?></td>
                                <td>
                                    <button onclick="showRemoveRsvpModal(<?= $event_id ?>, <?= $reg['student_id'] ?>, '<?= addslashes(htmlspecialchars($reg['name'])) ?>')"
                                        style="background:#FADBD8; color:#C0392B; border:none; padding:5px 10px; border-radius:6px; font-size:0.75rem; font-weight:700; cursor:pointer; font-family:'Inter', sans-serif;">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($registrants)) echo "<tr><td colspan='3' style='text-align:center; padding:40px; color:#ccc; font-family:Inter,sans-serif;'>No one has RSVP'd yet.</td></tr>"; ?>
                        </tbody>
                    </table>
                </div>

                <!-- EDIT SECTION -->
                <div class="white-card" id="editSection">
                    <h3>Edit Event Details</h3>
                    <form method="POST" class="edit-form" style="margin-top:20px;" id="editForm" onsubmit="showReviewModal(event)">
                        <label>Event Title</label>
                        <input type="text" name="title" id="inp_title" value="<?= htmlspecialchars($event['title']) ?>" required>

                        <label>Description</label>
                        <textarea name="desc" id="inp_desc" rows="5"><?= htmlspecialchars($event['description']) ?></textarea>

                        <label>Venue</label>
                        <input type="text" name="venue" id="inp_venue" value="<?= htmlspecialchars($event['venue']) ?>" required>

                        <label>Start Date & Time</label>
                        <input type="datetime-local" name="start_datetime" id="inp_start" value="<?= date('Y-m-d\TH:i', strtotime($event['start_datetime'])) ?>" required>

                        <label>End Date & Time</label>
                        <input type="datetime-local" name="end_datetime" id="inp_end" value="<?= date('Y-m-d\TH:i', strtotime($event['end_datetime'])) ?>" required>

                        <label>Max Capacity</label>
                        <input type="number" name="cap" id="inp_cap" value="<?= htmlspecialchars($event['maximum_capacity']) ?>" required>

                        <button type="submit" style="background:var(--teal); color:white; border:none; padding:14px 36px; border-radius:10px; font-weight:800; font-family:'Montserrat', sans-serif; font-size:0.95rem; cursor:pointer; transition:0.2s; display:inline-flex; align-items:center; gap:8px;">
                            <i class='bx bx-revision'></i> Review Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- ── REVIEW / CONFIRM EDIT MODAL ── -->
<div class="modal-overlay" id="reviewModal" style="display:none;">
    <div class="modal-box">
        <div class="modal-icon">📋</div>
        <h3>Review Your Changes</h3>
        <p class="modal-sub">Please verify the details below before saving.</p>
        <table class="review-table">
            <tr><td>Title</td><td id="rev_title"></td></tr>
            <tr><td>Description</td><td id="rev_desc" style="max-height:80px; overflow:auto; white-space:pre-wrap;"></td></tr>
            <tr><td>Venue</td><td id="rev_venue"></td></tr>
            <tr><td>Start</td><td id="rev_start"></td></tr>
            <tr><td>End</td><td id="rev_end"></td></tr>
            <tr><td>Max Capacity</td><td id="rev_cap"></td></tr>
        </table>
        <p style="font-size:0.85rem; color:#E67E22; margin-bottom:20px; font-family:'Inter', sans-serif;">⚠️ Are you sure these details are correct?</p>
        <div class="modal-actions">
            <button class="btn-cancel-modal" onclick="closeModal('reviewModal')">No, Go Back</button>
            <button class="btn-confirm-modal" onclick="submitConfirmedEdit()">Yes, Save Changes</button>
        </div>
    </div>
</div>

<!-- Confirmed Edit Hidden Form -->
<form method="POST" id="confirmedEditForm" style="display:none;">
    <input type="hidden" name="confirmed_update" value="1">
    <input type="hidden" name="title"  id="hid_title">
    <input type="hidden" name="desc"   id="hid_desc">
    <input type="hidden" name="venue"  id="hid_venue">
    <input type="hidden" name="cap"    id="hid_cap">
    <input type="hidden" name="start_datetime" id="hid_start">
    <input type="hidden" name="end_datetime"   id="hid_end">
</form>

<!-- ── REMOVE RSVP MODAL ── -->
<div class="modal-overlay" id="removeRsvpModal" style="display:none;">
    <div class="modal-box" style="text-align:center;">
        <div class="modal-icon">🎟️</div>
        <h3>Remove This RSVP?</h3>
        <p class="modal-sub">You are about to remove <strong id="removeRsvpStudentName"></strong> from this event.</p>
        <p style="font-size:0.85rem; color:#E74C3C; font-weight:600; margin-bottom:24px; font-family:'Inter', sans-serif;">This cannot be undone.</p>
        <div class="modal-actions" style="justify-content:center;">
            <button class="btn-cancel-modal" onclick="closeModal('removeRsvpModal')">No, Keep It</button>
            <a id="removeRsvpLink" href="#" class="btn-confirm-danger" style="text-decoration:none; display:inline-block; padding:13px 28px; border-radius:10px;">Yes, Remove RSVP</a>
        </div>
    </div>
</div>

<!-- ── DELETE CONFIRMATION MODAL ── -->
<div class="modal-overlay" id="deleteModal" style="display:none;">
    <div class="modal-box" style="text-align:center;">
        <div class="modal-icon">🗑️</div>
        <h3>Delete This Event?</h3>
        <p class="modal-sub">This action is permanent and cannot be undone. All RSVPs for this event will also be removed.</p>
        <p style="font-size:0.85rem; color:#E74C3C; font-weight:600; margin-bottom:24px; font-family:'Inter', sans-serif;">Are you sure you want to delete this event?</p>
        <div class="modal-actions" style="justify-content:center;">
            <button class="btn-cancel-modal" onclick="closeModal('deleteModal')">No, Keep It</button>
            <a id="deleteLink" href="#" class="btn-confirm-danger" style="text-decoration:none; display:inline-block; padding:13px 28px; border-radius:10px;">Yes, Delete It</a>
        </div>
    </div>
</div>

<script>
function showReviewModal(e) {
    e.preventDefault();
    document.getElementById('rev_title').textContent  = document.getElementById('inp_title').value;
    document.getElementById('rev_desc').textContent   = document.getElementById('inp_desc').value;
    document.getElementById('rev_venue').textContent  = document.getElementById('inp_venue').value;
    document.getElementById('rev_start').textContent  = document.getElementById('inp_start').value;
    document.getElementById('rev_end').textContent    = document.getElementById('inp_end').value;
    document.getElementById('rev_cap').textContent    = document.getElementById('inp_cap').value;
    document.getElementById('reviewModal').style.display = 'flex';
}

function submitConfirmedEdit() {
    document.getElementById('hid_title').value = document.getElementById('inp_title').value;
    document.getElementById('hid_desc').value  = document.getElementById('inp_desc').value;
    document.getElementById('hid_venue').value = document.getElementById('inp_venue').value;
    document.getElementById('hid_cap').value   = document.getElementById('inp_cap').value;
    document.getElementById('hid_start').value = document.getElementById('inp_start').value;
    document.getElementById('hid_end').value   = document.getElementById('inp_end').value;
    document.getElementById('confirmedEditForm').submit();
}

function showRemoveRsvpModal(eventId, studentId, studentName) {
    document.getElementById('removeRsvpStudentName').textContent = studentName;
    document.getElementById('removeRsvpLink').href = 'remove_rsvp.php?event_id=' + eventId + '&student_id=' + studentId;
    document.getElementById('removeRsvpModal').style.display = 'flex';
}

function showDeleteModal(id) {
    document.getElementById('deleteLink').href = 'delete_event.php?id=' + id;
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
});
</script>
</body>
</html>