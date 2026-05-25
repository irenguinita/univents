<?php
include 'db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['status' => 'error', 'message' => 'Please log in as a student to RSVP.']);
    exit();
}

$student_id = $_SESSION['user_id'];
$event_id = isset($_POST['id']) ? $_POST['id'] : null;

if (!$event_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Event ID.']);
    exit();
}

try {
    $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM rsvp WHERE student_id = ? AND event_id = ? AND rsvp_status != 'cancelled'");
    $stmtCheck->execute([$student_id, $event_id]);
    
    if ($stmtCheck->fetchColumn() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'You have already registered for this event!']);
        exit();
    }

    $stmtCap = $conn->prepare("
        SELECT maximum_capacity, 
        (SELECT COUNT(*) FROM rsvp WHERE event_id = ? AND rsvp_status != 'cancelled') as current_rsvps 
        FROM event WHERE event_id = ?
    ");
    $stmtCap->execute([$event_id, $event_id]);
    $event_data = $stmtCap->fetch();

    if ($event_data['current_rsvps'] >= $event_data['maximum_capacity']) {
        echo json_encode(['status' => 'error', 'message' => 'Sorry, this event is already full!']);
        exit();
    }

    // If a cancelled row exists, reactivate it; otherwise insert fresh
    $stmtExisting = $conn->prepare("SELECT COUNT(*) FROM rsvp WHERE student_id = ? AND event_id = ? AND rsvp_status = 'cancelled'");
    $stmtExisting->execute([$student_id, $event_id]);

    if ($stmtExisting->fetchColumn() > 0) {
        $sql = "UPDATE rsvp SET rsvp_status = 'Confirmed' WHERE student_id = ? AND event_id = ?";
        $stmtInsert = $conn->prepare($sql);
    } else {
        $sql = "INSERT INTO rsvp (student_id, event_id, rsvp_status) VALUES (?, ?, 'Confirmed')";
        $stmtInsert = $conn->prepare($sql);
    }

    if ($stmtInsert->execute([$student_id, $event_id])) {
        echo json_encode(['status' => 'success', 'message' => 'Your spot has been reserved!']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
}