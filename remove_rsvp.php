<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'org') {
    header("Location: login.php");
    exit();
}

$org_id     = $_SESSION['user_id'];
$event_id   = $_GET['event_id'] ?? null;
$student_id = $_GET['student_id'] ?? null;

if (!$event_id || !$student_id) {
    $_SESSION['flash_msg']  = "Invalid request.";
    $_SESSION['flash_type'] = "error";
    header("Location: org_dashboard.php");
    exit();
}

//verification
$check = $conn->prepare("SELECT event_id FROM event WHERE event_id = ? AND organization_id = ?");
$check->execute([$event_id, $org_id]);
if (!$check->fetch()) {
    $_SESSION['flash_msg']  = "Access denied.";
    $_SESSION['flash_type'] = "error";
    header("Location: org_dashboard.php");
    exit();
}

try {
    $stmt = $conn->prepare("DELETE FROM rsvp WHERE event_id = ? AND student_id = ?");
    $stmt->execute([$event_id, $student_id]);

    $_SESSION['flash_msg']  = "RSVP removed successfully.";
    $_SESSION['flash_type'] = "success";
} catch (PDOException $e) {
    $_SESSION['flash_msg']  = "Error removing RSVP: " . $e->getMessage();
    $_SESSION['flash_type'] = "error";
}

header("Location: manage_event.php?id=$event_id");
exit();
?>
