<?php
session_start();
include 'config.php';

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
    // Check if the part is in any warehouse
    if (isset($partToWarehouse[$searchLower])) {
        $warehouseId = $partToWarehouse[$searchLower];
        $stmt = $conn->prepare("SELECT * FROM stock WHERE LOWER(part_name) = ?");
        $stmt->bind_param("s", $searchLower);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['warehouse'] = $warehouseNames[$warehouseId];
            $stockItems[] = $row;
        }
    }
} elseif ($selectedWarehouseId && isset($warehouseItems[$selectedWarehouseId])) {
    // Show all items from selected warehouse
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
<html>
<head>
    <title>Storage — Panja Trading</title>
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
    <h1 class="page-title">Storage</h1>
</div>

<div class="content">

    <!-- Search & Warehouse Filter -->
    <div class="panel">
        <div class="panel-title">Search or Filter by Warehouse</div>

        <form method="get">
            <div class="form-row">
                <div class="form-group">
                    <label>Search Part Name</label>
                    <input type="text" name="search"
                           placeholder="e.g. Battery, Brakes..."
                           value="<?= htmlspecialchars($search ?? '') ?>">
                </div>
                <div class="form-group" style="display:flex;align-items:flex-end;gap:10px">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="Storage.php" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>

        <!-- Warehouse Buttons -->
        <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:20px">
            <?php foreach ($warehouseNames as $id => $name): ?>
                <a href="?warehouse=<?= $id ?>"
                   class="btn <?= ($selectedWarehouseId == $id) ? 'btn-primary' : 'btn-secondary' ?>">
                    📦 <?= htmlspecialchars($name) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Results -->
    <?php if (!empty($stockItems)): ?>

        <?php if ($selectedWarehouseId): ?>
            <div class="mb-2">
                <a href="Storage.php" class="btn btn-ghost btn-sm">← Back to All Warehouses</a>
            </div>
        <?php endif; ?>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Part Name</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Purchase Price</th>
                        <th>Selling Price</th>
                        <th>Added On</th>
                        <?php if ($search): ?><th>Warehouse</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stockItems as $i => $item): ?>
                    <tr>
                        <td class="text-muted"><?= $i + 1 ?></td>
                        <td style="font-weight:500"><?= htmlspecialchars($item['part_name']) ?></td>
                        <td><?= htmlspecialchars($item['category']) ?></td>
                        <td>
                            <?php if ($item['quantity'] < 5): ?>
                                <span class="badge badge-red"><?= $item['quantity'] ?> ⚠</span>
                            <?php else: ?>
                                <?= $item['quantity'] ?>
                            <?php endif; ?>
                        </td>
                        <td>₹<?= number_format($item['purchase_price'], 2) ?></td>
                        <td>₹<?= number_format($item['selling_price'], 2) ?></td>
                        <td class="text-muted"><?= date('d-m-Y', strtotime($item['added_on'])) ?></td>
                        <?php if ($search): ?>
                            <td><?= htmlspecialchars($item['warehouse']) ?></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($search): ?>
        <div class="alert alert-error">No part found with that name.</div>

    <?php elseif ($selectedWarehouseId): ?>
        <div class="alert alert-info">No items found in this warehouse.</div>

    <?php else: ?>
        <div class="alert alert-info">Select a warehouse above or search for a part name.</div>

    <?php endif; ?>

</div>

</body>
</html>
