<?php
session_start();
ob_start();
error_reporting(E_ALL);
ini_set('log_errors', 1);   
ini_set('display_errors', 1);
require_once '../config/db_con.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../auths/login.php');
    exit();
}
//fetch staff details from database
$staff_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'staff' AND user_id = ?" );
$stmt->execute([$staff_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff Dashboard</title>
  <link rel="stylesheet" href="../assets/css/staff.css">

</head>
<body>
  <header>
    <h1>Water Billing - Staff Dashboard</h1>
    <nav>
      <ul>
        <li><a href="dashst.php">Dashboard</a></li>
        <li><a href="customers.php">Customers</a></li>
        <li><a href="billing.php">Billing Management</a></li>
        <li><a href="readings.php">Readings</a></li>
        <li><a href="invoices.php">Invoices</a></li>
        <li><a href="payments.php">Payments</a></li>
        <li><a href="charges.php">Charges</a></li>
        <li><a href="cust_reports.php">Reports</a></li>
        <li><a href="monthly_bill.php">Monthly Billing</a></li>
        <li><a href="payment_reports.php">Payment Reports</a></li>
        <li><a href="balances_reports.php">Balances Reports</a></li>
        <li><a href="tarriffs.php">Add Tarriffs</a></li>
        <li><a href="../auths/logout.php">Logout</a></li>
      </ul>
    </nav>
  </header>

  <main class="dashboard">
    <h2>Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?> 👋</h2>
    <section class="stats">
      <?php
        $customerCount = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
        $invoiceCount = $pdo->query("SELECT COUNT(*) FROM invoices")->fetchColumn();
        $paymentTotal = $pdo->query("SELECT SUM(cash) + SUM(mpesa) + SUM(bank) FROM payments")->fetchColumn();
      ?>
      <div class="card">Total Customers<br><span><?= $customerCount ?></span></div>
      <div class="card">Total Invoices<br><span><?= $invoiceCount ?></span></div>
      <div class="card">Payments Received<br><span>Ksh <?= number_format($paymentTotal ?? 0, 2) ?></span></div>
    </section>
  </main>

  <footer>
    &copy; <?= date('Y') ?> Water Billing System. All rights reserved.
  </footer>
</body>
</html>