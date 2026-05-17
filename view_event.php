<?php
include 'db.php';
session_start();

$event_id = isset($_GET['id']) ? $_GET['id'] : null;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

if (!$event_id) { die("No event selected."); }

$stmt = $conn->prepare("SELECT e.*, o.org_name FROM event e JOIN organization o ON e.organization_id = o.user_id WHERE e.event_id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) { die("Event not found."); }

$is_rsvpd = false;
if ($user_id && $role === 'student') {
    $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM rsvp WHERE student_id = ? AND event_id = ?");
    $stmtCheck->execute([$user_id, $event_id]);
    $count = $stmtCheck->fetchColumn();
    
    if ($count > 0) {
        $is_rsvpd = true;
    }
}

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
    <link rel="stylesheet" href="auth-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body { background-color: #F9F7F2; font-family: 'Inter', sans-serif; }
        
        .view-wrapper {
            max-width: 1200px;
            margin: 40px auto;
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 40px;
            padding: 0 20px;
        }

        /* Left Content Column */
        .event-main-card {
            background: white;
            padding: 50px;
            border-radius: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        }

        .org-badge {
            display: inline-block;
            background: rgba(50, 98, 87, 0.1);
            color: #326257;
            padding: 6px 16px;
            border-radius: 50px;
            font-weight: 800;
            font-size: 0.75rem;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }

        .event-title {
            font-family: 'Montserrat';
            font-weight: 900;
            font-size: 3.2rem;
            line-height: 1.1;
            color: #2D2E2E;
            margin-bottom: 30px;
        }

        .section-label {
            font-family: 'Montserrat';
            font-weight: 800;
            font-size: 1.1rem;
            margin-bottom: 15px;
            color: #333;
        }

        .description-text {
            color: #666;
            line-height: 1.8;
            font-size: 1.05rem;
        }

        /* Right Sidebar Column */
        .action-sidebar {
            background: #326257;
            color: white;
            padding: 40px;
            border-radius: 30px;
            height: fit-content;
            position: sticky;
            top: 40px;
        }

        .info-row {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 25px;
        }

        .info-row i { font-size: 1.4rem; color: #F1948A; }
        .info-text h4 { font-size: 0.8rem; opacity: 0.7; text-transform: uppercase; margin-bottom: 4px; }
        .info-text p { font-weight: 600; font-size: 1rem; }

        .btn-action {
            width: 100%;
            padding: 18px;
            border-radius: 15px;
            border: none;
            font-weight: 800;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-primary { background: #F1948A; color: white; }
        .btn-primary:hover { background: #E68A6E; transform: translateY(-3px); }
        
        .btn-cancel { 
            background: transparent; 
            border: 2px solid rgba(255,255,255,0.3); 
            color: white; 
            margin-top: 15px;
        }
        .btn-cancel:hover { background: rgba(231, 76, 60, 0.2); border-color: #E74C3C; }

        /* Modal styling */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(8px); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: white; padding: 40px; border-radius: 25px; text-align: center; width: 90%; max-width: 400px; }
    </style>
</head>
<body>

   <header style="background: white; padding: 20px 0; border-bottom: 1px solid rgba(0,0,0,0.05);">
    <div class="nav-wrapper" style="display: flex; justify-content: space-between; align-items: center; width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 20px; box-sizing: border-box;">
        
        <!-- Univents Logo -->
        <a href="index.php" class="logo" style="font-family: 'Montserrat'; font-weight: 900; font-size: 1.8rem; color: #333; text-decoration: none;">Univents</a>
        
        <!-- Smart Back Button -->
        <div class="nav-buttons">
            <a href="javascript:history.back()" style="text-decoration: none; color: #326257; font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; gap: 8px;">
                <i class='bx bx-arrow-back' style="font-size: 1.2rem;"></i> BACK
            </a>
        </div>
    </div>
</header>

    <div class="view-wrapper">
        <!-- Main Content Area -->
        <div class="event-main-card">
            <!-- Breadcrumb style back link for extra UX -->
            <a href="events.php" style="text-decoration: none; color: #999; font-size: 0.8rem; font-weight: 600; margin-bottom: 20px; display: block;">
                EVENTS / <?php echo strtoupper(htmlspecialchars($event['title'])); ?>
            </a>
            
            <span class="org-badge">HOSTED BY <?php echo strtoupper($event['org_name']); ?></span>
            <h1 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h1>
            
            <div class="description-section">
                <h3 class="section-label">About this Event</h3>
                <p class="description-text"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
            </div>
        </div>

        <!-- Sticky Sidebar Area -->
        <div class="action-sidebar">
            <h3 class="section-label" style="color: white; margin-bottom: 30px;">Event Details</h3>
            
            <div class="info-row">
                <i class='bx bx-calendar-event'></i>
                <div class="info-text">
                    <h4>Date & Time</h4>
                    <p><?php echo date('F j, Y', strtotime($event['start_datetime'])); ?><br>
                       <?php echo date('g:i A', strtotime($event['start_datetime'])); ?></p>
                </div>
            </div>

            <div class="info-row">
                <i class='bx bx-map-pin'></i>
                <div class="info-text">
                    <h4>Venue</h4>
                    <p><?php echo htmlspecialchars($event['venue']); ?></p>
                </div>
            </div>

            <div class="info-row" style="margin-bottom: 40px;">
                <i class='bx bx-group'></i>
                <div class="info-text">
                    <h4>Availability</h4>
                    <p><?php echo ($spots_left > 0) ? $spots_left . " spots remaining" : "Event Full"; ?></p>
                </div>
            </div>

            <!-- Contextual Action Buttons -->
            <?php if (!$user_id): ?>
                <button class="btn-action btn-primary" onclick="location.href='login.php'">
                    Log in to RSVP <i class='bx bx-right-arrow-alt'></i>
                </button>
            
            <?php elseif ($role === 'org'): ?>
                <div style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 12px; text-align: center;">
                    <p style="font-size: 0.9rem; font-weight: 500;">Org View: RSVPs Disabled</p>
                </div>

            <?php elseif ($is_rsvpd === true): ?>
                <div style="text-align:center;">
                    <div style="background: #27AE60; color: white; padding: 15px; border-radius: 12px; margin-bottom: 10px; font-weight: bold;">
                        <i class='bx bx-check-circle'></i> You are Registered
                    </div>
                    <button class="btn-action btn-cancel" onclick="confirmCancel(<?php echo $event_id; ?>)">
                        Cancel My RSVP
                    </button>
                </div>

            <?php else: ?>
                <button class="btn-action btn-primary" onclick="handleRSVP(<?php echo $event_id; ?>)" <?php echo ($spots_left <= 0) ? 'disabled' : ''; ?>>
                    <?php echo ($spots_left > 0) ? "RSVP Now" : "Sold Out"; ?> <i class='bx bx-chevron-right'></i>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Result Modal -->
    <div id="resultModal" class="modal-overlay">
        <div class="modal-content">
            <h2 id="resultTitle" style="font-family: 'Montserrat'; font-weight: 900;"></h2>
            <p id="resultMessage" style="margin: 20px 0; color: #666;"></p>
            <button class="btn-action btn-primary" onclick="location.reload()">Done</button>
        </div>
    </div>

    <script>
        function confirmCancel(id) {
            if (confirm("Are you sure you want to release your spot?")) {
                window.location.href = "cancel_rsvp.php?id=" + id;
            }
        }

        function handleRSVP(eventId) {
            fetch('process_rsvp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + eventId
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('resultTitle').innerText = (data.status === 'success') ? "Success!" : "Wait!";
                document.getElementById('resultMessage').innerText = data.message;
                document.getElementById('resultModal').style.display = 'flex';
            });
        }
    </script>
</body>
</html>