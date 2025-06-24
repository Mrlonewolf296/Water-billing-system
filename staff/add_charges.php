<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
require_once '../config/db_con.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
  header("Location: ../auths/login.php");
  exit();
}

// Fetch customers and invoices for dropdowns
$customers = $pdo->query("SELECT customer_id, name FROM customers ORDER BY name")->fetchAll();
$invoices = $pdo->query("SELECT invoice_id FROM invoices ORDER BY invoice_id DESC")->fetchAll();

$allowedChargeTypes = ['consumption','reconnection','penalty','service_fee','adjustment','other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $customer_id = $_POST['customer_id'] ?? null;
  $invoice_id = $_POST['invoice_id'] !== '' ? $_POST['invoice_id'] : null;
  $charge_type = $_POST['charge_type'] ?? '';
  $description = $_POST['description'] ?? '';
  $amt = isset($_POST['amt']) ? floatval($_POST['amt']) : 0;
  $charge_date = $_POST['charge_date'] ?? date('Y-m-d');
  $status = $_POST['status'] ?? 'unpaid';
  $created_by = $_SESSION['user_id'] ?? null;

  // Validate charge_type
  if (!in_array($charge_type, $allowedChargeTypes)) {
    die("Invalid charge type.");
  }
  // Validate amount
  if ($amt <= 0) {
    die("Amount must be greater than zero.");
  }

  $stmt = $pdo->prepare("INSERT INTO charges (customer_id, invoice_id, charge_type, description, amt, charge_date, status, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->execute([$customer_id, $invoice_id, $charge_type, $description, $amt, $charge_date, $status, $created_by]);

  header("Location: charges.php");
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
    <a class="back-link" href="charges.php">← go back</a>
  <h1 class="h1">Add New Charge</h1>
  <form method="POST">
    <label for="customer_id">Customer</label>
    <select name="customer_id" required>
      <option value="">-- Select Customer --</option>
      <?php foreach ($customers as $c): ?>
        <option value="<?= $c['customer_id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <label for="invoice_id">Invoice (optional)</label>
    <select name="invoice_id">
      <option value="">-- None --</option>
      <?php foreach ($invoices as $inv): ?>
        <option value="<?= $inv['invoice_id'] ?>">#<?= $inv['invoice_id'] ?></option>
      <?php endforeach; ?>
    </select>

    <label for="charge_type">Charge Type</label>
    <select name="charge_type" required>
      <option value="">-- Select Charge Type --</option>
      <?php foreach ($allowedChargeTypes as $type): ?>
        <option value="<?= $type ?>"><?= ucfirst(str_replace('_',' ',$type)) ?></option>
      <?php endforeach; ?>
    </select>

    <label for="description">Description</label>
    <textarea name="description"></textarea><br>

    <label for="amt">Amount (KES)</label>
    <input type="number" step="0.01" name="amt" min="0.01" required>

    <label for="charge_date">Charge Date</label>
    <input type="date" name="charge_date" value="<?= date('Y-m-d') ?>">

    <label for="status">Status</label>
    <select name="status">
      <option value="unpaid">Unpaid</option>
      <option value="partial">Partial</option>
      <option value="paid">Paid</option>
    </select>

    <button type="submit">Submit Charge</button>
  </form>
</body>
</html>
