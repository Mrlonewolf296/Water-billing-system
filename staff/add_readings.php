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
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// Fetch customers
$customers = $pdo->query("SELECT customer_id, name, meter_number FROM customers WHERE billing_type = 'metered'")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $customer_id = $_POST['customer_id'];
  $previous = (float)$_POST['prev_reading'];
  $current = (float)$_POST['curr_reading'];
  $reading_month = $_POST['reading_month'];
  $date = $_POST['created_at'];
  $usage = $current - $previous;

  if ($current < $previous) {
    echo "<script>alert('Current reading cannot be less than previous reading.'); window.history.back();</script>";
    exit();
  }

  // Fetch active tariffs
  $tariffs = $pdo->query("SELECT * FROM tariffs WHERE status = 'active' ORDER BY min_unit ASC")->fetchAll();
  $remaining = $usage;
  $total_amount = 0;

  foreach ($tariffs as $slab) {
    $min = $slab['min_unit'];
    $max = $slab['max_unit'] ?? null;
    $rate = $slab['rate_per_unit'];

    if ($remaining <= 0) break;

    if ($max !== null) {
      $units_in_range = max(0, min($remaining, $max - $min + 1));
    } else {
      $units_in_range = $remaining;
    }

    $total_amount += $units_in_range * $rate;
    $remaining -= $units_in_range;
  }

  // Step 1: Insert Reading
  $stmt = $pdo->prepare("INSERT INTO readings (customer_id, prev_reading, curr_reading, reading_month, `usage`, usage_cost, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?)");
  $stmt->execute([$customer_id, $previous, $current, $reading_month, $usage, $total_amount, $date]);
  $reading_id = $pdo->lastInsertId();

  // Step 2: Generate invoice_no (e.g. INV-202506230001)
  $sequence = str_pad($reading_id, 4, '0', STR_PAD_LEFT);
  $invoice_no = 'INV-' . date('Ymd') . $sequence;

  // Step 3: Insert Invoice
  $stmt = $pdo->prepare("INSERT INTO invoices (invoice_no, customer_id, reading_id, billing_month, amt, status)
                         VALUES (?, ?, ?, ?, ?, 'unpaid')");
  $stmt->execute([$invoice_no, $customer_id, $reading_id, $reading_month, $total_amount]);
  $invoice_id = $pdo->lastInsertId();

  // Step 4: Insert Charge
  $charge_desc = "Water consumption for $usage units (Reading Date: $date)";
  $stmt = $pdo->prepare("INSERT INTO charges (customer_id, invoice_id, description, amt, charge_type, charge_date, status, created_by)
                         VALUES (?, ?, ?, ?, 'consumption', ?, 'unpaid', ?)");
  $stmt->execute([$customer_id, $invoice_id, $charge_desc, $total_amount, $date, $_SESSION['user_id']]);
  $charge_id = $pdo->lastInsertId();

  // Step 5: Insert into Customer Balances
  // Get the charge_type id for 'consumption'
  $chargeTypeStmt = $pdo->prepare("SELECT charge_id FROM charge_type WHERE name = 'consumption' LIMIT 1");
  $chargeTypeStmt->execute();
  $chargeTypeRow = $chargeTypeStmt->fetch();
  $charge_type_id = $chargeTypeRow ? $chargeTypeRow['charge_id'] : null;

  if (!$charge_type_id) {
      die("Charge type 'consumption' not found in charge_type table.");
  }

  // Now insert into customer_balances with the correct charge_type id
  $stmt = $pdo->prepare("INSERT INTO customer_balances (cust_id, balance, charge_type, last_updated)
                       VALUES (?, ?, ?, ?)");
  $stmt->execute([$customer_id, $total_amount, $charge_type_id, $date]);

  echo "<script>alert('Reading, invoice and charge saved. Total: KES " . number_format($total_amount, 2) . "'); window.location='readings.php';</script>";
  exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Meter Reading</title>
  <link rel="stylesheet" href="../assets/css/stylev.css">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f4f9ff;
      margin: 0;
      padding: 20px;
    }
    form {
      max-width: 500px;
      margin: auto;
      background: #ffffff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
    }
    input, select {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    button {
      padding: 10px 16px;
      background-color: #0077cc;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    a.back-link {
      display: block;
      margin-bottom: 20px;
      color: #0077cc;
      text-decoration: none;
    }
  </style>
</head>
<body>
<a class="back-link" href="readings.php">← Back to Readings</a>
<h2>Add Meter Reading</h2>
<form method="POST">
  <label>Customer</label>
  <select name="customer_id" required>
    <option value="">Select Customer</option>
    <?php foreach ($customers as $cust): ?>
      <option value="<?= $cust['customer_id'] ?>"> <?= htmlspecialchars($cust['name']) ?> - <?= $cust['meter_number'] ?> </option>
    <?php endforeach; ?>
  </select>
  <label>Previous Reading</label>
  <input type="number" name="prev_reading" required>
  <label>Current Reading</label>
  <input type="number" name="curr_reading" required>
  <label>Reading Month</label>
  <select name="reading_month" required>
    <option value="">Select Month</option>
    <?php
      $months = [
      '01' => 'January', '02' => 'February', '03' => 'March',
      '04' => 'April', '05' => 'May', '06' => 'June',
      '07' => 'July', '08' => 'August', '09' => 'September',
      '10' => 'October', '11' => 'November', '12' => 'December'
      ];
      $currentYear = date('Y');
      for ($y = $currentYear; $y >= $currentYear - 2; $y--) {
      foreach ($months as $num => $name) {
        $value = $name . ' ' . $y; // e.g. "June 2024"
        echo "<option value=\"$value\">$value</option>";
      }
      }
    ?>
  </select>
  <label>Reading Date</label>
  <input type="date" name="created_at" value="<?= date('Y-m-d') ?>">
  <button type="submit">Submit Reading</button>
</form>
</body>
</html>
