<?php
session_start();
include 'config.php';

$activePage = 'inventory';

$result = mysqli_query($conn, "SELECT * FROM stock ORDER BY part_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock List – Panja Trading</title>
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
            <div class="topbar-title">Stock List</div>
            <div class="topbar-actions">
                <input type="text" id="searchInput" class="search-input" placeholder="Search parts...">
                <a href="add_stock.php" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg"></i> Add Stock
                </a>
            </div>
        </div>

        <div class="page-content">
            <div class="card-section">
                <div class="card-section-header">
                    <span class="section-title"><i class="bi bi-box-seam"></i> Stock Inventory</span>
                    <span style="font-size:12px; color:#6b7a8d;" id="rowCount"></span>
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="stockTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Part Name</th>
                                <th>Category</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Purchase Price</th>
                                <th>Selling Price</th>
                                <th>Supplier</th>
                                <th>Added On</th>
                            </tr>
                        </thead>
                        <tbody id="stockBody">
                            <?php
                            $i = 1;
                            while ($row = mysqli_fetch_assoc($result)):
                                $qty = (int)$row['quantity'];
                                if ($qty === 0)   $pill = '<span class="pill pill-out">Out</span>';
                                elseif ($qty < 5) $pill = '<span class="pill pill-low">Low</span>';
                                else              $pill = '<span class="pill pill-ok">In Stock</span>';
                            ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td class="item-name"><?= htmlspecialchars($row['part_name']) ?></td>
                                <td><?= htmlspecialchars($row['category']) ?></td>
                                <td><?= $qty ?></td>
                                <td><?= $pill ?></td>
                                <td>₹<?= number_format($row['purchase_price'], 2) ?></td>
                                <td>₹<?= number_format($row['selling_price'], 2) ?></td>
                                <td><?= htmlspecialchars($row['supplier']) ?></td>
                                <td><?= date('d M Y', strtotime($row['added_on'])) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const searchInput = document.getElementById('searchInput');
const rows        = document.querySelectorAll('#stockBody tr');
const rowCount    = document.getElementById('rowCount');

function updateCount() {
    const visible = [...rows].filter(r => r.style.display !== 'none').length;
    rowCount.textContent = visible + ' item' + (visible !== 1 ? 's' : '');
}

searchInput.addEventListener('keyup', function () {
    const keyword = this.value.toLowerCase();
    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(keyword) ? '' : 'none';
    });
    updateCount();
});

updateCount();
</script>
</body>
</html>