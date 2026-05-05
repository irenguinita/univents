<?php
include 'db.php';
session_start();

// Security
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'org') {
    header("Location: login.php");
    exit();
}

$org_id = $_SESSION['user_id'];
$event_id = $_GET['id'];

try {
    // Delete the event (Ensuring it belongs to this org)
    $stmt = $conn->prepare("DELETE FROM event WHERE event_id = ? AND organization_id = ?");
    $stmt->execute([$event_id, $org_id]);

    header("Location: org_dashboard.php?msg=deleted");
} catch (PDOException $e) {
    die("Error deleting event: " . $e->getMessage());
}