<?php
include 'config.php';

$showSuccess = false;
$showCartFor = null;
// Fetch stock items for dropdown
$stockItems = $conn->query("SELECT id, part_name, selling_price FROM stock ORDER BY part_name");

// Handle customer submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['customer_name'])) {
    $billing_number = $conn->real_escape_string($_POST['billing_number']);
    $customer_name = $conn->real_escape_string($_POST['customer_name']);
    $mobile_number = $conn->real_escape_string($_POST['mobile_number']);
    $mode_of_payment = $conn->real_escape_string($_POST['mode_of_payment']);
    $vin_no = $conn->real_escape_string($_POST['vin_no']);
    $item_id = (int)$_POST['item_id'];
    $item_qty = (int)$_POST['item_qty'];

    $billing_date = date("Y-m-d H:i:s");

    // Insert customer first
    $insert = "INSERT INTO customers (billing_number, customer_name, mobile_number, mode_of_payment, billing_date, vin_no) 
               VALUES ('$billing_number', '$customer_name', '$mobile_number', '$mode_of_payment', '$billing_date', '$vin_no')";

    if ($conn->query($insert)) {
        $customer_id = $conn->insert_id;

        // Get selling price from stock for selected item
        $priceResult = $conn->query("SELECT selling_price FROM stock WHERE id = $item_id LIMIT 1");
        if ($priceResult && $priceResult->num_rows > 0) {
            $row = $priceResult->fetch_assoc();
            $selling_price = (float)$row['selling_price'];
            $total_price = $selling_price * $item_qty;

            $cart_date = date("Y-m-d H:i:s");

            // Insert into cart with price info
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
<html>
<head>
    <title>Customer Details — Panja Trading</title>
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
    <h1 class="page-title">Customer Details</h1>
</div>

<div class="content">

    <!-- Add Customer Form -->
    <div class="panel">
        <div class="panel-title">Add Customer & Item</div>

        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Billing Number</label>
                    <input type="text" name="billing_number" placeholder="Billing Number" required>
                </div>
                <div class="form-group">
                    <label>Customer Name</label>
                    <input type="text" name="customer_name" placeholder="Customer Name" required>
                </div>
                <div class="form-group">
                    <label>Mobile Number</label>
                    <input type="text" name="mobile_number" placeholder="10-digit mobile"
                           pattern="\d{10}" title="Enter exactly 10 digits" maxlength="10" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Payment Mode</label>
                    <select name="mode_of_payment" required>
                        <option value="">Select Payment Mode</option>
                        <option value="Cash">Cash</option>
                        <option value="UPI">UPI</option>
                        <option value="Card">Card</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Bike VIN Number</label>
                    <input type="text" name="vin_no" placeholder="e.g. AS01..." required>
                </div>
                <div class="form-group">
                    <label>Select Item</label>
                    <select name="item_id" required>
                        <option value="">Select Item</option>
                        <?php while($part = $stockItems->fetch_assoc()): ?>
                            <option value="<?= $part['id'] ?>">
                                <?= htmlspecialchars($part['part_name']) ?> - ₹<?= number_format($part['selling_price'], 2) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Quantity</label>
                    <input type="number" name="item_qty" placeholder="Quantity" min="1" required>
                </div>
                <div class="form-group"></div>
                <div class="form-group"></div>
            </div>

            <div class="flex gap-2 mt-2">
                <button type="submit" class="btn btn-primary">Add Customer & Item</button>
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </form>
    </div>

    <?php if ($showSuccess): ?>
        <script>alert("Added to cart successfully");</script>
    <?php endif; ?>

    <!-- Cart Preview -->
    <?php if ($showSuccess && $showCartFor !== null):
        $cartItems = $conn->query("SELECT c.item_qty, c.selling_price, c.total_price, s.part_name 
                                   FROM cart c 
                                   JOIN stock s ON c.item_id = s.id 
                                   WHERE c.billing_number = '".$conn->real_escape_string($showCartFor)."' 
                                   AND c.is_checkout = 1");
    ?>
    <div class="panel">
        <div class="panel-title">Cart — Billing #<?= htmlspecialchars($showCartFor) ?></div>

        <?php if ($cartItems && $cartItems->num_rows > 0): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Part Name</th>
                            <th>Quantity</th>
                            <th>Selling Price (₹)</th>
                            <th>Total Price (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $cartItems->fetch_assoc()): ?>
                        <tr>
                            <td style="font-weight:500"><?= htmlspecialchars($item['part_name']) ?></td>
                            <td><?= (int)$item['item_qty'] ?></td>
                            <td>₹<?= number_format($item['selling_price'], 2) ?></td>
                            <td>₹<?= number_format($item['total_price'], 2) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="flex gap-2 mt-3">
                <a href="print_bill.php?billing_number=<?= urlencode($showCartFor) ?>"
                   target="_blank" class="btn btn-success">
                    🖨️ Print Bill
                </a>
                <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-danger">
                    🔄 Reset Customer
                </a>
            </div>

        <?php else: ?>
            <p class="text-muted">No items in the cart yet.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Existing Customers -->
    <div class="panel">
        <div class="panel-title" style="justify-content:space-between">
            <span>Existing Customers</span>
            <button class="btn btn-secondary btn-sm" onclick="toggleCustomers()">Show / Hide</button>
        </div>

        <div id="existingCustomers" style="display:none;margin-top:16px">
            <?php if ($customers->num_rows > 0): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
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
                            <?php while($row = $customers->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['billing_number']) ?></td>
                                <td style="font-weight:500"><?= htmlspecialchars($row['customer_name']) ?></td>
                                <td><?= htmlspecialchars($row['mobile_number']) ?></td>
                                <td><span class="badge badge-blue"><?= htmlspecialchars($row['mode_of_payment']) ?></span></td>
                                <td class="text-muted"><?= htmlspecialchars($row['vin_no']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($row['billing_date']) ?></td>
                                <td>
                                    <a href="print_bill.php?billing_number=<?= urlencode($row['billing_number']) ?>"
                                       target="_blank" class="btn btn-ghost btn-sm">🖨️ Print</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No customers yet.</p>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
    function toggleCustomers() {
        const el = document.getElementById('existingCustomers');
        el.style.display = el.style.display === 'none' ? 'block' : 'none';
    }
</script>

</body>
</html>


