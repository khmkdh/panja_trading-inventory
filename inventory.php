<?php
session_start();
include 'config.php';
$activePage = 'inventory';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory – Panja Trading</title>
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
            <div class="topbar-title">Inventory</div>
            <div class="topbar-actions">
                <input type="text" id="searchInput" class="search-input" placeholder="Search parts or supplier...">
                <a href="add_workshop_usage.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-hammer"></i> Workshop Usage
                </a>
                <a href="add_stock.php" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg"></i> Add Stock
                </a>
            </div>
        </div>

        <div class="page-content">
            <div class="card-section">
                <div class="table-responsive">
                    <table class="data-table" id="inventoryTable">
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="stock-body">
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="bi bi-arrow-clockwise"></i> Loading...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let isSearching = false;

function getStatusPill(qty) {
    qty = parseInt(qty);
    if (qty === 0)  return '<span class="pill pill-out">Out</span>';
    if (qty < 5)    return '<span class="pill pill-low">Low</span>';
    return '<span class="pill pill-ok">In Stock</span>';
}

function fetchStock() {
    if (isSearching) return;
    fetch('get_stock.php')
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('stock-body');
            tbody.innerHTML = '';
            if (!data.length) {
                tbody.innerHTML = '<tr><td colspan="10" class="text-center py-4 text-muted">No items found.</td></tr>';
                return;
            }
            data.forEach((item, index) => {
                tbody.innerHTML += `
                <tr>
                    <td>${index + 1}</td>
                    <td class="item-name">${item.part_name}</td>
                    <td>${item.category ?? '—'}</td>
                    <td>${item.quantity}</td>
                    <td>${getStatusPill(item.quantity)}</td>
                    <td>₹${parseFloat(item.purchase_price).toLocaleString('en-IN')}</td>
                    <td>₹${parseFloat(item.selling_price).toLocaleString('en-IN')}</td>
                    <td>${item.supplier ?? '—'}</td>
                    <td>${new Date(item.added_on).toLocaleDateString('en-IN')}</td>
                    <td>
                        <a href="workshop_usage.php?part_id=${item.id}" class="icon-btn" title="View Usage">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>`;
            });
        })
        .catch(err => console.error('Error:', err));
}

window.onload = fetchStock;
setInterval(fetchStock, 5000);

document.getElementById('searchInput').addEventListener('keyup', function () {
    const keyword = this.value.toLowerCase();
    isSearching = keyword.trim().length > 0;
    document.querySelectorAll('#stock-body tr').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(keyword) ? '' : 'none';
    });
});
</script>
</body>
</html>