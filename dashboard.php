<?php
include 'config.php';
session_start();

$activePage = 'dashboard';

// Total stock items
$totalItems = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM stock"))['cnt'] ?? 0;

// Low stock count (quantity < 5)
$lowStockCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM stock WHERE quantity < 5"))['cnt'] ?? 0;

// Total customers
$totalCustomers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM customers"))['cnt'] ?? 0;

// Workshop usage today
$workshopToday = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(quantity_used), 0) AS total FROM workshop_usage WHERE DATE(date_used) = CURDATE()"))['total'] ?? 0;

// Low stock item names for alert banner
$lowStockResult = mysqli_query($conn, "SELECT part_name FROM stock WHERE quantity < 5 LIMIT 3");
$lowNames = [];
while ($row = mysqli_fetch_assoc($lowStockResult)) {
    $lowNames[] = htmlspecialchars($row['part_name']);
}

// Recent 5 stock items
$recentItems = mysqli_query($conn, "SELECT part_name, category, quantity, selling_price FROM stock ORDER BY id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard – Panja Trading</title>
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
            <div class="topbar-title">Dashboard</div>
            <div class="topbar-actions">
                <span class="live-badge"><span class="live-dot"></span> Live</span>
                <a href="add_stock.php" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg"></i> Add Stock
                </a>
            </div>
        </div>

        <div class="page-content">

            <!-- Stat Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon bg-blue"><i class="bi bi-box-seam"></i></div>
                    <div>
                        <div class="stat-label">Total Stock Items</div>
                        <div class="stat-value"><?= $totalItems ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-purple"><i class="bi bi-people"></i></div>
                    <div>
                        <div class="stat-label">Total Customers</div>
                        <div class="stat-value"><?= $totalCustomers ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon <?= $lowStockCount > 0 ? 'bg-orange' : 'bg-green' ?>">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div>
                        <div class="stat-label">Low Stock Items</div>
                        <div class="stat-value"><?= $lowStockCount ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-green"><i class="bi bi-hammer"></i></div>
                    <div>
                        <div class="stat-label">Workshop Usage Today</div>
                        <div class="stat-value"><?= $workshopToday ?></div>
                    </div>
                </div>
            </div>

            <!-- Low Stock Alert -->
            <?php if ($lowStockCount > 0): ?>
            <div class="alert-banner">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>
                    <strong><?= $lowStockCount ?> item<?= $lowStockCount > 1 ? 's' : '' ?></strong> running low —
                    <?= implode(', ', $lowNames) ?><?= count($lowNames) < $lowStockCount ? ', and more' : '' ?>.
                </span>
                <a href="inventory.php" class="ms-auto alert-link">View all →</a>
            </div>
            <?php endif; ?>

            <!-- Recent Stock Table -->
            <div class="card-section">
                <div class="card-section-header">
                    <span class="section-title">Recent Stock</span>
                    <a href="inventory.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-right"></i> View All
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Part Name</th>
                                <th>Category</th>
                                <th>Qty</th>
                                <th>Selling Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            while ($row = mysqli_fetch_assoc($recentItems)):
                                $qty = (int)$row['quantity'];
                                if ($qty === 0) {
                                    $pill = '<span class="pill pill-out">Out</span>';
                                } elseif ($qty < 5) {
                                    $pill = '<span class="pill pill-low">Low</span>';
                                } else {
                                    $pill = '<span class="pill pill-ok">In Stock</span>';
                                }
                            ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td class="item-name"><?= htmlspecialchars($row['part_name']) ?></td>
                                <td><?= htmlspecialchars($row['category'] ?? '—') ?></td>
                                <td><?= $qty ?></td>
                                <td>₹<?= number_format($row['selling_price'], 2) ?></td>
                                <td><?= $pill ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>