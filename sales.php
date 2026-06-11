<?php
include 'config.php';
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$activePage = 'sales';
$success = '';
$error = '';

// ─── HANDLE FORM SUBMISSION ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_sale'])) {

    $billing_number   = trim($conn->real_escape_string($_POST['billing_number']));
    $customer_name    = trim($conn->real_escape_string($_POST['customer_name']));
    $mobile           = trim($conn->real_escape_string($_POST['mobile_number']));
    $vin              = trim($conn->real_escape_string($_POST['vin_no']));
    $mode_of_payment  = trim($conn->real_escape_string($_POST['mode_of_payment']));
    $billing_date     = date('Y-m-d H:i:s');

    $items      = $_POST['item_id']      ?? [];
    $quantities = $_POST['item_qty']     ?? [];
    $types      = $_POST['item_type']    ?? [];
    $descs      = $_POST['service_desc'] ?? [];

    if (empty($billing_number) || empty($customer_name) || empty($items)) {
        $error = "Please fill in all required fields and add at least one item.";
    } else {
        $conn->begin_transaction();
        try {
            // Insert customer
            $stmt = $conn->prepare("INSERT INTO customers (billing_number, customer_name, mobile_number, vin_no, mode_of_payment, billing_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $billing_number, $customer_name, $mobile, $vin, $mode_of_payment, $billing_date);
            $stmt->execute();
            $stmt->close();

            foreach ($items as $i => $item_id) {
                $item_id = (int)$item_id;
                $qty     = (int)($quantities[$i] ?? 1);
                $type    = $types[$i] ?? 'parts';
                $desc    = trim($conn->real_escape_string($descs[$i] ?? ''));

                if ($item_id <= 0 || $qty <= 0) continue;

                // Get stock details
                $res  = $conn->query("SELECT part_name, selling_price, quantity FROM stock WHERE id = $item_id");
                $part = $res->fetch_assoc();

                if (!$part) continue;

                if ($part['quantity'] < $qty) {
                    throw new Exception("Not enough stock for: {$part['part_name']} (available: {$part['quantity']})");
                }

                $total_price   = $part['selling_price'] * $qty;
                $selling_price = $part['selling_price'];

                // Deduct stock
                $conn->query("UPDATE stock SET quantity = quantity - $qty WHERE id = $item_id");

                // Insert into cart
                $stmt2 = $conn->prepare("INSERT INTO cart (billing_number, item_id, item_qty, selling_price, total_price, sale_type, service_description, cart_date, is_checkout) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
                $stmt2->bind_param("siiddsss", $billing_number, $item_id, $qty, $selling_price, $total_price, $type, $desc, $billing_date);
                $stmt2->execute();
                $stmt2->close();

                // If service — also log in workshop_usage
                if ($type === 'service') {
                    $used_for = !empty($desc) ? $desc : 'Workshop service';
                    $today    = date('Y-m-d');
                    $conn->query("INSERT INTO workshop_usage (part_id, quantity_used, used_for, date_used) VALUES ($item_id, $qty, '$used_for', '$today')");
                }
            }

            $conn->commit();
            $success = $billing_number;

        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

// ─── FETCH DATA ───────────────────────────────────────────────────────────────
$stockItems = $conn->query("SELECT id, part_name, selling_price, quantity FROM stock WHERE quantity > 0 ORDER BY part_name");
$stockList  = [];
while ($r = $stockItems->fetch_assoc()) $stockList[] = $r;

// Recent sales — last 20 bills
$recentSales = $conn->query("
    SELECT c.billing_number, c.customer_name, c.mobile_number,
           c.mode_of_payment, c.billing_date,
           SUM(ct.total_price) AS grand_total,
           COUNT(ct.id)        AS item_count
    FROM customers c
    LEFT JOIN cart ct ON ct.billing_number = c.billing_number
    GROUP BY c.billing_number, c.customer_name, c.mobile_number, c.mode_of_payment, c.billing_date
    ORDER BY c.billing_date DESC
    LIMIT 20
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales – GearVault</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="app-shell">

<?php include 'includes/sidebar.php'; ?>

<div class="main-area">

    <!-- Topbar -->
    <div class="topbar">
        <div>
            <h4 class="topbar-title">Sales & Billing</h4>
            <p style="font-size:12px;color:#6b7a8d;margin:0">Create bills, record parts sales and workshop services</p>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs px-4 pt-3" id="salesTab">
        <li class="nav-item">
            <a class="nav-link <?= empty($_GET['tab']) || $_GET['tab'] === 'new' ? 'active' : '' ?>"
               href="sales.php?tab=new">
                <i class="bi bi-receipt me-1"></i> New Sale
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= ($_GET['tab'] ?? '') === 'history' ? 'active' : '' ?>"
               href="sales.php?tab=history">
                <i class="bi bi-clock-history me-1"></i> Sales History
            </a>
        </li>
    </ul>

    <div class="page-content">

        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
                <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
                <i class="bi bi-check-circle-fill"></i>
                Bill <strong><?= htmlspecialchars($success) ?></strong> created successfully!
                <a href="print_bill.php?billing_number=<?= urlencode($success) ?>" target="_blank"
                   class="btn btn-sm btn-success ms-auto">
                    <i class="bi bi-printer"></i> Print Bill
                </a>
            </div>
        <?php endif; ?>

        <!-- ══════════════ NEW SALE TAB ══════════════ -->
        <?php if (empty($_GET['tab']) || $_GET['tab'] === 'new'): ?>

        <form method="POST" id="saleForm">
            <input type="hidden" name="submit_sale" value="1">

            <!-- Customer Details -->
            <div class="panel mb-4">
                <div class="panel-title">
                    <i class="bi bi-person-vcard me-2"></i>Customer Details
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Billing Number <span class="text-danger">*</span></label>
                        <input type="text" name="billing_number" class="form-control"
                               placeholder="e.g. BILL1011" required
                               value="<?= htmlspecialchars($_POST['billing_number'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                        <input type="text" name="customer_name" class="form-control"
                               placeholder="Full name" required
                               value="<?= htmlspecialchars($_POST['customer_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Mobile Number</label>
                        <input type="text" name="mobile_number" class="form-control"
                               placeholder="10-digit number"
                               value="<?= htmlspecialchars($_POST['mobile_number'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Vehicle VIN / Reg. No.</label>
                        <input type="text" name="vin_no" class="form-control"
                               placeholder="e.g. AS01AB1234"
                               value="<?= htmlspecialchars($_POST['vin_no'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mode of Payment</label>
                        <select name="mode_of_payment" class="form-select">
                            <option value="Cash">Cash</option>
                            <option value="UPI">UPI</option>
                            <option value="Card">Card</option>
                            <option value="Credit">Credit</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Items -->
            <div class="panel mb-4">
                <div class="panel-title d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-box-seam me-2"></i>Items</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow()">
                        <i class="bi bi-plus-lg me-1"></i>Add Row
                    </button>
                </div>

                <div class="row g-2 mb-2 d-none d-md-flex text-muted small fw-semibold px-1">
                    <div class="col-md-4">Item</div>
                    <div class="col-md-2">Qty</div>
                    <div class="col-md-2">Unit Price</div>
                    <div class="col-md-2">Type</div>
                    <div class="col-md-1">Total</div>
                    <div class="col-md-1"></div>
                </div>

                <div id="itemRows"></div>

                <div class="d-flex justify-content-end mt-3 pt-3 border-top">
                    <div class="text-end">
                        <div class="text-muted small">Grand Total</div>
                        <div class="fs-4 fw-bold text-primary" id="grandTotal">₹0.00</div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-circle me-2"></i>Submit & Generate Bill
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                </button>
            </div>
        </form>

        <!-- ══════════════ HISTORY TAB ══════════════ -->
        <?php else: ?>

        <div class="panel">
            <div class="panel-title">
                <i class="bi bi-clock-history me-2"></i>Recent Sales
                <span class="badge bg-secondary ms-2"><?= $recentSales->num_rows ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Billing No.</th>
                            <th>Customer</th>
                            <th>Mobile</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($recentSales && $recentSales->num_rows > 0):
                        while ($sale = $recentSales->fetch_assoc()): ?>
                        <tr>
                            <td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($sale['billing_number']) ?></span></td>
                            <td class="fw-medium"><?= htmlspecialchars($sale['customer_name']) ?></td>
                            <td class="text-muted"><?= htmlspecialchars($sale['mobile_number']) ?></td>
                            <td><span class="badge bg-secondary"><?= $sale['item_count'] ?> items</span></td>
                            <td class="fw-bold text-success">₹<?= number_format($sale['grand_total'], 2) ?></td>
                            <td><span class="badge bg-info-subtle text-info"><?= htmlspecialchars($sale['mode_of_payment']) ?></span></td>
                            <td class="text-muted small"><?= date('d M Y, h:i A', strtotime($sale['billing_date'])) ?></td>
                            <td>
                                <a href="print_bill.php?billing_number=<?= urlencode($sale['billing_number']) ?>"
                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="bi bi-printer"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No sales recorded yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php endif; ?>
    </div>
</div>

<script>
const stockData = <?= json_encode($stockList) ?>;

function addRow() {
    const container = document.getElementById('itemRows');
    const div = document.createElement('div');
    div.className = 'item-row border rounded p-3 mb-2 bg-light';
    div.innerHTML = `
        <div class="row g-2 align-items-start">
            <div class="col-md-4">
                <select name="item_id[]" class="form-select item-select" onchange="onItemChange(this)" required>
                    <option value="">— Select item —</option>
                    ${stockData.map(s =>
                        `<option value="${s.id}" data-price="${s.selling_price}">
                            ${s.part_name} (Stock: ${s.quantity})
                        </option>`
                    ).join('')}
                </select>
            </div>
            <div class="col-md-2">
                <input type="number" name="item_qty[]" class="form-control item-qty"
                       value="1" min="1" onchange="recalcRow(this)" required>
            </div>
            <div class="col-md-2">
                <input type="text" class="form-control unit-price" readonly placeholder="₹0.00">
            </div>
            <div class="col-md-2">
                <select name="item_type[]" class="form-select item-type" onchange="toggleDesc(this)">
                    <option value="parts">Parts / Accessory</option>
                    <option value="service">Workshop Service</option>
                </select>
            </div>
            <div class="col-md-1">
                <input type="text" class="form-control row-total fw-bold" readonly placeholder="₹0.00">
            </div>
            <div class="col-md-1 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <div class="col-12 service-desc-wrap d-none">
                <input type="text" name="service_desc[]" class="form-control mt-1"
                       placeholder="Service description (e.g. Brake pad replacement for Hero Splendor)">
            </div>
        </div>
    `;
    container.appendChild(div);
    updateGrandTotal();
}

function onItemChange(sel) {
    const row   = sel.closest('.item-row');
    const price = parseFloat(sel.selectedOptions[0]?.dataset.price || 0);
    row.querySelector('.unit-price').value = price > 0 ? '₹' + price.toFixed(2) : '';
    recalcRow(row.querySelector('.item-qty'));
}

function recalcRow(qtyInput) {
    const row   = qtyInput.closest('.item-row');
    const price = parseFloat(row.querySelector('.item-select').selectedOptions[0]?.dataset.price || 0);
    const qty   = parseInt(qtyInput.value) || 0;
    const total = price * qty;
    row.querySelector('.row-total').value = total > 0 ? '₹' + total.toFixed(2) : '';
    updateGrandTotal();
}

function toggleDesc(sel) {
    const wrap = sel.closest('.item-row').querySelector('.service-desc-wrap');
    sel.value === 'service' ? wrap.classList.remove('d-none') : wrap.classList.add('d-none');
}

function removeRow(btn) {
    btn.closest('.item-row').remove();
    updateGrandTotal();
}

function updateGrandTotal() {
    let total = 0;
    document.querySelectorAll('.row-total').forEach(el => {
        total += parseFloat(el.value.replace('₹', '')) || 0;
    });
    document.getElementById('grandTotal').textContent = '₹' + total.toFixed(2);
}

function resetForm() {
    document.getElementById('saleForm').reset();
    document.getElementById('itemRows').innerHTML = '';
    addRow();
    document.getElementById('grandTotal').textContent = '₹0.00';
}

addRow();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>