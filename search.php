<?php
include 'config.php';
session_start();

$activePage = 'search';
$searchTerm = '';
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searchTerm = $conn->real_escape_string($_POST['search_term']);

    $custSql = "
        SELECT * FROM customers
        WHERE billing_number LIKE '%$searchTerm%'
           OR mobile_number LIKE '%$searchTerm%'
    ";
    $custResult = $conn->query($custSql);

    if ($custResult && $custResult->num_rows > 0) {
        while ($customer = $custResult->fetch_assoc()) {
            $billing_number = $customer['billing_number'];
            $cartSql = "
                SELECT c.*, s.part_name, c.selling_price, c.total_price
                FROM cart c
                LEFT JOIN stock s ON c.item_id = s.id
                WHERE c.billing_number = '$billing_number'
            ";
            $cartResult = $conn->query($cartSql);
            $cartItems = [];
            $invoiceTotal = 0;
            if ($cartResult && $cartResult->num_rows > 0) {
                while ($item = $cartResult->fetch_assoc()) {
                    $cartItems[]   = $item;
                    $invoiceTotal += $item['total_price'];
                }
            }
            $results[] = [
                'customer'   => $customer,
                'cart_items' => $cartItems,
                'total'      => $invoiceTotal
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search – Panja Trading</title>
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
            <div class="topbar-title">Search Invoices</div>
            <div class="topbar-actions">
                <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>

        <div class="page-content">

            <!-- Search Form -->
            <div class="card-section" style="max-width: 600px;">
                <div class="card-section-header">
                    <span class="section-title"><i class="bi bi-search"></i> Search by Invoice or Mobile</span>
                </div>
                <div style="padding: 20px;">
                    <form method="POST">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Invoice Number or Mobile</label>
                                <input type="text" name="search_term" class="form-control"
                                       placeholder="Enter invoice number or mobile number"
                                       value="<?= htmlspecialchars($searchTerm) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Results -->
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <?php if (count($results) > 0): ?>

                <div style="font-size:13px; color:#6b7a8d; margin-bottom:4px;">
                    <?= count($results) ?> result<?= count($results) !== 1 ? 's' : '' ?> found for
                    "<strong><?= htmlspecialchars($searchTerm) ?></strong>"
                </div>

                <?php foreach ($results as $result):
                    $cust = $result['customer'];
                    $cart = $result['cart_items'];
                    $total = $result['total'];
                ?>
                <div class="card-section">
                    <!-- Customer Info Header -->
                    <div class="card-section-header" style="flex-wrap:wrap; gap:12px;">
                        <div class="d-flex flex-wrap gap-4 align-items-center">
                            <div>
                                <div class="stat-label">Invoice No.</div>
                                <span class="pill" style="background:#e8f0fe; color:#1a56db; font-size:13px;">
                                    <?= htmlspecialchars($cust['billing_number']) ?>
                                </span>
                            </div>
                            <div>
                                <div class="stat-label">Customer</div>
                                <div class="item-name"><?= htmlspecialchars($cust['customer_name']) ?></div>
                            </div>
                            <div>
                                <div class="stat-label">Mobile</div>
                                <div><?= htmlspecialchars($cust['mobile_number']) ?></div>
                            </div>
                            <div>
                                <div class="stat-label">Payment</div>
                                <span class="pill pill-ok" style="background:#e8f5e9; color:#2e7d32;">
                                    <?= htmlspecialchars($cust['mode_of_payment']) ?>
                                </span>
                            </div>
                            <div>
                                <div class="stat-label">VIN No.</div>
                                <div><?= htmlspecialchars($cust['vin_no']) ?></div>
                            </div>
                            <div>
                                <div class="stat-label">Date</div>
                                <div><?= date('d M Y', strtotime($cust['billing_date'])) ?></div>
                            </div>
                            <?php if ($total > 0): ?>
                            <div>
                                <div class="stat-label">Invoice Total</div>
                                <div style="font-weight:700; color:#1e2a3a;">₹<?= number_format($total, 2) ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <a href="print_bill.php?billing_number=<?= urlencode($cust['billing_number']) ?>"
                           target="_blank" class="btn btn-sm btn-primary ms-auto">
                            <i class="bi bi-printer"></i> Print Bill
                        </a>
                    </div>

                    <!-- Cart Items -->
                    <div class="table-responsive">
                        <?php if (count($cart) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Item Name</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart as $i => $item): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td class="item-name"><?= htmlspecialchars($item['part_name'] ?? 'Unknown') ?></td>
                                    <td><?= htmlspecialchars($item['item_qty']) ?></td>
                                    <td>₹<?= number_format($item['selling_price'], 2) ?></td>
                                    <td style="font-weight:600;">₹<?= number_format($item['total_price'], 2) ?></td>
                                    <td><?= date('d M Y', strtotime($item['cart_date'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="text-center py-3 text-muted" style="font-size:13px;">
                            No items in cart for this invoice.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php else: ?>
                <div class="alert-banner" style="background:#fff8e1; border-color:#ffe082;">
                    <i class="bi bi-info-circle-fill" style="color:#f59e0b;"></i>
                    <span style="color:#7d5a00;">
                        No invoices found matching "<strong><?= htmlspecialchars($searchTerm) ?></strong>".
                    </span>
                </div>
                <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>
</div>
</body>
</html>