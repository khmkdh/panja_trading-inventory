<?php
include 'config.php';

$message = "";
$newCategory = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_name'])) {
    $newCategory = trim($_POST['category_name']);
    if (!empty($newCategory)) {
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $newCategory);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Category added successfully.</div>';
        } else {
            $message = '<div class="alert alert-danger">Error: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-warning">Category name cannot be empty.</div>';
    }
}


$sql = "SELECT * FROM stock ORDER BY category, part_name";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Category — Panja Trading</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="page-wrapper">

<nav>
    <span class="nav-brand">Panja Trading</span>
    <div class="nav-actions">
        <a href="dashboard.php" class="btn btn-ghost btn-sm">← Dashboard</a>
    </div>
</nav>

<div class="page-header">
    <h1 class="page-title">Categories</h1>
</div>

<div class="content">

    <!-- Add Category -->
    <div class="panel">
        <div class="panel-title">Add New Category</div>
        <?= $message ?>
        <form method="POST" id="addCategoryForm">
            <div class="form-row">
                <div class="form-group">
                    <label>Category Name</label>
                    <input type="text" name="category_name" placeholder="Enter category name" required>
                </div>
                <div class="form-group" style="display:flex;align-items:flex-end;gap:10px">
                    <button type="submit" class="btn btn-primary">Add Category</button>
                    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Category-wise Stock -->
    <?php
    $currentCategory = null;
    $categoriesWithItems = [];
    $categories = [];

    $catResult = $conn->query("SELECT name FROM categories ORDER BY id");
    if ($catResult->num_rows > 0) {
        while ($catRow = $catResult->fetch_assoc()) {
            $categories[] = $catRow['name'];
        }
    }

    $sql = "SELECT * FROM stock ORDER BY category, part_name";
    $result = $conn->query($sql);

    if ($result->num_rows > 0):
        while ($row = $result->fetch_assoc()):
            if ($currentCategory !== $row['category']):
                if ($currentCategory !== null) echo '</tbody></table></div></div>'; // close table-wrapper + panel
                $currentCategory = $row['category'];
                $categoriesWithItems[] = $currentCategory;
    ?>
        <div class="panel" style="margin-bottom:20px">
            <div class="panel-title"><?= htmlspecialchars($currentCategory) ?></div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Part Name</th>
                            <th>Quantity</th>
                            <th>Purchase Price</th>
                            <th>Selling Price</th>
                            <th>Supplier</th>
                            <th>Added On</th>
                        </tr>
                    </thead>
                    <tbody>
    <?php
            endif;
    ?>
                        <tr>
                            <td><?= htmlspecialchars($row['part_name']) ?></td>
                            <td><?= $row['quantity'] ?></td>
                            <td>₹<?= number_format($row['purchase_price'], 2) ?></td>
                            <td>₹<?= number_format($row['selling_price'], 2) ?></td>
                            <td><?= htmlspecialchars($row['supplier']) ?></td>
                            <td><?= $row['added_on'] ?></td>
                        </tr>
    <?php
        endwhile;
        if ($currentCategory !== null) echo '</tbody></table></div></div>';
    endif;

    // Empty categories
    foreach ($categories as $cat):
        $idAttr = ($cat === $newCategory) ? 'id="new-category"' : '';
        if (!in_array($cat, $categoriesWithItems)):
    ?>
        <div class="panel" <?= $idAttr ?> style="margin-bottom:20px">
            <div class="panel-title"><?= htmlspecialchars($cat) ?></div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Part Name</th>
                            <th>Quantity</th>
                            <th>Purchase Price</th>
                            <th>Selling Price</th>
                            <th>Supplier</th>
                            <th>Added On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" class="text-muted" style="text-align:center;padding:20px">
                                No items in this category yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php
        endif;
    endforeach;
    ?>

</div>

<script>
    window.addEventListener('load', function() {
        const newCat = document.getElementById('new-category');
        if (newCat) newCat.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
</script>

</body>
</html>




