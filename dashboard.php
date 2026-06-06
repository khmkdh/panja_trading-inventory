<?php
include 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = $_POST['product_name'];
    $storage_id = $_POST['storage_id'];
    $category_id = $_POST['category_id'];
    $bike_id = $_POST['bike_id'];
    $variant_name = $_POST['variant_name'];
    $model_year = $_POST['model_year'];
    $stock_units = $_POST['stock_units'];
    $price_per_unit = $_POST['price_per_unit'];

    $query = "INSERT INTO items (product_name, storage_id, category_id, bike_id, variant_name, model_year, stock_units, price_per_unit)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("siiiisid", $product_name, $storage_id, $category_id, $bike_id, $variant_name, $model_year, $stock_units, $price_per_unit);

    if ($stmt->execute()) {
        $success = "Item added successfully!";
    } else {
        $error = "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard — Panja Trading</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="page-wrapper">

<nav>
    <span class="nav-brand">Panja Trading</span>
    <div class="nav-actions">
        <a href="profile.php" class="btn btn-ghost btn-sm">⊙ Profile</a>
        <a href="logout.php" class="btn btn-secondary btn-sm">↗ Logout</a>
    </div>
</nav>

<div class="dashboard">
    <div class="dashboard-title">PANJA <span style="color:var(--accent)">TRADING</span></div>
    <div class="dashboard-subtitle">Bike Xpert — Inventory Management</div>

    <div class="nav-grid">
        <a href="Storage.php" class="nav-card">
            <div class="icon">📦</div>
            <div class="label">Storage</div>
        </a>
        <a href="category.php" class="nav-card">
            <div class="icon">🏷</div>
            <div class="label">Category</div>
        </a>
        <a href="bikes.php" class="nav-card">
            <div class="icon">🚴</div>
            <div class="label">Bikes</div>
        </a>
        <a href="items.php" class="nav-card">
            <div class="icon">➕</div>
            <div class="label">Items</div>
        </a>
        <a href="inventory.php" class="nav-card">
            <div class="icon">≡</div>
            <div class="label">Inventory</div>
        </a>
        <a href="search.php" class="nav-card">
            <div class="icon">🔍</div>
            <div class="label">Search</div>
        </a>
        <a href="customer.php" class="nav-card">
            <div class="icon">👤</div>
            <div class="label">Customer Details</div>
        </a>
        <a href="report.php" class="nav-card">
            <div class="icon">📊</div>
            <div class="label">Report</div>
        </a>
    </div>
</div>

</body>
</html>