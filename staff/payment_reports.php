<?php
session_start();
require_once '../config/db_con.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
  header("Location: ../auths/login.php");
  exit();
}

$month = $_GET['month'] ?? date('Y-m');
$method = $_GET['method'] ?? '';

$query = "SELECT p.payment_id, p.payment_date, p.cash, p.mpesa, p.bank, c.name, c.customer_id
  FROM payments p
  LEFT JOIN customers c ON p.customer_id = c.customer_id
  WHERE DATE_FORMAT(p.payment_date, '%Y-%m') = ?";
$params = [$month];

if ($method && in_array($method, ['cash', 'mpesa', 'bank'])) {
  $query .= " AND p.$method > 0";
}

$query .= " ORDER BY p.payment_date DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll();

$total_cash = 0;
$total_mpesa = 0;
$total_bank = 0;
foreach ($payments as $pay) {
  $total_cash += $pay['cash'];
  $total_mpesa += $pay['mpesa'];
  $total_bank += $pay['bank'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Collection Report</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f4f9ff; padding: 20px; }
    h2 { color: #0077cc; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
    th, td { padding: 10px; border: 1px solid #ccc; }
    th { background: #0077cc; color: white; }
    .summary { margin-top: 20px; background: #fff; padding: 15px; border-radius: 4px; box-shadow: 0 0 5px rgba(0,0,0,0.1); }
    .summary p { margin: 8px 0; }
    .btn-print { margin-top: 10px; background: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
  </style>
</head>
<body>
    <a href="./dashst.php" class="back-link">← Back to Dashboard</a>
  <h2>Payment Collection Report (<?= htmlspecialchars($month) ?>)</h2>

  <form method="GET">
    <label for="month">Select Month:</label>
    <input type="month" name="month" id="month" value="<?= htmlspecialchars($month) ?>">

    <label for="method">Payment Method:</label>
    <select name="method" id="method">
      <option value="">All</option>
      <option value="cash" <?= $method === 'cash' ? 'selected' : '' ?>>Cash</option>
      <option value="mpesa" <?= $method === 'mpesa' ? 'selected' : '' ?>>M-Pesa</option>
      <option value="bank" <?= $method === 'bank' ? 'selected' : '' ?>>Bank</option>
    </select>

    <button type="submit">Filter</button>
    <button type="button" class="btn-print" onclick="window.print()">🖨️ Print Report</button>
  </form>

  <div class="summary">
    <p><strong>Total Cash:</strong> KES <?= number_format($total_cash, 2) ?></p>
    <p><strong>Total M-Pesa:</strong> KES <?= number_format($total_mpesa, 2) ?></p>
    <p><strong>Total Bank:</strong> KES <?= number_format($total_bank, 2) ?></p>
    <p><strong>Total Collected:</strong> KES <?= number_format($total_cash + $total_mpesa + $total_bank, 2) ?></p>
  </div>

  <table>
    <thead>
      <tr>
        <th>Payment #</th>
        <th>Date</th>
        <th>Customer</th>
        <th>Customer ID</th>
        <th>Cash (KES)</th>
        <th>M-Pesa (KES)</th>
        <th>Bank (KES)</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($payments as $pay): ?>
        <tr>
          <td>#<?= $pay['payment_id'] ?></td>
          <td><?= $pay['payment_date'] ?></td>
          <td><?= htmlspecialchars($pay['name']) ?></td>
          <td><?= htmlspecialchars($pay['customer_id']) ?></td>
          <td><?= number_format($pay['cash'], 2) ?></td>
          <td><?= number_format($pay['mpesa'], 2) ?></td>
          <td><?= number_format($pay['bank'], 2) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (count($payments) === 0): ?>
        <tr><td colspan="7">No payments found for this month.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
