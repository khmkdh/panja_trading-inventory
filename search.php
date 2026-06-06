<?php
include 'config.php';

$searchTerm = '';
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searchTerm = $conn->real_escape_string($_POST['search_term']);

    // Find customers matching billing_number or mobile_number
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
            SELECT c.*, s.part_name 
            FROM cart c
            LEFT JOIN stock s ON c.item_id = s.id
            WHERE c.billing_number = '$billing_number'
        ";

            $cartResult = $conn->query($cartSql);

            $cartItems = [];
            if ($cartResult && $cartResult->num_rows > 0) {
                while ($item = $cartResult->fetch_assoc()) {
                    $cartItems[] = $item;
                }
            }

            $results[] = [
                'customer' => $customer,
                'cart_items' => $cartItems
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Search — Panja Trading</title>
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
    <h1 class="page-title">Search Invoices</h1>
</div>

<div class="content">

    <!-- Search Form -->
    <div class="panel">
        <div class="panel-title">Search by Invoice or Mobile Number</div>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Invoice Number or Mobile</label>
                    <input type="text" name="search_term"
                           placeholder="Enter invoice number or mobile number"
                           value="<?= htmlspecialchars($searchTerm) ?>" required>
                </div>
                <div class="form-group" style="display:flex;align-items:flex-end">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Results -->
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <?php if (count($results) > 0): ?>
            <?php foreach ($results as $result):
                $cust = $result['customer'];
                $cart = $result['cart_items'];
            ?>
            <div class="panel" style="margin-bottom:20px">

                <!-- Customer Info Header -->
                <div style="display:flex;flex-wrap:wrap;gap:16px 32px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--border)">
                    <div>
                        <div style="font-size:.72rem;color:var(--text-2);text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px">Invoice Number</div>
                        <div style="font-family:var(--font-head);font-weight:700;font-size:1.1rem;color:var(--accent)">
                            <?= htmlspecialchars($cust['billing_number']) ?>
                        </div>
                    </div>
                    <div>
                        <div style="font-size:.72rem;color:var(--text-2);text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px">Customer</div>
                        <div style="font-weight:500"><?= htmlspecialchars($cust['customer_name']) ?></div>
                    </div>
                    <div>
                        <div style="font-size:.72rem;color:var(--text-2);text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px">Mobile</div>
                        <div><?= htmlspecialchars($cust['mobile_number']) ?></div>
                    </div>
                    <div>
                        <div style="font-size:.72rem;color:var(--text-2);text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px">Payment</div>
                        <span class="badge badge-blue"><?= htmlspecialchars($cust['mode_of_payment']) ?></span>
                    </div>
                    <div style="margin-left:auto;display:flex;align-items:center">
                        <a href="print_bill.php?billing_number=<?= urlencode($cust['billing_number']) ?>"
                           target="_blank" class="btn btn-ghost btn-sm">🖨️ Print Bill</a>
                    </div>
                </div>

                <!-- Cart Items -->
                <?php if (count($cart) > 0): ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Item Name</th>
                                <th>Quantity</th>
                                <th>Cart Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart as $i => $item): ?>
                            <tr>
                                <td class="text-muted"><?= $i + 1 ?></td>
                                <td style="font-weight:500"><?= htmlspecialchars($item['part_name'] ?? 'Unknown') ?></td>
                                <td><?= htmlspecialchars($item['item_qty']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($item['cart_date']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <p class="text-muted">No items in the cart for this invoice.</p>
                <?php endif; ?>

            </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="alert alert-info">No invoices or customers found matching your search.</div>
        <?php endif; ?>
    <?php endif; ?>

</div>

</body>
</html>
