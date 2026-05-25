<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/models/account.php';
require_once __DIR__ . '/controllers/account.php';
require_once __DIR__ . '/public/database.config.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: /index.php");
    exit();
}

if (isset($_SESSION['user_id'])) {
    header("Location: /views/dashboard/index.php");
    exit();
}

$errors  = "";
$message = "";
$mode    = $_POST['mode'] ?? $_GET['mode'] ?? 'login';

$controller = new AccountController($SERVER_NAME, $USERNAME, $PASSWORD, $DB_NAME);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($mode === 'register') {
        $confirm   = $_POST["confirm"]    ?? "";
        $email     = trim($_POST["email"] ?? "");
        $full_name = trim($_POST["full_name"] ?? "");

        // ── Validation ──────────────────────────────────────────────────
        if (empty($username) || empty($password) || empty($email)) {
            $errors = "Username, email, and password are required.";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors = "Please enter a valid email address.";

        } elseif (strlen($password) < 6) {
            $errors = "Password must be at least 6 characters.";

        } elseif ($password !== $confirm) {
            $errors = "Passwords do not match.";

        } else {
            $result = $controller->register($username, $password, $email, $full_name);

            if ($result === true) {
                $message = "Account created! You can now log in.";
                $mode    = 'login';
            } elseif ($result === 'username_taken') {
                $errors = "Username already taken. Try another.";
            } elseif ($result === 'email_taken') {
                $errors = "That email is already registered. Try logging in.";
            } else {
                $errors = "Something went wrong. Please try again.";
            }
        }

    } else {
        // ── Login: accept username OR email ─────────────────────────────
        $identifier = trim($_POST["username"] ?? ""); // field kept as "username" for compat
        $result = $controller->login($identifier, $password);

        if ($result) {
            header("Location: /views/dashboard/index.php");
            exit();
        } else {
            $errors = "Invalid username/email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>We Are The Oceans — Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Poppins', sans-serif;
    min-height: 100vh;
    background: linear-gradient(160deg, #060f1e 0%, #0a2744 35%, #0a4a7a 70%, #006994 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
}

/* ── BUBBLES ── */
.bubbles { position: fixed; bottom: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 0; overflow: hidden; }
.bubble {
    position: absolute; bottom: -60px; border-radius: 50%;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.12);
    animation: rise linear infinite;
}
.bubble:nth-child(1)  { width:16px;height:16px;left:7%;  animation-duration:8s;   animation-delay:0s;   }
.bubble:nth-child(2)  { width:26px;height:26px;left:18%; animation-duration:11s;  animation-delay:1.5s; }
.bubble:nth-child(3)  { width:10px;height:10px;left:30%; animation-duration:7s;   animation-delay:3s;   }
.bubble:nth-child(4)  { width:20px;height:20px;left:42%; animation-duration:9s;   animation-delay:0.8s; }
.bubble:nth-child(5)  { width:32px;height:32px;left:55%; animation-duration:13s;  animation-delay:2s;   }
.bubble:nth-child(6)  { width:14px;height:14px;left:65%; animation-duration:8.5s; animation-delay:4s;   }
.bubble:nth-child(7)  { width:22px;height:22px;left:75%; animation-duration:10s;  animation-delay:1s;   }
.bubble:nth-child(8)  { width:12px;height:12px;left:85%; animation-duration:7.5s; animation-delay:2.5s; }
.bubble:nth-child(9)  { width:28px;height:28px;left:92%; animation-duration:12s;  animation-delay:0.5s; }
.bubble:nth-child(10) { width:18px;height:18px;left:50%; animation-duration:9.5s; animation-delay:3.5s; }
@keyframes rise {
    0%   { transform: translateY(0) translateX(0) scale(1);    opacity: 0;   }
    10%  { opacity: 1; }
    90%  { opacity: 0.6; }
    100% { transform: translateY(-110vh) translateX(25px) scale(1.1); opacity: 0; }
}

/* ── WAVE ── */
.wave-wrap { position: fixed; bottom: 0; left: 0; width: 100%; z-index: 0; line-height: 0; }
.wave-wrap svg { display: block; width: 100%; }
.wave1 { animation: waveMove 8s linear infinite; }
.wave2 { animation: waveMove 12s linear infinite reverse; opacity: 0.4; }
@keyframes waveMove {
    0%   { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}

/* ── CARD ── */
.auth-wrap {
    position: relative; z-index: 10;
    display: flex; flex-direction: column; align-items: center;
    animation: slideUp 0.65s cubic-bezier(.22,1,.36,1) both;
    width: 100%; max-width: 420px;
    padding: 0 1rem;
}
@keyframes slideUp {
    from { opacity: 0; transform: translateY(36px); }
    to   { opacity: 1; transform: translateY(0); }
}
.logo-float {
    width: 82px; height: 82px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0d6e8a, #0a3d5c);
    border: 3px solid rgba(0,188,212,0.5);
    display: flex; align-items: center; justify-content: center;
    font-size: 2.6rem;
    margin-bottom: -41px;
    position: relative; z-index: 2;
    box-shadow: 0 0 0 6px rgba(0,188,212,0.12), 0 0 28px rgba(0,188,212,0.25);
    animation: pulse 3s ease-in-out infinite;
}
@keyframes pulse {
    0%,100% { box-shadow: 0 0 0 6px rgba(0,188,212,0.12), 0 0 28px rgba(0,188,212,0.25); }
    50%      { box-shadow: 0 0 0 12px rgba(0,188,212,0.06), 0 0 48px rgba(0,188,212,0.4); }
}
.auth-card {
    background: rgba(8, 22, 42, 0.72);
    backdrop-filter: blur(22px);
    -webkit-backdrop-filter: blur(22px);
    border: 1px solid rgba(0,188,212,0.2);
    border-radius: 22px;
    padding: 3.2rem 2rem 2rem;
    width: 100%;
    box-shadow: 0 24px 60px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.06);
}
h1.auth-title {
    font-family: 'Montserrat', sans-serif;
    font-size: 1.8rem; font-weight: 900; color: #fff;
    text-align: center; letter-spacing: 1.5px; margin-bottom: 0.2rem;
}
p.auth-sub {
    text-align: center; color: rgba(255,255,255,0.45);
    font-size: 0.83rem; margin-bottom: 1.5rem;
}

/* Alerts */
.alert { padding: 0.7rem 1rem; border-radius: 9px; margin-bottom: 1rem; font-size: 0.82rem; font-weight: 500; }
.alert-success { background: rgba(67,160,71,0.18);  color: #a8f0ac; border: 1px solid rgba(67,160,71,0.28); }
.alert-danger  { background: rgba(229,57,53,0.18);  color: #ffb3b3; border: 1px solid rgba(229,57,53,0.28); }

/* Input groups */
.input-wrap { position: relative; margin-bottom: 0.9rem; }
.input-icon {
    position: absolute; left: 13px; top: 50%;
    transform: translateY(-50%);
    font-size: 1rem; opacity: 0.45;
    pointer-events: none; transition: opacity 0.2s;
}
.input-wrap:focus-within .input-icon { opacity: 1; }
.auth-input {
    width: 100%;
    padding: 0.7rem 1rem 0.7rem 2.7rem;
    background: rgba(255,255,255,0.07);
    border: 1.5px solid rgba(255,255,255,0.12);
    border-radius: 11px; color: #fff;
    font-size: 0.9rem; font-family: 'Poppins', sans-serif;
    outline: none;
    transition: border-color 0.22s, background 0.22s, box-shadow 0.22s;
}
.auth-input::placeholder { color: rgba(255,255,255,0.3); }
.auth-input:focus {
    border-color: #00bcd4;
    background: rgba(0,188,212,0.07);
    box-shadow: 0 4px 18px rgba(0,188,212,0.18);
}

/* Password hint */
.pw-hint { font-size: 0.73rem; color: rgba(255,255,255,0.3); margin-top: -0.5rem; margin-bottom: 0.9rem; padding-left: 0.3rem; }

/* Options row */
.options-row {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 1.3rem; font-size: 0.8rem;
}
.remember { display: flex; align-items: center; gap: 0.4rem; color: rgba(255,255,255,0.5); cursor: pointer; }
.remember input[type=checkbox] { accent-color: #00bcd4; width: 14px; height: 14px; }
.forgot { color: #00bcd4; text-decoration: none; font-weight: 600; }
.forgot:hover { text-decoration: underline; }

/* Submit button */
.btn-login {
    width: 100%; padding: 0.8rem;
    background: linear-gradient(90deg, #2e7d32, #00acc1);
    background-size: 200% 100%; background-position: 0% 50%;
    border: none; border-radius: 11px; color: #fff;
    font-size: 0.95rem; font-weight: 700;
    font-family: 'Poppins', sans-serif; cursor: pointer;
    transition: background-position 0.4s, transform 0.2s, box-shadow 0.3s;
}
.btn-login:hover {
    background-position: 100% 50%;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,188,212,0.4);
}

/* Switch */
.auth-switch { text-align: center; margin-top: 1.2rem; color: rgba(255,255,255,0.4); font-size: 0.82rem; }
.auth-switch a { color: #00bcd4; font-weight: 700; text-decoration: none; }
.auth-switch a:hover { text-decoration: underline; }

@media (max-width: 480px) {
    .auth-card { padding: 2.8rem 1.3rem 1.7rem; }
}
</style>
</head>
<body>

<!-- Bubbles -->
<div class="bubbles">
    <div class="bubble"></div><div class="bubble"></div><div class="bubble"></div>
    <div class="bubble"></div><div class="bubble"></div><div class="bubble"></div>
    <div class="bubble"></div><div class="bubble"></div><div class="bubble"></div>
    <div class="bubble"></div>
</div>

<!-- Wave -->
<div class="wave-wrap">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 80" preserveAspectRatio="none" style="height:80px;">
        <path class="wave1" fill="rgba(0,188,212,0.12)"
              d="M0,40 C180,80 360,0 540,40 C720,80 900,0 1080,40 C1260,80 1440,0 1440,40
                 C1440,0 1260,80 1080,40 C900,0 720,80 540,40 C360,0 180,80 0,40 Z"/>
        <path class="wave2" fill="rgba(8,22,42,0.5)"
              d="M0,50 C200,10 400,70 600,50 C800,30 1000,70 1200,50 C1350,35 1440,55 1440,50
                 L1440,80 L0,80 Z"/>
    </svg>
</div>

<!-- Card -->
<div class="auth-wrap">
    <div class="logo-float">🌊</div>

    <div class="auth-card">

        <?php if ($mode === 'register'): ?>
            <h1 class="auth-title">JOIN THE CREW</h1>
            <p class="auth-sub">Create your OceanStock account</p>
        <?php else: ?>
            <h1 class="auth-title">WELCOME BACK!</h1>
            <p class="auth-sub">Login to manage your ocean's inventory</p>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errors) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="mode" value="<?= $mode ?>">

            <?php if ($mode === 'register'): ?>
                <!-- Full Name (optional) -->
                <div class="input-wrap">
                    <span class="input-icon">🪪</span>
                    <input class="auth-input" type="text" name="full_name"
                           placeholder="Full Name (optional)"
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                </div>
            <?php endif; ?>

            <!-- Username -->
            <div class="input-wrap">
                <span class="input-icon">👤</span>
                <input class="auth-input" type="text" name="username"
                       placeholder="<?= $mode === 'register' ? 'Username' : 'Username or Email' ?>"
                       required
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>

            <?php if ($mode === 'register'): ?>
                <!-- Email — register only -->
                <div class="input-wrap">
                    <span class="input-icon">✉️</span>
                    <input class="auth-input" type="email" name="email"
                           placeholder="Email address" required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
            <?php endif; ?>

            <!-- Password -->
            <div class="input-wrap">
                <span class="input-icon">🔒</span>
                <input class="auth-input" type="password" name="password"
                       placeholder="Password"
                       minlength="6"
                       required>
            </div>
            <?php if ($mode === 'register'): ?>
                <p class="pw-hint">Minimum 6 characters</p>
            <?php endif; ?>

            <?php if ($mode === 'register'): ?>
                <!-- Confirm Password -->
                <div class="input-wrap">
                    <span class="input-icon">🔒</span>
                    <input class="auth-input" type="password" name="confirm"
                           placeholder="Confirm password"
                           minlength="6"
                           required>
                </div>
            <?php endif; ?>

            <?php if ($mode === 'login'): ?>
                <div class="options-row">
                    <label class="remember">
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                    <a href="#" class="forgot">Forgot password?</a>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn-login">
                <?= $mode === 'register' ? '🌊 Create Account' : '🔑 Login' ?>
            </button>
        </form>

        <?php if ($mode === 'register'): ?>
            <p class="auth-switch">Already have an account? <a href="?mode=login">Sign in</a></p>
        <?php else: ?>
            <p class="auth-switch">No account yet? <a href="?mode=register">Sign up</a></p>
        <?php endif; ?>

    </div><!-- /.auth-card -->
</div><!-- /.auth-wrap -->

</body>
</html>