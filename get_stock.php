<?php
include 'config.php';

header('Content-Type: application/json');

// Fetch stock from database
$sql = "SELECT * FROM stock";
$result = mysqli_query($conn, $sql);

$stockData = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $stockData[] = $row;
    }
}

echo json_encode($stockData);
?>

