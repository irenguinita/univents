<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$event_id = $_GET['id'];

try {
    $stmt = $conn->prepare("DELETE FROM rsvp WHERE student_id = ? AND event_id = ?");
    $stmt->execute([$student_id, $event_id]);

    $_SESSION['msg'] = "Your RSVP has been cancelled.";
    $_SESSION['msg_type'] = "success";
    header("Location: student_dashboard.php");
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}