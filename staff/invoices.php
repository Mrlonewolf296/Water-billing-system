<?php
session_start();
// Ensure session is started and error reporting is enabled
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
require_once '../config/db_con.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
  header("Location: ../login.php");
  exit();
}

$search = $_GET['search'] ?? '';
$searchQuery = "%$search%";

$stmt = $pdo->prepare("SELECT i.*, c.name, c.meter_number FROM invoices i JOIN customers c ON i.customer_id = c.customer_id WHERE c.name LIKE ? OR c.meter_number LIKE ? ORDER BY i.created_at DESC");
$stmt->execute([$searchQuery, $searchQuery]);
$invoices = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoices</title>
  <link rel="stylesheet" href="../assets/css/staff.css">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f9f9f9;
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
  <h1>Invoice Records</h1>
  <div class="top-bar">
    <form class="search-bar" method="GET">
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or meter number">
      <button type="submit">Search</button>
    </form>
  </div>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Customer</th>
        <th>Meter No.</th>
        <th>Amount</th>
        <th>Status</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($invoices as $index => $invoice): ?>
        <tr>
          <td><?= $index + 1 ?></td>
          <td><?= htmlspecialchars($invoice['name']) ?></td>
          <td><?= htmlspecialchars($invoice['meter_number']) ?></td>
          <td>KES <?= number_format($invoice['amt'], 2) ?></td>
          <td><?= ucfirst($invoice['status']) ?></td>
          <td><?= date('d M Y', strtotime($invoice['created_at'])) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (count($invoices) === 0): ?>
        <tr><td colspan="6">No invoices found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
