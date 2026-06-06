<?php
include 'config.php';
session_start();

$activePage = 'workshop_usage';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $part_id      = $_POST['part_id'];
    $quantity_used = $_POST['quantity_used'];
    $used_for     = $_POST['used_for'];
    $date_used    = $_POST['date_used'];

    $insert = "INSERT INTO workshop_usage (part_id, quantity_used, used_for, date_used)
               VALUES ('$part_id', '$quantity_used', '$used_for', '$date_used')";
    $conn->query($insert);

    $update = "UPDATE stock SET quantity = quantity - $quantity_used WHERE id = $part_id";
    $conn->query($update);

    $success = "Workshop usage recorded successfully.";
}

// Fetch part list from stock table
$parts = $conn->query("SELECT id, part_name, quantity FROM stock ORDER BY part_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Workshop Usage – Panja Trading</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-area">
        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-title">Record Workshop Usage</div>
            <div class="topbar-actions">
                <a href="inventory.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Inventory
                </a>
            </div>
        </div>

        <div class="page-content">

            <?php if (!empty($success)): ?>
            <div class="alert-banner" style="background:#e8f5e9; border-color:#a5d6a7;">
                <i class="bi bi-check-circle-fill" style="color:#2e7d32;"></i>
                <span style="color:#1b5e20;"><?= $success ?></span>
                <a href="inventory.php" class="ms-auto alert-link" style="color:#1b5e20;">Go to Inventory →</a>
            </div>
            <?php endif; ?>

            <div class="card-section" style="max-width: 680px;">
                <div class="card-section-header">
                    <span class="section-title"><i class="bi bi-hammer"></i> Usage Details</span>
                </div>

                <div style="padding: 24px;">
                    <form method="POST">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Part Name</label>
                            <select name="part_id" class="form-select" required>
                                <option value="">-- Select Part --</option>
                                <?php while ($row = $parts->fetch_assoc()): ?>
                                    <option value="<?= $row['id'] ?>">
                                        <?= htmlspecialchars($row['part_name']) ?> (Stock: <?= $row['quantity'] ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Quantity Used</label>
                                <input type="number" name="quantity_used" class="form-control"
                                       placeholder="Enter quantity" min="1" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Date Used</label>
                                <input type="date" name="date_used" class="form-control"
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Used For</label>
                            <textarea name="used_for" class="form-control" rows="3"
                                      placeholder="Describe what this part was used for..." required></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Submit Usage
                            </button>
                            <a href="inventory.php" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>