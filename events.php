<?php
include 'db.php';
session_start();

// 1. Fetch Events with Spot Counting
// We use a LEFT JOIN to count how many RSVPs each event has
$sql = "
    SELECT e.*, o.org_name, 
    (e.maximum_capacity - COUNT(r.event_id)) as spots_left
    FROM event e
    JOIN organization o ON e.organization_id = o.user_id
    LEFT JOIN rsvp r ON e.event_id = r.event_id
    GROUP BY e.event_id, o.org_name
    ORDER BY e.start_datetime ASC
";
$stmt = $conn->query($sql);
$events = $stmt->fetchAll();

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>What's Happening - Univents</title>
    <link rel="stylesheet" href="events-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@800;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>

    <!-- Header (Same as index) -->
    <header>
        <div class="container nav-wrapper">
            <div class="logo">
                <img src="logo.png" alt="Univents Logo"> <span>Univents</span>
            </div>
            <nav>
                <ul>
                    <li><a href="#" class="active">HOME</a></li>
                    <li><a href="events.php">EVENTS</a></li>
                    <li><a href="rsvps.php">RSVPs</a></li>
                    <li><a href="#">ABOUT</a></li>
                </ul>
            </nav>
            <div class="nav-buttons">
                <a href="register.php" class="btn-text">SIGN-UP</a>
                <a href="login.php" class="btn-primary login-btn">LOG-IN</a>
            </div>
        </div>
    </header>

    <!-- Banner Section -->
    <section class="events-banner">
        <div class="container">
            <p class="sub-label">Campus Events</p>
            <h1>WHAT'S HAPPENING ON <br> CAMPUS</h1>
            <p>Browse, filter, and RSVP to events from all organizations <br> across university.</p>
            
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search events, organizations, venue...">
                <button class="btn-search">Search</button>
            </div>
        </div>
        <!-- Abstract Shapes -->
        <div class="shape s1"></div>
        <div class="shape s2"></div>
    </section>

    <!-- Filters -->
    <section class="filters-section">
        <div class="container filter-wrapper">
            <div class="tags">
                <button class="tag active">All</button>
                <button class="tag">Technology</button>
                <button class="tag">Organization</button>
                <button class="tag">Hackathon</button>
                <button class="tag">Academic</button>
                <button class="tag">Culture</button>
                <button class="tag">Sports</button>
            </div>
            <select class="sort-dropdown">
                <option>Sort: Newest</option>
            </select>
        </div>
    </section>

    <!-- Events Grid -->
    <section class="events-grid-section">
        <div class="container events-grid">
            <?php foreach($events as $e): 
                // Determine a random banner color for cards (or based on category)
                $colors = ['#63B3A0', '#F5B041', '#5D6D7E', '#A569BD', '#EB984E', '#52BE80'];
                $rand_color = $colors[array_rand($colors)];
            ?>
            <div class="event-card">
                <div class="card-banner" style="background: <?php echo $rand_color; ?>">
                    <span class="category-tag">CATEGORY</span>
                </div>
                <div class="card-content">
                    <p class="org-name"><span class="dot"></span> <?php echo $e['org_name']; ?></p>
                    <h3><?php echo $e['title']; ?></h3>
                    <p class="desc"><?php echo substr($e['description'], 0, 100); ?>...</p>
                    
                    <div class="event-meta">
                        <span><i class='bx bx-calendar'></i> <?php echo date('M d, Y', strtotime($e['start_datetime'])); ?></span>
                        <span><i class='bx bx-map-pin'></i> <?php echo $e['venue']; ?></span>
                    </div>
                    
                    <div class="card-footer">
                        <span class="spots"><?php echo $e['spots_left']; ?> spots left</span>
                        <button class="btn-rsvp" onclick="handleRSVP(<?php echo $e['event_id']; ?>)">RSVP Now</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- POPUP MODAL: WANNA RSVP? -->
    <div id="rsvpModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-shapes">
                <div class="m-shape m1"></div>
                <div class="m-shape m2"></div>
                <div class="m-shape m3"></div>
            </div>
            <h2>Wanna RSVP?</h2>
            <div class="modal-buttons">
                <a href="login.php" class="btn-modal">Log In</a>
                <a href="register.php" class="btn-modal">Sign Up</a>
            </div>
            <button class="close-modal" onclick="closeModal()">×</button>
        </div>
    </div>

    <script>
        const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;

        function handleRSVP(eventId) {
            if (!isLoggedIn) {
                document.getElementById('rsvpModal').style.display = 'flex';
            } else {
                // If logged in, redirect to an RSVP processing page
                window.location.href = "process_rsvp.php?id=" + eventId;
            }
        }

        function closeModal() {
            document.getElementById('rsvpModal').style.display = 'none';
        }

        // Close modal if user clicks outside of it
        window.onclick = function(event) {
            let modal = document.getElementById('rsvpModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>

</body>
</html>