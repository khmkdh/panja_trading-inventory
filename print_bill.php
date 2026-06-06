<?php
include 'config.php';
session_start();

if (!isset($_GET['billing_number'])) {
    die("Billing number is required");
}

$billing_number = $conn->real_escape_string($_GET['billing_number']);

// Fetch customer details
$customerQuery = $conn->query("SELECT * FROM customers WHERE billing_number = '$billing_number' LIMIT 1");
if ($customerQuery->num_rows == 0) die("Customer not found");
$customer = $customerQuery->fetch_assoc();

// Fetch cart items
$cartQuery = $conn->query("
    SELECT s.part_name, ct.item_qty, ct.selling_price, ct.total_price
    FROM cart ct
    JOIN stock s ON ct.item_id = s.id
    JOIN customers c ON ct.billing_number = c.billing_number
    WHERE c.billing_number = '$billing_number' AND ct.is_checkout = 1
");

$grand_total = 0;
$items = [];
while ($row = $cartQuery->fetch_assoc()) {
    $grand_total += $row['total_price'];
    $items[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bill — <?= htmlspecialchars($billing_number) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
            color: #2d3748;
            font-size: 14px;
        }

        /* Screen toolbar */
        .bill-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 32px;
            background: #1e2a3a;
            color: #fff;
        }

        .toolbar-brand {
            font-size: 15px;
            font-weight: 600;
            color: #fff;
        }

        .toolbar-actions { display: flex; gap: 10px; }

        .btn-print {
            background: #4f8cff;
            color: #fff;
            border: none;
            border-radius: 7px;
            padding: 7px 16px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-back {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 7px;
            padding: 7px 16px;
            font-size: 13px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Bill wrapper */
        .bill-wrapper {
            max-width: 760px;
            margin: 32px auto;
            padding: 0 24px 60px;
        }

        /* Bill card */
        .bill-card {
            background: #fff;
            border: 1px solid #e8ecf0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.07);
        }

        /* Bill header */
        .bill-header {
            background: #1e2a3a;
            color: #fff;
            text-align: center;
            padding: 28px 32px 24px;
        }

        .bill-company {
            font-size: 26px;
            font-weight: 700;
            letter-spacing: 0.12em;
            color: #fff;
            margin-bottom: 4px;
        }

        .bill-subtitle {
            font-size: 11px;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        /* Bill body */
        .bill-body { padding: 28px 32px; }

        /* Customer info grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px 40px;
            margin-bottom: 28px;
            padding-bottom: 24px;
            border-bottom: 1px solid #e8ecf0;
        }

        .info-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #9aa5b4;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 14px;
            font-weight: 500;
            color: #1e2a3a;
        }

        .payment-pill {
            display: inline-block;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
            background: #e8f0fe;
            color: #1a56db;
        }

        /* Items table */
        .bill-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .bill-table thead th {
            padding: 10px 12px;
            text-align: left;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7a8d;
            background: #f8fafc;
            border-bottom: 1px solid #e8ecf0;
        }

        .bill-table tbody td {
            padding: 11px 12px;
            color: #4a5568;
            border-bottom: 1px solid #f0f4f8;
            vertical-align: middle;
        }

        .bill-table tbody tr:last-child td { border-bottom: none; }

        .total-row td {
            padding: 14px 12px !important;
            border-top: 2px solid #e8ecf0 !important;
            border-bottom: none !important;
        }

        .total-label {
            text-align: right;
            font-weight: 700;
            font-size: 14px;
            color: #1e2a3a;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .total-amount {
            font-weight: 700;
            font-size: 18px;
            color: #1a56db;
        }

        /* Footer */
        .bill-footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #e8ecf0;
            font-size: 12px;
            color: #9aa5b4;
        }

        /* Print styles */
        @media print {
            body { background: #fff !important; }
            .bill-toolbar { display: none !important; }
            .bill-wrapper { margin: 0; padding: 0; max-width: 100%; }
            .bill-card { box-shadow: none !important; border: 1px solid #ccc !important; border-radius: 0 !important; }
            .bill-header { background: #1e2a3a !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<!-- Toolbar (hidden on print) -->
<div class="bill-toolbar">
    <div class="toolbar-brand"><i class="bi bi-tools"></i> Panja Trading</div>
    <div class="toolbar-actions">
        <button class="btn-print" onclick="window.print()">
            <i class="bi bi-printer"></i> Print Bill
        </button>
        <a href="customer.php" class="btn-back">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<!-- Bill -->
<div class="bill-wrapper">
    <div class="bill-card">

        <!-- Header -->
        <div class="bill-header">
            <div class="bill-company">PANJA TRADING</div>
            <div class="bill-subtitle">Bike Xpert — Tax Invoice</div>
        </div>

        <!-- Body -->
        <div class="bill-body">

            <!-- Customer Info -->
            <div class="info-grid">
                <div>
                    <div class="info-label">Customer Name</div>
                    <div class="info-value"><?= htmlspecialchars($customer['customer_name']) ?></div>
                </div>
                <div>
                    <div class="info-label">Billing Number</div>
                    <div class="info-value"><?= htmlspecialchars($billing_number) ?></div>
                </div>
                <div>
                    <div class="info-label">Mobile</div>
                    <div class="info-value"><?= htmlspecialchars($customer['mobile_number']) ?></div>
                </div>
                <div>
                    <div class="info-label">Payment Mode</div>
                    <div class="info-value">
                        <span class="payment-pill"><?= htmlspecialchars($customer['mode_of_payment']) ?></span>
                    </div>
                </div>
                <div>
                    <div class="info-label">Bike VIN No.</div>
                    <div class="info-value"><?= htmlspecialchars($customer['vin_no']) ?></div>
                </div>
                <div>
                    <div class="info-label">Date</div>
                    <div class="info-value"><?= date('d M Y', strtotime($customer['billing_date'])) ?></div>
                </div>
            </div>

            <!-- Items Table -->
            <table class="bill-table">
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
                        <td><?= $index + 1 ?></td>
                        <td style="font-weight:500; color:#1e2a3a;"><?= htmlspecialchars($item['part_name']) ?></td>
                        <td><?= $item['item_qty'] ?></td>
                        <td>₹<?= number_format($item['selling_price'], 2) ?></td>
                        <td>₹<?= number_format($item['total_price'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="4" class="total-label">Grand Total</td>
                        <td class="total-amount">₹<?= number_format($grand_total, 2) ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Footer -->
            <div class="bill-footer">
                Thank you for your business! — Panja Trading, Bike Xpert
            </div>

        </div>
    </div>
</div>

</body>
</html>