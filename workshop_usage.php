<?php
include 'config.php';

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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Workshop Usage — Panja Trading</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="page-wrapper">

<nav>
    <span class="nav-brand">Panja Trading</span>
    <div class="nav-actions">
        <a href="inventory.php" class="btn btn-ghost btn-sm">← Inventory</a>
    </div>
</nav>

<div class="page-header">
    <h1 class="page-title">Workshop Usage History</h1>
</div>

<div class="content">

    <div style="margin-bottom:20px">
        <input type="text" id="searchInput"
               placeholder="Search by part name or usage reason..."
               style="max-width:320px">
    </div>

    <div class="table-wrapper">
        <table id="usageTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Part Name</th>
                    <th>Quantity Used</th>
                    <th>Used For</th>
                    <th>Date Used</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sn = 1;
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td class='text-muted'>{$sn}</td>";
                        echo "<td style='font-weight:500'>" . htmlspecialchars($row['part_name']) . "</td>";
                        echo "<td>{$row['quantity_used']}</td>";
                        echo "<td>" . htmlspecialchars($row['used_for']) . "</td>";
                        echo "<td class='text-muted'>" . date("d M Y", strtotime($row['date_used'])) . "</td>";
                        echo "</tr>";
                        $sn++;
                    }
                } else {
                    echo "<tr><td colspan='5' class='text-muted' style='text-align:center;padding:24px'>
                            No usage records found for this part.
                          </td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

</div>

<script>
    const searchInput = document.getElementById('searchInput');
    const rows = document.querySelectorAll("#usageTable tbody tr");

    searchInput.addEventListener('keyup', function () {
        const keyword = this.value.toLowerCase();
        rows.forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(keyword) ? '' : 'none';
        });
    });
</script>

</body>
</html>
