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
    <title>Login — Panja Trading</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-wrapper {
            display: flex;
            width: 100%;
            max-width: 900px;
            min-height: 520px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 40px rgba(0,0,0,0.12);
            margin: 24px;
        }

        /* Left panel */
        .login-panel-left {
            flex: 1;
            background: #1e2a3a;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 36px;
            color: #fff;
            text-align: center;
        }

        .brand-icon {
            width: 64px;
            height: 64px;
            background: rgba(79,140,255,0.2);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #7eb3ff;
            margin-bottom: 20px;
        }

        .brand-title {
            font-size: 22px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 6px;
        }

        .brand-subtitle {
            font-size: 13px;
            color: rgba(255,255,255,0.45);
            margin-bottom: 36px;
        }

        .feature-list {
            list-style: none;
            text-align: left;
            width: 100%;
        }

        .feature-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: rgba(255,255,255,0.6);
            padding: 7px 0;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        .feature-list li:last-child { border-bottom: none; }

        .feature-list i {
            font-size: 15px;
            color: #7eb3ff;
            width: 18px;
            text-align: center;
            flex-shrink: 0;
        }

        /* Right panel */
        .login-panel-right {
            flex: 1;
            background: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 48px 40px;
        }

        .login-heading {
            font-size: 22px;
            font-weight: 700;
            color: #1e2a3a;
            margin-bottom: 4px;
        }

        .login-subheading {
            font-size: 13px;
            color: #6b7a8d;
            margin-bottom: 32px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .form-control {
            border: 1px solid #dde2e8;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            color: #1e2a3a;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .form-control:focus {
            border-color: #4f8cff;
            box-shadow: 0 0 0 3px rgba(79,140,255,0.12);
            outline: none;
        }

        .input-group-text {
            background: #f8fafc;
            border: 1px solid #dde2e8;
            border-radius: 8px 0 0 8px;
            color: #6b7a8d;
            font-size: 15px;
        }

        .input-group .form-control {
            border-radius: 0 8px 8px 0;
            border-left: none;
        }

        .input-group .form-control:focus {
            border-left: 1px solid #4f8cff;
        }

        .btn-login {
            background: #1e2a3a;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 11px;
            font-size: 14px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: background 0.15s;
            margin-top: 8px;
        }

        .btn-login:hover { background: #2d3f57; }

        .error-box {
            background: #fce8e8;
            border: 1px solid #f5c6c6;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            color: #c62828;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .login-footer {
            margin-top: 28px;
            font-size: 12px;
            color: #a0aab4;
            text-align: center;
        }

        @media (max-width: 640px) {
            .login-panel-left { display: none; }
            .login-panel-right { padding: 36px 24px; }
            .login-wrapper { margin: 16px; min-height: unset; }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <!-- Left branding panel -->
    <div class="login-panel-left">
        <div class="brand-icon">
            <i class="bi bi-tools"></i>
        </div>
        <div class="brand-title">Panja Trading</div>
        <div class="brand-subtitle">Bike Workshop Inventory System</div>

        <ul class="feature-list">
            <li><i class="bi bi-box-seam"></i> Parts & stock management</li>
            <li><i class="bi bi-receipt"></i> Sales tracking</li>
            <li><i class="bi bi-hammer"></i> Workshop usage logs</li>
            <li><i class="bi bi-bar-chart-line"></i> Reports & analytics</li>
            <li><i class="bi bi-shield-lock"></i> Role-based access</li>
        </ul>
    </div>

    <!-- Right login form -->
    <div class="login-panel-right">
        <div class="login-heading">Welcome back</div>
        <div class="login-subheading">Sign in to your staff account</div>

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

            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control"
                           placeholder="Enter your password" required id="passwordInput">
                    <span class="input-group-text" style="border-radius:0 8px 8px 0; border-left:none; cursor:pointer"
                          onclick="togglePassword()">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </span>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right"></i> Sign In
            </button>
        </form>

        <div class="login-footer">
            &copy; <?= date('Y') ?> Panja Trading. All rights reserved.
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('passwordInput');
    const icon  = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
</body>
</html>