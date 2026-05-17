<?php
include 'db.php';
session_start();

// Security: Only allow Organizers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'org') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM organization WHERE user_id = ?");
$stmt->execute([$user_id]);
$org = $stmt->fetch();

$message = "";

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
        
        $message = "<p class='alert success'>Event created successfully!</p>";
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
        /* Specific styles for the Form */
        .form-container {
            max-width: 800px;
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            font-family: 'Montserrat';
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 15px;
            border: 1.5px solid #D1D1D1;
            border-radius: 8px;
            background: #fff;
            font-size: 1rem;
            font-family: 'Inter';
        }
        .form-control:disabled {
            background: #E8E8E8;
            color: #888;
        }
        textarea.form-control {
            height: 150px;
            resize: vertical;
        }
        .row {
            display: flex;
            gap: 20px;
        }
        .row .form-group {
            flex: 1;
        }
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        .btn {
            padding: 15px 40px;
            border-radius: 8px;
            font-weight: 800;
            cursor: pointer;
            font-size: 1rem;
            transition: 0.3s;
        }
        .btn-outline {
            background: transparent;
            border: 2px solid var(--teal);
            color: var(--teal);
        }
        .btn-solid {
            background: var(--teal);
            border: none;
            color: white;
            flex: 1;
        }
        .btn:hover { opacity: 0.8; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; }
        .success { background: #D5F5E3; color: #27AE60; }
        .error { background: #FADBD8; color: #E74C3C; }
    </style>
</head>
<body>

    <div class="dashboard-container">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-user">
                <div class="user-avatar"><?php echo substr($org['org_name'], 0, 1); ?></div>
                <div class="user-info">
                    <h4><?php echo $org['org_name']; ?></h4>
                    <p>ORGANIZER • <?php echo $org['verification_status']; ?></p>
                </div>
            </div>

            <nav class="side-nav">
                <p class="nav-label">MAIN</p>
                <a href="org_dashboard.php"><i class='bx bxs-dashboard'></i> Dashboard</a>
                
                <p class="nav-label">OPERATIONS</p>
                <a href="create_event.php" class="active"><i class='bx bx-plus-circle'></i> Create Event</a>
                <a href="#"><i class='bx bx-bar-chart-alt-2'></i> Summary</a>

                <p class="nav-label">ACCOUNT</p>
                <a href="#"><i class='bx bx-cog'></i> Settings</a>
                <a href="logout.php"><i class='bx bx-log-out'></i> Log out</a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <header class="top-header">
                <div class="logo">Univents</div>
            </header>

            <div class="content-body">
                <h1 style="font-family:'Montserrat'; font-size:2.5rem;">Create New Event</h1>
                
                <?php echo $message; ?>

                <div class="form-container">
                    <form method="POST">
                        <div class="form-group">
                            <label>Event ID:</label>
                            <input type="text" class="form-control" disabled placeholder="Auto-generated by system">
                        </div>

                        <div class="form-group">
                            <label>Event Title:</label>
                            <input type="text" name="title" class="form-control" placeholder="E.g. Tech Conference 2026" required>
                        </div>

                        <div class="form-group">
                            <label>Description:</label>
                            <textarea name="description" class="form-control" placeholder="Tell students what the event is about..." required></textarea>
                        </div>

                        <div class="form-group">
                            <label>Venue:</label>
                            <input type="text" name="venue" class="form-control" placeholder="E.g. STC Auditorium" required>
                        </div>

                        <div class="row">
                            <div class="form-group">
                                <label>Start Date & Time:</label>
                                <input type="datetime-local" name="start_datetime" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>End Date & Time:</label>
                                <input type="datetime-local" name="end_datetime" class="form-control" required>
                            </div>
                        </div>

                        <div class="form-group" style="max-width: 300px;">
                            <label>Maximum Capacity:</label>
                            <input type="number" name="capacity" class="form-control" placeholder="E.g. 100" required>
                        </div>

                        <div class="button-group">
                            <button type="button" class="btn btn-outline">Save Draft</button>
                            <button type="submit" name="create_event" class="btn btn-solid">Create Event</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

</body>
</html>