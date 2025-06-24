<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
require_once '../config/db_con.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../auths/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $meter_number = $_POST['meter_number'] ?? null;
    $billing_type = $_POST['billing_type'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $location = $_POST['location'] ?? '';

    // Basic validation
    if ($name && $billing_type && $phone && $location) {
        // Insert with NULL for account_number
        $stmt = $pdo->prepare("INSERT INTO customers (name, meter_number, account_number, billing_type, phone, location) VALUES (?, ?, '', ?, ?, ?)");
        $stmt->execute([$name, $meter_number, $billing_type, $phone, $location]);
        $lastInsertId = $pdo->lastInsertId();

        // Generate account number and update
        $generated_account_number = 'WBS-' . str_pad($lastInsertId, 6, '0', STR_PAD_LEFT);
        $pdo->prepare("UPDATE customers SET account_number = ? WHERE customer_id = ?")->execute([$generated_account_number, $lastInsertId]);

        echo "<script>alert('Customer added successfully. Account Number: $generated_account_number'); window.location='customers.php';</script>";
        exit();
    } else {
        echo "<script>alert('Please fill in all required fields.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add New Customer</title>
  <link rel="stylesheet" href="../assets/css/add_customer.css">
</head>
<body>
  <a class="back-link" href="customers.php">← Back to Customers</a>
  <h1>Add New Customer</h1>
  <form method="POST">
    <label for="name">Full Name</label>
    <input type="text" name="name" id="name" required>

    <label for="billing_type">Connection Type</label>
    <select name="billing_type" id="billing_type" required onchange="toggleMeterField()">
      <option value="">--Select--</option>
      <option value="flat_rate">Flat Rate</option>
      <option value="metered">Metered</option>
    </select>

    <div id="meter_fields" style="display: none;">
      <label for="meter_number">Meter Number</label>
      <input type="text" name="meter_number" id="meter_number" required>
    </div>
    <label for="phone">Phone</label>
    <input type="text" name="phone" id="phone" required>

    <label for="location">Location</label>
    <input type="text" name="location" id="location" required>

    <button type="submit">Add Customer</button>
  </form>
  <script>
  function toggleMeterField() {
    const billingType = document.getElementById('billing_type').value;
    const meterFields = document.getElementById('meter_fields');
    const meterInput = document.getElementById('meter_number');
    if (billingType === 'metered') {
      meterFields.style.display = 'block';
      meterInput.required = true;
    } else {
      meterFields.style.display = 'none';
      meterInput.required = false;
      meterInput.value = '';
    }
  }
  document.addEventListener('DOMContentLoaded', toggleMeterField);
  </script>
</body>
</html>
