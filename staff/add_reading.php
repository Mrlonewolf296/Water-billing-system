<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config/db_con.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../auths/login.php');
    exit();
}

$customer_id = $_GET['customer_id'] ?? null;
$customers = $pdo->query("SELECT customer_id, name, meter_number FROM customers WHERE billing_type = 'metered' ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer_id'];
    $current_reading = floatval($_POST['current_reading']);
    $reading_date = $_POST['reading_date'] ?? date('Y-m-d');
    
    // Get previous reading
    $stmt = $pdo->prepare("SELECT curr_reading FROM readings WHERE customer_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$customer_id]);
    $prev_reading = $stmt->fetchColumn() ?: 0;
    
    $usage = max(0, $current_reading - $prev_reading);
    
    // Calculate cost using tariffs
    $tariffs = $pdo->query("SELECT * FROM tariffs ORDER BY min_unit ASC")->fetchAll();
    $usage_cost = 0;
    
    foreach ($tariffs as $tariff) {
        $min = $tariff['min_unit'];
        $max = $tariff['max_unit'] ?? PHP_INT_MAX;
        $rate = $tariff['rate_per_unit'];
        
        if ($usage > $min) {
            $applicable_units = min($usage, $max) - $min;
            $usage_cost += $applicable_units * $rate;
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO readings (customer_id, prev_reading, curr_reading, usage, usage_cost, created_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$customer_id, $prev_reading, $current_reading, $usage, $usage_cost, $reading_date]);
    
    header("Location: billing.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Meter Reading</title>
    <link rel="stylesheet" href="../assets/css/add_customer.css">
</head>
<body>
    <a class="back-link" href="billing.php">← Back to Billing</a>
    <h1>Add Meter Reading</h1>
    
    <form method="POST">
        <label>Customer</label>
        <select name="customer_id" required>
            <option value="">-- Select Customer --</option>
            <?php foreach ($customers as $c): ?>
                <option value="<?= $c['customer_id'] ?>" <?= $customer_id == $c['customer_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['meter_number']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        
        <label>Current Reading</label>
        <input type="number" name="current_reading" step="0.01" required>
        
        <label>Reading Date</label>
        <input type="date" name="reading_date" value="<?= date('Y-m-d') ?>" required>
        
        <button type="submit">Add Reading</button>
    </form>
</body>
</html>