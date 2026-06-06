<?php
include 'config.php';
session_start();

$activePage = 'sales';
$success = '';
$error = '';

// Handle sale submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $part_id       = (int)$_POST['part_id'];
    $quantity_sold = (int)$_POST['quantity'];
    $billing_number = trim($_POST['billing_number']);

    // Get stock details
    $stockQuery = mysqli_query($conn, "SELECT * FROM stock WHERE id = '$part_id' LIMIT 1");
    $stock = mysqli_fetch_assoc($stockQuery);

    if ($stock) {
        $new_quantity = $stock['quantity'] - $quantity_sold;

        if ($new_quantity < 0) {
            $error = "Not enough stock! Only {$stock['quantity']} unit(s) available.";
        } else {
            $selling_price = (float)$stock['selling_price'];
            $total_price   = $selling_price * $quantity_sold;
            $cart_date     = date("Y-m-d H:i:s");

            // Deduct stock
            mysqli_query($conn, "UPDATE stock SET quantity = $new_quantity WHERE id = $part_id");

            // Insert into cart
            $cartInsert = "INSERT INTO cart (item_id, billing_number, item_qty, is_checkout, cart_date, selling_price, total_price)
                           VALUES ('$part_id', '$billing_number', '$quantity_sold', 1, '$cart_date', $selling_price, $total_price)";
            mysqli_query($conn, $cartInsert);

            $success = "Sale recorded successfully for billing #" . htmlspecialchars($billing_number) . "!";
        }
    } else {
        $error = "Selected part not found.";
    }
}

// Fetch stock items with quantity > 0
$stock_result = mysqli_query($conn, "SELECT id, part_name, quantity, selling_price FROM stock WHERE quantity > 0 ORDER BY part_name");

// Fetch recent sales from cart
$recentSales = mysqli_query($conn, "
    SELECT ct.billing_number, s.part_name, ct.item_qty, ct.selling_price, ct.total_price, ct.cart_date
    FROM cart ct
    JOIN stock s ON ct.item_id = s.id
    WHERE ct.is_checkout = 1
    ORDER BY ct.cart_date DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales – Panja Trading</title>
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
            <div class="topbar-title">Sales Management</div>
            <div class="topbar-actions">
                <a href="add_stock.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-plus-lg"></i> Add Stock
                </a>
                <a href="report.php" class="btn btn-sm btn-primary">
                    <i class="bi bi-bar-chart-line"></i> View Report
                </a>
            </div>
        </div>

        <div class="page-content">

            <?php if ($success): ?>
            <div class="alert-banner" style="background:#e8f5e9; border-color:#a5d6a7;">
                <i class="bi bi-check-circle-fill" style="color:#2e7d32;"></i>
                <span style="color:#1b5e20;"><?= $success ?></span>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert-banner" style="background:#fce8e8; border-color:#f5c6c6;">
                <i class="bi bi-exclamation-circle-fill" style="color:#c62828;"></i>
                <span style="color:#c62828;"><?= $error ?></span>
            </div>
            <?php endif; ?>

            <!-- Sale Form -->
            <div class="card-section" style="max-width: 620px;">
                <div class="card-section-header">
                    <span class="section-title"><i class="bi bi-receipt"></i> Process a Sale</span>
                </div>
                <div style="padding: 24px;">
                    <form method="POST" action="sales.php">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Billing Number</label>
                            <input type="text" name="billing_number" class="form-control"
                                   placeholder="Enter billing number" required>
                            <div class="form-text">Must match an existing customer billing number.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Select Product</label>
                            <select name="part_id" class="form-select" required>
                                <option value="">-- Select a Product --</option>
                                <?php while ($row = mysqli_fetch_assoc($stock_result)): ?>
                                <option value="<?= $row['id'] ?>">
                                    <?= htmlspecialchars($row['part_name']) ?>
                                    — Stock: <?= $row['quantity'] ?>
                                    — ₹<?= number_format($row['selling_price'], 2) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Quantity</label>
                            <input type="number" name="quantity" class="form-control"
                                   placeholder="Enter quantity" min="1" required>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Process Sale
                            </button>
                            <a href="inventory.php" class="btn btn-outline-secondary">View Stock</a>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Recent Sales -->
            <div class="card-section">
                <div class="card-section-header">
                    <span class="section-title">Recent Sales</span>
                    <a href="report.php" class="btn btn-sm btn-outline-secondary">
                        Full Report →
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Billing No.</th>
                                <th>Part Name</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                                <th>Date</th>
                                <th>Bill</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            while ($row = mysqli_fetch_assoc($recentSales)):
                            ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td>
                                    <span class="pill" style="background:#e8f0fe; color:#1a56db;">
                                        <?= htmlspecialchars($row['billing_number']) ?>
                                    </span>
                                </td>
                                <td class="item-name"><?= htmlspecialchars($row['part_name']) ?></td>
                                <td><?= $row['item_qty'] ?></td>
                                <td>₹<?= number_format($row['selling_price'], 2) ?></td>
                                <td style="font-weight:600;">₹<?= number_format($row['total_price'], 2) ?></td>
                                <td><?= date('d M Y', strtotime($row['cart_date'])) ?></td>
                                <td>
                                    <a href="print_bill.php?billing_number=<?= urlencode($row['billing_number']) ?>"
                                       target="_blank" class="icon-btn" title="Print Bill">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </td>
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