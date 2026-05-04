<?php
include 'db.php';
session_start();

$error = "";
if (isset($_POST['login'])) {
    $stmt = $conn->prepare("SELECT * FROM \"user\" WHERE institutional_email = ?");
    $stmt->execute([$_POST['email']]);
    $user = $stmt->fetch();

    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        // Check Role
        $stmtS = $conn->prepare("SELECT * FROM student WHERE user_id = ?");
        $stmtS->execute([$user['user_id']]);
       if ($stmtS->fetch()) {
            $_SESSION['role'] = 'student';
            header("Location: student_dashboard.php");
            exit;
        } else {
            $_SESSION['role'] = 'org';
            header("Location: org_dashboard.php");
            exit;
        }
    } else {
        $error = "Invalid institutional email or password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Log In - Univents</title>
    <link rel="stylesheet" href="auth-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@900&family=Inter:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="logo">Univents</div>
        <nav><a href="index.php">HOME</a><a href="#">EVENTS</a><a href="#">RSVPs</a><a href="#">ABOUT</a></nav>
    </header>
    <div class="auth-container">
        <div class="left-panel" style="background: var(--teal-bg);">
            <div class="circle" style="width:500px; height:500px; background:#4B8074; top:-50px; left:-50px;"></div>
            <h1>WELCOME <br> BACK TO <br> UNIVENTS</h1>
            <p>Your campus event hub is waiting. Log in to check your upcoming RSVPs and discover what's happening today.</p>
        </div>
        <div class="right-panel">
            <div class="form-box">
                <h2>Log In</h2>
                <p>Don't have an account? <a href="register.php">Sign up free →</a></p>
                <?php if($error) echo "<p style='color:red'>$error</p>"; ?>
                <form method="POST">
                    <div class="input-group">
                        <label>Institutional Email</label>
                        <input type="email" name="email" placeholder="juan.delacruz@cit.edu" required>
                    </div>
                    <div class="input-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="*************" required>
                    </div>
                    <p style="text-align:right; font-size:0.8rem;"><a href="#">Forgot Password?</a></p>
                    <button type="submit" name="login" class="btn-auth">Log In →</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>