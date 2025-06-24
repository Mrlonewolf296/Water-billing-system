<?php
session_start();
// Ensure error reporting is enabled for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
require_once '../config/db_con.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
  header("Location: ../index.php");
  exit();
}

$search = $_GET['search'] ?? '';
$searchQuery = "%$search%";

$stmt = $pdo->prepare("SELECT p.*, c.name, c.meter_number FROM payments p JOIN customers c ON p.customer_id = c.customer_id WHERE c.name LIKE ? OR c.meter_number LIKE ? ORDER BY p.created_at DESC");
$stmt->execute([$searchQuery, $searchQuery]);
$payments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payments</title>
  <link rel="stylesheet" href="../assets/css/stylec.css">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f4f9ff;
      margin: 0;
      padding: 20px;
    }
    h1 {
      color: #0077cc;
    }
    .top-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    .search-bar input {
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .search-bar button {
      padding: 8px 12px;
      background-color: #0077cc;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    .add-button {
      padding: 8px 16px;
      background-color: #28a745;
      color: white;
      border: none;
      border-radius: 4px;
      text-decoration: none;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      background-color: white;
    }
    th, td {
      padding: 10px;
      text-align: left;
      border-bottom: 1px solid #ccc;
    }
    th {
      background-color: #0077cc;
      color: white;
    }
    tr:hover {
      background-color: #f1f1f1;
    }
    .back-link {
      display: inline-block;
      margin-bottom: 15px;
      color: #0077cc;
      text-decoration: none;
    }
  </style>
</head>
<body>
<a class="back-link" href="dashst.php">← Back to Dashboard</a>
<h1>Payments</h1>
<div class="top-bar">
  <form class="search-bar" method="GET">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or meter number">
    <button type="submit">Search</button>
  </form>
  <a class="add-button" href="add_payment.php">+ Record Payment</a>
</div>
<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Customer</th>
      <th>Meter No.</th>
      <th>Amount</th>
      <th>Payment Method</th>
      <th>Payment Date</th>
      <th>Reference</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($payments as $index => $payment): ?>
      <tr>
        <td><?= $index + 1 ?></td>
        <td><?= htmlspecialchars($payment['name']) ?></td>
        <td><?= htmlspecialchars($payment['meter_number']) ?></td>
        <td>KES <?= number_format($payment['amount'], 2) ?></td>
        <td><?= htmlspecialchars($payment['method']) ?></td>
        <td><?= date('d M Y', strtotime($payment['created_at'])) ?></td>
        <td><?= htmlspecialchars($payment['reference']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (count($payments) === 0): ?>
      <tr><td colspan="7">No payments found.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
</body>
</html>
