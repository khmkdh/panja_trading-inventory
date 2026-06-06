<?php
include 'config.php';
session_start();

$activePage = 'inventory';

$product_name = $storage_id = $category_id = $bike_id = $variant_name = $model_year = $stock_units = $price_per_unit = $supplier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name   = trim($_POST['product_name']);
    $storage_id     = $_POST['storage_id'];
    $category_id    = $_POST['category_id'];
    $bike_id        = $_POST['bike_id'];
    $variant_name   = trim($_POST['variant_name']);
    $model_year     = $_POST['model_year'];
    $supplier       = trim($_POST['supplier']);
    $stock_units    = $_POST['stock_units'];
    $price_per_unit = $_POST['price_per_unit'];

    $query = "INSERT INTO items (product_name, storage_id, category_id, bike_id, variant_name, model_year, stock_units, price_per_unit)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("siiiisid", $product_name, $storage_id, $category_id, $bike_id, $variant_name, $model_year, $stock_units, $price_per_unit);

    if ($stmt->execute()) {
        $stmt->close();

        $catQuery = $conn->prepare("SELECT name FROM categories WHERE id = ?");
        $catQuery->bind_param("i", $category_id);
        $catQuery->execute();
        $catResult = $catQuery->get_result();
        $category_name = '';
        if ($catRow = $catResult->fetch_assoc()) $category_name = $catRow['name'];
        $catQuery->close();

        if (!empty($category_name)) {
            $added_on    = date('Y-m-d');
            $stockInsert = $conn->prepare("INSERT INTO stock (part_name, category, quantity, purchase_price, selling_price, supplier, added_on)
                                           VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stockInsert->bind_param("ssiddss", $product_name, $category_name, $stock_units, $price_per_unit, $price_per_unit, $supplier, $added_on);
            $stockInsert->execute();
            $stockInsert->close();
            $success = "Item \"" . htmlspecialchars($product_name) . "\" added successfully!";
            $product_name = $storage_id = $category_id = $bike_id = $variant_name = $model_year = $stock_units = $price_per_unit = $supplier = '';
        } else {
            $error = "Error: Category not found for the given ID.";
        }
    } else {
        $error = "Error: " . $stmt->error;
        $stmt->close();
    }
}

// Fetch dropdowns
$storages    = $conn->query("SELECT id, name FROM storages");
$categories  = $conn->query("SELECT id, name FROM categories");
$bikes       = $conn->query("SELECT id, bike_name, brand, engine_capacity FROM bikes");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Item – Panja Trading</title>
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
            <div class="topbar-title">Add New Item</div>
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
                <a href="inventory.php" class="ms-auto alert-link" style="color:#1b5e20;">View Inventory →</a>
            </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
            <div class="alert-banner" style="background:#fce8e8; border-color:#f5c6c6;">
                <i class="bi bi-exclamation-circle-fill" style="color:#c62828;"></i>
                <span style="color:#c62828;"><?= $error ?></span>
            </div>
            <?php endif; ?>

            <div class="card-section" style="max-width: 820px;">
                <div class="card-section-header">
                    <span class="section-title"><i class="bi bi-box-seam"></i> Item Details</span>
                </div>

                <div style="padding: 24px;">
                    <form method="POST" novalidate>

                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Product Name</label>
                                <input type="text" name="product_name" class="form-control"
                                       placeholder="Enter the product name" required
                                       value="<?= htmlspecialchars($product_name) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Storage</label>
                                <select name="storage_id" class="form-select" required>
                                    <option value="" disabled <?= empty($storage_id) ? 'selected' : '' ?>>-- Select Storage --</option>
                                    <?php while ($row = $storages->fetch_assoc()): ?>
                                    <option value="<?= $row['id'] ?>" <?= $storage_id == $row['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($row['name']) ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Category</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="" disabled <?= empty($category_id) ? 'selected' : '' ?>>-- Select Category --</option>
                                    <?php while ($row = $categories->fetch_assoc()): ?>
                                    <option value="<?= $row['id'] ?>" <?= $category_id == $row['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($row['name']) ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Bike Model</label>
                                <select name="bike_id" class="form-select" required>
                                    <option value="" disabled <?= empty($bike_id) ? 'selected' : '' ?>>-- Select Bike Model --</option>
                                    <?php while ($row = $bikes->fetch_assoc()):
                                        $display = htmlspecialchars($row['bike_name']);
                                        if (!empty($row['brand'])) $display .= " (" . htmlspecialchars($row['brand']) . ")";
                                        if (!empty($row['engine_capacity'])) $display .= " - " . htmlspecialchars($row['engine_capacity']);
                                    ?>
                                    <option value="<?= $row['id'] ?>" <?= $bike_id == $row['id'] ? 'selected' : '' ?>>
                                        <?= $display ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Variant Name</label>
                                <input type="text" name="variant_name" class="form-control"
                                       placeholder="e.g. Standard, ABS" required
                                       value="<?= htmlspecialchars($variant_name) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Supplier</label>
                                <input type="text" name="supplier" class="form-control"
                                       placeholder="Enter supplier name" required
                                       value="<?= htmlspecialchars($supplier) ?>">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Model Year</label>
                                <select name="model_year" class="form-select" required>
                                    <option value="" disabled <?= empty($model_year) ? 'selected' : '' ?>>-- Select Year --</option>
                                    <?php for ($year = date("Y"); $year >= 2010; $year--): ?>
                                    <option value="<?= $year ?>" <?= $model_year == $year ? 'selected' : '' ?>><?= $year ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Stock Units</label>
                                <input type="number" name="stock_units" class="form-control"
                                       placeholder="Enter quantity" min="0" required
                                       value="<?= htmlspecialchars($stock_units) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Price per Unit (₹)</label>
                                <input type="number" name="price_per_unit" class="form-control"
                                       placeholder="e.g. 120.50" step="0.01" min="0" required
                                       value="<?= htmlspecialchars($price_per_unit) ?>">
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus-lg"></i> Add Item
                            </button>
                            <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>