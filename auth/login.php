<?php
require_once __DIR__ . '/../includes/config.php';

session_name(SESSION_NAME);
if (session_status() === PHP_SESSION_NONE) session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ' . APP_URL . '/index.php');
    exit();
}

$error = '';
$timeout = isset($_GET['timeout']) && $_GET['timeout'] == 1;

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/functions.php';

    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter email and password.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['last_activity'] = time();

            // Update last login
            $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

            header('Location: ' . APP_URL . '/index.php');
            exit();
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/custom.css">
    <link rel="manifest" href="<?= APP_URL ?>/manifest.json">
    <meta name="theme-color" content="#1e3a5f">
    <style>
        body { background: none; }
        .floating-dots { position:absolute; inset:0; overflow:hidden; pointer-events:none; }
        .dot {
            position:absolute; border-radius:50%;
            background: rgba(246,173,27,0.12);
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%,100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-20px) scale(1.05); }
        }
    </style>
</head>
<body>
<div class="login-page">
    <div class="floating-dots">
        <div class="dot" style="width:200px;height:200px;left:10%;top:10%;animation-delay:0s"></div>
        <div class="dot" style="width:120px;height:120px;right:15%;top:20%;animation-delay:1s"></div>
        <div class="dot" style="width:80px;height:80px;left:20%;bottom:20%;animation-delay:2s"></div>
        <div class="dot" style="width:160px;height:160px;right:10%;bottom:10%;animation-delay:0.5s"></div>
    </div>

    <div class="login-card">
        <!-- Logo & Title -->
        <div class="text-center mb-4">
            <img src="<?= APP_URL ?>/assets/images/logo.png" alt="Logo" class="login-logo mb-3">
            <h1 class="login-title"><?= APP_NAME ?></h1>
            <p class="login-subtitle"><?= APP_TAGLINE ?></p>
        </div>

        <!-- Timeout Alert -->
        <?php if ($timeout): ?>
        <div class="alert" style="background:rgba(231,76,60,0.15);border:1px solid rgba(231,76,60,0.3);color:#ff6b6b;border-radius:8px;padding:10px 14px;font-size:0.85rem;margin-bottom:16px;">
            <i class="bi bi-clock me-2"></i>Your session expired. Please login again.
        </div>
        <?php endif; ?>

        <!-- Error Alert -->
        <?php if ($error): ?>
        <div class="alert" style="background:rgba(231,76,60,0.15);border:1px solid rgba(231,76,60,0.3);color:#ff6b6b;border-radius:8px;padding:10px 14px;font-size:0.85rem;margin-bottom:16px;">
            <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" autocomplete="off" novalidate>
            <div class="mb-3">
                <label class="login-label mb-1">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text" style="background:rgba(255,255,255,0.08);border-color:rgba(255,255,255,0.15);color:rgba(255,255,255,0.5);">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email" name="email" class="form-control login-input" placeholder="admin@arockia.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label class="login-label mb-1">Password</label>
                <div class="input-group">
                    <span class="input-group-text" style="background:rgba(255,255,255,0.08);border-color:rgba(255,255,255,0.15);color:rgba(255,255,255,0.5);">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input type="password" name="password" id="passwordInput" class="form-control login-input" placeholder="••••••••" required>
                    <button type="button" class="input-group-text" id="togglePwd" style="background:rgba(255,255,255,0.08);border-color:rgba(255,255,255,0.15);color:rgba(255,255,255,0.5);cursor:pointer;">
                        <i class="bi bi-eye" id="togglePwdIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn login-btn w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>

        <div class="text-center mt-4">
            <small style="color:rgba(255,255,255,0.3);">© <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('togglePwd').addEventListener('click', function() {
    const input = document.getElementById('passwordInput');
    const icon = document.getElementById('togglePwdIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
});
</script>
</body>
</html>
