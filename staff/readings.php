<?php
session_start();
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

$stmt = $pdo->prepare("SELECT r.*, c.name, c.meter_number, r.usage_cost FROM readings r JOIN customers c ON r.customer_id = c.customer_id WHERE c.name LIKE ? OR c.meter_number LIKE ? ORDER BY r.created_at DESC");
$stmt->execute([$searchQuery, $searchQuery]);
$readings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Meter Readings</title>
  <link rel="stylesheet" href="../assets/css/stylse.css">
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
    .edit-button {
      padding: 5px 10px;
      background-color: #ffc107;
      color: #000;
      border: none;
      border-radius: 4px;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <a class="back-link" href="dashst.php">← Back to Dashboard</a>
  <h1>Meter Readings</h1>
  <div class="top-bar">
    <form class="search-bar" method="GET">
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or meter number">
      <button type="submit">Search</button>
    </form>
    <a class="add-button" href="add_readings.php">+ Add Reading</a>
  </div>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Customer</th>
        <th>Meter No.</th>
        <th>Previous</th>
        <th>Current</th>
        <th>Units Used</th>
        <th>Usage Cost (KES)</th>
        <th>Date</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($readings as $index => $reading): ?>
        <tr>
          <td><?= $index + 1 ?></td>
          <td><?= htmlspecialchars($reading['name']) ?></td>
          <td><?= htmlspecialchars($reading['meter_number']) ?></td>
          <td><?= $reading['prev_reading'] ?></td>
          <td><?= $reading['curr_reading'] ?></td>
          <td><?= $reading['usage'] ?></td>
          <td>
            <?php if ($reading['usage_cost'] !== null): ?>
              KES <?= number_format($reading['usage_cost'], 2) ?>
            <?php else: ?>
              N/A
            <?php endif; ?>
          </td>
          <td><?= date('d M Y', strtotime($reading['created_at'])) ?></td>
          <td><a class="edit-button" href="edit_reading.php?id=<?= $reading['readings_id'] ?>">Edit</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (count($readings) === 0): ?>
        <tr><td colspan="8">No readings found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
