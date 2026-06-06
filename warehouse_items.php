<?php
include 'config.php';

$warehouse = isset($_GET['warehouse']) ? $_GET['warehouse'] : '';

if ($warehouse !== 'main' && $warehouse !== 'secondary') {
    echo "Invalid warehouse.";
    exit;
}

$sql = "SELECT * FROM warehouse_items WHERE warehouse = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $warehouse);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= ucfirst($warehouse) ?> Warehouse | PANJA TRADING</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <style>
    body {
        background-color: #f9f9f9;
    }

    .container {
        margin-top: 40px;
    }

    table {
        background-color: white;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }

    .btn-back {
        margin-bottom: 20px;
        border-radius: 10px;
    }

    h2 {
        font-weight: 600;
        color: #333;
    }
  </style>
</head>
<body>

<div class="container">
  <a href="storage.php" class="btn btn-outline-secondary btn-back">← Back to Storage</a>
  <h2><?= ucfirst($warehouse) ?> Warehouse Items</h2>

  <table class="table table-bordered mt-4">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Part Name</th>
        <th>Quantity</th>
        <th>Added On</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $row['id'] ?></td>
            <td><?= $row['part_name'] ?></td>
            <td><?= $row['quantity'] ?></td>
            <td><?= $row['added_on'] ?></td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="4" class="text-center">No items in <?= $warehouse ?> warehouse.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

</body>
</html>
