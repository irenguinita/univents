<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];

try {
    $conn->beginTransaction();

    if ($role === 'student') {

        $conn->prepare("DELETE FROM rsvp WHERE student_id = ?")->execute([$user_id]);

        $conn->prepare("DELETE FROM review WHERE student_id = ?")->execute([$user_id]);

        $conn->prepare("DELETE FROM student WHERE user_id = ?")->execute([$user_id]);
    } else {

        $conn->prepare("DELETE FROM rsvp WHERE event_id IN (SELECT event_id FROM event WHERE organization_id = ?)")->execute([$user_id]);

        $conn->prepare("DELETE FROM event WHERE organization_id = ?")->execute([$user_id]);

        $conn->prepare("DELETE FROM organization WHERE user_id = ?")->execute([$user_id]);
    }


    $conn->prepare("DELETE FROM \"user\" WHERE user_id = ?")->execute([$user_id]);

    $conn->commit();


    session_destroy();
    header("Location: index.php?msg=account_deleted");
    exit();

} catch (PDOException $e) {
    $conn->rollBack();
    $_SESSION['flash_msg']  = "Error deleting account: " . $e->getMessage();
    $_SESSION['flash_type'] = "error";
    header("Location: edit_profile.php");
    exit();
}
?>
