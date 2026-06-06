<?php
include 'config.php';
session_start();

$activePage = 'category';
$message = "";
$messageType = "";
$newCategory = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_name'])) {
    $newCategory = trim($_POST['category_name']);
    if (!empty($newCategory)) {
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $newCategory);
        if ($stmt->execute()) {
            $message = "Category \"" . htmlspecialchars($newCategory) . "\" added successfully.";
            $messageType = "success";
        } else {
            $message = "Error: " . $stmt->error;
            $messageType = "error";
        }
        $stmt->close();
    } else {
        $message = "Category name cannot be empty.";
        $messageType = "warning";
    }
}

// Fetch all categories
$catResult = $conn->query("SELECT name FROM categories ORDER BY id");
$categories = [];
while ($catRow = $catResult->fetch_assoc()) {
    $categories[] = $catRow['name'];
}

// Fetch all stock grouped by category
$result = $conn->query("SELECT * FROM stock ORDER BY category, part_name");
$grouped = [];
while ($row = $result->fetch_assoc()) {
    $grouped[$row['category']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Categories – Panja Trading</title>
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
            <div class="topbar-title">Categories</div>
            <div class="topbar-actions">
                <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>

        <div class="page-content">

            <?php if ($message): ?>
            <?php
                $bg    = $messageType === 'success' ? '#e8f5e9' : ($messageType === 'error' ? '#fce8e8' : '#fff8e1');
                $bc    = $messageType === 'success' ? '#a5d6a7' : ($messageType === 'error' ? '#f5c6c6' : '#ffe082');
                $color = $messageType === 'success' ? '#1b5e20' : ($messageType === 'error' ? '#c62828' : '#7d5a00');
                $icon  = $messageType === 'success' ? 'check-circle-fill' : ($messageType === 'error' ? 'exclamation-circle-fill' : 'exclamation-triangle-fill');
            ?>
            <div class="alert-banner" style="background:<?= $bg ?>; border-color:<?= $bc ?>;">
                <i class="bi bi-<?= $icon ?>" style="color:<?= $color ?>;"></i>
                <span style="color:<?= $color ?>;"><?= $message ?></span>
            </div>
            <?php endif; ?>

            <!-- Add Category -->
            <div class="card-section" style="max-width: 680px;">
                <div class="card-section-header">
                    <span class="section-title"><i class="bi bi-tags"></i> Add New Category</span>
                </div>
                <div style="padding: 20px;">
                    <form method="POST">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Category Name</label>
                                <input type="text" name="category_name" class="form-control"
                                       placeholder="e.g. Brakes, Tyres, Engine" required>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-plus-lg"></i> Add Category
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Category-wise stock tables -->
            <?php foreach ($categories as $cat):
                $idAttr = ($cat === $newCategory) ? 'id="new-category"' : '';
                $items = $grouped[$cat] ?? [];
            ?>
            <div class="card-section" <?= $idAttr ?>>
                <div class="card-section-header">
                    <span class="section-title">
                        <i class="bi bi-tag"></i> <?= htmlspecialchars($cat) ?>
                    </span>
                    <span class="badge" style="background:#f0f4f8; color:#6b7a8d; font-size:11px; padding:4px 10px; border-radius:20px;">
                        <?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?>
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Part Name</th>
                                <th>Quantity</th>
                                <th>Purchase Price</th>
                                <th>Selling Price</th>
                                <th>Supplier</th>
                                <th>Added On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-3 text-muted">
                                    No items in this category yet.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($items as $i => $row): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="item-name"><?= htmlspecialchars($row['part_name']) ?></td>
                                <td><?= $row['quantity'] ?></td>
                                <td>₹<?= number_format($row['purchase_price'], 2) ?></td>
                                <td>₹<?= number_format($row['selling_price'], 2) ?></td>
                                <td><?= htmlspecialchars($row['supplier']) ?></td>
                                <td><?= date('d M Y', strtotime($row['added_on'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Stock items with no matching category -->
            <?php
            $uncategorized = [];
            foreach ($grouped as $cat => $items) {
                if (!in_array($cat, $categories)) {
                    foreach ($items as $item) $uncategorized[] = $item;
                }
            }
            if (!empty($uncategorized)): ?>
            <div class="card-section">
                <div class="card-section-header">
                    <span class="section-title"><i class="bi bi-question-circle"></i> Uncategorized</span>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Part Name</th>
                                <th>Quantity</th>
                                <th>Purchase Price</th>
                                <th>Selling Price</th>
                                <th>Supplier</th>
                                <th>Added On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($uncategorized as $i => $row): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="item-name"><?= htmlspecialchars($row['part_name']) ?></td>
                                <td><?= $row['quantity'] ?></td>
                                <td>₹<?= number_format($row['purchase_price'], 2) ?></td>
                                <td>₹<?= number_format($row['selling_price'], 2) ?></td>
                                <td><?= htmlspecialchars($row['supplier']) ?></td>
                                <td><?= date('d M Y', strtotime($row['added_on'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
window.addEventListener('load', function () {
    const newCat = document.getElementById('new-category');
    if (newCat) newCat.scrollIntoView({ behavior: 'smooth', block: 'start' });
});
</script>
</body>
</html>