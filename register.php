<?php
include 'db.php';
session_start();

if (isset($_POST['register'])) {
    $email = $_POST['email'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    try {
        $conn->beginTransaction();
        $stmt1 = $conn->prepare("INSERT INTO \"user\"(institutional_email, password, user_status) VALUES (?, ?, 'Active')");
        $stmt1->execute([$email, $pass]);
        $new_id = $conn->lastInsertId();

        if ($role == 'student') {
            $stmt2 = $conn->prepare("INSERT INTO student (user_id, name, department, year_level, program) VALUES (?, ?, ?, ?, ?)");
            $stmt2->execute([$new_id, $_POST['fname']." ".$_POST['lname'], $_POST['dept'], $_POST['year_level'], $_POST['program']]);
        } else {
            $stmt2 = $conn->prepare("INSERT INTO organization (user_id, org_name, verification_status) VALUES (?, ?, 'Pending')");
            $stmt2->execute([$new_id, $_POST['org_name']]);
        }
        $conn->commit();
        header("Location: login.php");
        exit();
    } catch (Exception $e) { 
        if ($conn->inTransaction()) $conn->rollBack(); 
        die("Error: " . $e->getMessage()); 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Account - Univents</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@900&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-beige: #F2EDE4;
            --panel-dark: #262626;
            --input-bg: #F2EDE4;
            --orange-btn: #E68A6E;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
        body { background: var(--bg-beige); height: 100vh; overflow: hidden; }

        header {
            height: 80px;
            padding: 0 80px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--bg-beige);
        }
        .logo-nav { font-family: 'Montserrat'; font-weight: 900; font-size: 2rem; color: #444; }
        nav a { text-decoration: none; color: #666; margin-left: 40px; font-weight: 600; font-size: 1rem; }

        .auth-container { 
            display: flex; 
            height: calc(100vh - 80px); 
        }

        .left-panel {
            flex: 1;
            background: var(--panel-dark);
            position: relative;
            padding: 80px;
            color: white;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .left-panel h1 { font-family: 'Montserrat'; font-size: 5.5rem; line-height: 0.9; z-index: 2; margin-bottom: 20px; }
        .left-panel p { font-size: 1.1rem; max-width: 400px; z-index: 2; opacity: 0.9; }

        .circle { position: absolute; border-radius: 50%; z-index: 1; opacity: 0.6; }
        .c1 { width: 600px; height: 600px; background: #34495E; top: 50%; left: 50%; transform: translate(-40%, -40%); }
        .c2 { width: 200px; height: 200px; background: #4B8074; top: 100px; left: -50px; }
        .c3 { width: 150px; height: 150px; background: #9B6B6B; bottom: 80px; right: 50px; }

        .right-panel {
            flex: 1;
            background: white;
            padding: 0 100px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start; 
            padding-top: 50px; /* Reduced from 100px to sit higher */
            overflow-y: auto;
        }

        .form-box { 
            max-width: 550px; 
            width: 100%; 
            margin: 0 auto; 
            min-height: 620px; /* Maintains the fixed vertical spacing */
        }

        .form-box h2 { font-family: 'Montserrat'; font-size: 3rem; margin-bottom: 5px; }
        .form-box .login-link { color: #888; margin-bottom: 25px; }
        .form-box .login-link a { color: #E67E22; font-weight: bold; text-decoration: none; }

        .role-toggle {
            display: flex;
            background: var(--input-bg);
            border-radius: 15px;
            padding: 5px;
            margin-bottom: 25px;
        }
        .role-btn {
            flex: 1;
            padding: 14px;
            text-align: center;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: 0.2s;
        }
        .role-btn.active {
            background: white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
            border-radius: 12px;
            color: #333;
        }

        .input-row { display: flex; gap: 20px; }
        .input-group { display: flex; flex-direction: column; margin-bottom: 20px; width: 100%; }
        .input-group label { font-weight: bold; margin-bottom: 8px; color: #555; font-size: 0.9rem; }
        
        input, select {
            width: 100%;
            padding: 16px;
            border: none;
            background: var(--input-bg);
            border-radius: 15px;
            font-size: 1rem;
            outline: none;
        }

        .btn-container { display: flex; justify-content: flex-end; margin-top: 10px; }
        .btn-auth {
            background: var(--orange-btn);
            color: white;
            border: none;
            padding: 16px 35px;
            border-radius: 15px;
            font-size: 1.3rem;
            font-weight: 900;
            cursor: pointer;
        }

        .footer-disclaimer {
            text-align: center;
            font-size: 0.75rem;
            color: #999;
            margin-top: 30px;
        }
    </style>
</head>
<body>

    <header>
        <div class="logo-nav">Univents</div>
        <nav>
            <a href="index.php">HOME</a>
            <a href="events.php">EVENTS</a>
            <a href="rsvps.php">RSVPs</a>
            <a href="about.php">ABOUT</a>
        </nav>
    </header>

    <div class="auth-container">
        <div class="left-panel">
            <div class="circle c1"></div>
            <div class="circle c2"></div>
            <div class="circle c3"></div>
            <h1>BE PART <br> OF EVERY <br> MOMENT.</h1>
            <p>Create your account and become part of the campus community. Never miss an event again.</p>
        </div>

        <div class="right-panel">
            <div class="form-box">
                <h2>Create an account</h2>
                <p class="login-link">Already have an account? <a href="login.php">Log in →</a></p>
                
                <form method="POST">
                    <input type="hidden" name="role" id="role_input" value="student">
                    
                    <div class="role-toggle">
                        <div class="role-btn active" id="btn_student" onclick="setRole('student')">🎓 Student</div>
                        <div class="role-btn" id="btn_org" onclick="setRole('org')">🏛️ Organizer</div>
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label>First Name</label>
                            <input type="text" name="fname" placeholder="Juan" required>
                        </div>
                        <div class="input-group">
                            <label>Last Name</label>
                            <input type="text" name="lname" placeholder="Dela Cruz" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Institutional Email</label>
                        <input type="email" name="email" placeholder="juan.delacruz@cit.edu" required>
                    </div>

                    <div class="input-group">
                        <label id="dynamic_label">College</label>
                        <select name="dept" id="dept_select">
                            <option value="" disabled selected>Select College</option>
                            <option value="CCS">College of Computer Studies</option>
                            <option value="CEA">College of Engineering & Architecture</option>
                            <option value="CASE">College of Arts, Sciences & Education</option>
                        </select>
                        <input type="text" name="org_name" id="org_input" placeholder="E.g. Google Developers Group" style="display:none;">
                    </div>

                    <div class="input-row" id="student_row_2">
                        <div class="input-group">
                            <label>Year Level</label>
                            <select name="year_level">
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label>Program</label>
                            <input type="text" name="program" placeholder="BSCS">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="*************" required>
                    </div>

                    <div class="btn-container">
                        <button type="submit" name="register" class="btn-auth">Create Account →</button>
                    </div>
                </form>

                <p class="footer-disclaimer">
                    By signing up, you agree to UNIVENTS' <b>Terms of Service</b> and <b>Privacy Policy</b>.
                </p>
            </div>
        </div>
    </div>

    <script>
        function setRole(role) {
            document.getElementById('role_input').value = role;
            document.getElementById('btn_student').classList.toggle('active', role === 'student');
            document.getElementById('btn_org').classList.toggle('active', role === 'org');
            
            const label = document.getElementById('dynamic_label');
            const deptSelect = document.getElementById('dept_select');
            const orgInput = document.getElementById('org_input');
            const studentRow2 = document.getElementById('student_row_2');

            if (role === 'student') {
                label.innerText = "Department";
                deptSelect.style.display = "block";
                orgInput.style.display = "none";
                studentRow2.style.display = "flex";
                deptSelect.required = true;
                orgInput.required = false;
            } else {
                label.innerText = "Organization Name";
                deptSelect.style.display = "none";
                orgInput.style.display = "block";
                studentRow2.style.display = "none";
                deptSelect.required = false;
                orgInput.required = true;
            }
        }
    </script>
</body>
</html>