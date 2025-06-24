<?php
session_start();
require_once '../config/db_con.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
  header("Location: ../auths/login.php");
  exit();
}

$customers = $pdo->query("SELECT customer_id, name, meter_number FROM customers ORDER BY name")->fetchAll();
$invoices = $pdo->query("SELECT invoice_id FROM invoices WHERE status = 'unpaid' ORDER BY invoice_id DESC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $customer_id = $_POST['customer_id'];
  $invoice_id = $_POST['invoice_id'] ?: null; // optional
  $cash = floatval($_POST['cash'] ?? 0);
  $mpesa = floatval($_POST['mpesa'] ?? 0);
  $bank = floatval($_POST['bank'] ?? 0);
  $payment_date = $_POST['payment_date'] ?? date('Y-m-d');

  // Decide primary payment method (optional logic)
  if ($cash > 0) $method = 'cash';
  elseif ($mpesa > 0) $method = 'mpesa';
  elseif ($bank > 0) $method = 'bank';
  else $method = null;

  $stmt = $pdo->prepare("INSERT INTO payments (customer_id, invoice_id, payment_method, mpesa, cash, bank, payment_date)
                       VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$customer_id, $invoice_id, $method, $mpesa, $cash, $bank, $payment_date]);

$lastPaymentId = $pdo->lastInsertId();

header("Location: print_payment.php?payment_id=" . $lastPaymentId);
exit();

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Record Payment</title>
  <link rel="stylesheet" href="../assets/css/add_customer.css">
</head>
<body>
<a class="back-link" href="payments.php">← Back to Payments</a>
<h1 class="h1">Record a New Payment</h1>
<form method="POST">
  <label for="customer_id">Customer</label>
  <select name="customer_id" required>
    <option value="">-- Select Customer --</option>
    <?php foreach ($customers as $cust): ?>
      <option value="<?= $cust['id'] ?>">
        <?= htmlspecialchars($cust['name']) ?> (<?= htmlspecialchars($cust['meter_number']) ?>)
      </option>
    <?php endforeach; ?>
  </select>

  <label for="amount">Amount (KES)</label>
  <input type="number" step="0.01" name="amount" required>

  <label for="method">Payment Method</label>
  <select name="method" required>
    <option value="Cash">Cash</option>
    <option value="Mpesa">Mpesa</option>
    <option value="Bank">Bank</option>
  </select>

  <label for="reference">Reference</label>
  <input type="text" name="reference" placeholder="e.g., QWJKE244KJ or BankSlipNo">

  <label for="payment_date">Payment Date</label>
  <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>">

  <button type="submit">Submit Payment</button>
</form>
</body>
</html>
