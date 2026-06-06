<?php
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$activePage = '';
$username = $_SESSION['username'];
$success = '';
$error = '';

// Fetch current user
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!password_verify($current_password, $user['password'])) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->bind_param("ss", $hashed, $username);
        $success = $stmt->execute() ? "Password updated successfully." : "Something went wrong. Please try again.";
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile – Panja Trading</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-area">
        <div class="topbar">
            <div class="topbar-title">Profile</div>
            <div class="topbar-actions">
                <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Dashboard
                </a>
                <a href="logout.php" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>

        <div class="page-content">

            <?php if ($success): ?>
            <div class="alert-banner" style="background:#e8f5e9; border-color:#a5d6a7; max-width:600px;">
                <i class="bi bi-check-circle-fill" style="color:#2e7d32;"></i>
                <span style="color:#1b5e20;"><?= $success ?></span>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert-banner" style="background:#fce8e8; border-color:#f5c6c6; max-width:600px;">
                <i class="bi bi-exclamation-circle-fill" style="color:#c62828;"></i>
                <span style="color:#c62828;"><?= $error ?></span>
            </div>
            <?php endif; ?>

            <!-- Account Info -->
            <div class="card-section" style="max-width:600px;">
                <div class="card-section-header">
                    <span class="section-title"><i class="bi bi-person-circle"></i> Account Info</span>
                </div>
                <div style="padding: 24px;">
                    <div class="d-flex align-items-center gap-4 mb-4">
                        <!-- Avatar -->
                        <div style="width:64px; height:64px; border-radius:50%;
                                    background:rgba(79,140,255,0.15); border:2px solid #4f8cff;
                                    display:flex; align-items:center; justify-content:center;
                                    font-size:24px; font-weight:700; color:#4f8cff; flex-shrink:0;">
                            <?= strtoupper(substr($user['username'], 0, 1)) ?>
                        </div>
                        <div>
                            <div style="font-size:18px; font-weight:700; color:#1e2a3a;">
                                <?= htmlspecialchars($user['username']) ?>
                            </div>
                            <span class="pill" style="background:#e8f0fe; color:#1a56db; margin-top:4px; display:inline-block;">
                                <?= htmlspecialchars($user['role'] ?? 'Staff') ?>
                            </span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="stat-label">Username</div>
                            <div class="item-name"><?= htmlspecialchars($user['username']) ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-label">Role</div>
                            <div class="item-name"><?= htmlspecialchars($user['role'] ?? 'Staff') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card-section" style="max-width:600px;">
                <div class="card-section-header">
                    <span class="section-title"><i class="bi bi-shield-lock"></i> Change Password</span>
                </div>
                <div style="padding: 24px;">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Current Password</label>
                            <input type="password" name="current_password" class="form-control"
                                   placeholder="Enter current password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">New Password</label>
                            <input type="password" name="new_password" class="form-control"
                                   placeholder="At least 6 characters" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control"
                                   placeholder="Repeat new password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>