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

$customers = $pdo->query("SELECT customer_id, name, meter_number FROM customers ORDER BY name")->fetchAll();
  $invoices = $pdo->query("SELECT invoice_id FROM invoices WHERE status IN ('unpaid', 'partial') ORDER BY invoice_id DESC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $customer_id = $_POST['customer_id'] ?? null;
  $invoice_id = $_POST['invoice_id'] ?? null;
  $cash = floatval($_POST['cash'] ?? 0);
  $mpesa = floatval($_POST['mpesa'] ?? 0);
  $bank = floatval($_POST['bank'] ?? 0);
  $reference = $_POST['reference'] ?? null;
  $payment_date = $_POST['payment_date'] ?? date('Y-m-d');

  // Determine main payment method (for record-keeping)
  if ($cash > 0) $method = 'cash';
  elseif ($mpesa > 0) $method = 'mpesa';
  elseif ($bank > 0) $method = 'bank';
  else $method = null;

  // Optional: Ensure at least one payment value > 0
  if (($cash + $mpesa + $bank) <= 0) {
    die("Error: Enter at least one payment amount.");
  }

  // Insert payment
  $stmt = $pdo->prepare("INSERT INTO payments 
    (customer_id, invoice_id, mpesa, cash, bank, reference, payment_date)
    VALUES (?, ?, ?, ?, ?, ?, ?)");

  $stmt->execute([$customer_id, $invoice_id, $mpesa, $cash, $bank, $reference, $payment_date]);

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
      <option value="<?= $cust['customer_id'] ?>">
        <?= htmlspecialchars($cust['name']) ?> (<?= htmlspecialchars($cust['meter_number']) ?>)
      </option>
    <?php endforeach; ?>
  </select>

  <label for="invoice_id">Invoice</label>
  <select name="invoice_id" id="invoice_id" required>
    <option value="">-- Select Invoice --</option>
    <!-- Options will be populated by JavaScript -->
  </select>

  <label for="cash">Cash Amount (KES)</label>
  <input type="number" step="0.01" name="cash" placeholder="0.00">

  <label for="mpesa">Mpesa Amount (KES)</label>
  <input type="number" step="0.01" name="mpesa" placeholder="0.00">

  <label for="bank">Bank Amount (KES)</label>
  <input type="number" step="0.01" name="bank" placeholder="0.00">

  <label for="reference">Reference</label>
  <input type="text" name="reference" placeholder="e.g., MPESA123ABC or BankSlip#456">

  <label for="payment_date">Payment Date</label>
  <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>">

  <button type="submit">Submit Payment</button>
</form>
 <script>
    const invoicesByCustomer = {};
    <?php
      // Build a mapping of customer_id => invoices
      $invoiceMap = [];
      $stmt = $pdo->query("SELECT invoice_id, customer_id FROM invoices WHERE status = 'unpaid' ORDER BY invoice_id DESC");
      foreach ($stmt as $row) {
        $cid = $row['customer_id'];
        if (!isset($invoiceMap[$cid])) $invoiceMap[$cid] = [];
        $invoiceMap[$cid][] = [
          'invoice_id' => $row['invoice_id']
        ];
      }
      echo "Object.assign(invoicesByCustomer, " . json_encode($invoiceMap) . ");";
    ?>

    document.querySelector('select[name="customer_id"]').addEventListener('change', function() {
      const customerId = this.value;
      const invoiceSelect = document.getElementById('invoice_id');
      invoiceSelect.innerHTML = '<option value="">-- Select Invoice --</option>';
      if (invoicesByCustomer[customerId]) {
        invoicesByCustomer[customerId].forEach(function(inv) {
          const opt = document.createElement('option');
          opt.value = inv.invoice_id;
          opt.textContent = 'Invoice #' + inv.invoice_id;
          invoiceSelect.appendChild(opt);
        });
      }
    });
  </script>
</body>
</html>
