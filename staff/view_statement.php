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

$customer_id = $_GET['customer_id'] ?? null;
if (!$customer_id) {
  echo "Customer ID not provided.";
  exit();
}

// Fetch customer details
$customer = $pdo->prepare("SELECT * FROM customers WHERE customer_id = ?");
$customer->execute([$customer_id]);
$cust = $customer->fetch();
if (!$cust) {
  echo "Customer not found.";
  exit();
}

// Fetch invoices
$invoices = $pdo->prepare("SELECT * FROM invoices WHERE customer_id = ? ORDER BY created_at DESC");
$invoices->execute([$customer_id]);

// Fetch payments
$payments = $pdo->prepare("SELECT * FROM payments WHERE customer_id = ? ORDER BY payment_date DESC");
$payments->execute([$customer_id]);

// Fetch charges
$charges = $pdo->prepare("SELECT * FROM charges WHERE customer_id = ? ORDER BY charge_date DESC");
$charges->execute([$customer_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customer Statement</title>
  <link rel="stylesheet" href="../assets/css/staff.css">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f4f9ff;
      padding: 20px;
    }
    h2 {
      color: #0077cc;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 30px;
      background-color: #fff;
    }
    th, td {
      padding: 10px;
      border: 1px solid #ccc;
    }
    th {
      background-color: #0077cc;
      color: white;
    }
    .btn-print {
      padding: 10px 15px;
      background-color: #28a745;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>
  <h2>Statement for <?= htmlspecialchars($cust['name']) ?> (<?= htmlspecialchars($cust['account_number']) ?>)</h2>
  <p>Location: <?= htmlspecialchars($cust['location']) ?> | Phone: <?= htmlspecialchars($cust['phone']) ?></p>

  <button class="btn-print" onclick="window.print()">🖨️ Print Statement</button>

  <h3>Invoices</h3>
  <table>
    <thead>
      <tr>
        <th>Invoice #</th>
        <th>Date</th>
        <th>Total</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($invoices as $inv): ?>
        <tr>
          <td>#<?= $inv['created_at'] ?></td>
          <td><?= $inv['created_at'] ?></td>
          <td><?= number_format($inv['amt'], 2) ?></td>
          <td><?= ucfirst($inv['status']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if ($invoices->rowCount() === 0): ?>
        <tr><td colspan="4">No invoices found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <h3>Payments</h3>
  <table>
    <thead>
      <tr>
        <th>Date</th>
        <th>Cash</th>
        <th>M-Pesa</th>
        <th>Bank</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($payments as $pay):
        $total_paid = ($pay['cash'] ?? 0) + ($pay['mpesa'] ?? 0) + ($pay['bank'] ?? 0);
      ?>
        <tr>
          <td><?= $pay['payment_date'] ?></td>
          <td><?= number_format($pay['cash'], 2) ?></td>
          <td><?= number_format($pay['mpesa'], 2) ?></td>
          <td><?= number_format($pay['bank'], 2) ?></td>
          <td><?= number_format($total_paid, 2) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if ($payments->rowCount() === 0): ?>
        <tr><td colspan="5">No payments found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <h3>Charges</h3>
  <table>
    <thead>
      <tr>
        <th>Type</th>
        <th>Description</th>
        <th>Amount</th>
        <th>Status</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($charges as $chg): ?>
        <tr>
          <td><?= htmlspecialchars($chg['charge_type']) ?></td>
          <td><?= htmlspecialchars($chg['description']) ?></td>
          <td><?= number_format($chg['amt'], 2) ?></td>
          <td><?= ucfirst($chg['status']) ?></td>
          <td><?= $chg['charge_date'] ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if ($charges->rowCount() === 0): ?>
        <tr><td colspan="5">No charges found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <a href="cust_reports.php">← Back to Customer List</a>
</body>
</html>
