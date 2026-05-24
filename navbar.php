<?php
$current_page = basename($_SERVER['PHP_SELF']);
$is_logged_in = isset($_SESSION['user_id']);
$role = $_SESSION['role'] ?? null;

$dashboard_link = "login.php";
if ($is_logged_in) {
    $dashboard_link = ($role === 'student') ? "student_dashboard.php" : "org_dashboard.php";
}
?>
<header>
    <div class="container nav-wrapper">
        <div class="logo"><a href="index.php" style="text-decoration:none; color:inherit;">Univents</a></div>
        <nav>
            <ul>
                <li><a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>">HOME</a></li>
                <li><a href="events.php" class="<?= $current_page == 'events.php' ? 'active' : '' ?>">EVENTS</a></li>
                <li><a href="rsvps.php" class="<?= $current_page == 'rsvps.php' ? 'active' : '' ?>">RSVPs</a></li>
                <li><a href="about.php" class="<?= $current_page == 'about.php' ? 'active' : '' ?>">ABOUT</a></li>
            </ul>
        </nav>
        <div class="nav-buttons">
            <?php if($is_logged_in): ?>
                <a href="<?= $dashboard_link ?>" class="btn-primary">DASHBOARD</a>
                <a href="logout.php" class="btn-text" style="margin-left:10px;">LOGOUT</a>
            <?php else: ?>
                <a href="register.php" class="btn-text">SIGN-UP</a>
                <a href="login.php" class="btn-primary">LOG-IN</a>
            <?php endif; ?>
        </div>
    </div>
</header>