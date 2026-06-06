<?php
include("config.php");
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM stock");
$data = mysqli_fetch_assoc($result);
echo "<h5 class='mb-0'>{$data['total']}</h5>";
?>