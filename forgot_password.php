<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

include 'db.php';

header('Content-Type: application/json');

ob_start();

$action = $_POST['action'] ?? '';

if ($action === 'verify_email') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Email is required.']);
        exit();
    }

    try {
        $stmt = $conn->prepare("SELECT user_id, institutional_email FROM \"user\" WHERE institutional_email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        ob_end_clean();

        if ($user) {
            $emailLocal = explode('@', $user['institutional_email'])[0]; 
            $firstName  = ucfirst(explode('.', $emailLocal)[0]);         

            echo json_encode([
                'success' => true,
                'name'    => $firstName,
                'uid'     => base64_encode($user['user_id'])
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No account found with that email address.']);
        }

    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }

    exit();
}

// ── Step 2: Reset password ───────────────────────────────────
if ($action === 'reset_password') {
    $uid             = $_POST['uid']              ?? '';
    $newPassword     = $_POST['new_password']     ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($uid)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid request. Please start over.']);
        exit();
    }

    $userId = base64_decode($uid);

    if (!is_numeric($userId)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid request. Please start over.']);
        exit();
    }

    if (strlen($newPassword) < 8) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
        exit();
    }

    if ($newPassword !== $confirmPassword) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        exit();
    }

    try {
        $check = $conn->prepare("SELECT user_id FROM \"user\" WHERE user_id = ?");
        $check->execute([$userId]);
        if (!$check->fetch()) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            exit();
        }

        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt   = $conn->prepare("UPDATE \"user\" SET password = ? WHERE user_id = ?");
        $stmt->execute([$hashed, $userId]);

        ob_end_clean();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }

    exit();
}

ob_end_clean();
echo json_encode(['success' => false, 'message' => 'Invalid action.']);