<?php
include 'db.php';
session_start();

$error = "";
if (isset($_POST['login'])) {
    // Note: Standardize table name to "user" or user (wrapped in quotes for PostgreSQL)
    $stmt = $conn->prepare("SELECT * FROM \"user\" WHERE institutional_email = ?");
    $stmt->execute([$_POST['email']]);
    $user = $stmt->fetch();

    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        
        // Check if Student
        $stmtS = $conn->prepare("SELECT user_id FROM student WHERE user_id = ?");
        $stmtS->execute([$user['user_id']]);
        if ($stmtS->fetch()) {
            $_SESSION['role'] = 'student';
            header("Location: student_dashboard.php");
        } else {
            $_SESSION['role'] = 'org';
            header("Location: org_dashboard.php");
        }
        exit();
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!-- Rest of your login HTML... ensure you use the auth-style.css -->
<!DOCTYPE html>
<html>
<head>
    <title>Log In - Univents</title>
    <link rel="stylesheet" href="auth-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@900&family=Inter:wght@400;600&display=swap" rel="stylesheet">

    <style>
        /* Sidebar Container - Stripped of the extra circle background */
        .left-panel {
            background-color: #326257 !important;
            position: relative;
            overflow: hidden; 
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px;
            color: white;
        }

        /* Base Bubble Style */
        .bubble {
            position: absolute;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(5px);
            z-index: 1;
        }

        /* Large Bubble - Positioned top left */
        .bubble-lg {
            width:500px; 
            height:500px;
            top:-50px; 
            left:-50px;
            background: rgb(40, 90, 72);
        }

        /* Medium Bubble - Positioned bottom right */
        .bubble-md {
            width: 180px;
            height: 180px;
            bottom: 10%;
            right: -20px;
            background: rgb(64, 138, 113);
        }

        /* Small Bubble - Floating near the middle */
        .bubble-sm {
            width: 70px;
            height: 70px;
            top: 40%;
            right: 15%;
            background:rgb(90, 150, 144)
        }

        /* Keep text on top */
        .left-panel h1, .left-panel p {
            position: relative;
            z-index: 10;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">Univents</div>
        <nav>
            <a href="index.php">HOME</a>
            <a href="events.php">EVENTS</a>
            <a href="rsvps.php">RSVPs</a>
            <a href="about.php">ABOUT</a>
        </nav>
    </header>
    <div class="auth-container">
        <div class="login-circle-lg"></div>
        <div class="login-circle-sm"></div>
        
        <div class="left-panel" style="background: var(--teal-bg);">
            <div class="bubble bubble-lg"></div>
            <div class="bubble bubble-md"></div>
            <div class="bubble bubble-sm"></div>


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