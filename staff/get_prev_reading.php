<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
require_once '../config/db_con.php';
$customer_id = $_GET['customer_id'] ?? 0;
$prev = 0;
if ($customer_id) {
    $stmt = $pdo->prepare("SELECT curr_reading FROM readings WHERE customer_id = ? ORDER BY created_at DESC, reading_month DESC LIMIT 1");
    $stmt->execute([$customer_id]);
    $row = $stmt->fetch();
    if ($row) $prev = $row['curr_reading'];
}
echo $prev;
?>