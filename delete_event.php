<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'org') {
    header("Location: login.php");
    exit();
}

$org_id = $_SESSION['user_id'];
$event_id = $_GET['id'];

try {
    $stmt = $conn->prepare("DELETE FROM event WHERE event_id = ? AND organization_id = ?");
    $stmt->execute([$event_id, $org_id]);

    $_SESSION['flash_msg']  = "Event deleted successfully.";
    $_SESSION['flash_type'] = "success";
    header("Location: org_dashboard.php");
} catch (PDOException $e) {
    $_SESSION['flash_msg']  = "Error deleting event: " . $e->getMessage();
    $_SESSION['flash_type'] = "error";
    header("Location: org_dashboard.php");
}
exit();
