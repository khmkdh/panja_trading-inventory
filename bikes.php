<?php
include 'config.php';
session_start();

$activePage = 'bikes';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $bike_name = trim($_POST['bike_name']);
        $brand = trim($_POST['brand']);
        $engine_capacity = trim($_POST['engine_capacity']);
        if ($bike_name && $brand && $engine_capacity) {
            $stmt = $conn->prepare("INSERT INTO bikes (bike_name, brand, engine_capacity) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $bike_name, $brand, $engine_capacity);
            $stmt->execute();
            $stmt->close();
            $success = "Bike model added successfully.";
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
        $success = "Bike model updated successfully.";
    }

    if (isset($_POST['delete'])) {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM bikes WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $success = "Bike model deleted.";
    }
}

$bikes = $conn->query("SELECT * FROM bikes ORDER BY brand, bike_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bike Models – Panja Trading</title>
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
            <div class="topbar-title">Bike Models</div>
            <div class="topbar-actions">
                <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>

        <div class="page-content">

            <?php if (!empty($success)): ?>
            <div class="alert-banner" style="background:#e8f5e9; border-color:#a5d6a7;">
                <i class="bi bi-check-circle-fill" style="color:#2e7d32;"></i>
                <span style="color:#1b5e20;"><?= $success ?></span>
            </div>
            <?php endif; ?>

            <!-- Add New Model -->
            <div class="card-section" style="max-width: 760px;">
                <div class="card-section-header">
                    <span class="section-title"><i class="bi bi-bicycle"></i> Add New Model</span>
                </div>
                <div style="padding: 20px;">
                    <form method="POST">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Model Name</label>
                                <input type="text" name="bike_name" class="form-control"
                                       placeholder="e.g. Splendor Plus" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Brand</label>
                                <input type="text" name="brand" class="form-control"
                                       placeholder="e.g. Hero" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Engine Capacity</label>
                                <input type="text" name="engine_capacity" class="form-control"
                                       placeholder="e.g. 100cc" required>
                            </div>
                        </div>
                        <button type="submit" name="add" class="btn btn-primary">
                            <i class="bi bi-plus-lg"></i> Add Model
                        </button>
                    </form>
                </div>
            </div>

            <!-- Bikes Table -->
            <div class="card-section">
                <div class="card-section-header">
                    <span class="section-title">All Bike Models</span>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Model Name</th>
                                <th>Brand</th>
                                <th>Engine Capacity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            while ($row = $bikes->fetch_assoc()):
                            ?>
                            <tr>
                                <form method="POST">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <td><?= $i++ ?></td>
                                    <td>
                                        <input type="text" name="bike_name" class="form-control form-control-sm"
                                               value="<?= htmlspecialchars($row['bike_name']) ?>">
                                    </td>
                                    <td>
                                        <input type="text" name="brand" class="form-control form-control-sm"
                                               value="<?= htmlspecialchars($row['brand']) ?>">
                                    </td>
                                    <td>
                                        <input type="text" name="engine_capacity" class="form-control form-control-sm"
                                               value="<?= htmlspecialchars($row['engine_capacity']) ?>">
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button type="submit" name="update" class="btn btn-sm btn-primary">
                                                <i class="bi bi-check-lg"></i> Update
                                            </button>
                                            <button type="submit" name="delete" class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Delete this model?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </form>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>