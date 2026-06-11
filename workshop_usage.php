<?php
session_start();
include 'config.php';

$activePage = 'workshop_usage';

$filter = '';
if (isset($_GET['part_id'])) {
    $part_id = intval($_GET['part_id']);
    $filter = "WHERE wu.part_id = $part_id";
}

$query = "SELECT wu.*, s.part_name
          FROM workshop_usage wu
          JOIN stock s ON wu.part_id = s.id
          $filter
          ORDER BY wu.date_used DESC";
$result = mysqli_query($conn, $query);
$totalRows = mysqli_num_rows($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Workshop Usage – GearVault</title>
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
            <div class="topbar-title">Workshop Usage History</div>
            <div class="topbar-actions">
                <input type="text" id="searchInput" class="search-input"
                       placeholder="Search part or usage reason...">
                <a href="add_workshop_usage.php" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg"></i> Record Usage
                </a>
                <?php if (isset($_GET['part_id'])): ?>
                <a href="workshop_usage.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-lg"></i> Clear Filter
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="page-content">

            <?php if (isset($_GET['part_id'])): ?>
            <div class="alert-banner" style="background:#e8f0fe; border-color:#90b4f5;">
                <i class="bi bi-funnel-fill" style="color:#1a56db;"></i>
                <span style="color:#1a56db;">
                    Showing usage filtered by part ID <strong><?= intval($_GET['part_id']) ?></strong>.
                </span>
                <a href="workshop_usage.php" class="ms-auto alert-link" style="color:#1a56db;">View All →</a>
            </div>
            <?php endif; ?>

            <div class="card-section">
                <div class="card-section-header">
                    <span class="section-title"><i class="bi bi-hammer"></i> Usage Records</span>
                    <span style="font-size:12px; color:#6b7a8d;" id="rowCount">
                        <?= $totalRows ?> record<?= $totalRows !== 1 ? 's' : '' ?>
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="data-table" id="usageTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Part Name</th>
                                <th>Quantity Used</th>
                                <th>Used For</th>
                                <th>Date Used</th>
                            </tr>
                        </thead>
                        <tbody id="usageBody">
                            <?php if ($totalRows > 0):
                                $sn = 1;
                                while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?= $sn++ ?></td>
                                <td class="item-name"><?= htmlspecialchars($row['part_name']) ?></td>
                                <td><?= $row['quantity_used'] ?></td>
                                <td><?= htmlspecialchars($row['used_for']) ?></td>
                                <td><?= date('d M Y', strtotime($row['date_used'])) ?></td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    No usage records found.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
const searchInput = document.getElementById('searchInput');
const rows        = document.querySelectorAll('#usageBody tr');
const rowCount    = document.getElementById('rowCount');

searchInput.addEventListener('keyup', function () {
    const keyword = this.value.toLowerCase();
    let visible = 0;
    rows.forEach(row => {
        const show = row.innerText.toLowerCase().includes(keyword);
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    rowCount.textContent = visible + ' record' + (visible !== 1 ? 's' : '');
});
</script>
</body>
</html>