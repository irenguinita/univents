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

$message = "";

//Save Draft
if (isset($_POST['save_draft'])) {
    try {
        $sql = "INSERT INTO event (title, description, venue, start_datetime, end_datetime, maximum_capacity, organization_id, current_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Draft')";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $_POST['title'],
            $_POST['description'],
            $_POST['venue'],
            $_POST['start_datetime'],
            $_POST['end_datetime'],
            $_POST['capacity'],
            $user_id
        ]);
        $_SESSION['flash_msg'] = "Draft saved successfully!";
        $_SESSION['flash_type'] = "success";
        header("Location: org_dashboard.php");
        exit();
    } catch (PDOException $e) {
        $message = "<p class='alert error'>Error: " . $e->getMessage() . "</p>";
    }
}

//Create Event
if (isset($_POST['create_event'])) {
    try {
        $sql = "INSERT INTO event (title, description, venue, start_datetime, end_datetime, maximum_capacity, organization_id, current_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'Upcoming')";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $_POST['title'],
            $_POST['description'],
            $_POST['venue'],
            $_POST['start_datetime'],
            $_POST['end_datetime'],
            $_POST['capacity'],
            $user_id
        ]);
        $_SESSION['flash_msg'] = "Event created successfully!";
        $_SESSION['flash_type'] = "success";
        header("Location: org_dashboard.php");
        exit();
    } catch (PDOException $e) {
        $message = "<p class='alert error'>Error: " . $e->getMessage() . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Event - Univents</title>
    <link rel="stylesheet" href="dashboard-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .form-container { max-width: 800px; margin-top: 20px; }
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; font-family: 'Montserrat'; font-weight: 700; font-size: 1rem; margin-bottom: 8px; color: #333; }
        .form-control { width: 100%; padding: 14px 15px; border: 1.5px solid #D1D1D1; border-radius: 10px; background: #fff; font-size: 1rem; font-family: 'Inter'; transition: border 0.2s; }
        .form-control:focus { border-color: var(--teal); outline: none; }
        textarea.form-control { height: 150px; resize: vertical; }
        .row { display: flex; gap: 20px; }
        .row .form-group { flex: 1; }
        .button-group { display: flex; gap: 15px; margin-top: 30px; }
        .btn { padding: 14px 36px; border-radius: 10px; font-weight: 800; cursor: pointer; font-size: 0.95rem; font-family: 'Montserrat'; transition: 0.2s; border: none; }
        .btn-outline { background: transparent; border: 2px solid var(--teal); color: var(--teal); }
        .btn-outline:hover { background: var(--teal); color: white; }
        .btn-solid { background: var(--teal); color: white; flex: 1; }
        .btn-solid:hover { opacity: 0.88; }
        .alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; }
        .success { background: #D5F5E3; color: #27AE60; }
        .error { background: #FADBD8; color: #E74C3C; }
        .page-title { font-family: 'Montserrat'; font-size: 2.2rem; font-weight: 900; margin-bottom: 6px; }
        .page-sub { color: #888; font-size: 0.9rem; margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- SIDEBAR -->
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
                <p class="nav-label">OPERATIONS</p>
                <a href="create_event.php" class="active"><i class='bx bx-plus-circle'></i> Create Event</a>
                <a href="org_summary.php"><i class='bx bx-bar-chart-alt-2'></i> Analytics</a>
                <p class="nav-label">ACCOUNT</p>
                <a href="edit_profile.php"><i class='bx bx-user-circle'></i> Edit Profile</a>
                <a href="logout.php"><i class='bx bx-log-out'></i> Log out</a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <header class="top-header">
                <div class="logo">Univents</div>
                <a href="org_dashboard.php" style="text-decoration:none; color:var(--teal); font-weight:bold; font-size:0.9rem;">← Back to Dashboard</a>
            </header>

            <div class="content-body">
                <h1 class="page-title">Create New Event</h1>
                <p class="page-sub">Fill in the details below. The Event ID is auto-generated by the system.</p>

                <?= $message ?>

                <div class="form-container">
                    <form method="POST">
                        <div class="form-group">
                            <label>Event Title</label>
                            <input type="text" name="title" class="form-control" placeholder="E.g. Tech Conference 2026" required>
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" placeholder="Tell students what the event is about..." required></textarea>
                        </div>

                        <div class="form-group">
                            <label>Venue</label>
                            <input type="text" name="venue" class="form-control" placeholder="E.g. CITU Gymnasium" required>
                        </div>

                        <div class="row">
                            <div class="form-group">
                                <label>Start Date & Time</label>
                                <input type="datetime-local" name="start_datetime" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>End Date & Time</label>
                                <input type="datetime-local" name="end_datetime" class="form-control" required>
                            </div>
                        </div>

                        <div class="form-group" style="max-width: 300px;">
                            <label>Maximum Capacity</label>
                            <input type="number" name="capacity" class="form-control" placeholder="E.g. 100" required>
                        </div>

                        <div class="button-group">
                            <button type="submit" name="save_draft" class="btn btn-outline">
                                <i class='bx bx-save' style="margin-right:6px;"></i>Save Draft
                            </button>
                            <button type="submit" name="create_event" class="btn btn-solid">
                                <i class='bx bx-check-circle' style="margin-right:6px;"></i>Create Event
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
