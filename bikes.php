<?php
include 'config.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ADD
    if (isset($_POST['add'])) {
        $bike_name = trim($_POST['bike_name']);
        $brand = trim($_POST['brand']);
        $engine_capacity = trim($_POST['engine_capacity']);

        if ($bike_name && $brand && $engine_capacity) {
            $stmt = $conn->prepare("INSERT INTO bikes (bike_name, brand, engine_capacity) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $bike_name, $brand, $engine_capacity);
            $stmt->execute();
            $stmt->close();
        }
    }

  
    if (isset($_POST['update'])) {
        $id = $_POST['id'];
        $bike_name = trim($_POST['bike_name']);
        $brand = trim($_POST['brand']);
        $engine_capacity = trim($_POST['engine_capacity']);

        $stmt = $conn->prepare("UPDATE bikes SET bike_name=?, brand=?, engine_capacity=? WHERE id=?");
        $stmt->bind_param("sssi", $bike_name, $brand, $engine_capacity, $id);
        $stmt->execute();
        $stmt->close();
    }

  
    if (isset($_POST['delete'])) {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM bikes WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    
    header("Location: bikes.php");
    exit();
}

$bikes = $conn->query("SELECT * FROM bikes ORDER BY brand, bike_name");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Bike Models — Panja Trading</title>
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
    <h1 class="page-title">Bike Models</h1>
</div>

<div class="content">

    <!-- Add New Model -->
    <div class="panel">
        <div class="panel-title">Add New Model</div>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Model Name</label>
                    <input type="text" name="bike_name" placeholder="e.g. Splendor Plus" required>
                </div>
                <div class="form-group">
                    <label>Brand</label>
                    <input type="text" name="brand" placeholder="e.g. Hero" required>
                </div>
                <div class="form-group">
                    <label>Engine Capacity</label>
                    <input type="text" name="engine_capacity" placeholder="e.g. 100cc" required>
                </div>
            </div>
            <button type="submit" name="add" class="btn btn-primary">Add Model</button>
        </form>
    </div>

    <!-- Bikes Table -->
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Model Name</th>
                    <th>Brand</th>
                    <th>Engine Capacity</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $bikes->fetch_assoc()): ?>
                <tr>
                    <form method="POST">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <td>
                            <input type="text" name="bike_name"
                                   value="<?= htmlspecialchars($row['bike_name']) ?>">
                        </td>
                        <td>
                            <input type="text" name="brand"
                                   value="<?= htmlspecialchars($row['brand']) ?>">
                        </td>
                        <td>
                            <input type="text" name="engine_capacity"
                                   value="<?= htmlspecialchars($row['engine_capacity']) ?>">
                        </td>
                        <td style="display:flex;gap:8px;align-items:center">
                            <button type="submit" name="update" class="btn btn-primary btn-sm">Update</button>
                            <button type="submit" name="delete" class="btn btn-danger btn-sm"
                                    onclick="return confirm('Delete this model?')">Delete</button>
                        </td>
                    </form>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>

