<?php
include 'config.php';

if (!isset($_GET['billing_number'])) {
    die("Billing number is required");
}

$billing_number = $conn->real_escape_string($_GET['billing_number']);

// Fetch customer details
$customerQuery = $conn->query("SELECT * FROM customers WHERE billing_number = '$billing_number' LIMIT 1");
if ($customerQuery->num_rows == 0) {
    die("Customer not found");
}
$customer = $customerQuery->fetch_assoc();

// Fetch cart items
$cartQuery = $conn->query("
    SELECT s.part_name, ct.item_qty, ct.selling_price, ct.total_price 
    FROM cart ct 
    JOIN stock s ON ct.item_id = s.id 
    JOIN customers c ON ct.billing_number = c.billing_number 
    WHERE c.billing_number = '$billing_number' AND ct.is_checkout = 1
");

// Build items array and grand total HERE so the HTML below can use them
$grand_total = 0;
$items = [];
while ($row = $cartQuery->fetch_assoc()) {
    $grand_total += $row['total_price'];
    $items[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Bill — <?= htmlspecialchars($billing_number) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; color: #000 !important; }
            .bill-card { box-shadow: none !important; border: 1px solid #ccc !important; }
            nav { display: none !important; }
        }
    </style>
</head>
<body class="page-wrapper">

<nav class="no-print">
    <span class="nav-brand">Panja Trading</span>
    <div class="nav-actions">
        <a href="customer.php" class="btn btn-ghost btn-sm">← Customers</a>
    </div>
</nav>

<div class="content" style="max-width:760px;margin:40px auto;padding:0 24px 60px">

    <!-- Print Button -->
    <div class="no-print flex gap-2 mb-3">
        <button onclick="window.print()" class="btn btn-primary">🖨️ Print Bill</button>
        <a href="customer.php" class="btn btn-secondary">← Back</a>
    </div>

    <!-- Bill Card -->
    <div class="panel bill-card">

        <!-- Header -->
        <div style="text-align:center;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--border)">
            <div style="font-family:var(--font-head);font-size:2rem;font-weight:700;letter-spacing:.1em;color:var(--accent)">
                PANJA TRADING
            </div>
            <div style="font-size:.8rem;color:var(--text-2);text-transform:uppercase;letter-spacing:.08em;margin-top:4px">
                Bike Xpert — Tax Invoice
            </div>
        </div>

        <!-- Customer Info -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px 32px;margin-bottom:28px">
            <div>
                <div style="font-size:.72rem;color:var(--text-2);text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px">Customer Name</div>
                <div style="font-weight:500"><?= htmlspecialchars($customer['customer_name']) ?></div>
            </div>
            <div>
                <div style="font-size:.72rem;color:var(--text-2);text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px">Billing Number</div>
                <div style="font-weight:500"><?= htmlspecialchars($billing_number) ?></div>
            </div>
            <div>
                <div style="font-size:.72rem;color:var(--text-2);text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px">Mobile</div>
                <div><?= htmlspecialchars($customer['mobile_number']) ?></div>
            </div>
            <div>
                <div style="font-size:.72rem;color:var(--text-2);text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px">Payment Mode</div>
                <div><span class="badge badge-blue"><?= htmlspecialchars($customer['mode_of_payment']) ?></span></div>
            </div>
            <div>
                <div style="font-size:.72rem;color:var(--text-2);text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px">Bike VIN No.</div>
                <div><?= htmlspecialchars($customer['vin_no']) ?></div>
            </div>
            <div>
                <div style="font-size:.72rem;color:var(--text-2);text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px">Date</div>
                <div><?= htmlspecialchars($customer['billing_date']) ?></div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item Name</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $index => $item): ?>
                    <tr>
                        <td class="text-muted"><?= $index + 1 ?></td>
                        <td style="font-weight:500"><?= htmlspecialchars($item['part_name']) ?></td>
                        <td><?= $item['item_qty'] ?></td>
                        <td>₹<?= number_format($item['selling_price'], 2) ?></td>
                        <td>₹<?= number_format($item['total_price'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <!-- Grand Total Row -->
                    <tr style="border-top:2px solid var(--border)">
                        <td colspan="4"
                            style="text-align:right;font-family:var(--font-head);font-weight:700;font-size:1rem;letter-spacing:.05em;text-transform:uppercase">
                            Grand Total
                        </td>
                        <td style="font-family:var(--font-head);font-weight:700;font-size:1.1rem;color:var(--accent)">
                            ₹<?= number_format($grand_total, 2) ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div style="text-align:center;margin-top:28px;padding-top:20px;border-top:1px solid var(--border);color:var(--text-2);font-size:.8rem">
            Thank you for your business! — Panja Trading, Bike Xpert
        </div>

    </div>
</div>

</body>
</html>
