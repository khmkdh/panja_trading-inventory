<?php
include 'config.php'; // your DB config file

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $part_id = $_POST['part_id'];
    $quantity_used = $_POST['quantity_used'];
    $used_for = $_POST['used_for'];
    $date_used = $_POST['date_used'];

    // Insert into workshop_usage
    $insert = "INSERT INTO workshop_usage (part_id, quantity_used, used_for, date_used)
               VALUES ('$part_id', '$quantity_used', '$used_for', '$date_used')";
    $conn->query($insert);

    // Decrease stock quantity
    $update = "UPDATE stock SET quantity = quantity - $quantity_used WHERE id = $part_id";
    $conn->query($update);

    echo "<script>alert('Workshop usage recorded successfully.'); window.location.href='inventory.php';</script>";
    exit();
}

// Fetch part list from stock table
$parts = $conn->query("SELECT id, part_name FROM stock");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Workshop Usage — Panja Trading</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="page-wrapper">

<nav>
    <span class="nav-brand">Panja Trading</span>
    <div class="nav-actions">
        <a href="inventory.php" class="btn btn-ghost btn-sm">← Inventory</a>
    </div>
</nav>

<div class="page-header">
    <h1 class="page-title">Record Workshop Usage</h1>
</div>

<div class="content">

    <div class="panel">
        <div class="panel-title">Usage Details</div>

        <form method="POST">

            <div class="form-group">
                <label>Part Name</label>
                <select name="part_id" required>
                    <option value="">-- Select Part --</option>
                    <?php while($row = $parts->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['part_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Quantity Used</label>
                    <input type="number" name="quantity_used" placeholder="Enter quantity" required>
                </div>
                <div class="form-group">
                    <label>Date Used</label>
                    <input type="date" name="date_used" required>
                </div>
            </div>

            <div class="form-group">
                <label>Used For</label>
                <textarea name="used_for" rows="3" placeholder="Describe what this part was used for..." required></textarea>
            </div>

            <div class="flex gap-2 mt-2">
                <button type="submit" class="btn btn-primary">Submit Usage</button>
                <a href="inventory.php" class="btn btn-secondary">Cancel</a>
            </div>

        </form>
    </div>

</div>

</body>
</html>
