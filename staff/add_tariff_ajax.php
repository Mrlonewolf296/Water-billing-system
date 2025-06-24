<?php
session_start();
require_once '../config/db_con.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $min = (int)$_POST['min_unit'];
    $max = $_POST['max_unit'] !== '' ? (int)$_POST['max_unit'] : null;
    $rate = (float)$_POST['rate_per_unit'];

    $stmt = $pdo->prepare("INSERT INTO tariffs (min_unit, max_unit, rate_per_unit) VALUES (?, ?, ?)");
    $stmt->execute([$min, $max, $rate]);

    echo "Tariff added successfully!";
    exit();
}
echo "Invalid request.";
?>
