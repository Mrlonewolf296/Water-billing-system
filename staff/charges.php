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

// Fetch charges
$stmt = $pdo->query("SELECT c.charge_id, cu.name AS customer_name, i.invoice_no, c.charge_type, c.description, c.amt, c.status, c.charge_date FROM charges c LEFT JOIN customers cu ON c.customer_id = cu.customer_id LEFT JOIN invoices i ON c.invoice_id = i.invoice_id ORDER BY c.charge_date DESC");
$charges = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Charges</title>
  <link rel="stylesheet" href="../assets/css/staff.css">
  <style>
    
    .add-button {
      padding: 8px 16px;
      background-color: #28a745;
      color: white;
      border: none;
      border-radius: 4px;
      text-decoration: none;
      float: right;
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
    .action-btns a {
      margin-right: 8px;
      color: #0077cc;
      text-decoration: underline;
    }
  </style>
</head>
<body>
<a class="back-link" href="dashst.php">← Back to Dashboard</a>
<h1>Service Charges</h1>
<a class="add-button" href="add_charges.php">+ Add New Charge</a>
<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Customer</th>
      <th>Invoice</th>
      <th>Charge Type</th>
      <th>Description</th>
      <th>Amount (KES)</th>
      <th>Status</th>
      <th>Date</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($charges as $index => $charge): ?>
      <tr>
        <td><?= $index + 1 ?></td>
        <td><?= htmlspecialchars($charge['customer_name'] ?? '—') ?></td>
        <td><?= $charge['invoice_no'] ? '#' . $charge['invoice_no'] : '—' ?></td>
        <td><?= htmlspecialchars($charge['charge_type']) ?></td>
        <td><?= htmlspecialchars($charge['description']) ?></td>
        <td><?= number_format($charge['amt'], 2) ?></td>
        <td><?= ucfirst($charge['status']) ?></td>
        <td><?= htmlspecialchars($charge['charge_date']) ?></td>
        <td class="action-btns">
          <a href="edit_charge.php?id=<?= $charge['charge_id'] ?>">Edit</a>
          <a href="delete_charge.php?id=<?= $charge['charge_id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (count($charges) === 0): ?>
      <tr><td colspan="9">No charges found.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
</body>
</html>
