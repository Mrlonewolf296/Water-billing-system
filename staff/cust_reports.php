<?php
session_start();
require_once '../config/db_con.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
  header("Location: ../index.php");
  exit();
}

$search = $_GET['search'] ?? '';
$customers = [];
if ($search) {
  $stmt = $pdo->prepare("SELECT * FROM customers WHERE name LIKE ? ORDER BY name");
  $stmt->execute(['%' . $search . '%']);
  $customers = $stmt->fetchAll();
} else {
  $customers = $pdo->query("SELECT * FROM customers ORDER BY name")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customer Statements</title>
  <link rel="stylesheet" href="../assets/css/staff.css">
  <style>
    
    .search-box {
      margin-bottom: 20px;
    }
    input[type="text"] {
      padding: 8px;
      width: 300px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    button {
      padding: 8px 12px;
      background-color: #0077cc;
      color: white;
      border: none;
      border-radius: 4px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
    }
    th, td {
      padding: 10px;
      border-bottom: 1px solid #ccc;
    }
    th {
      background-color: #0077cc;
      color: white;
    }
    a {
      color: #0077cc;
      text-decoration: none;
    }
    a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
        <a class="back-link" href="dashst.php">← Back to Dashboard</a>

  <h1>Customer Statements</h1>

  <form method="GET" class="search-box">
    <input type="text" name="search" placeholder="Search customer by name..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
  </form>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Name</th>
        <th>Account No.</th>
        <th>Email</th>
        <th>Phone</th>
        <th>View Statement</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($customers as $index => $cust): ?>
        <tr>
          <td><?= $index + 1 ?></td>
          <td><?= htmlspecialchars($cust['name']) ?></td>
          <td><?= htmlspecialchars($cust['account_number']) ?></td>
          <td><?= htmlspecialchars($cust['email']) ?></td>
          <td><?= htmlspecialchars($cust['phone']) ?></td>
          <td><a href="view_statement.php?customer_id=<?= $cust['customer_id'] ?>">View</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (count($customers) === 0): ?>
        <tr><td colspan="6">No customers found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
