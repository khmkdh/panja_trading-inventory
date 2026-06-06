<?php
include("config.php"); // Adjust if your DB connection file has a different name

// Fetch total number of parts
$query = "SELECT COUNT(*) AS total_parts FROM parts";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);
$total_parts = $data['total_parts'];

echo "<h5 class='mb-0'>$total_parts</h5>";
?>
