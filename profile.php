<?php
session_start();
include 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

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
    $new_password = $_POST['new_password'];
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
        if ($stmt->execute()) {
            $success = "Password updated successfully.";
        } else {
            $error = "Something went wrong. Please try again.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Profile — Panja Trading</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="page-wrapper">

<nav>
    <span class="nav-brand">Panja Trading</span>
    <div class="nav-actions">
        <a href="dashboard.php" class="btn btn-ghost btn-sm">← Dashboard</a>
        <a href="logout.php" class="btn btn-secondary btn-sm">↗ Logout</a>
    </div>
</nav>

<div class="page-header">
    <h1 class="page-title">Profile</h1>
</div>

<div class="content" style="max-width:600px">

    <!-- User Info Card -->
    <div class="panel" style="margin-bottom:20px">
        <div class="panel-title">Account Info</div>

        <div style="display:flex;align-items:center;gap:20px;margin-bottom:20px">
            <!-- Avatar -->
            <div style="width:64px;height:64px;border-radius:50%;background:var(--accent-dim);
                        border:2px solid var(--accent);display:flex;align-items:center;
                        justify-content:center;font-family:var(--font-head);font-size:1.6rem;
                        font-weight:700;color:var(--accent);flex-shrink:0">
                <?= strtoupper(substr($user['username'], 0, 1)) ?>
            </div>
            <div>
                <div style="font-family:var(--font-head);font-size:1.3rem;font-weight:700;
                            text-transform:uppercase;letter-spacing:.05em">
                    <?= htmlspecialchars($user['username']) ?>
                </div>
                <div>
                    <span class="badge badge-blue" style="margin-top:4px">
                        <?= htmlspecialchars($user['role'] ?? 'Staff') ?>
                    </span>
                </div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div>
                <div style="font-size:.72rem;color:var(--text-2);text-transform:uppercase;
                            letter-spacing:.06em;margin-bottom:3px">Username</div>
                <div style="font-weight:500"><?= htmlspecialchars($user['username']) ?></div>
            </div>
            <div>
                <div style="font-size:.72rem;color:var(--text-2);text-transform:uppercase;
                            letter-spacing:.06em;margin-bottom:3px">Role</div>
                <div style="font-weight:500"><?= htmlspecialchars($user['role'] ?? 'Staff') ?></div>
            </div>
        </div>
    </div>

    <!-- Change Password -->
    <div class="panel">
        <div class="panel-title">Change Password</div>

        <?php if ($success) echo "<div class='alert alert-success'>$success</div>"; ?>
        <?php if ($error)   echo "<div class='alert alert-error'>$error</div>"; ?>

        <form method="POST">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password"
                       placeholder="Enter current password" required>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password"
                       placeholder="At least 6 characters" required>
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password"
                       placeholder="Repeat new password" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Password</button>
        </form>
    </div>

</div>

</body>
</html>