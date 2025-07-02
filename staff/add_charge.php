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
$customers = $pdo->query("SELECT customer_id, name, account_number FROM customers ORDER BY name")->fetchAll();

$charge_types = [
    'consumption' => 'Water Consumption',
    'service_fee' => 'Service Fee',
    'reconnection' => 'Reconnection Fee',
    'penalty' => 'Late Payment Penalty',
    'arrears' => 'Outstanding Arrears',
    'remission' => 'Remission/Discount',
    'adjustment' => 'Billing Adjustment',
    'other' => 'Other Charges'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer_id'];
    $charge_type = $_POST['charge_type'];
    $description = $_POST['description'];
    $amount = floatval($_POST['amount']);
    $charge_date = $_POST['charge_date'] ?? date('Y-m-d');
    
    $stmt = $pdo->prepare("INSERT INTO charges (customer_id, charge_type, description, amt, charge_date, status, created_by) VALUES (?, ?, ?, ?, ?, 'unpaid', ?)");
    $stmt->execute([$customer_id, $charge_type, $description, $amount, $charge_date, $_SESSION['user_id']]);
    
    header("Location: billing.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Charge</title>
    <link rel="stylesheet" href="../assets/css/add_customer.css">
</head>
<body>
    <a class="back-link" href="billing.php">← Back to Billing</a>
    <h1>Add Customer Charge</h1>
    
    <form method="POST">
        <label>Customer</label>
        <select name="customer_id" required>
            <option value="">-- Select Customer --</option>
            <?php foreach ($customers as $c): ?>
                <option value="<?= $c['customer_id'] ?>" <?= $customer_id == $c['customer_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['account_number']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        
        <label>Charge Type</label>
        <select name="charge_type" required>
            <option value="">-- Select Charge Type --</option>
            <?php foreach ($charge_types as $key => $label): ?>
                <option value="<?= $key ?>"><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        
        <label>Description</label>
        <textarea name="description" required placeholder="Enter charge description"></textarea>
        
        <label>Amount (KES)</label>
        <input type="number" name="amount" step="0.01" required>
        
        <label>Charge Date</label>
        <input type="date" name="charge_date" value="<?= date('Y-m-d') ?>" required>
        
        <button type="submit">Add Charge</button>
    </form>
</body>
</html>