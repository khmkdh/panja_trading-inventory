<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Stock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow p-4">
        <h2 class="text-center">Add New Stock</h2>
        <form action="add_stock.php" method="POST">
            <div class="mb-3">
                <label class="form-label">Part Name</label>
                <input type="text" name="part_name" class="form-control" placeholder="Enter Part Name" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Category</label>
                <input type="text" name="category" class="form-control" placeholder="Enter Category">
            </div>
            <div class="mb-3">
                <label class="form-label">Quantity</label>
                <input type="number" name="quantity" class="form-control" placeholder="Enter Quantity" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Purchase Price</label>
                <input type="text" name="purchase_price" class="form-control" placeholder="Enter Purchase Price" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Selling Price</label>
                <input type="text" name="selling_price" class="form-control" placeholder="Enter Selling Price" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Supplier</label>
                <input type="text" name="supplier" class="form-control" placeholder="Enter Supplier Name">
            </div>
            <button type="submit" class="btn btn-primary w-100">Add Stock</button>
        </form>
    </div>
</div>

</body>
</html>
