<?php
include 'config.php';
session_start();

$activePage = 'inventory';

$success = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $part_name      = $_POST['part_name'];
    $category       = $_POST['category'];
    $quantity       = $_POST['quantity'];
    $purchase_price = $_POST['purchase_price'];
    $selling_price  = $_POST['selling_price'];
    $supplier       = $_POST['supplier'];

    $stmt = $conn->prepare("INSERT INTO stock (part_name, category, quantity, purchase_price, selling_price, supplier)
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisss", $part_name, $category, $quantity, $purchase_price, $selling_price, $supplier);

    if ($stmt->execute()) {
        $success = "Stock item \"" . htmlspecialchars($part_name) . "\" added successfully.";
    } else {
        $error = "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Stock – Panja Trading</title>
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
            <div class="topbar-title">Add New Stock</div>
            <div class="topbar-actions">
                <a href="inventory.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Inventory
                </a>
            </div>
        </div>

        <div class="page-content">

            <?php if ($success): ?>
            <div class="alert-banner" style="background:#e8f5e9; border-color:#a5d6a7;">
                <i class="bi bi-check-circle-fill" style="color:#2e7d32;"></i>
                <span style="color:#1b5e20;"><?= $success ?></span>
                <a href="inventory.php" class="ms-auto alert-link" style="color:#1b5e20;">View Inventory →</a>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert-banner" style="background:#fce8e8; border-color:#f5c6c6;">
                <i class="bi bi-exclamation-circle-fill" style="color:#c62828;"></i>
                <span style="color:#c62828;"><?= $error ?></span>
            </div>
            <?php endif; ?>

            <div class="card-section" style="max-width: 680px;">
                <div class="card-section-header">
                    <span class="section-title"><i class="bi bi-box-seam"></i> Stock Details</span>
                </div>

                <div style="padding: 24px;">
                    <form action="add_stock.php" method="POST">

                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Part Name</label>
                                <input type="text" name="part_name" class="form-control"
                                       placeholder="Enter part name" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Category</label>
                                <input type="text" name="category" class="form-control"
                                       placeholder="e.g. Brakes, Tyres">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Quantity</label>
                                <input type="number" name="quantity" class="form-control"
                                       placeholder="0" min="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Purchase Price (₹)</label>
                                <input type="number" name="purchase_price" class="form-control"
                                       placeholder="0.00" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Selling Price (₹)</label>
                                <input type="number" name="selling_price" class="form-control"
                                       placeholder="0.00" step="0.01" min="0" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Supplier</label>
                            <input type="text" name="supplier" class="form-control"
                                   placeholder="Enter supplier name">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus-lg"></i> Add Stock
                            </button>
                            <a href="inventory.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>