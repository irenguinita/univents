<?php
include 'db.php';
if (isset($_POST['register'])) {
    $email = $_POST['email'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    try {
        $conn->beginTransaction();
        $stmt1 = $conn->prepare("INSERT INTO \"user\" (institutional_email, password, user_status) VALUES (?, ?, 'Active')");
        $stmt1->execute([$email, $pass]);
        $new_id = $conn->lastInsertId();

        if ($role == 'student') {
            $stmt2 = $conn->prepare("INSERT INTO student (user_id, name, department) VALUES (?, ?, ?)");
            $stmt2->execute([$new_id, $_POST['fname']." ".$_POST['lname'], $_POST['dept']]);
        } else {
            $stmt2 = $conn->prepare("INSERT INTO organization (user_id, org_name, verification_status) VALUES (?, ?, 'Pending')");
            $stmt2->execute([$new_id, $_POST['org_name']]);
        }
        $conn->commit();
        header("Location: login.php");
    } catch (Exception $e) { $conn->rollBack(); echo "Error: " . $e->getMessage(); }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Account - Univents</title>
    <link rel="stylesheet" href="auth-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@900&family=Inter:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="logo">Univents</div>
        <nav><a href="index.php">HOME</a><a href="#">EVENTS</a><a href="#">RSVPs</a><a href="#">ABOUT</a></nav>
    </header>
    <div class="auth-container">
        <div class="left-panel" style="background: var(--charcoal-bg);">
            <div class="circle" style="width:450px; height:450px; background:#34495E; bottom:50px; left:-100px;"></div>
            <h1>BE PART <br> OF EVERY <br> MOMENT.</h1>
            <p>Create your account and become part of the campus community. Never miss an event again.</p>
        </div>
        <!-- Replace the form part of your register.php with this -->
<div class="right-panel">
    <div class="form-box">
        <h2>Create an account</h2>
        <p>Already have an account? <a href="login.php">Log in →</a></p>
        
        <form method="POST">
            <!-- Hidden input to store the role -->
            <input type="hidden" name="role" id="role_input" value="student">
            
            <!-- Role Toggle UI -->
            <div class="role-toggle">
                <div class="role-btn active" id="btn_student" onclick="setRole('student')">🎓 Student</div>
                <div class="role-btn" id="btn_org" onclick="setRole('org')">🏛️ Organizer</div>
            </div>

            <!-- Common Fields: Name and Email -->
            <div style="display:flex; gap:10px;">
                <div class="input-group" style="flex:1;">
                    <label>First Name</label><input type="text" name="fname" placeholder="Juan" required>
                </div>
                <div class="input-group" style="flex:1;">
                    <label>Last Name</label><input type="text" name="lname" placeholder="Dela Cruz" required>
                </div>
            </div>

            <div class="input-group">
                <label>Institutional Email</label>
                <input type="email" name="email" placeholder="juan.delacruz@cit.edu" required>
            </div>

            <!-- STUDENT ONLY FIELDS -->
            <div id="student_only_fields">
                <div class="input-group">
                    <label>Department</label>
                    <select name="dept">
                        <option value="">Select Department</option>
                        <option value="CCS">College of Computer Studies</option>
                        <option value="CEA">College of Engineering & Architecture</option>
                        <option value="CASE">College of Arts, Sciences & Education</option>
                    </select>
                </div>
                <div style="display:flex; gap:10px;">
                    <div class="input-group" style="flex:1;">
                        <label>Year Level</label>
                        <select name="year_level">
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>
                    <div class="input-group" style="flex:1;">
                        <label>Program</label>
                        <input type="text" name="program" placeholder="BSCS">
                    </div>
                </div>
            </div>

            <!-- ORGANIZER ONLY FIELDS -->
            <div id="org_only_fields" style="display:none;">
                <div class="input-group">
                    <label>Organization Name</label>
                    <input type="text" name="org_name" placeholder="E.g. Google Developers Group">
                </div>
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="*************" required>
            </div>

            <button type="submit" name="register" class="btn-auth">Create Account →</button>
        </form>
    </div>
</div>
<script>
    function setRole(role) {
        document.getElementById('role_input').value = role;
        document.getElementById('btn_student').classList.toggle('active', role === 'student');
        document.getElementById('btn_org').classList.toggle('active', role === 'org');
        
        // Toggle visibility of role-specific fields
        document.getElementById('student_only_fields').style.display = (role === 'student') ? 'block' : 'none';
        document.getElementById('org_only_fields').style.display = (role === 'org') ? 'block' : 'none';
    }
</script>

</body>
</html>