<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION["username"] = $user["username"];
            $_SESSION["role"] = $user["role"];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Invalid username or password.";
    }

    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login — GearVault</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow: hidden;
        }

        body {
            display: flex;
            background: #0f1923;
        }

        /* ── Left panel ── */
        .login-left {
            flex: 1.1;
            position: relative;
            background: #0f1923;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px 56px;
            overflow: hidden;
        }

        /* Animated gear background */
        .gear-bg {
            position: absolute;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
        }

        .gear-circle {
            position: absolute;
            border-radius: 50%;
            border: 2px solid rgba(79,140,255,0.08);
        }

        .gc1 { width: 500px; height: 500px; top: -120px; right: -160px; border-color: rgba(79,140,255,0.06); }
        .gc2 { width: 320px; height: 320px; bottom: -80px; left: -80px; border-color: rgba(79,140,255,0.05); }
        .gc3 { width: 180px; height: 180px; top: 40%; right: 60px; border-color: rgba(79,140,255,0.1); animation: spin 20s linear infinite; }

        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

        /* Glowing dot grid */
        .dot-grid {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle, rgba(79,140,255,0.12) 1px, transparent 1px);
            background-size: 32px 32px;
        }

        .brand-block { position: relative; z-index: 1; }

        .brand-icon {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, #1a3a6b, #2d5bb5);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: #7eb3ff;
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(79,140,255,0.25);
        }

        .brand-name {
            font-size: 36px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.5px;
            line-height: 1;
            margin-bottom: 8px;
        }

        .brand-name span { color: #4f8cff; }

        .brand-tagline {
            font-size: 13px;
            color: rgba(255,255,255,0.4);
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 48px;
        }

        .feature-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .feature-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13px;
            color: rgba(255,255,255,0.55);
            transition: background 0.2s;
        }

        .feature-list li:hover {
            background: rgba(255,255,255,0.05);
            color: rgba(255,255,255,0.8);
        }

        .feature-list i {
            width: 32px;
            height: 32px;
            background: rgba(79,140,255,0.12);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            color: #4f8cff;
            flex-shrink: 0;
        }

        .version-tag {
            position: absolute;
            bottom: 28px;
            left: 56px;
            font-size: 11px;
            color: rgba(255,255,255,0.2);
            letter-spacing: 1px;
        }

        /* ── Right panel ── */
        .login-right {
            width: 460px;
            flex-shrink: 0;
            background: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 56px 48px;
            position: relative;
        }

        /* Top accent bar */
        .login-right::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4f8cff, #7eb3ff, #4f8cff);
            background-size: 200%;
            animation: shimmer 3s linear infinite;
        }

        @keyframes shimmer {
            0% { background-position: 0% 0; }
            100% { background-position: 200% 0; }
        }

        .login-heading {
            font-size: 26px;
            font-weight: 800;
            color: #0f1923;
            margin-bottom: 4px;
        }

        .login-subheading {
            font-size: 13px;
            color: #8896a7;
            margin-bottom: 36px;
        }

        .form-label {
            font-size: 12px;
            font-weight: 700;
            color: #4a5568;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 6px;
        }

        .input-group-text {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-right: none;
            border-radius: 10px 0 0 10px;
            color: #8896a7;
            font-size: 15px;
            padding: 0 14px;
        }

        .form-control {
            border: 1.5px solid #e2e8f0;
            border-left: none;
            border-radius: 0 10px 10px 0;
            padding: 12px 14px;
            font-size: 14px;
            color: #0f1923;
            background: #fff;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .form-control:focus {
            border-color: #4f8cff;
            box-shadow: 0 0 0 3px rgba(79,140,255,0.1);
            outline: none;
            border-left: 1.5px solid #4f8cff;
        }

        .toggle-pw {
            border: 1.5px solid #e2e8f0;
            border-left: none;
            border-radius: 0 10px 10px 0 !important;
            background: #f8fafc;
            color: #8896a7;
            cursor: pointer;
            padding: 0 14px;
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #1a3a6b, #2d5bb5);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            letter-spacing: 0.03em;
            transition: opacity 0.15s, transform 0.1s;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-login:hover { opacity: 0.92; transform: translateY(-1px); }
        .btn-login:active { transform: translateY(0); }

        .error-box {
            background: #fef2f2;
            border: 1.5px solid #fecaca;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            color: #dc2626;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
        }

        .login-footer {
            margin-top: 32px;
            font-size: 11px;
            color: #c0cad6;
            text-align: center;
        }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0;
            color: #c0cad6;
            font-size: 11px;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e8ecf0;
        }

        @media (max-width: 768px) {
            .login-left { display: none; }
            .login-right { width: 100%; padding: 40px 28px; }
            html, body { overflow: auto; }
        }
    </style>
</head>
<body>

<!-- Left panel -->
<div class="login-left">
    <div class="gear-bg">
        <div class="dot-grid"></div>
        <div class="gear-circle gc1"></div>
        <div class="gear-circle gc2"></div>
        <div class="gear-circle gc3"></div>
    </div>

    <div class="brand-block">
        <div class="brand-icon">
            <i class="bi bi-gear-wide-connected"></i>
        </div>
        <div class="brand-name"><span>Gear</span>Vault</div>
        <div class="brand-tagline">Bike Workshop Inventory</div>

        <ul class="feature-list">
            <li>
                <i class="bi bi-box-seam"></i>
                Parts & stock management
            </li>
            <li>
                <i class="bi bi-receipt"></i>
                Sales tracking & billing
            </li>
            <li>
                <i class="bi bi-hammer"></i>
                Workshop usage logs
            </li>
            <li>
                <i class="bi bi-bar-chart-line"></i>
                Reports & analytics
            </li>
            <li>
                <i class="bi bi-shield-lock"></i>
                Role-based access control
            </li>
        </ul>
    </div>

    <div class="version-tag">GEARVAULT v1.0 &nbsp;·&nbsp; STAFF PORTAL</div>
</div>

<!-- Right panel -->
<div class="login-right">
    <div class="login-heading">Welcome back</div>
    <div class="login-subheading">Sign in to your staff account to continue</div>

    <?php if (!empty($error)): ?>
    <div class="error-box">
        <i class="bi bi-exclamation-circle-fill"></i>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="username" class="form-control"
                       placeholder="Enter your username" required
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
        </div>

        <div class="mb-2">
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control"
                       placeholder="Enter your password" required id="passwordInput"
                       style="border-radius:0; border-right:none;">
                <button type="button" class="input-group-text toggle-pw" onclick="togglePassword()">
                    <i class="bi bi-eye" id="eyeIcon"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-login">
            <i class="bi bi-box-arrow-in-right"></i> Sign In
        </button>
    </form>

    <div class="login-footer">
        &copy; <?= date('Y') ?> GearVault. All rights reserved.
    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('passwordInput');
    const icon  = document.getElementById('eyeIcon');
    input.type  = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
</script>
</body>
</html>