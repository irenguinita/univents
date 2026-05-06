<?php
include 'db.php';
session_start();

if ($_SESSION['role'] !== 'org') { header("Location: login.php"); exit(); }
$org_id = $_SESSION['user_id'];

// 1. Fetch Stats
$totalManaged = $conn->prepare("SELECT COUNT(*) FROM event WHERE organization_id = ?");
$totalManaged->execute([$org_id]);

$totalReg = $conn->prepare("SELECT COUNT(*) FROM rsvp r JOIN event e ON r.event_id = e.event_id WHERE e.organization_id = ?");
$totalReg->execute([$org_id]);

// 2. Event Analytics Table Data
$stmt = $conn->prepare("
    SELECT e.title, e.start_datetime, 
    COALESCE((SELECT AVG(rating) FROM review WHERE event_id = e.event_id), 4.5) as avg_rating,
    (SELECT COUNT(*) FROM review WHERE event_id = e.event_id) as comment_count
    FROM event e WHERE organization_id = ?
");
$stmt->execute([$org_id]);
$analytics = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="univents-theme.css">
    <title>Summary - Organizer</title>
</head>
<body style="display:flex;">
    <!-- Reuse Sidebar from Dashboard -->
    <div class="dashboard-container" style="display:flex; width:100%;">
        <aside class="sidebar" style="width:260px; background:var(--charcoal); color:white; height:100vh; padding:30px;">
            <h2>Univents</h2>
            <!-- Nav links here... -->
        </aside>

        <main style="flex:1; padding:50px;">
            <h1>Total Overview</h1>
            <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:20px; margin:40px 0;">
                <div class="stat-card" style="background:white; padding:30px; border-radius:20px; text-align:center; box-shadow:0 5px 15px rgba(0,0,0,0.05);">
                    <p style="color:#888; font-weight:bold;">Total Events Managed</p>
                    <h2 style="font-size:4rem;"><?= $totalManaged->fetchColumn(); ?></h2>
                </div>
                <div class="stat-card" style="background:white; padding:30px; border-radius:20px; text-align:center; box-shadow:0 5px 15px rgba(0,0,0,0.05);">
                    <p style="color:#888; font-weight:bold;">Global Average Rating</p>
                    <h2 style="font-size:4rem;">4.5/5</h2>
                    <p style="color:#F1C40F;">⭐⭐⭐⭐★</p>
                </div>
                <div class="stat-card" style="background:white; padding:30px; border-radius:20px; text-align:center; box-shadow:0 5px 15px rgba(0,0,0,0.05);">
                    <p style="color:#888; font-weight:bold;">Total Registered Attendees</p>
                    <h2 style="font-size:4rem;"><?= number_format($totalReg->fetchColumn()); ?></h2>
                </div>
            </div>

            <div style="background:white; border-radius:20px; padding:30px;">
                <h3 style="margin-bottom:20px;">EVENT ANALYTICS</h3>
                <table style="width:100%; border-collapse:collapse;">
                    <thead style="background:#f9f9f9; color:#888; text-align:left;">
                        <tr><th style="padding:15px;">Event Title</th><th>Date</th><th>Avg. Rating</th><th>Comments</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($analytics as $row): ?>
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:20px;"><strong><?= $row['title'] ?></strong></td>
                            <td><?= date('m/d/Y', strtotime($row['start_datetime'])) ?></td>
                            <td>⭐ <?= number_format($row['avg_rating'], 1) ?></td>
                            <td><a href="#" style="color:var(--teal-main);">View all <?= $row['comment_count'] ?> comments</a></td>
                            <td><button style="border:1px solid #ddd; padding:8px 15px; border-radius:8px; background:white;">Export</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>