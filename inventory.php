<?php
session_start();
include 'config.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Inventory — Panja Trading</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="page-wrapper">

<nav>
    <span class="nav-brand">Panja Trading</span>
    <div class="nav-actions">
        <a href="add_workshop_usage.php" class="btn btn-secondary btn-sm">Workshop Usage</a>
        <a href="dashboard.php" class="btn btn-ghost btn-sm">← Dashboard</a>
    </div>
</nav>

<div class="page-header">
    <h1 class="page-title">Inventory</h1>
</div>

<div class="content">

    <!-- Search & Stats -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:20px">
        <input type="text" id="searchInput"
               placeholder="Search by part name or category..."
               style="max-width:320px">
        <div class="stat-chips">
            <div class="stat-chip">
                <div class="value" id="totalCount">—</div>
                <div class="label">Total Parts</div>
            </div>
            <div class="stat-chip">
                <div class="value" id="lowCount" style="color:var(--accent-2)">—</div>
                <div class="label">Low Stock</div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="table-wrapper">
        <table id="inventoryTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Part Name</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Realtime Qty</th>
                    <th>Usage Stats</th>
                    <th>Reorder Alert</th>
                    <th>Purchase Price</th>
                    <th>Selling Price</th>
                    <th>Supplier</th>
                    <th>Added On</th>
                </tr>
            </thead>
            <tbody id="stock-body"></tbody>
        </table>
    </div>

</div>

<script>
    let isSearching = false;

    function fetchStock() {
        if (isSearching) return;

        fetch('get_stock.php')
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('stock-body');
                tbody.innerHTML = "";

                let lowCount = 0;

                data.forEach((item, index) => {
                    const isLow = item.quantity < 5;
                    if (isLow) lowCount++;

                    const reorderCell = isLow
                        ? `<span class="badge badge-red">⚠ Low Stock</span>`
                        : `<span class="text-muted">—</span>`;

                    const qtyCell = isLow
                        ? `<span style="color:var(--accent-2);font-weight:600">${item.quantity}</span>`
                        : item.quantity;

                    tbody.innerHTML += `
                        <tr>
                            <td class="text-muted">${index + 1}</td>
                            <td style="font-weight:500">${item.part_name}</td>
                            <td>${item.category}</td>
                            <td>${qtyCell}</td>
                            <td>${item.quantity}</td>
                            <td>
                                <a href="workshop_usage.php?part_id=${item.id}"
                                   class="btn btn-ghost btn-sm">View Usage</a>
                            </td>
                            <td>${reorderCell}</td>
                            <td>₹${parseFloat(item.purchase_price).toLocaleString('en-IN', {minimumFractionDigits:2})}</td>
                            <td>₹${parseFloat(item.selling_price).toLocaleString('en-IN', {minimumFractionDigits:2})}</td>
                            <td class="text-muted">${item.supplier}</td>
                            <td class="text-muted">${new Date(item.added_on).toLocaleDateString('en-IN')}</td>
                        </tr>`;
                });

                document.getElementById('totalCount').textContent = data.length;
                document.getElementById('lowCount').textContent = lowCount;
            })
            .catch(error => console.error('Error fetching stock:', error));
    }

    window.onload = fetchStock;
    const intervalId = setInterval(fetchStock, 5000);

    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('keyup', function () {
        const keyword = this.value.toLowerCase();
        isSearching = keyword.trim().length > 0;

        document.querySelectorAll("#stock-body tr").forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(keyword) ? '' : 'none';
        });
    });
</script>

</body>
</html>

