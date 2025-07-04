<?php
session_start();
// Ensure the session is started and the user is authenticated
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
require_once '../config/db_con.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
  header("Location: ../auths/login.php");
  exit();
}

// Fetch all customers with their balances
$search = $_GET['search'] ?? '';
$searchQuery = "%$search%";
$query = "SELECT c.customer_id, c.name, c.meter_number, c.phone,
  COALESCE(SUM(i.amt), 0) AS total_billed,
  COALESCE((SELECT SUM(p.cash + p.mpesa + p.bank) FROM payments p WHERE p.customer_id = c.customer_id), 0) AS total_paid
  FROM customers c
  LEFT JOIN invoices i ON c.customer_id = i.customer_id
  WHERE c.name LIKE :name OR c.meter_number LIKE :meter_number
  GROUP BY c.customer_id, c.name, c.meter_number, c.phone
  ORDER BY c.name";

$stmt = $pdo->prepare($query);
$stmt->execute([
  'name' => "%$search%",
  'meter_number' => "%$search%"
]);
$customers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customer Balances Report</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f4f9ff; padding: 20px; }
    h2 { color: #0077cc; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
    th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
    th { background: #0077cc; color: white; }
    .btn-print { margin-top: 10px; background: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
    @media print {
      body {
        background: white;
        padding: 0;
      }
      .search-bar{
        display: none;
      }
      .btn-print {
        display: none;
      }
      .back-link {
        display: none;
      }
    }
  </style>
</head>
<body>
  <a class="back-link" href="dashst.php">← Back to Dashboard</a>
  <h2>Customer Balances Report</h2>
  <form class="search-bar" method="GET">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or meter number">
    <button type="submit">Search</button>
  </form>
  <button type="button" class="btn-print" onclick="window.print()">🖨️ Print Report</button>

  <table>
    <thead>
      <tr>
        <th>Customer</th>
        <th>Meter Number</th>
        <th>Phone</th>
        <th>Total Billed (KES)</th>
        <th>Total Paid (KES)</th>
        <th>Balance (KES)</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($customers as $row): 
        $balance = $row['total_billed'] - $row['total_paid']; ?>
        <tr>
          <td><?= htmlspecialchars($row['name']) ?></td>
          <td><?= htmlspecialchars($row['meter_number']) ?></td>
          <td><?= htmlspecialchars($row['phone']) ?></td>
          <td><?= number_format($row['total_billed'], 2) ?></td>
          <td><?= number_format($row['total_paid'], 2) ?></td>
          <td><?= number_format($balance, 2) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (count($customers) === 0): ?>
        <tr><td colspan="6">No data found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
