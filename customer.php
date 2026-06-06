<?php
include 'config.php';
session_start();

$activePage = 'customer';
$showSuccess = false;
$showCartFor = null;

// Fetch stock items for dropdown
$stockItems = $conn->query("SELECT id, part_name, selling_price FROM stock ORDER BY part_name");

// Handle customer submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['customer_name'])) {
    $billing_number  = $conn->real_escape_string($_POST['billing_number']);
    $customer_name   = $conn->real_escape_string($_POST['customer_name']);
    $mobile_number   = $conn->real_escape_string($_POST['mobile_number']);
    $mode_of_payment = $conn->real_escape_string($_POST['mode_of_payment']);
    $vin_no          = $conn->real_escape_string($_POST['vin_no']);
    $item_id         = (int)$_POST['item_id'];
    $item_qty        = (int)$_POST['item_qty'];
    $billing_date    = date("Y-m-d H:i:s");

    $insert = "INSERT INTO customers (billing_number, customer_name, mobile_number, mode_of_payment, billing_date, vin_no)
               VALUES ('$billing_number', '$customer_name', '$mobile_number', '$mode_of_payment', '$billing_date', '$vin_no')";

    if ($conn->query($insert)) {
        $customer_id  = $conn->insert_id;
        $priceResult  = $conn->query("SELECT selling_price FROM stock WHERE id = $item_id LIMIT 1");
        if ($priceResult && $priceResult->num_rows > 0) {
            $row           = $priceResult->fetch_assoc();
            $selling_price = (float)$row['selling_price'];
            $total_price   = $selling_price * $item_qty;
            $cart_date     = date("Y-m-d H:i:s");

            $insertCart = "INSERT INTO cart (item_id, billing_number, item_qty, is_checkout, cart_date, selling_price, total_price)
                           VALUES ('$item_id', '$billing_number', '$item_qty', 1, '$cart_date', $selling_price, $total_price)";
            $conn->query($insertCart);
            $showSuccess = true;
            $showCartFor = $billing_number;
        }
    }
}

// Fetch existing customers
$customers = $conn->query("SELECT * FROM customers ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Details – Panja Trading</title>
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
            <div class="topbar-title">Customer Details</div>
            <div class="topbar-actions">
                <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>

        <div class="page-content">

            <?php if ($showSuccess): ?>
            <div class="alert-banner" style="background:#e8f5e9; border-color:#a5d6a7;">
                <i class="bi bi-check-circle-fill" style="color:#2e7d32;"></i>
                <span style="color:#1b5e20;">Customer and item added to cart successfully.</span>
            </div>
            <?php endif; ?>

            <!-- Add Customer Form -->
            <div class="card-section">
                <div class="card-section-header">
                    <span class="section-title"><i class="bi bi-person-plus"></i> Add Customer & Item</span>
                </div>
                <div style="padding: 24px;">
                    <form method="POST">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Billing Number</label>
                                <input type="text" name="billing_number" class="form-control"
                                       placeholder="Billing Number" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Customer Name</label>
                                <input type="text" name="customer_name" class="form-control"
                                       placeholder="Customer Name" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Mobile Number</label>
                                <input type="text" name="mobile_number" class="form-control"
                                       placeholder="10-digit mobile"
                                       pattern="\d{10}" maxlength="10" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Payment Mode</label>
                                <select name="mode_of_payment" class="form-select" required>
                                    <option value="">Select Payment Mode</option>
                                    <option value="Cash">Cash</option>
                                    <option value="UPI">UPI</option>
                                    <option value="Card">Card</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Bike VIN Number</label>
                                <input type="text" name="vin_no" class="form-control"
                                       placeholder="e.g. AS01..." required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Quantity</label>
                                <input type="number" name="item_qty" class="form-control"
                                       placeholder="Quantity" min="1" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Select Item</label>
                                <select name="item_id" class="form-select" required>
                                    <option value="">Select Item</option>
                                    <?php while ($part = $stockItems->fetch_assoc()): ?>
                                    <option value="<?= $part['id'] ?>">
                                        <?= htmlspecialchars($part['part_name']) ?> — ₹<?= number_format($part['selling_price'], 2) ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-cart-plus"></i> Add Customer & Item
                            </button>
                            <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Cart Preview -->
            <?php if ($showSuccess && $showCartFor !== null):
                $cartItems = $conn->query("SELECT c.item_qty, c.selling_price, c.total_price, s.part_name
                                           FROM cart c
                                           JOIN stock s ON c.item_id = s.id
                                           WHERE c.billing_number = '" . $conn->real_escape_string($showCartFor) . "'
                                           AND c.is_checkout = 1");
            ?>
            <div class="card-section">
                <div class="card-section-header">
                    <span class="section-title">
                        <i class="bi bi-cart"></i> Cart — Billing #<?= htmlspecialchars($showCartFor) ?>
                    </span>
                    <div class="d-flex gap-2">
                        <a href="print_bill.php?billing_number=<?= urlencode($showCartFor) ?>"
                           target="_blank" class="btn btn-sm btn-primary">
                            <i class="bi bi-printer"></i> Print Bill
                        </a>
                        <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Reset
                        </a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Part Name</th>
                                <th>Quantity</th>
                                <th>Selling Price</th>
                                <th>Total Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($cartItems && $cartItems->num_rows > 0): ?>
                            <?php while ($item = $cartItems->fetch_assoc()): ?>
                            <tr>
                                <td class="item-name"><?= htmlspecialchars($item['part_name']) ?></td>
                                <td><?= (int)$item['item_qty'] ?></td>
                                <td>₹<?= number_format($item['selling_price'], 2) ?></td>
                                <td>₹<?= number_format($item['total_price'], 2) ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-3 text-muted">No items in cart.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Existing Customers -->
            <div class="card-section">
                <div class="card-section-header">
                    <span class="section-title"><i class="bi bi-people"></i> Existing Customers</span>
                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleCustomers()">
                        <i class="bi bi-chevron-down" id="toggleIcon"></i> Show / Hide
                    </button>
                </div>
                <div id="existingCustomers" style="display:none;">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Billing No</th>
                                    <th>Name</th>
                                    <th>Mobile</th>
                                    <th>Payment</th>
                                    <th>VIN</th>
                                    <th>Date</th>
                                    <th>Bill</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($customers->num_rows > 0):
                                    $i = 1;
                                    while ($row = $customers->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= htmlspecialchars($row['billing_number']) ?></td>
                                    <td class="item-name"><?= htmlspecialchars($row['customer_name']) ?></td>
                                    <td><?= htmlspecialchars($row['mobile_number']) ?></td>
                                    <td>
                                        <span class="pill pill-ok" style="background:#e8f0fe; color:#1a56db;">
                                            <?= htmlspecialchars($row['mode_of_payment']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row['vin_no']) ?></td>
                                    <td><?= date('d M Y', strtotime($row['billing_date'])) ?></td>
                                    <td>
                                        <a href="print_bill.php?billing_number=<?= urlencode($row['billing_number']) ?>"
                                           target="_blank" class="icon-btn" title="Print Bill">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile;
                                else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-3 text-muted">No customers yet.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function toggleCustomers() {
    const el   = document.getElementById('existingCustomers');
    const icon = document.getElementById('toggleIcon');
    const open = el.style.display === 'none';
    el.style.display  = open ? 'block' : 'none';
    icon.className    = open ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
}
</script>
</body>
</html>