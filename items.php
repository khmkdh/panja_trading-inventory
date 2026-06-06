<?php 
include 'config.php'; 
session_start();

$product_name = $storage_id = $category_id = $bike_id = $variant_name = $model_year = $stock_units = $price_per_unit = '';
$supplier = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name']);
    $storage_id = $_POST['storage_id'];
    $category_id = $_POST['category_id'];
    $bike_id = $_POST['bike_id'];
    $variant_name = trim($_POST['variant_name']);
    $model_year = $_POST['model_year'];
    $supplier = trim($_POST['supplier']);
    $stock_units = $_POST['stock_units'];
    $price_per_unit = $_POST['price_per_unit'];

    // Insert into items table
    $query = "INSERT INTO items (product_name, storage_id, category_id, bike_id, variant_name, model_year, stock_units, price_per_unit)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("siiiisid", $product_name, $storage_id, $category_id, $bike_id, $variant_name, $model_year, $stock_units, $price_per_unit);

    if ($stmt->execute()) {
        $stmt->close();

        // Get category name
        $category_name = '';
        $catQuery = $conn->prepare("SELECT name FROM categories WHERE id = ?");
        $catQuery->bind_param("i", $category_id);
        $catQuery->execute();
        $catResult = $catQuery->get_result();
        if ($catRow = $catResult->fetch_assoc()) {
            $category_name = $catRow['name'];
        }
        $catQuery->close();

        if (!empty($category_name)) {
            // Insert into stock table
            $supplier = trim($_POST['supplier'] ?? 'System');
            $added_on = date('Y-m-d');

        $stockInsert = $conn->prepare("INSERT INTO stock (part_name, category, quantity, purchase_price, selling_price, supplier, added_on)
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
$stockInsert->bind_param("ssiddss", $product_name, $category_name, $stock_units, $price_per_unit, $price_per_unit, $supplier, $added_on);
$stockInsert->execute();
$stockInsert->close();


            $success = "Item and stock entry added successfully!";
            $product_name = $storage_id = $category_id = $bike_id = $variant_name = $model_year = $stock_units = $price_per_unit = '';
        } else {
            $error = "Error: Category not found for the given ID.";
        }
    } else {
        $error = "Error: " . $stmt->error;
        $stmt->close();
    }
}



    if (empty($error)) {
        $product_name = $storage_id = $category_id = $bike_id = $variant_name = $model_year = $stock_units = $price_per_unit = '';
    }

?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Item — Panja Trading</title>
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
    </div>
</nav>

<div class="page-header">
    <h1 class="page-title">Add New Item</h1>
</div>

<div class="content">

    <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
    <?php if (isset($error)) echo "<div class='alert alert-error'>$error</div>"; ?>

    <div class="panel">
        <div class="panel-title">Item Details</div>

        <form method="POST" novalidate>

            <div class="form-row">
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="product_name" required
                           placeholder="Enter the product name"
                           value="<?= htmlspecialchars($product_name) ?>">
                </div>
                <div class="form-group">
                    <label>Storage</label>
                    <select name="storage_id" required>
                        <option disabled <?= empty($storage_id) ? 'selected' : '' ?>>-- Select Storage --</option>
                        <?php
                        $storages = $conn->query("SELECT id, name FROM storages");
                        while ($row = $storages->fetch_assoc()) {
                            $selected = ($storage_id == $row['id']) ? 'selected' : '';
                            echo "<option value='{$row['id']}' $selected>" . htmlspecialchars($row['name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" required>
                        <option disabled <?= empty($category_id) ? 'selected' : '' ?>>-- Select Category --</option>
                        <?php
                        $categories = $conn->query("SELECT id, name FROM categories");
                        while ($row = $categories->fetch_assoc()) {
                            $selected = ($category_id == $row['id']) ? 'selected' : '';
                            echo "<option value='{$row['id']}' $selected>" . htmlspecialchars($row['name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Bike Model</label>
                    <select name="bike_id" required>
                        <option disabled <?= empty($bike_id) ? 'selected' : '' ?>>-- Select Bike Model --</option>
                        <?php
                        $bikes = $conn->query("SELECT id, bike_name, brand, engine_capacity FROM bikes");
                        while ($row = $bikes->fetch_assoc()) {
                            $selected = ($bike_id == $row['id']) ? 'selected' : '';
                            $displayName = htmlspecialchars($row['bike_name']);
                            if (!empty($row['brand'])) $displayName .= " (" . htmlspecialchars($row['brand']) . ")";
                            if (!empty($row['engine_capacity'])) $displayName .= " - " . htmlspecialchars($row['engine_capacity']);
                            echo "<option value='{$row['id']}' $selected>$displayName</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Variant Name</label>
                    <input type="text" name="variant_name" required
                           placeholder="e.g., Standard, ABS"
                           value="<?= htmlspecialchars($variant_name) ?>">
                </div>
                <div class="form-group">
                    <label>Supplier</label>
                    <input type="text" name="supplier" required
                           placeholder="Enter supplier name"
                           value="<?= htmlspecialchars($supplier) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Model Year</label>
                    <select name="model_year" required>
                        <option disabled <?= empty($model_year) ? 'selected' : '' ?>>-- Select Model Year --</option>
                        <?php
                        for ($year = date("Y"); $year >= 2010; $year--) {
                            $selected = ($model_year == $year) ? 'selected' : '';
                            echo "<option value='$year' $selected>$year</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Stock Units</label>
                    <input type="number" name="stock_units" required
                           placeholder="Enter stock quantity"
                           value="<?= htmlspecialchars($stock_units) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Price per Unit (₹)</label>
                    <input type="number" name="price_per_unit" step="0.01" required
                           placeholder="e.g., 120.50"
                           value="<?= htmlspecialchars($price_per_unit) ?>">
                </div>
                <div class="form-group"></div>
            </div>

            <div class="flex gap-2 mt-2">
                <button type="submit" class="btn btn-primary">Add Item</button>
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>

        </form>
    </div>

</div>

</body>
</html>

