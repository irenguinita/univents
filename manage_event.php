<?php
include 'db.php';
session_start();

// Security: Only allow Organizers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'org') {
    header("Location: login.php");
    exit();
}

$org_id = $_SESSION['user_id'];
$event_id = $_GET['id'];

// 1. Fetch Event Details (Verify the Org actually owns this event)
$stmt = $conn->prepare("SELECT * FROM event WHERE event_id = ? AND organization_id = ?");
$stmt->execute([$event_id, $org_id]);
$event = $stmt->fetch();

if (!$event) {
    die("Event not found or access denied.");
}

// 2. Fetch Registrants (Students who RSVP'd)
$stmtReg = $conn->prepare("
    SELECT s.name, s.department, r.timestamp 
    FROM student s 
    JOIN rsvp r ON s.user_id = r.student_id 
    WHERE r.event_id = ?
    ORDER BY r.timestamp DESC
");
$stmtReg->execute([$event_id]);
$registrants = $stmtReg->fetchAll();

// 3. Handle Update (Edit Event)
if (isset($_POST['update_event'])) {
    $sql = "UPDATE event SET title = ?, description = ?, venue = ?, maximum_capacity = ? WHERE event_id = ?";
    $conn->prepare($sql)->execute([$_POST['title'], $_POST['desc'], $_POST['venue'], $_POST['cap'], $event_id]);
    header("Location: manage_event.php?id=$event_id&msg=updated");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Event - Univents</title>
    <link rel="stylesheet" href="dashboard-style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .manage-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px; margin-top: 30px; }
        .white-card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .registrants-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .registrants-table th { text-align: left; border-bottom: 2px solid #eee; padding: 10px; color: #888; font-size: 0.8rem; }
        .registrants-table td { padding: 15px 10px; border-bottom: 1px solid #f9f9f9; font-size: 0.9rem; }
        .btn-edit { background: #5D6D7E; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; }
        .btn-delete { background: #E74C3C; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; margin-left: 10px; }
        .edit-form input, .edit-form textarea { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 8px; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Sidebar (Same as Org Dashboard) -->
    <aside class="sidebar">...</aside>

    <main class="main-content">
        <header class="top-header">
            <div class="logo">Univents</div>
            <a href="org_dashboard.php" style="text-decoration:none; color:var(--teal); font-weight:bold;">← Back to Dashboard</a>
        </header>

        <div class="content-body">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h1>Manage: <span class="teal-text"><?php echo $event['title']; ?></span></h1>
                <div>
                    <button class="btn-edit" onclick="document.getElementById('editSection').scrollIntoView({behavior:'smooth'})">Edit Details</button>
                    <button class="btn-delete" onclick="confirmDelete(<?php echo $event['event_id']; ?>)">Delete Event</button>
                </div>
            </div>

            <div class="manage-grid">
                <!-- LIST OF REGISTRANTS -->
                <div class="white-card">
                    <h3>Registrants (<?php echo count($registrants); ?>)</h3>
                    <table class="registrants-table">
                        <thead>
                            <tr><th>Student Name</th><th>Dept</th><th>RSVP Date</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($registrants as $reg): ?>
                            <tr>
                                <td><strong><?php echo $reg['name']; ?></strong></td>
                                <td><?php echo $reg['department']; ?></td>
                                <td style="color:#aaa; font-size:0.8rem;"><?php echo date('M d, H:i', strtotime($reg['timestamp'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($registrants)) echo "<tr><td colspan='3' style='text-align:center; padding:40px; color:#ccc;'>No one has RSVP'd yet.</td></tr>"; ?>
                        </tbody>
                    </table>
                </div>

                <!-- EDIT SECTION -->
                <div class="white-card" id="editSection">
                    <h3>Edit Event Details</h3>
                    <form method="POST" class="edit-form" style="margin-top:20px;">
                        <label>Event Title</label>
                        <input type="text" name="title" value="<?php echo $event['title']; ?>" required>
                        
                        <label>Description</label>
                        <textarea name="desc" rows="5"><?php echo $event['description']; ?></textarea>
                        
                        <label>Venue</label>
                        <input type="text" name="venue" value="<?php echo $event['venue']; ?>" required>
                        
                        <label>Max Capacity</label>
                        <input type="number" name="cap" value="<?php echo $event['maximum_capacity']; ?>" required>
                        
                        <button type="submit" name="update_event" class="btn-auth" style="width:auto; padding:15px 40px;">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
function confirmDelete(id) {
    if (confirm("Are you sure you want to delete this event? This cannot be undone.")) {
        window.location.href = "delete_event.php?id=" + id;
    }
}
</script>

</body>
</html>