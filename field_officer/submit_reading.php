<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
require_once '../config/db_con.php';

// Allow only field officers
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'field_officer') {
  header("Location: ../auths/login.php");
  exit();
}

$field_officer_id = $_SESSION['user_id'];

// Fetch all customers
$customers = $pdo->query("SELECT customer_id, name, meter_number FROM customers ORDER BY name")->fetchAll();

$selectedCustomerId = $_POST['customer_id'] ?? null;
$lastReading = 0;

// Fetch last reading if customer is selected
if ($selectedCustomerId) {
  $stmt = $pdo->prepare("SELECT curr_reading FROM readings WHERE customer_id = ? ORDER BY reading_month DESC LIMIT 1");
  $stmt->execute([$selectedCustomerId]);
  $lastReading = $stmt->fetchColumn() ?: 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reading'])) {
  $customer_id = $_POST['customer_id'] ?? null;
  $previous_reading = floatval($_POST['previous_reading'] ?? 0);
  $current_reading = floatval($_POST['current_reading'] ?? 0);
  $reading_date = $_POST['reading_month'] ?? date('Y-m');
  //$remarks = $_POST['remarks'] ?? null;

  if ($customer_id && $current_reading > $previous_reading) {
    $stmt = $pdo->prepare("INSERT INTO readings 
      (customer_id, field_officer_id, prev_reading, curr_reading, reading_month)
      VALUES (?, ?, ?, ?, ?)");

    $stmt->execute([$customer_id, $field_officer_id, $previous_reading, $current_reading, $reading_date]);

    $success = "Reading submitted successfully.";
  } else {
    $error = "Current reading must be greater than previous.";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Submit Meter Reading</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f0f2f5;
      padding: 20px;
    }

    h2 {
      text-align: center;
    }

    form {
      max-width: 500px;
      margin: auto;
      background: #fff;
      padding: 20px;
      border-radius: 10px;
    }

    label {
      font-weight: bold;
      display: block;
      margin-top: 15px;
    }

    input, select, textarea {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border-radius: 5px;
      border: 1px solid #ccc;
    }

    button {
      margin-top: 20px;
      width: 100%;
      padding: 12px;
      background: #28a745;
      color: white;
      font-size: 16px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
    }
    .back_link{
        display: block;
        margin-bottom: 20px;
        text-align: center;
        color: #007bff;
        text-decoration: none;
    }

    .message { text-align: center; color: green; margin-bottom: 15px; }
    .error { text-align: center; color: red; margin-bottom: 15px; }
  </style>
</head>
<body>
<a class="back-link" href="dashfo.php">← Back to Dashboard</a>
<h2>Submit Meter Reading</h2>

<?php if (isset($success)): ?>
  <div class="message"><?= htmlspecialchars($success) ?></div>
<?php elseif (isset($error)): ?>
  <div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST">
  <label for="customer_id">Customer</label>
  <select name="customer_id" onchange="this.form.submit()" required>
    <option value="">-- Select Customer --</option>
    <?php foreach ($customers as $cust): ?>
      <option value="<?= $cust['customer_id'] ?>" <?= $cust['customer_id'] == $selectedCustomerId ? 'selected' : '' ?>>
        <?= htmlspecialchars($cust['name']) ?> (Meter: <?= htmlspecialchars($cust['meter_number']) ?>)
      </option>
    <?php endforeach; ?>
  </select>

  <input type="hidden" name="submit_reading" value="1">

  <label for="previous_reading">Previous Reading</label>
  <input type="number" step="0.01" name="previous_reading" value="<?= htmlspecialchars($lastReading) ?>" readonly required>

  <label for="current_reading">Current Reading</label>
  <input type="number" step="0.01" name="current_reading" required>

  <label for="reading_month">Reading Month</label>
  <input type="month" name="reading_month" value="<?= date('Y-m') ?>">


  <button type="submit">Submit Reading</button>
</form>

</body>
</html>
