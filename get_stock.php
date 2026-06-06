<?php
session_start();
include 'config.php';

// Block unauthenticated access
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

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