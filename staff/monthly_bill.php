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

$month = $_GET['month'] ?? date('Y-m');
$status_filter = $_GET['status'] ?? '';

$query = "SELECT i.invoice_id, i.created_at, i.amt, i.status, c.name, c.account_number
  FROM invoices i
  LEFT JOIN customers c ON i.customer_id = c.customer_id
  WHERE DATE_FORMAT(i.created_at, '%Y-%m') = ?";
$params = [$month];

if ($status_filter && in_array($status_filter, ['paid', 'unpaid', 'partial'])) {
  $query .= " AND i.status = ?";
  $params[] = $status_filter;
}

$query .= " ORDER BY i.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

$total_billed = 0;
$total_paid = 0;
foreach ($invoices as $inv) {
  $total_billed += $inv['amt'];
  if ($inv['status'] === 'paid') $total_paid += $inv['amt'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Monthly Billing Report</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f9ff;
      padding: 20px;
    }
    h2 {
      color: #0077cc;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background: white;
    }
    th, td {
      padding: 10px;
      border: 1px solid #ccc;
    }
    th {
      background: #0077cc;
      color: white;
    }
    .summary {
      margin-top: 20px;
      background: #fff;
      padding: 15px;
      border-radius: 4px;
      box-shadow: 0 0 5px rgba(0,0,0,0.1);
    }
    .summary p {
      margin: 8px 0;
    }
    .btn-print {
      margin-top: 10px;
      background: #28a745;
      color: white;
      padding: 10px 15px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
  </style>
</head>
<body>
      <a class="back-link" href="dashst.php">← Back to Dashboard</a>

  <h2>Monthly Billing Report (<?= htmlspecialchars($month) ?>)</h2>

  <form method="GET">
    <label for="month">Select Month:</label>
    <input type="month" name="month" id="month" value="<?= htmlspecialchars($month) ?>">

    <label for="status">Status:</label>
    <select name="status" id="status">
      <option value="">All</option>
      <option value="paid" <?= $status_filter === 'paid' ? 'selected' : '' ?>>Paid</option>
      <option value="unpaid" <?= $status_filter === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
      <option value="partial" <?= $status_filter === 'partial' ? 'selected' : '' ?>>Partial</option>
    </select>

    <button type="submit">Filter</button>
    <button type="button" class="btn-print" onclick="window.print()">🖨️ Print Report</button>
  </form>

  <div class="summary">
    <p><strong>Total Invoiced:</strong> KES <?= number_format($total_billed, 2) ?></p>
    <p><strong>Total Paid:</strong> KES <?= number_format($total_paid, 2) ?></p>
    <p><strong>Total Outstanding:</strong> KES <?= number_format($total_billed - $total_paid, 2) ?></p>
  </div>

  <table>
    <thead>
      <tr>
        <th>Invoice #</th>
        <th>Date</th>
        <th>Customer</th>
        <th>Account #</th>
        <th>Total (KES)</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($invoices as $inv): ?>
        <tr>
          <td>#<?= $inv['invoice_id'] ?></td>
          <td><?= $inv['created_at'] ?></td>
          <td><?= htmlspecialchars($inv['name']) ?></td>
          <td><?= htmlspecialchars($inv['account_number']) ?></td>
          <td><?= number_format($inv['amt'], 2) ?></td>
          <td><?= ucfirst($inv['status']) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (count($invoices) === 0): ?>
        <tr><td colspan="6">No invoices found for this month.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
