<?php
session_start();
include 'config.php';

$activePage = 'warehouse';
$search = $_GET['search'] ?? null;
$selectedWarehouseId = $_GET['warehouse'] ?? null;

$warehouseItems = [
    1 => [ 'Battery', 'Spark Plug', 'Valve', 'Chain Spoket', 'Tyres', 'Gearing', 'Bulb', 'Gasket',
           'Injector', 'Air Filter', 'Block Key', 'Headlight Assembly', 'Tail Light Cover', 'Kick Lever',
           'Horn', 'Throttle Cable', 'Radiator Coolant', 'Speedometer Assembly', 'Swing Arm', 'Brake Shoe',
           'Fuel Tank Cap', 'Ignition Coil', 'Number Plate Frame', 'Clutch Plate', 'Shock Absorber', 'Side Stand/Main Stand' ],
    2 => [ 'Brakes', 'Cable', 'Engine Oil Lubricant', 'Oil Seal', 'Curvator', 'Oil Filter', 'Piston Kit',
           'Rear View Mirror', 'Meter Cable', 'Mudguard', 'Fuel Tap', 'Handle Grip Set', 'Clutch Lever',
           'Brake Disc', 'Gear Lever', 'Front Fork', 'Tool Kit', 'Saree Guard', 'Chain Cover',
           'Kick Starter Spring', 'Side Panel Set', 'Camshaft', 'Head Gasket', 'Exhaust Pipe', 'Main Stand', 'Mirror' ]
];

$warehouseNames = [];
$res = mysqli_query($conn, "SELECT id, name FROM storages");
while ($row = mysqli_fetch_assoc($res)) {
    $warehouseNames[$row['id']] = $row['name'];
}

$partToWarehouse = [];
foreach ($warehouseItems as $wid => $items) {
    foreach ($items as $part) {
        $partToWarehouse[strtolower($part)] = $wid;
    }
}

$stockItems = [];

if ($search) {
    $searchLower = strtolower(trim($search));
    if (isset($partToWarehouse[$searchLower])) {
        $warehouseId = $partToWarehouse[$searchLower];
        $stmt = $conn->prepare("SELECT * FROM stock WHERE LOWER(part_name) = ?");
        $stmt->bind_param("s", $searchLower);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['warehouse'] = $warehouseNames[$warehouseId] ?? '—';
            $stockItems[] = $row;
        }
    }
} elseif ($selectedWarehouseId && isset($warehouseItems[$selectedWarehouseId])) {
    $placeholders = implode(',', array_fill(0, count($warehouseItems[$selectedWarehouseId]), '?'));
    $types = str_repeat('s', count($warehouseItems[$selectedWarehouseId]));
    $stmt = $conn->prepare("SELECT * FROM stock WHERE part_name IN ($placeholders)");
    $stmt->bind_param($types, ...$warehouseItems[$selectedWarehouseId]);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $stockItems[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Storage – Panja Trading</title>
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
            <div class="topbar-title">Storage</div>
            <div class="topbar-actions">
                <a href="Storage.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </a>
                <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>

        <div class="page-content">

            <!-- Search & Filter -->
            <div class="card-section">
                <div class="card-section-header">
                    <span class="section-title"><i class="bi bi-building"></i> Search or Filter by Warehouse</span>
                </div>
                <div style="padding: 20px;">
                    <form method="GET">
                        <div class="row g-3 align-items-end mb-4">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Search Part Name</label>
                                <input type="text" name="search" class="form-control"
                                       placeholder="e.g. Battery, Brakes..."
                                       value="<?= htmlspecialchars($search ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Warehouse Buttons -->
                    <div>
                        <div class="stat-label mb-2">Browse by Warehouse</div>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($warehouseNames as $id => $name): ?>
                            <a href="?warehouse=<?= $id ?>"
                               class="btn btn-sm <?= ($selectedWarehouseId == $id) ? 'btn-primary' : 'btn-outline-secondary' ?>">
                                <i class="bi bi-box-seam"></i> <?= htmlspecialchars($name) ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results -->
            <?php if (!empty($stockItems)): ?>

            <div class="card-section">
                <div class="card-section-header">
                    <span class="section-title">
                        <?php if ($selectedWarehouseId && isset($warehouseNames[$selectedWarehouseId])): ?>
                            <i class="bi bi-building"></i> <?= htmlspecialchars($warehouseNames[$selectedWarehouseId]) ?>
                        <?php elseif ($search): ?>
                            <i class="bi bi-search"></i> Results for "<?= htmlspecialchars($search) ?>"
                        <?php endif; ?>
                    </span>
                    <span style="font-size:12px; color:#6b7a8d;">
                        <?= count($stockItems) ?> item<?= count($stockItems) !== 1 ? 's' : '' ?>
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Part Name</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Purchase Price</th>
                                <th>Selling Price</th>
                                <th>Added On</th>
                                <?php if ($search): ?><th>Warehouse</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stockItems as $i => $item):
                                $qty = (int)$item['quantity'];
                                if ($qty === 0)     $pill = '<span class="pill pill-out">Out</span>';
                                elseif ($qty < 5)   $pill = '<span class="pill pill-low">Low</span>';
                                else                $pill = '<span class="pill pill-ok">In Stock</span>';
                            ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="item-name"><?= htmlspecialchars($item['part_name']) ?></td>
                                <td><?= htmlspecialchars($item['category']) ?></td>
                                <td><?= $qty ?></td>
                                <td><?= $pill ?></td>
                                <td>₹<?= number_format($item['purchase_price'], 2) ?></td>
                                <td>₹<?= number_format($item['selling_price'], 2) ?></td>
                                <td><?= date('d M Y', strtotime($item['added_on'])) ?></td>
                                <?php if ($search): ?>
                                <td><?= htmlspecialchars($item['warehouse']) ?></td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php elseif ($search): ?>
            <div class="alert-banner" style="background:#fce8e8; border-color:#f5c6c6;">
                <i class="bi bi-exclamation-circle-fill" style="color:#c62828;"></i>
                <span style="color:#c62828;">No part found matching "<?= htmlspecialchars($search) ?>".</span>
            </div>

            <?php elseif ($selectedWarehouseId): ?>
            <div class="alert-banner" style="background:#fff8e1; border-color:#ffe082;">
                <i class="bi bi-info-circle-fill" style="color:#f59e0b;"></i>
                <span style="color:#7d5a00;">No items found in this warehouse.</span>
            </div>

            <?php else: ?>
            <div class="alert-banner" style="background:#e8f0fe; border-color:#90b4f5;">
                <i class="bi bi-info-circle-fill" style="color:#1a56db;"></i>
                <span style="color:#1a56db;">Select a warehouse above or search for a part name to get started.</span>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>
</body>
</html>