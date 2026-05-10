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

<!DOCTYPE html>
<html>
<head>
    <title>Log In - Univents</title>
    <link rel="stylesheet" href="auth-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@900&family=Inter:wght@400;600&display=swap" rel="stylesheet">

    <style>
        /* ── Left Panel Bubbles ── */
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
        .bubble {
            position: absolute;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.15);
            backdrop-filter: blur(5px);
            z-index: 1;
        }
        .bubble-lg { width:500px; height:500px; top:-50px; left:-50px; background:rgb(40,90,72); }
        .bubble-md { width:180px; height:180px; bottom:10%; right:-20px; background:rgb(64,138,113); }
        .bubble-sm { width:70px; height:70px; top:40%; right:15%; background:rgb(90,150,144); }
        .left-panel h1, .left-panel p { position:relative; z-index:10; }

        /* ── Modal Overlay ── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }

        /* ── Modal Card ── */
        .modal-card {
            background: #fff;
            border-radius: 20px;
            padding: 48px 44px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.18);
            position: relative;
            animation: modalIn 0.28s cubic-bezier(.34,1.36,.64,1) both;
        }
        @keyframes modalIn {
            from { opacity:0; transform: translateY(24px) scale(0.97); }
            to   { opacity:1; transform: translateY(0) scale(1); }
        }

        .modal-close {
            position: absolute;
            top: 18px; right: 22px;
            background: none; border: none;
            font-size: 1.5rem; color: #aaa;
            cursor: pointer; line-height: 1;
            transition: color .15s;
        }
        .modal-close:hover { color: #333; }

        /* Modal step display */
        .modal-step { display: none; }
        .modal-step.active { display: block; }

        .modal-card h3 {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.7rem;
            margin-bottom: 6px;
            color: #222;
        }
        .modal-card .modal-sub {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 28px;
            line-height: 1.5;
        }

        .modal-input-group { margin-bottom: 16px; }
        .modal-input-group label {
            display: block;
            font-weight: 600;
            font-size: 0.82rem;
            color: #555;
            margin-bottom: 7px;
        }
        .modal-input-group input {
            width: 100%;
            padding: 14px 16px;
            border: 1.5px solid #E8E3DB;
            background: #F2EDE4;
            border-radius: 10px;
            font-size: 0.97rem;
            outline: none;
            transition: border-color .2s;
        }
        .modal-input-group input:focus { border-color: #326257; background: #fff; }

        /* Password strength bar */
        .strength-bar-wrap {
            height: 5px;
            background: #eee;
            border-radius: 99px;
            margin-top: 8px;
            overflow: hidden;
        }
        .strength-bar {
            height: 100%;
            border-radius: 99px;
            width: 0%;
            transition: width .3s, background .3s;
        }
        .strength-label { font-size: 0.75rem; color: #999; margin-top: 4px; }

        .btn-modal-primary {
            width: 100%;
            background: #E68A6E;
            color: white;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 8px;
            transition: background .2s, transform .1s;
        }
        .btn-modal-primary:hover { background: #d4775a; }
        .btn-modal-primary:active { transform: scale(0.98); }

        .modal-error {
            background: #fff0ed;
            border: 1px solid #f5c4b8;
            color: #c0392b;
            border-radius: 9px;
            padding: 11px 14px;
            font-size: 0.85rem;
            margin-bottom: 16px;
            display: none;
        }
        .modal-error.show { display: block; }

        .modal-success-icon {
            font-size: 3rem;
            text-align: center;
            margin-bottom: 14px;
        }

        /* Password toggle eye */
        .pw-wrap { position: relative; }
        .pw-wrap input { padding-right: 44px; }
        .pw-toggle {
            position: absolute;
            right: 14px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            cursor: pointer; padding: 0;
            display: flex; align-items: center;
        }
        .pw-toggle svg { width: 18px; height: 18px; stroke: #bbb; fill: none; transition: stroke .15s; }
        .pw-toggle:hover svg { stroke: #326257; }

        /* Back link */
        .modal-back {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.82rem;
            color: #888;
            cursor: pointer;
            background: none;
            border: none;
            margin-bottom: 18px;
            padding: 0;
        }
        .modal-back:hover { color: #326257; }
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
        <div class="left-panel">
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
                    <p style="text-align:right; font-size:0.8rem;">
                        <a href="#" id="forgotPasswordLink">Forgot Password?</a>
                    </p>
                    <button type="submit" name="login" class="btn-auth">Log In →</button>
                </form>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════
         FORGOT PASSWORD MODAL
    ══════════════════════════════════════════ -->
    <div class="modal-overlay" id="forgotModal">
        <div class="modal-card">
            <button class="modal-close" id="modalClose" aria-label="Close">✕</button>

            <!-- Step 1: Enter email -->
            <div class="modal-step active" id="step1">
                <div class="modal-success-icon">🔑</div>
                <h3>Reset Password</h3>
                <p class="modal-sub">Enter the institutional email linked to your Univents account and we'll verify it.</p>
                <div class="modal-error" id="step1Error"></div>
                <div class="modal-input-group">
                    <label>Institutional Email</label>
                    <input type="email" id="resetEmail" placeholder="juan.delacruz@cit.edu">
                </div>
                <button class="btn-modal-primary" id="verifyEmailBtn">Verify Email →</button>
            </div>

            <!-- Step 2: New password -->
            <div class="modal-step" id="step2">
                <button class="modal-back" id="backToStep1">← Back</button>
                <div class="modal-success-icon">🔒</div>
                <h3>New Password</h3>
                <p class="modal-sub" id="step2Sub">Create a strong new password for your account.</p>
                <div class="modal-error" id="step2Error"></div>
                <div class="modal-input-group">
                    <label>New Password</label>
                    <div class="pw-wrap">
                        <input type="password" id="newPassword" placeholder="Min. 8 characters">
                        <button type="button" class="pw-toggle" data-target="newPassword" aria-label="Show password">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    <div class="strength-bar-wrap"><div class="strength-bar" id="strengthBar"></div></div>
                    <div class="strength-label" id="strengthLabel"></div>
                </div>
                <div class="modal-input-group">
                    <label>Confirm Password</label>
                    <div class="pw-wrap">
                        <input type="password" id="confirmPassword" placeholder="Re-enter password">
                        <button type="button" class="pw-toggle" data-target="confirmPassword" aria-label="Show password">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <button class="btn-modal-primary" id="resetPasswordBtn">Update Password →</button>
            </div>

            <!-- Step 3: Success -->
            <div class="modal-step" id="step3">
                <div class="modal-success-icon" style="font-size:3.5rem;">✅</div>
                <h3 style="text-align:center;">Password Updated!</h3>
                <p class="modal-sub" style="text-align:center; margin-top:8px;">
                    Your password has been changed successfully. You can now log in with your new password.
                </p>
                <button class="btn-modal-primary" id="doneBtn" style="margin-top:16px;">Back to Log In</button>
            </div>
        </div>
    </div>

    <script>
    (() => {
        const modal      = document.getElementById('forgotModal');
        const closeBtn   = document.getElementById('modalClose');
        const forgotLink = document.getElementById('forgotPasswordLink');

        // Stores the uid returned from step 1, sent back in step 2
        let pendingUid = null;

        // ── Open / Close ──────────────────────────
        forgotLink.addEventListener('click', e => { e.preventDefault(); openModal(); });
        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

        function openModal() {
            pendingUid = null;
            goToStep(1);
            modal.classList.add('active');
            document.getElementById('resetEmail').focus();
        }
        function closeModal() {
            modal.classList.remove('active');
        }

        // ── Step Navigation ───────────────────────
        function goToStep(n) {
            document.querySelectorAll('.modal-step').forEach(s => s.classList.remove('active'));
            document.getElementById('step' + n).classList.add('active');
            clearErrors();
        }

        document.getElementById('backToStep1').addEventListener('click', () => {
            pendingUid = null;
            goToStep(1);
        });
        document.getElementById('doneBtn').addEventListener('click', closeModal);

        // ── Helper: show error ────────────────────
        function showError(id, msg) {
            const el = document.getElementById(id);
            el.textContent = msg;
            el.classList.add('show');
        }
        function clearErrors() {
            document.querySelectorAll('.modal-error').forEach(e => e.classList.remove('show'));
        }

        // ── Step 1: Verify Email ──────────────────
        document.getElementById('verifyEmailBtn').addEventListener('click', async () => {
            clearErrors();
            const email = document.getElementById('resetEmail').value.trim();
            if (!email) { showError('step1Error', 'Please enter your email.'); return; }

            const btn = document.getElementById('verifyEmailBtn');
            btn.textContent = 'Checking…'; btn.disabled = true;

            try {
                const fd = new FormData();
                fd.append('action', 'verify_email');
                fd.append('email', email);

                const res  = await fetch('forgot_password.php', { method:'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    pendingUid = data.uid; // store uid for step 2
                    document.getElementById('step2Sub').textContent =
                        `Hi, ${data.name}! Create a strong new password for your account.`;
                    goToStep(2);
                    document.getElementById('newPassword').focus();
                } else {
                    showError('step1Error', data.message);
                }
            } catch (err) {
                showError('step1Error', 'Something went wrong. Please try again.');
            }

            btn.textContent = 'Verify Email →'; btn.disabled = false;
        });

        // ── Password Strength ─────────────────────
        document.getElementById('newPassword').addEventListener('input', function() {
            const val = this.value;
            let score = 0;
            if (val.length >= 8)           score++;
            if (/[A-Z]/.test(val))         score++;
            if (/[0-9]/.test(val))         score++;
            if (/[^A-Za-z0-9]/.test(val))  score++;

            const bar    = document.getElementById('strengthBar');
            const label  = document.getElementById('strengthLabel');
            const colors = ['#e74c3c','#e67e22','#f1c40f','#2ecc71'];
            const labels = ['Weak','Fair','Good','Strong'];

            bar.style.width      = (score * 25) + '%';
            bar.style.background = colors[score - 1] || '#eee';
            label.textContent    = score > 0 ? labels[score - 1] : '';
        });

        // ── Password Toggle ───────────────────────
        const EYE_OPEN = `<svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
        const EYE_SHUT = `<svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`;

        document.querySelectorAll('.pw-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = document.getElementById(btn.dataset.target);
                const showing = input.type === 'text';
                input.type = showing ? 'password' : 'text';
                btn.innerHTML = showing ? EYE_OPEN : EYE_SHUT;
                btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            });
        });

        // ── Step 2: Reset Password ────────────────
        document.getElementById('resetPasswordBtn').addEventListener('click', async () => {
            clearErrors();
            const newPw  = document.getElementById('newPassword').value;
            const confPw = document.getElementById('confirmPassword').value;

            if (newPw.length < 8) {
                showError('step2Error', 'Password must be at least 8 characters.'); return;
            }
            if (newPw !== confPw) {
                showError('step2Error', 'Passwords do not match.'); return;
            }
            if (!pendingUid) {
                showError('step2Error', 'Session lost. Please go back and verify your email again.'); return;
            }

            const btn = document.getElementById('resetPasswordBtn');
            btn.textContent = 'Updating…'; btn.disabled = true;

            try {
                const fd = new FormData();
                fd.append('action',           'reset_password');
                fd.append('uid',              pendingUid); // send uid instead of relying on session
                fd.append('new_password',     newPw);
                fd.append('confirm_password', confPw);

                const res  = await fetch('forgot_password.php', { method:'POST', body: fd });
                const data = await res.json();

                if (data.success) {
                    pendingUid = null;
                    goToStep(3);
                } else {
                    showError('step2Error', data.message);
                }
            } catch (err) {
                showError('step2Error', 'Something went wrong. Please try again.');
            }

            btn.textContent = 'Update Password →'; btn.disabled = false;
        });

        // ── Enter key shortcuts ───────────────────
        document.getElementById('resetEmail').addEventListener('keydown', e => {
            if (e.key === 'Enter') document.getElementById('verifyEmailBtn').click();
        });
        document.getElementById('confirmPassword').addEventListener('keydown', e => {
            if (e.key === 'Enter') document.getElementById('resetPasswordBtn').click();
        });
    })();
    </script>
</body>
</html>