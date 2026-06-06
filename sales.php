<?php
include 'db_connect.php'; // Include database connection

// Handle the sale submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $part_id = $_POST['part_id'];
    $quantity_sold = $_POST['quantity'];

    // Get current stock details
    $query = "SELECT * FROM stock WHERE id = '$part_id'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);

    if ($row) {
        $new_quantity = $row['quantity'] - $quantity_sold;

        if ($new_quantity >= 0) {
            // Update stock quantity after sale
            $update_query = "UPDATE stock SET quantity = '$new_quantity' WHERE id = '$part_id'";
            mysqli_query($conn, $update_query);

            // Insert into sales table
            $sale_query = "INSERT INTO sales (part_id, quantity_sold, selling_price, sale_date)
                           VALUES ('$part_id', '$quantity_sold', '{$row['selling_price']}', NOW())";
            mysqli_query($conn, $sale_query);

            echo "<div class='alert alert-success'>Sale recorded successfully!</div>";
        } else {
            echo "<div class='alert alert-danger'>Not enough stock available!</div>";
        }
    }
}

// Fetch stock items
$stock_query = "SELECT * FROM stock WHERE quantity > 0";
$stock_result = mysqli_query($conn, $stock_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow p-4">
        <h2 class="text-center">Sales Management</h2>

        <form method="POST" action="sales.php">
            <div class="mb-3">
                <label class="form-label">Select Product</label>
                <select name="part_id" class="form-select" required>
                    <option value="">-- Select a Product --</option>
                    <?php while ($row = mysqli_fetch_assoc($stock_result)) { ?>
                        <option value="<?= $row['id'] ?>">
                            <?= $row['part_name'] ?> (Stock: <?= $row['quantity'] ?>)
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Quantity</label>
                <input type="number" name="quantity" class="form-control" placeholder="Enter Quantity" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Process Sale</button>
        </form>

        <a href="view_stock.php" class="btn btn-secondary mt-3">View Stock</a>
    </div>
</div>
<a href="view_stock.php" class="btn btn-secondary mt-3">View Stock</a>
<a href="add_stock.php" class="btn btn-info mt-3">Add New Stock</a>
</body>
</html>
