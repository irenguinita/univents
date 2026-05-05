<?php
include 'db.php';
session_start();

// Set header to JSON so the browser knows how to read the response
header('Content-Type: application/json');

// 1. Security Check: Is the user a logged-in student?
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
    // 2. Check if already RSVP'd
    $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM rsvp WHERE student_id = ? AND event_id = ?");
    $stmtCheck->execute([$student_id, $event_id]);
    
    if ($stmtCheck->fetchColumn() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'You have already registered for this event!']);
        exit();
    }

    // 3. Check Capacity
    $stmtCap = $conn->prepare("
        SELECT maximum_capacity, 
        (SELECT COUNT(*) FROM rsvp WHERE event_id = ?) as current_rsvps 
        FROM event WHERE event_id = ?
    ");
    $stmtCap->execute([$event_id, $event_id]);
    $event_data = $stmtCap->fetch();

    if ($event_data['current_rsvps'] >= $event_data['maximum_capacity']) {
        echo json_encode(['status' => 'error', 'message' => 'Sorry, this event is already full!']);
        exit();
    }

    // 4. Insert RSVP
    $sql = "INSERT INTO rsvp (student_id, event_id, rsvp_status) VALUES (?, ?, 'Confirmed')";
    $stmtInsert = $conn->prepare($sql);
    
    if ($stmtInsert->execute([$student_id, $event_id])) {
        echo json_encode(['status' => 'success', 'message' => 'Your spot has been reserved!']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
}