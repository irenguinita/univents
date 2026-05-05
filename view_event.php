<?php
include 'db.php';
session_start();

// 1. Get IDs
$event_id = isset($_GET['id']) ? $_GET['id'] : null;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

if (!$event_id) { die("No event selected."); }

// 2. Fetch Event Details
$stmt = $conn->prepare("SELECT e.*, o.org_name FROM event e JOIN organization o ON e.organization_id = o.user_id WHERE e.event_id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) { die("Event not found."); }

// 3. CHECK RSVP STATUS
$is_rsvpd = false;
if ($user_id && $role === 'student') {
    $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM rsvp WHERE student_id = ? AND event_id = ?");
    $stmtCheck->execute([$user_id, $event_id]);
    $count = $stmtCheck->fetchColumn();
    
    if ($count > 0) {
        $is_rsvpd = true;
    }
}

// 4. Calculate Spots Left
$stmtSpots = $conn->prepare("SELECT COUNT(*) FROM rsvp WHERE event_id = ?");
$stmtSpots->execute([$event_id]);
$spots_taken = $stmtSpots->fetchColumn();
$spots_left = $event['maximum_capacity'] - $spots_taken;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($event['title']); ?> - Univents</title>
    <link rel="stylesheet" href="events-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .detail-container { display: grid; grid-template-columns: 1.5fr 1fr; gap: 50px; padding: 60px 80px; background: #F9F7F2; min-height: 80vh; }
        .event-main-info h1 { font-family: 'Montserrat'; font-size: 3.5rem; line-height: 1; margin-bottom: 20px; }
        .org-badge { color: #326257; font-weight: 800; margin-bottom: 10px; display: block; }
        .description-box { margin-top: 30px; font-size: 1.1rem; line-height: 1.8; color: #555; }
        
        .action-card { background: white; padding: 40px; border-radius: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.05); height: fit-content; position: sticky; top: 100px; }
        .btn-cancel { background: #FADBD8; color: #E74C3C; border: 2px solid #E74C3C; width: 100%; padding: 15px; border-radius: 12px; font-weight: bold; cursor: pointer; margin-top: 20px; }
        .btn-cancel:hover { background: #E74C3C; color: white; }

        /* Modal Styles for Feedback */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: none; justify-content: center; align-items: center; z-index: 9999; backdrop-filter: blur(5px); }
        .modal-content { background: white; width: 400px; padding: 40px; border-radius: 30px; text-align: center; }
    </style>
</head>
<body>

    <header>
        <div class="nav-wrapper">
            <div class="logo">Univents</div>
            <nav>
                <ul>
                    <li><a href="index.php">HOME</a></li>
                    <li><a href="events.php">EVENTS</a></li>
                </ul>
            </nav>
            <a href="student_dashboard.php" style="text-decoration:none; color:#326257; font-weight:bold;">← Back to Dashboard</a>
        </div>
    </header>

    <div class="detail-container">
        <!-- LEFT: Content -->
        <div class="event-main-info">
            <span class="org-badge">HOSTED BY <?php echo strtoupper($event['org_name']); ?></span>
            <h1><?php echo htmlspecialchars($event['title']); ?></h1>
            
            <div class="description-box">
                <h3 style="font-family: 'Montserrat'; margin-bottom: 15px;">About this Event</h3>
                <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
            </div>
        </div>

        <!-- RIGHT: Action Sidebar -->
        <div class="action-card">
            <h3>Event Logistics</h3>
            <p style="margin-top:10px;"><strong>Location:</strong> <?php echo htmlspecialchars($event['venue']); ?></p>
            <p><strong>Time:</strong> <?php echo date('F j, Y • g:i A', strtotime($event['start_datetime'])); ?></p>
            <p><strong>Spots:</strong> <?php echo $spots_left; ?> remaining</p>
            
            <hr style="margin:20px 0; border:none; border-top: 1px solid #eee;">

            <?php if (!$user_id): ?>
                <button class="btn-rsvp" onclick="location.href='login.php'" style="width:100%;">Log in to RSVP</button>
            
            <?php elseif ($role === 'org'): ?>
                <p style="text-align:center; color:#888;">Organizations cannot RSVP.</p>

            <?php elseif ($is_rsvpd === true): ?>
                <div style="text-align:center;">
                    <p style="color:#27AE60; font-weight:bold; margin-bottom:10px;">✓ You are registered</p>
                    <button class="btn-cancel" onclick="confirmCancel(<?php echo $event_id; ?>)">Cancel RSVP</button>
                </div>

            <?php else: ?>
                <button class="btn-rsvp" onclick="handleRSVP(<?php echo $event_id; ?>)" style="width:100%;">RSVP Now</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- RESULT MODAL (For RSVP Feedback) -->
    <div id="resultModal" class="modal-overlay">
        <div class="modal-content">
            <h2 id="resultTitle">Success!</h2>
            <p id="resultMessage" style="margin: 20px 0; color: #666;"></p>
            <button class="btn-modal" onclick="location.reload()" style="width:100%; padding:15px; background:#E68A6E; color:white; border:none; border-radius:10px; font-weight:bold; cursor:pointer;">Great!</button>
        </div>
    </div>

    <script>
        const isLoggedIn = <?php echo $user_id ? 'true' : 'false'; ?>;

        // CANCEL FUNCTION
        function confirmCancel(id) {
            if (confirm("Are you sure you want to cancel your spot?")) {
                window.location.href = "cancel_rsvp.php?id=" + id;
            }
        }

        // RSVP FUNCTION (AJAX)
        function handleRSVP(eventId) {
            fetch('process_rsvp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + eventId
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('resultTitle').innerText = (data.status === 'success') ? "Success!" : "Oops!";
                document.getElementById('resultMessage').innerText = data.message;
                document.getElementById('resultModal').style.display = 'flex';
            });
        }
    </script>
</body>
</html>