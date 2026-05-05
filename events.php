<?php
include 'db.php';
session_start();

// Fetch Events with real-time spots left
$sql = "
    SELECT e.*, o.org_name, 
    (e.maximum_capacity - (SELECT COUNT(*) FROM rsvp r WHERE r.event_id = e.event_id)) as spots_left
    FROM event e
    JOIN organization o ON e.organization_id = o.user_id
    ORDER BY e.start_datetime ASC
";
$stmt = $conn->query($sql);
$events = $stmt->fetchAll();

$is_logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Events - Univents</title>
    <link rel="stylesheet" href="events-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>

    <!-- NAVBAR -->
    <header>
        <div class="nav-wrapper">
            <div class="logo">Univents</div>
            <nav>
                <ul>
                    <li><a href="index.php">HOME</a></li>
                    <li><a href="events.php" class="active">EVENTS</a></li>
                    <li><a href="#">RSVPs</a></li>
                    <li><a href="about.php">ABOUT</a></li>
                </ul>
            </nav>
            <div class="nav-buttons">
                <?php if($is_logged_in): ?>
                    <a href="dashboard.php" class="btn-primary">DASHBOARD</a>
                <?php else: ?>
                    <a href="register.php" class="btn-text" style="text-decoration:none; color:#333; font-weight:bold; margin-right:15px;">SIGN-UP</a>
                    <a href="login.php" class="btn-primary">LOG-IN</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- BANNER -->
    <section class="events-banner">
        <div class="container">
            <p class="sub-label">Campus Events</p>
            <h1>WHAT'S HAPPENING ON <br> CAMPUS</h1>
            <div class="search-bar">
                <input type="text" placeholder="Search events...">
                <button class="btn-search">Search</button>
            </div>
        </div>
    </section>

    <!-- GRID -->
<section class="events-grid-section">
    <div class="events-grid">
        <?php foreach($events as $e): ?>
            <!-- Wrap the entire card in the anchor tag -->
            <a href="view_event.php?id=<?php echo $e['event_id']; ?>" class="card-link" style="text-decoration:none; color:inherit;">
                <div class="event-card">
                    <!-- Banner -->
                    <div class="card-banner" style="background: #4BA68D;"></div>
                    
                    <div class="card-content">
                        <!-- Organization Name -->
                        <p class="org-name"><?php echo htmlspecialchars($e['org_name']); ?></p>
                        
                        <!-- Event Title -->
                        <h3><?php echo htmlspecialchars($e['title']); ?></h3>
                        
                        <!-- Description -->
                        <p class="desc"><?php echo htmlspecialchars(substr($e['description'], 0, 80)); ?>...</p>
                        
                        <div class="card-footer">
                            <!-- Spots Counter -->
                            <span class="spots"><?php echo $e['spots_left']; ?> spots left</span>
                            
                            <!-- RSVP Button -->
                            <!-- We use stopPropagation so clicking the button doesn't open the "view_event" page -->
                            <button class="btn-rsvp" onclick="event.preventDefault(); event.stopPropagation(); handleRSVP(<?php echo $e['event_id']; ?>)">
                                RSVP Now
                            </button>
                        </div>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>

    <!-- MODAL 1: AUTH PROMPT (The "Wanna RSVP?" pop-up) -->
    <div id="authModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-shapes">
                <div class="m-shape m1" style="background:#D5D8DC;"></div>
                <div class="m-shape m2" style="background:#FADBD8;"></div>
            </div>
            <h2>Wanna RSVP?</h2>
            <div class="modal-buttons">
                <a href="login.php" class="btn-modal">Log In</a>
                <a href="register.php" class="btn-modal" style="background:#eee; color:#333;">Sign Up</a>
            </div>
            <button class="close-modal" onclick="closeModal('authModal')">×</button>
        </div>
    </div>

    <!-- MODAL 2: RESULT FEEDBACK (The Success/Error pop-up) -->
    <div id="resultModal" class="modal-overlay">
        <div class="modal-content">
            <h2 id="resultTitle">Success!</h2>
            <p id="resultMessage" style="margin: 20px 0; color: #666;"></p>
            <button class="btn-modal" onclick="location.reload()">Great!</button>
        </div>
    </div>

    <script>
        const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;

        function handleRSVP(eventId) {
            if (!isLoggedIn) {
                // Show Login Pop-up
                document.getElementById('authModal').style.display = 'flex';
            } else {
                // Process RSVP via AJAX (Background request)
                fetch('process_rsvp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + eventId
                })
                .then(response => response.json())
                .then(data => {
                    // Show Result Pop-up
                    document.getElementById('resultTitle').innerText = (data.status === 'success') ? "Success!" : "Wait...";
                    document.getElementById('resultMessage').innerText = data.message;
                    document.getElementById('resultModal').style.display = 'flex';
                });
            }
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }
    </script>
</body>
</html>