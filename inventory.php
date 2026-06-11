<?php
session_start();
include 'config.php';

$activePage = 'inventory';
$activeTab  = $_GET['tab'] ?? 'stock';

// ── Category: Add ──────────────────────────────────────────
$catMessage = ''; $catMessageType = ''; $newCategory = '';
if ($activeTab === 'categories' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_name'])) {
    $newCategory = trim($_POST['category_name']);
    if (!empty($newCategory)) {
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $newCategory);
        $catMessage     = $stmt->execute() ? "Category \"" . htmlspecialchars($newCategory) . "\" added successfully." : "Error: " . $stmt->error;
        $catMessageType = $stmt->execute() ? "success" : "error";
        $stmt->close();
    } else {
        $catMessage = "Category name cannot be empty.";
        $catMessageType = "warning";
    }
}

// ── Item: Add ──────────────────────────────────────────────
$itemSuccess = ''; $itemError = '';
$product_name = $storage_id = $category_id = $bike_id = $variant_name = $model_year = $stock_units = $price_per_unit = $supplier = $compatible_cc = '';
if ($activeTab === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_name'])) {
    $product_name   = trim($_POST['product_name']);
    $storage_id     = $_POST['storage_id'];
    $category_id    = $_POST['category_id'];
    $bike_id        = $_POST['bike_id'];
    $variant_name   = trim($_POST['variant_name']);
    $model_year     = $_POST['model_year'];
    $supplier       = trim($_POST['supplier']);
    $stock_units    = $_POST['stock_units'];
    $price_per_unit = $_POST['price_per_unit'];
    $compatible_cc  = trim($_POST['compatible_cc'] ?? '');

    $stmt = $conn->prepare("INSERT INTO items (product_name, storage_id, category_id, bike_id, variant_name, model_year, stock_units, price_per_unit) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siiiisid", $product_name, $storage_id, $category_id, $bike_id, $variant_name, $model_year, $stock_units, $price_per_unit);

    if ($stmt->execute()) {
        $stmt->close();
        $catQuery = $conn->prepare("SELECT name FROM categories WHERE id = ?");
        $catQuery->bind_param("i", $category_id);
        $catQuery->execute();
        $catRow = $catQuery->get_result()->fetch_assoc();
        $catQuery->close();
        $category_name = $catRow['name'] ?? '';

        if (!empty($category_name)) {
            $added_on    = date('Y-m-d');
            // Insert into stock with compatible_cc
            $stockInsert = $conn->prepare("INSERT INTO stock (part_name, category, quantity, purchase_price, selling_price, supplier, added_on, compatible_cc) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stockInsert->bind_param("ssiddss s", $product_name, $category_name, $stock_units, $price_per_unit, $price_per_unit, $supplier, $added_on, $compatible_cc);
            $stockInsert->execute();
            $new_stock_id = $conn->insert_id;
            $stockInsert->close();

            // Auto-link to bikes with matching engine_capacity
            if (!empty($compatible_cc)) {
                $bikeMatch = $conn->prepare("SELECT id FROM bikes WHERE engine_capacity = ?");
                $bikeMatch->bind_param("s", $compatible_cc);
                $bikeMatch->execute();
                $bikeRes = $bikeMatch->get_result();
                while ($brow = $bikeRes->fetch_assoc()) {
                    $check = $conn->query("SELECT id FROM bike_parts WHERE bike_id={$brow['id']} AND stock_id=$new_stock_id");
                    if ($check->num_rows === 0) {
                        $conn->query("INSERT INTO bike_parts (bike_id, stock_id) VALUES ({$brow['id']}, $new_stock_id)");
                    }
                }
                $bikeMatch->close();
                $autoLinked = $bikeRes->num_rows ?? 0;
            }

            $itemSuccess = "Item \"" . htmlspecialchars($product_name) . "\" added successfully!";
            if (!empty($compatible_cc)) {
                $itemSuccess .= " Auto-linked to all {$compatible_cc} bike models.";
            }
            $product_name = $storage_id = $category_id = $bike_id = $variant_name = $model_year = $stock_units = $price_per_unit = $supplier = $compatible_cc = '';
        } else {
            $itemError = "Error: Category not found.";
        }
    } else {
        $itemError = "Error: " . $stmt->error;
        $stmt->close();
    }
}

// ── Shared data fetches ────────────────────────────────────
$storages   = $conn->query("SELECT id, name FROM storages");
$categories = $conn->query("SELECT id, name FROM categories ORDER BY id");
$bikes      = $conn->query("SELECT id, bike_name, brand, engine_capacity FROM bikes");

// Distinct engine capacities for the cc datalist
$ccList = [];
$ccRes  = $conn->query("SELECT DISTINCT engine_capacity FROM bikes WHERE engine_capacity IS NOT NULL AND engine_capacity != '' ORDER BY engine_capacity");
while ($r = $ccRes->fetch_assoc()) $ccList[] = $r['engine_capacity'];

// Categories list for category tab
$catList = [];
$catRes  = $conn->query("SELECT name FROM categories ORDER BY id");
while ($r = $catRes->fetch_assoc()) $catList[] = $r['name'];

// Stock grouped by category
$grouped = [];
$stockAll = $conn->query("SELECT * FROM stock ORDER BY category, part_name");
while ($r = $stockAll->fetch_assoc()) $grouped[$r['category']][] = $r;

// Warehouse data
$warehouseItems = [
    1 => ['Battery','Spark Plug','Valve','Chain Spoket','Tyres','Gearing','Bulb','Gasket',
          'Injector','Air Filter','Block Key','Headlight Assembly','Tail Light Cover','Kick Lever',
          'Horn','Throttle Cable','Radiator Coolant','Speedometer Assembly','Swing Arm','Brake Shoe',
          'Fuel Tank Cap','Ignition Coil','Number Plate Frame','Clutch Plate','Shock Absorber','Side Stand/Main Stand'],
    2 => ['Brakes','Cable','Engine Oil Lubricant','Oil Seal','Curvator','Oil Filter','Piston Kit',
          'Rear View Mirror','Meter Cable','Mudguard','Fuel Tap','Handle Grip Set','Clutch Lever',
          'Brake Disc','Gear Lever','Front Fork','Tool Kit','Saree Guard','Chain Cover',
          'Kick Starter Spring','Side Panel Set','Camshaft','Head Gasket','Exhaust Pipe','Main Stand','Mirror']
];
$warehouseNames = [];
$whRes = mysqli_query($conn, "SELECT id, name FROM storages");
while ($r = mysqli_fetch_assoc($whRes)) $warehouseNames[$r['id']] = $r['name'];

$partToWarehouse = [];
foreach ($warehouseItems as $wid => $items) {
    foreach ($items as $part) $partToWarehouse[strtolower($part)] = $wid;
}

$whSearch      = $_GET['search'] ?? null;
$selectedWhId  = $_GET['warehouse'] ?? null;
$whStockItems  = [];

if ($activeTab === 'warehouse') {
    if ($whSearch) {
        $sl = strtolower(trim($whSearch));
        if (isset($partToWarehouse[$sl])) {
            $wid  = $partToWarehouse[$sl];
            $stmt = $conn->prepare("SELECT * FROM stock WHERE LOWER(part_name) = ?");
            $stmt->bind_param("s", $sl);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) { $r['warehouse'] = $warehouseNames[$wid] ?? '—'; $whStockItems[] = $r; }
        }
    } elseif ($selectedWhId && isset($warehouseItems[$selectedWhId])) {
        $ph   = implode(',', array_fill(0, count($warehouseItems[$selectedWhId]), '?'));
        $types = str_repeat('s', count($warehouseItems[$selectedWhId]));
        $stmt = $conn->prepare("SELECT * FROM stock WHERE part_name IN ($ph)");
        $stmt->bind_param($types, ...$warehouseItems[$selectedWhId]);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $whStockItems[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory – GearVault</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <style>
        .tab-nav {
            display: flex; gap: 2px; background: #f0f4f8; border-radius: 10px;
            padding: 4px; margin-bottom: 20px; width: fit-content;
        }
        .tab-btn {
            padding: 7px 18px; border-radius: 7px; border: none; background: transparent;
            font-size: 13px; font-weight: 500; color: #6b7a8d; cursor: pointer;
            transition: all 0.15s; display: flex; align-items: center; gap: 6px; text-decoration: none;
        }
        .tab-btn:hover { background: #fff; color: #1e2a3a; }
        .tab-btn.active { background: #fff; color: #1e2a3a; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* cc field hint */
        .cc-hint {
            font-size: .75rem; color: #6b7a8d; margin-top: 5px;
            display: flex; align-items: center; gap: 5px;
        }
        .cc-tag {
            display: inline-block; background: #e3f2fd; color: #1565c0;
            border-radius: 4px; padding: 1px 7px; font-size: .72rem; font-weight: 600;
        }
    </style>
</head>
<body>
<div class="app-shell">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-area">
        <div class="topbar">
            <div class="topbar-title">Inventory</div>
            <div class="topbar-actions">
                <input type="text" id="stockSearch" class="search-input" placeholder="Search stock..."
                       style="display:none">
                <a href="add_stock.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-plus-lg"></i> Quick Add Stock
                </a>
                <a href="add_workshop_usage.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-hammer"></i> Workshop Usage
                </a>
            </div>
        </div>

        <div class="page-content">

            <!-- Tab Navigation -->
            <div class="tab-nav">
                <a href="?tab=stock"      class="tab-btn <?= $activeTab==='stock'      ? 'active':'' ?>"><i class="bi bi-box-seam"></i> Stock List</a>
                <a href="?tab=add"        class="tab-btn <?= $activeTab==='add'        ? 'active':'' ?>"><i class="bi bi-plus-circle"></i> Add Item</a>
                <a href="?tab=categories" class="tab-btn <?= $activeTab==='categories' ? 'active':'' ?>"><i class="bi bi-tags"></i> Categories</a>
                <a href="?tab=warehouse"  class="tab-btn <?= $activeTab==='warehouse'  ? 'active':'' ?>"><i class="bi bi-building"></i> Warehouse</a>
            </div>

            <!-- ══════════════════════════════════════════════
                 TAB 1: Stock List
            ═══════════════════════════════════════════════ -->
            <?php if ($activeTab === 'stock'):
                $lowStockRes   = $conn->query("SELECT part_name, quantity, category FROM stock WHERE quantity < 5 ORDER BY quantity ASC");
                $lowStockCount = $lowStockRes->num_rows;
                $lowRows       = [];
                while ($lr = $lowStockRes->fetch_assoc()) $lowRows[] = $lr;

                $groupedStock = [];
                $allStock = $conn->query("SELECT * FROM stock ORDER BY category, part_name");
                while ($sr = $allStock->fetch_assoc()) $groupedStock[$sr['category']][] = $sr;
            ?>

            <?php if ($lowStockCount > 0): ?>
            <div class="alert-banner" style="background:#fff8e1; border-color:#ffe082; flex-wrap:wrap; gap:8px;">
                <i class="bi bi-exclamation-triangle-fill" style="color:#f59e0b; font-size:16px;"></i>
                <div style="flex:1;">
                    <strong style="color:#7d5a00;"><?= $lowStockCount ?> item<?= $lowStockCount > 1 ? 's' : '' ?> running low on stock</strong>
                    <div style="margin-top:6px; display:flex; flex-wrap:wrap; gap:6px;">
                        <?php foreach ($lowRows as $lr): ?>
                        <span style="background:#fff3cd; border:1px solid #ffe082; border-radius:20px;
                                     padding:2px 10px; font-size:11px; color:#7d5a00;">
                            <?= htmlspecialchars($lr['part_name']) ?> <strong>(<?= $lr['quantity'] ?>)</strong>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="card-section" style="padding:14px 18px;">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-search" style="color:#6b7a8d;"></i>
                    <input type="text" id="stockSearchInline" class="form-control"
                           placeholder="Search across all categories..." style="border:none; box-shadow:none; padding:0;">
                </div>
            </div>

            <?php if (empty($groupedStock)): ?>
            <div class="alert-banner" style="background:#e8f0fe; border-color:#90b4f5;">
                <i class="bi bi-info-circle-fill" style="color:#1a56db;"></i>
                <span style="color:#1a56db;">No stock items found. <a href="?tab=add" style="color:#1a56db; font-weight:600;">Add your first item →</a></span>
            </div>
            <?php else: ?>
            <?php foreach ($groupedStock as $cat => $items):
                $catLowCount = count(array_filter($items, fn($i) => (int)$i['quantity'] < 5));
            ?>
            <div class="card-section category-block">
                <div class="card-section-header">
                    <span class="section-title"><i class="bi bi-tag"></i> <?= htmlspecialchars($cat ?: 'Uncategorized') ?></span>
                    <div class="d-flex align-items-center gap-2">
                        <?php if ($catLowCount > 0): ?>
                        <span style="font-size:11px; background:#fff3cd; color:#7d5a00; padding:3px 10px; border-radius:20px; font-weight:600;">
                            <i class="bi bi-exclamation-triangle"></i> <?= $catLowCount ?> low
                        </span>
                        <?php endif; ?>
                        <span style="font-size:11px; background:#f0f4f8; color:#6b7a8d; padding:3px 10px; border-radius:20px;">
                            <?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?>
                        </span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Part Name</th>
                                <th>Compatible CC</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Purchase Price</th>
                                <th>Selling Price</th>
                                <th>Supplier</th>
                                <th>Added On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $i => $item):
                                $qty  = (int)$item['quantity'];
                                $pill = $qty === 0
                                    ? '<span class="pill pill-out">Out</span>'
                                    : ($qty < 5 ? '<span class="pill pill-low">Low</span>' : '<span class="pill pill-ok">In Stock</span>');
                            ?>
                            <tr class="stock-row">
                                <td><?= $i + 1 ?></td>
                                <td class="item-name"><?= htmlspecialchars($item['part_name']) ?></td>
                                <td>
                                    <?php if (!empty($item['compatible_cc'])): ?>
                                    <span class="cc-tag"><?= htmlspecialchars($item['compatible_cc']) ?></span>
                                    <?php else: ?>
                                    <span style="color:#bbb; font-size:.8rem;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $qty ?></td>
                                <td><?= $pill ?></td>
                                <td>₹<?= number_format($item['purchase_price'], 2) ?></td>
                                <td>₹<?= number_format($item['selling_price'], 2) ?></td>
                                <td><?= htmlspecialchars($item['supplier'] ?? '—') ?></td>
                                <td><?= date('d M Y', strtotime($item['added_on'])) ?></td>
                                <td>
                                    <a href="workshop_usage.php?part_id=<?= $item['id'] ?>"
                                       class="icon-btn" title="View Workshop Usage">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <!-- ══════════════════════════════════════════════
                 TAB 2: Add Item
            ═══════════════════════════════════════════════ -->
            <?php elseif ($activeTab === 'add'): ?>

            <?php if ($itemSuccess): ?>
            <div class="alert-banner" style="background:#e8f5e9; border-color:#a5d6a7;">
                <i class="bi bi-check-circle-fill" style="color:#2e7d32;"></i>
                <span style="color:#1b5e20;"><?= $itemSuccess ?></span>
                <a href="?tab=stock" class="ms-auto alert-link" style="color:#1b5e20;">View Stock →</a>
            </div>
            <?php endif; ?>
            <?php if ($itemError): ?>
            <div class="alert-banner" style="background:#fce8e8; border-color:#f5c6c6;">
                <i class="bi bi-exclamation-circle-fill" style="color:#c62828;"></i>
                <span style="color:#c62828;"><?= $itemError ?></span>
            </div>
            <?php endif; ?>

            <div class="card-section" style="max-width:820px;">
                <div class="card-section-header">
                    <span class="section-title"><i class="bi bi-box-seam"></i> Item Details</span>
                </div>
                <div style="padding:24px;">
                    <form method="POST" action="?tab=add" novalidate>
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
                                    <option value="" disabled selected>-- Select Storage --</option>
                                    <?php $storages->data_seek(0); while ($r = $storages->fetch_assoc()): ?>
                                    <option value="<?= $r['id'] ?>" <?= $storage_id==$r['id']?'selected':'' ?>><?= htmlspecialchars($r['name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Category</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="" disabled selected>-- Select Category --</option>
                                    <?php $categories->data_seek(0); while ($r = $categories->fetch_assoc()): ?>
                                    <option value="<?= $r['id'] ?>" <?= $category_id==$r['id']?'selected':'' ?>><?= htmlspecialchars($r['name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Bike Model</label>
                                <select name="bike_id" class="form-select" required>
                                    <option value="" disabled selected>-- Select Bike Model --</option>
                                    <?php $bikes->data_seek(0); while ($r = $bikes->fetch_assoc()):
                                        $d = htmlspecialchars($r['bike_name']);
                                        if ($r['brand']) $d .= " (".htmlspecialchars($r['brand']).")";
                                    ?>
                                    <option value="<?= $r['id'] ?>" <?= $bike_id==$r['id']?'selected':'' ?>><?= $d ?></option>
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
                                       placeholder="Enter supplier name"
                                       value="<?= htmlspecialchars($supplier) ?>">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Model Year</label>
                                <select name="model_year" class="form-select" required>
                                    <option value="" disabled selected>-- Select Year --</option>
                                    <?php for ($y = date("Y"); $y >= 2010; $y--): ?>
                                    <option value="<?= $y ?>" <?= $model_year==$y?'selected':'' ?>><?= $y ?></option>
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

                        <!-- ── Compatible Engine Capacity ── -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">
                                    Compatible Engine Capacity
                                    <span style="font-weight:400; color:#6b7a8d; font-size:.8rem;">(optional)</span>
                                </label>
                                <input type="text" name="compatible_cc" id="compatible_cc"
                                       class="form-control"
                                       placeholder="e.g. 100cc, 150cc"
                                       list="ccSuggestions"
                                       value="<?= htmlspecialchars($compatible_cc) ?>">
                                <datalist id="ccSuggestions">
                                    <?php foreach ($ccList as $cc): ?>
                                    <option value="<?= htmlspecialchars($cc) ?>">
                                    <?php endforeach; ?>
                                </datalist>
                                <div class="cc-hint">
                                    <i class="bi bi-lightning-fill" style="color:#f59e0b;"></i>
                                    Part will auto-link to all bikes with this engine capacity
                                </div>
                            </div>
                            <div class="col-md-8 d-flex align-items-end pb-1">
                                <?php if (!empty($ccList)): ?>
                                <div style="background:#f0f4f8; border-radius:8px; padding:10px 14px; font-size:.8rem; color:#4a5568; width:100%;">
                                    <strong style="display:block; margin-bottom:6px; color:#1e2a3a;">
                                        <i class="bi bi-bicycle"></i> Existing CC groups in your fleet:
                                    </strong>
                                    <div style="display:flex; flex-wrap:wrap; gap:6px;">
                                        <?php foreach ($ccList as $cc): ?>
                                        <span class="cc-tag" style="cursor:pointer;"
                                              onclick="document.getElementById('compatible_cc').value='<?= htmlspecialchars($cc) ?>'">
                                            <?= htmlspecialchars($cc) ?>
                                        </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Item</button>
                            <a href="?tab=stock" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════
                 TAB 3: Categories
            ═══════════════════════════════════════════════ -->
            <?php elseif ($activeTab === 'categories'): ?>

            <?php if ($catMessage):
                $bg    = $catMessageType==='success' ? '#e8f5e9' : ($catMessageType==='error' ? '#fce8e8' : '#fff8e1');
                $bc    = $catMessageType==='success' ? '#a5d6a7' : ($catMessageType==='error' ? '#f5c6c6' : '#ffe082');
                $color = $catMessageType==='success' ? '#1b5e20' : ($catMessageType==='error' ? '#c62828' : '#7d5a00');
                $icon  = $catMessageType==='success' ? 'check-circle-fill' : ($catMessageType==='error' ? 'exclamation-circle-fill' : 'exclamation-triangle-fill');
            ?>
            <div class="alert-banner" style="background:<?= $bg ?>; border-color:<?= $bc ?>;">
                <i class="bi bi-<?= $icon ?>" style="color:<?= $color ?>;"></i>
                <span style="color:<?= $color ?>;"><?= $catMessage ?></span>
                <?php if ($catMessageType === 'success'): ?>
                <a href="?tab=stock" class="ms-auto alert-link" style="color:<?= $color ?>;">View in Stock List →</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="card-section" style="max-width:680px;">
                <div class="card-section-header">
                    <span class="section-title"><i class="bi bi-tags"></i> Add New Category</span>
                </div>
                <div style="padding:20px;">
                    <form method="POST" action="?tab=categories">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Category Name</label>
                                <input type="text" name="category_name" class="form-control"
                                       placeholder="e.g. Brakes, Tyres, Engine" required autofocus>
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

            <div class="card-section" style="max-width:680px;">
                <div class="card-section-header">
                    <span class="section-title"><i class="bi bi-list-ul"></i> Existing Categories</span>
                    <span style="font-size:11px; background:#f0f4f8; color:#6b7a8d; padding:3px 10px; border-radius:20px;">
                        <?= count($catList) ?> total
                    </span>
                </div>
                <div style="padding:16px 20px;">
                    <?php if (empty($catList)): ?>
                    <p class="text-muted" style="font-size:13px;">No categories yet. Add your first one above.</p>
                    <?php else: ?>
                    <div style="display:flex; flex-wrap:wrap; gap:8px;">
                        <?php foreach ($catList as $cat):
                            $itemCount = count($grouped[$cat] ?? []);
                            $isNew = ($cat === $newCategory);
                        ?>
                        <div style="display:flex; align-items:center; gap:6px;
                                    background:<?= $isNew ? '#e8f0fe' : '#f8fafc' ?>;
                                    border:1px solid <?= $isNew ? '#90b4f5' : '#e2e8f0' ?>;
                                    border-radius:8px; padding:6px 12px; font-size:13px;">
                            <i class="bi bi-tag" style="color:<?= $isNew ? '#1a56db' : '#6b7a8d' ?>;"></i>
                            <span style="font-weight:500; color:<?= $isNew ? '#1a56db' : '#1e2a3a' ?>"><?= htmlspecialchars($cat) ?></span>
                            <span style="font-size:11px; color:#9aa5b4; margin-left:2px;"><?= $itemCount ?> item<?= $itemCount !== 1 ? 's' : '' ?></span>
                            <?php if ($isNew): ?>
                            <span style="font-size:10px; background:#1a56db; color:#fff; padding:1px 7px; border-radius:20px; margin-left:2px;">New</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top:14px; font-size:12px; color:#9aa5b4;">
                        <i class="bi bi-info-circle"></i>
                        Items added via <a href="?tab=add" style="color:#4f8cff;">Add Item</a> tab
                        will appear under their category in the
                        <a href="?tab=stock" style="color:#4f8cff;">Stock List</a> tab automatically.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════
                 TAB 4: Warehouse
            ═══════════════════════════════════════════════ -->
            <?php elseif ($activeTab === 'warehouse'): ?>

            <div class="card-section">
                <div class="card-section-header">
                    <span class="section-title"><i class="bi bi-building"></i> Search or Filter by Warehouse</span>
                    <?php if ($whSearch || $selectedWhId): ?>
                    <a href="?tab=warehouse" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                    <?php endif; ?>
                </div>
                <div style="padding:20px;">
                    <form method="GET" action="inventory.php">
                        <input type="hidden" name="tab" value="warehouse">
                        <div class="row g-3 align-items-end mb-4">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Search Part Name</label>
                                <input type="text" name="search" class="form-control"
                                       placeholder="e.g. Battery, Brakes..."
                                       value="<?= htmlspecialchars($whSearch ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </form>
                    <div class="stat-label mb-2">Browse by Warehouse</div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($warehouseNames as $id => $name): ?>
                        <a href="?tab=warehouse&warehouse=<?= $id ?>"
                           class="btn btn-sm <?= $selectedWhId==$id ? 'btn-primary' : 'btn-outline-secondary' ?>">
                            <i class="bi bi-box-seam"></i> <?= htmlspecialchars($name) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($whStockItems)): ?>
            <div class="card-section">
                <div class="card-section-header">
                    <span class="section-title">
                        <?php if ($selectedWhId && isset($warehouseNames[$selectedWhId])): ?>
                            <i class="bi bi-building"></i> <?= htmlspecialchars($warehouseNames[$selectedWhId]) ?>
                        <?php elseif ($whSearch): ?>
                            <i class="bi bi-search"></i> Results for "<?= htmlspecialchars($whSearch) ?>"
                        <?php endif; ?>
                    </span>
                    <span style="font-size:12px; color:#6b7a8d;"><?= count($whStockItems) ?> item<?= count($whStockItems)!==1?'s':'' ?></span>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th><th>Part Name</th><th>Category</th><th>Qty</th>
                                <th>Status</th><th>Purchase</th><th>Selling</th><th>Added On</th>
                                <?php if ($whSearch): ?><th>Warehouse</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($whStockItems as $i => $item):
                                $qty = (int)$item['quantity'];
                                $pill = $qty===0 ? '<span class="pill pill-out">Out</span>' : ($qty<5 ? '<span class="pill pill-low">Low</span>' : '<span class="pill pill-ok">In Stock</span>');
                            ?>
                            <tr>
                                <td><?= $i+1 ?></td>
                                <td class="item-name"><?= htmlspecialchars($item['part_name']) ?></td>
                                <td><?= htmlspecialchars($item['category']) ?></td>
                                <td><?= $qty ?></td>
                                <td><?= $pill ?></td>
                                <td>₹<?= number_format($item['purchase_price'],2) ?></td>
                                <td>₹<?= number_format($item['selling_price'],2) ?></td>
                                <td><?= date('d M Y', strtotime($item['added_on'])) ?></td>
                                <?php if ($whSearch): ?><td><?= htmlspecialchars($item['warehouse']) ?></td><?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php elseif ($whSearch): ?>
            <div class="alert-banner" style="background:#fce8e8; border-color:#f5c6c6;">
                <i class="bi bi-exclamation-circle-fill" style="color:#c62828;"></i>
                <span style="color:#c62828;">No part found matching "<?= htmlspecialchars($whSearch) ?>".</span>
            </div>
            <?php elseif ($selectedWhId): ?>
            <div class="alert-banner" style="background:#fff8e1; border-color:#ffe082;">
                <i class="bi bi-info-circle-fill" style="color:#f59e0b;"></i>
                <span style="color:#7d5a00;">No items found in this warehouse.</span>
            </div>
            <?php else: ?>
            <div class="alert-banner" style="background:#e8f0fe; border-color:#90b4f5;">
                <i class="bi bi-info-circle-fill" style="color:#1a56db;"></i>
                <span style="color:#1a56db;">Select a warehouse or search for a part name.</span>
            </div>
            <?php endif; ?>

            <?php endif; // end tab conditions ?>

        </div>
    </div>
</div>

<script>
<?php if ($activeTab === 'stock'): ?>
document.getElementById('stockSearchInline').addEventListener('keyup', function () {
    const kw = this.value.toLowerCase().trim();
    document.querySelectorAll('.category-block').forEach(block => {
        let visibleRows = 0;
        block.querySelectorAll('.stock-row').forEach(row => {
            const match = row.innerText.toLowerCase().includes(kw);
            row.style.display = match ? '' : 'none';
            if (match) visibleRows++;
        });
        block.style.display = (kw === '' || visibleRows > 0) ? '' : 'none';
    });
});
<?php endif; ?>
window.addEventListener('load', function () {
    const el = document.getElementById('new-category');
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
});
</script>
</body>
</html>