<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow p-4">
        <h2 class="text-center">Stock Inventory</h2>
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Part Name</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Purchase Price</th>
                    <th>Selling Price</th>
                    <th>Supplier</th>
                </tr>
            </thead>
            <tbody>
                <?php
                include 'db_connect.php';
                $sql = "SELECT * FROM stock";
                $result = mysqli_query($conn, $sql);
                
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>
                        <td>{$row['part_name']}</td>
                        <td>{$row['category']}</td>
                        <td>{$row['quantity']}</td>
                        <td>{$row['purchase_price']}</td>
                        <td>{$row['selling_price']}</td>
                        <td>{$row['supplier']}</td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
