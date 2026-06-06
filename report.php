<?php
include 'config.php';
session_start();

$activePage = 'report';
$selectedMonth = '';
$results = [];
$monthlyTotal = 0;

// Fetch revenue data for graph (last 6 months)
$graphQuery = "
    SELECT
        DATE_FORMAT(c.billing_date, '%Y-%m') AS month_key,
        DATE_FORMAT(c.billing_date, '%M %Y') AS month_label,
        SUM(ct.total_price) AS monthly_revenue
    FROM cart ct
    JOIN customers c ON ct.billing_number = c.billing_number
    WHERE ct.is_checkout = 1
    GROUP BY month_key
    ORDER BY month_key DESC
    LIMIT 6
";
$graphResult = mysqli_query($conn, $graphQuery);
$months = [];
$revenues = [];
while ($row = mysqli_fetch_assoc($graphResult)) {
    $months[]   = $row['month_label'];
    $revenues[] = (float)$row['monthly_revenue'];
}

// Handle report filtering
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedMonth = $_POST['month'];
    $whereClause   = "ct.is_checkout = 1";
    if (!empty($selectedMonth)) {
        $safe = mysqli_real_escape_string($conn, $selectedMonth);
        $whereClause .= " AND DATE_FORMAT(c.billing_date, '%Y-%m') = '$safe'";
    }

    $query = "
        SELECT
            DATE_FORMAT(c.billing_date, '%M %Y') AS month_year,
            c.customer_name,
            s.part_name AS item_name,
            c.billing_number,
            c.vin_no,
            ct.item_qty AS qty_sold,
            ct.selling_price AS per_unit_price,
            ct.total_price
        FROM cart ct
        JOIN customers c ON ct.billing_number = c.billing_number
        JOIN stock s ON ct.item_id = s.id
        WHERE $whereClause
        ORDER BY c.billing_date DESC
    ";

    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $results[]     = $row;
            $monthlyTotal += $row['total_price'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report – Panja Trading</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="app-shell">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-area">
        <div class="topbar">
            <div class="topbar-title">Sales Report</div>
            <div class="topbar-actions">
                <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>

        <div class="page-content">

            <!-- Filter -->
            <div class="card-section" style="max-width:500px;">
                <div class="card-section-header">
                    <span class="section-title"><i class="bi bi-funnel"></i> Filter by Month</span>
                </div>
                <div style="padding: 20px;">
                    <form method="POST">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-7">
                                <label class="form-label fw-semibold">Select Month</label>
                                <input type="month" name="month" class="form-control"
                                       value="<?= htmlspecialchars($selectedMonth) ?>">
                            </div>
                            <div class="col-md-5">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-bar-chart-line"></i> View Report
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Results -->
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <?php if (count($results) > 0): ?>

                <!-- Total Banner -->
                <div class="stats-grid" style="max-width:500px;">
                    <div class="stat-card">
                        <div class="stat-icon bg-blue"><i class="bi bi-currency-rupee"></i></div>
                        <div>
                            <div class="stat-label">
                                Total Sales — <?= !empty($selectedMonth) ? date('F Y', strtotime($selectedMonth . '-01')) : 'All Time' ?>
                            </div>
                            <div class="stat-value">₹<?= number_format($monthlyTotal, 2) ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-green"><i class="bi bi-receipt"></i></div>
                        <div>
                            <div class="stat-label">Transactions</div>
                            <div class="stat-value"><?= count($results) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Results Table -->
                <div class="card-section">
                    <div class="card-section-header">
                        <span class="section-title">Transaction Details</span>
                        <span style="font-size:12px; color:#6b7a8d;"><?= count($results) ?> record<?= count($results) !== 1 ? 's' : '' ?></span>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Month / Year</th>
                                    <th>Customer</th>
                                    <th>Item Name</th>
                                    <th>Billing No.</th>
                                    <th>VIN No.</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                    <th>Bill</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $i => $row): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($row['month_year']) ?></td>
                                    <td class="item-name"><?= htmlspecialchars($row['customer_name']) ?></td>
                                    <td><?= htmlspecialchars($row['item_name']) ?></td>
                                    <td>
                                        <span class="pill" style="background:#e8f0fe; color:#1a56db;">
                                            <?= htmlspecialchars($row['billing_number']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row['vin_no']) ?></td>
                                    <td><?= htmlspecialchars($row['qty_sold']) ?></td>
                                    <td>₹<?= number_format($row['per_unit_price'], 2) ?></td>
                                    <td style="font-weight:600; color:#1e2a3a;">₹<?= number_format($row['total_price'], 2) ?></td>
                                    <td>
                                        <a href="print_bill.php?billing_number=<?= urlencode($row['billing_number']) ?>"
                                           target="_blank" class="icon-btn" title="Print Bill">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php else: ?>
                <div class="alert-banner" style="background:#fff8e1; border-color:#ffe082;">
                    <i class="bi bi-info-circle-fill" style="color:#f59e0b;"></i>
                    <span style="color:#7d5a00;">No sales data found for the selected month.</span>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Revenue Chart -->
            <div class="card-section">
                <div class="card-section-header">
                    <span class="section-title"><i class="bi bi-bar-chart-line"></i> Last 6 Months Revenue</span>
                </div>
                <div style="padding: 20px;">
                    <canvas id="salesChart" height="80"></canvas>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_reverse($months)) ?>,
        datasets: [{
            label: 'Revenue (₹)',
            data: <?= json_encode(array_reverse($revenues)) ?>,
            backgroundColor: 'rgba(79, 140, 255, 0.15)',
            borderColor: 'rgba(79, 140, 255, 0.8)',
            borderWidth: 2,
            borderRadius: 6
        }]
    },
    options: {
        plugins: {
            legend: {
                labels: { color: '#6b7a8d', font: { family: 'Segoe UI' } }
            }
        },
        scales: {
            x: {
                ticks: { color: '#6b7a8d' },
                grid: { color: 'rgba(0,0,0,0.04)' }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    color: '#6b7a8d',
                    callback: value => '₹' + value.toLocaleString('en-IN')
                },
                grid: { color: 'rgba(0,0,0,0.04)' }
            }
        }
    }
});
</script>
</body>
</html>