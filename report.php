<?php
include('config.php');

$selectedMonth = '';
$results = [];
$monthlyTotal = 0;

// Fetch revenue data for graph (last 6 months)
$graphQuery = "
SELECT 
    DATE_FORMAT(c.billing_date, '%Y-%m') AS month_key,
    DATE_FORMAT(c.billing_date, '%M %Y') AS month_label,
    SUM(ct.total_price) AS monthly_revenue
FROM 
    cart ct
JOIN 
    customers c ON ct.billing_number = c.billing_number
WHERE 
    ct.is_checkout = 1
GROUP BY 
    month_key
ORDER BY 
    month_key DESC
LIMIT 6
";
$graphResult = mysqli_query($conn, $graphQuery);

$months = [];
$revenues = [];

while ($row = mysqli_fetch_assoc($graphResult)) {
    $months[] = $row['month_label'];
    $revenues[] = $row['monthly_revenue'];
}

// Handle report filtering
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedMonth = $_POST['month'];
    $whereClause = "ct.is_checkout = 1";

    if (!empty($selectedMonth)) {
        $whereClause .= " AND DATE_FORMAT(c.billing_date, '%Y-%m') = '$selectedMonth'";
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
    FROM 
        cart ct
    JOIN 
        customers c ON ct.billing_number = c.billing_number
    JOIN 
        stock s ON ct.item_id = s.id
    WHERE 
        $whereClause
    ORDER BY 
        c.billing_date DESC
    ";

    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $results[] = $row;
            $monthlyTotal += $row['total_price'];
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sales Report — Panja Trading</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="page-wrapper">

<nav>
    <span class="nav-brand">Panja Trading</span>
    <div class="nav-actions">
        <a href="dashboard.php" class="btn btn-ghost btn-sm">← Dashboard</a>
    </div>
</nav>

<div class="page-header">
    <h1 class="page-title">Sales Report</h1>
</div>

<div class="content">

    <!-- Filter Form -->
    <div class="panel">
        <div class="panel-title">Filter by Month</div>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Select Month</label>
                    <input type="month" name="month"
                           value="<?= htmlspecialchars($selectedMonth) ?>">
                </div>
                <div class="form-group" style="display:flex;align-items:flex-end">
                    <button type="submit" class="btn btn-primary">View Report</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Results -->
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <?php if (count($results) > 0): ?>

            <!-- Total Banner -->
            <div class="panel" style="margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
                <div>
                    <div style="font-size:.75rem;color:var(--text-2);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">
                        Total Sales — <?= date('F Y', strtotime($selectedMonth . '-01')) ?>
                    </div>
                    <div style="font-family:var(--font-head);font-size:2rem;font-weight:700;color:var(--accent)">
                        ₹<?= number_format($monthlyTotal, 2) ?>
                    </div>
                </div>
                <div style="font-size:.85rem;color:var(--text-2)">
                    <?= count($results) ?> transaction<?= count($results) !== 1 ? 's' : '' ?>
                </div>
            </div>

            <div class="table-wrapper" style="margin-bottom:28px">
                <table>
                    <thead>
                        <tr>
                            <th>Month / Year</th>
                            <th>Customer</th>
                            <th>Item Name</th>
                            <th>Billing No.</th>
                            <th>VIN No.</th>
                            <th>Qty Sold</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): ?>
                        <tr>
                            <td class="text-muted"><?= htmlspecialchars($row['month_year']) ?></td>
                            <td style="font-weight:500"><?= htmlspecialchars($row['customer_name']) ?></td>
                            <td><?= htmlspecialchars($row['item_name']) ?></td>
                            <td>
                                <span style="font-family:var(--font-head);color:var(--accent)">
                                    <?= htmlspecialchars($row['billing_number']) ?>
                                </span>
                            </td>
                            <td class="text-muted"><?= htmlspecialchars($row['vin_no']) ?></td>
                            <td><?= htmlspecialchars($row['qty_sold']) ?></td>
                            <td>₹<?= number_format($row['per_unit_price'], 2) ?></td>
                            <td style="font-weight:500">₹<?= number_format($row['total_price'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <div class="alert alert-info">No sales data found for the selected month.</div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Revenue Chart -->
    <div class="panel">
        <div class="panel-title">Last 6 Months Revenue</div>
        <canvas id="salesChart" height="80"></canvas>
    </div>

</div>

<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_reverse($months)) ?>,
            datasets: [{
                label: 'Revenue (₹)',
                data: <?= json_encode(array_reverse($revenues)) ?>,
                backgroundColor: 'rgba(245, 166, 35, 0.25)',
                borderColor: 'rgba(245, 166, 35, 0.9)',
                borderWidth: 2,
                borderRadius: 6
            }]
        },
        options: {
            plugins: {
                legend: {
                    labels: { color: '#9ba3b8', font: { family: 'DM Sans' } }
                }
            },
            scales: {
                x: {
                    ticks: { color: '#9ba3b8' },
                    grid: { color: 'rgba(255,255,255,0.05)' }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#9ba3b8',
                        callback: function(value) { return '₹' + value.toLocaleString('en-IN'); }
                    },
                    grid: { color: 'rgba(255,255,255,0.05)' }
                }
            }
        }
    });
</script>

</body>
</html>



