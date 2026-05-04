<?php
include 'db.php';
session_start();
if ($_SESSION['role'] !== 'org') header("Location: login.php");

if (isset($_POST['add_event'])) {
    $sql = "INSERT INTO event (title, venue, start_datetime, end_datetime, maximum_capacity, organization_id, current_status) 
            VALUES (?, ?, ?, ?, ?, ?, 'Upcoming')";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$_POST['title'], $_POST['venue'], $_POST['start'], $_POST['end'], $_POST['cap'], $_SESSION['user_id']]);
    echo "Event Posted!";
}
?>
<form method="POST">
    <input type="text" name="title" placeholder="Title"><br>
    <input type="text" name="venue" placeholder="Venue"><br>
    <input type="datetime-local" name="start"><br>
    <input type="datetime-local" name="end"><br>
    <input type="number" name="cap" placeholder="Capacity"><br>
    <button type="submit" name="add_event">Create Event</button>
</form>