<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

// Fetch current data
if ($role === 'student') {
    $stmt = $conn->prepare("SELECT * FROM student WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
    $dashboard = 'student_dashboard.php';
} else {
    $stmt = $conn->prepare("SELECT * FROM organization WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
    $dashboard = 'org_dashboard.php';
}

// Fetch email
$stmtU = $conn->prepare("SELECT institutional_email FROM \"user\" WHERE user_id = ?");
$stmtU->execute([$user_id]);
$userRow = $stmtU->fetch();

$errors = [];
$success = false;

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_email = trim($_POST['email'] ?? '');
    $new_pass  = trim($_POST['password'] ?? '');
    $new_pass2 = trim($_POST['password2'] ?? '');

    // Validate
    if (empty($new_email)) $errors[] = "Email is required.";
    if (!empty($new_pass) && $new_pass !== $new_pass2) $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        // Update email
        $stmtEmail = $conn->prepare("UPDATE \"user\" SET institutional_email = ? WHERE user_id = ?");
        $stmtEmail->execute([$new_email, $user_id]);

        // Update password if provided
        if (!empty($new_pass)) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmtPw = $conn->prepare("UPDATE \"user\" SET password = ? WHERE user_id = ?");
            $stmtPw->execute([$hashed, $user_id]);
        }

        // Update role-specific fields
        if ($role === 'student') {
            $name    = trim($_POST['name'] ?? '');
            $dept    = trim($_POST['dept'] ?? '');
            $year    = trim($_POST['year_level'] ?? '');
            $program = trim($_POST['program'] ?? '');
            $stmtP = $conn->prepare("UPDATE student SET name = ?, department = ?, year_level = ?, program = ? WHERE user_id = ?");
            $stmtP->execute([$name, $dept, $year, $program, $user_id]);
        } else {
            $org_name = trim($_POST['org_name'] ?? '');
            $stmtP = $conn->prepare("UPDATE organization SET org_name = ? WHERE user_id = ?");
            $stmtP->execute([$org_name, $user_id]);
        }

        $_SESSION['flash_msg']  = "Profile updated successfully!";
        $_SESSION['flash_type'] = "success";
        header("Location: edit_profile.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile - Univents</title>
    <link rel="stylesheet" href="dashboard-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .profile-card { background: white; border-radius: 20px; padding: 40px; max-width: 680px; box-shadow: 0 5px 20px rgba(0,0,0,0.06); }
        .form-section { margin-bottom: 32px; }
        .form-section h4 { font-family: 'Montserrat'; font-size: 0.8rem; color: #aaa; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 18px; border-bottom: 1px solid #f0f0f0; padding-bottom: 8px; }
        .field-group { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .field-group.single { grid-template-columns: 1fr; }
        .field { display: flex; flex-direction: column; margin-bottom: 16px; }
        .field label { font-weight: 600; font-size: 0.82rem; color: #555; margin-bottom: 6px; }
        .field input, .field select {
            padding: 12px 14px; border: 1.5px solid #eee; border-radius: 10px;
            font-size: 0.95rem; font-family: 'Inter'; background: #fdfdfd;
            transition: border-color 0.2s;
        }
        .field input:focus, .field select:focus { outline: none; border-color: #4BA68D; background: white; }
        .btn-save { background: #4BA68D; color: white; border: none; padding: 14px 36px; border-radius: 12px; font-weight: 700; font-size: 0.95rem; cursor: pointer; transition: opacity 0.2s; }
        .btn-save:hover { opacity: 0.88; }
        .btn-danger-outline { background: none; border: 2px solid #E74C3C; color: #E74C3C; padding: 12px 28px; border-radius: 12px; font-weight: 700; font-size: 0.88rem; cursor: pointer; text-decoration: none; display: inline-block; transition: 0.2s; }
        .btn-danger-outline:hover { background: #E74C3C; color: white; }
        .error-box { background: #FADBD8; color: #C0392B; border: 1.5px solid #E74C3C; border-radius: 12px; padding: 14px 18px; margin-bottom: 24px; font-size: 0.9rem; }
        .error-box ul { margin: 6px 0 0 18px; }
        .avatar-circle { width: 80px; height: 80px; border-radius: 50%; background: var(--teal); color: white; font-size: 2rem; font-family: 'Montserrat'; font-weight: 900; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; }
        .danger-zone { background: #FFF5F5; border: 1.5px solid #FADBD8; border-radius: 16px; padding: 28px 32px; margin-top: 30px; }
        .danger-zone h4 { color: #E74C3C; font-family: 'Montserrat'; margin-bottom: 8px; }
        .danger-zone p { color: #777; font-size: 0.88rem; margin-bottom: 18px; }

        /* Toast */
        .toast { position: fixed; top: 30px; right: 30px; z-index: 9999; padding: 18px 28px; border-radius: 14px; font-weight: 700; box-shadow: 0 8px 30px rgba(0,0,0,0.15); font-size: 0.95rem; display: flex; align-items: center; gap: 12px; animation: slideIn 0.4s ease, fadeOut 0.5s ease 3.5s forwards; max-width: 360px; }
        .toast.success { background: #D5F5E3; color: #1E8449; border: 1.5px solid #27AE60; }
        .toast.error   { background: #FADBD8; color: #C0392B; border: 1.5px solid #E74C3C; }
        @keyframes slideIn { from { transform: translateX(120%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; pointer-events: none; } }

        /* Delete modal */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.45); display: flex; align-items: center; justify-content: center; z-index: 8888; }
        .modal-box { background: white; border-radius: 24px; padding: 44px; max-width: 460px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.2); text-align: center; animation: popIn 0.3s ease; }
        @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .modal-box h3 { font-family: 'Montserrat'; font-size: 1.5rem; margin-bottom: 10px; }
        .modal-box p { color: #777; font-size: 0.9rem; margin-bottom: 14px; }
        .modal-icon { font-size: 2.8rem; margin-bottom: 16px; }
        .modal-actions { display: flex; gap: 14px; justify-content: center; margin-top: 24px; }
        .btn-cancel-modal { background: #f0f0f0; color: #555; border: none; padding: 13px 28px; border-radius: 10px; cursor: pointer; font-weight: 700; }
        .btn-confirm-danger { background: #E74C3C; color: white; border: none; padding: 13px 28px; border-radius: 10px; cursor: pointer; font-weight: 700; text-decoration: none; display: inline-block; }
    </style>
</head>
<body>

<?php if (isset($_SESSION['flash_msg'])): ?>
<div class="toast <?= $_SESSION['flash_type'] === 'success' ? 'success' : 'error' ?>" id="toastMsg">
    <i class='bx <?= $_SESSION['flash_type'] === 'success' ? 'bx-check-circle' : 'bx-x-circle' ?>'></i>
    <?= htmlspecialchars($_SESSION['flash_msg']) ?>
</div>
<?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
<script>setTimeout(() => { const t = document.getElementById('toastMsg'); if(t) t.remove(); }, 4200);</script>
<?php endif; ?>

<!-- Delete Account Modal -->
<div class="modal-overlay" id="deleteModal" style="display:none;">
    <div class="modal-box">
        <div class="modal-icon">⚠️</div>
        <h3>Delete Your Account?</h3>
        <p>This is <strong>permanent</strong>. All your data — RSVPs, events, and profile info — will be erased and cannot be recovered.</p>
        <p style="color:#E74C3C; font-weight:700;">Are you absolutely sure?</p>
        <div class="modal-actions">
            <button class="btn-cancel-modal" onclick="document.getElementById('deleteModal').style.display='none'">No, Keep My Account</button>
            <a href="delete_account.php" class="btn-confirm-danger">Yes, Delete It</a>
        </div>
    </div>
</div>

<div class="dashboard-container">
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="user-avatar" style="background: var(--teal);">
                <?= $role === 'student' ? substr($profile['name'], 0, 1) : substr($profile['org_name'], 0, 1) ?>
            </div>
            <div class="user-info">
                <h4><?= htmlspecialchars($role === 'student' ? $profile['name'] : $profile['org_name']) ?></h4>
                <p><?= strtoupper($role) ?> ACCOUNT</p>
            </div>
        </div>
        <nav class="side-nav">
            <p class="nav-label">MAIN</p>
            <a href="<?= $dashboard ?>"><i class='bx bxs-dashboard'></i> Dashboard</a>
            <?php if ($role === 'student'): ?>
            <a href="events.php"><i class='bx bx-calendar-event'></i> Browse Events</a>
            <a href="rsvps.php"><i class='bx bx-bookmark-heart'></i> My RSVPs</a>
            <?php else: ?>
            <a href="events.php"><i class='bx bx-globe'></i> Public View</a>
            <a href="create_event.php"><i class='bx bx-plus-circle'></i> Create Event</a>
            <?php endif; ?>
            <p class="nav-label">ACCOUNT</p>
            <a href="edit_profile.php" class="active"><i class='bx bx-user-circle'></i> Edit Profile</a>
            <a href="logout.php"><i class='bx bx-log-out'></i> Log out</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="top-header">
            <div class="logo">Univents</div>
            <a href="<?= $dashboard ?>" style="text-decoration:none; color:var(--teal); font-weight:700;">← Back to Dashboard</a>
        </header>

        <div class="content-body">
            <h1>Edit <span class="teal-text">Profile</span></h1>
            <p style="color:#888; margin-bottom:30px;">Update your account information and credentials.</p>

            <?php if (!empty($errors)): ?>
            <div class="error-box">
                <strong>Please fix the following:</strong>
                <ul><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
            </div>
            <?php endif; ?>

            <div class="profile-card">
                <div class="avatar-circle">
                    <?= $role === 'student' ? strtoupper(substr($profile['name'], 0, 1)) : strtoupper(substr($profile['org_name'], 0, 1)) ?>
                </div>

                <form method="POST">
                    <!-- Account Credentials -->
                    <div class="form-section">
                        <h4>Account Credentials</h4>
                        <div class="field-group single">
                            <div class="field">
                                <label>Institutional Email</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($userRow['institutional_email']) ?>" required>
                            </div>
                        </div>
                        <div class="field-group">
                            <div class="field">
                                <label>New Password <span style="color:#bbb; font-weight:400;">(leave blank to keep current)</span></label>
                                <input type="password" name="password" placeholder="New password">
                            </div>
                            <div class="field">
                                <label>Confirm New Password</label>
                                <input type="password" name="password2" placeholder="Repeat new password">
                            </div>
                        </div>
                    </div>

                    <?php if ($role === 'student'): ?>
                    <!-- Student Profile -->
                    <div class="form-section">
                        <h4>Student Information</h4>
                        <div class="field-group single">
                            <div class="field">
                                <label>Full Name</label>
                                <input type="text" name="name" value="<?= htmlspecialchars($profile['name']) ?>" required>
                            </div>
                        </div>
                        <div class="field-group">
                            <div class="field">
                                <label>Department / College</label>
                                <select name="dept">
                                    <?php foreach (['CCS','CEA','CASE'] as $d): ?>
                                    <option value="<?= $d ?>" <?= $profile['department'] === $d ? 'selected' : '' ?>><?= $d ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field">
                                <label>Year Level</label>
                                <select name="year_level">
                                    <?php for ($y = 1; $y <= 4; $y++): ?>
                                    <option value="<?= $y ?>" <?= $profile['year_level'] == $y ? 'selected' : '' ?>>Year <?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="field-group single">
                            <div class="field">
                                <label>Program</label>
                                <input type="text" name="program" value="<?= htmlspecialchars($profile['program'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <?php else: ?>
                    <!-- Org Profile -->
                    <div class="form-section">
                        <h4>Organization Information</h4>
                        <div class="field-group single">
                            <div class="field">
                                <label>Organization Name</label>
                                <input type="text" name="org_name" value="<?= htmlspecialchars($profile['org_name']) ?>" required>
                            </div>
                        </div>
                        <div class="field-group single">
                            <div class="field">
                                <label>Verification Status</label>
                                <input type="text" value="<?= htmlspecialchars($profile['verification_status']) ?>" disabled style="background:#f5f5f5; color:#999;">
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div style="display:flex; justify-content:flex-end;">
                        <button type="submit" class="btn-save">Save Changes</button>
                    </div>
                </form>
            </div>

            <!-- Danger Zone -->
            <div class="danger-zone">
                <h4>⚠️ Danger Zone</h4>
                <p>Permanently delete your account and all associated data. This action <strong>cannot be undone</strong>.</p>
                <button class="btn-danger-outline" onclick="document.getElementById('deleteModal').style.display='flex'">Delete My Account</button>
            </div>
        </div>
    </main>
</div>

<script>
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>
</body>
</html>
