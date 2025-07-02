<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
require_once '../config/db_con.php';

// Allow only field officers
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'field_officer') {
  header("Location: ../auths/login.php");
  exit();
}

$search = $_GET['search'] ?? '';

$query = "
  SELECT 
    c.customer_id, 
    c.name, 
    c.meter_number, 
    c.phone,
    c.location,
    c.status,
    (
      SELECT r.curr_reading 
      FROM readings r 
      WHERE r.customer_id = c.customer_id 
      ORDER BY r.created_at DESC, r.readings_id DESC 
      LIMIT 1
    ) AS last_reading,
    (
      SELECT r.created_at 
      FROM readings r 
      WHERE r.customer_id = c.customer_id 
      ORDER BY r.created_at DESC, r.readings_id DESC 
      LIMIT 1
    ) AS last_reading_date
  FROM customers c
  WHERE c.name LIKE :name_search OR c.meter_number LIKE :meter_search
  ORDER BY c.name ASC
";

$stmt = $pdo->prepare($query);
$stmt->execute([
  'name_search' => "%$search%",
  'meter_search' => "%$search%"
]);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Customers</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f5f5f5;
      padding: 20px;
    }
    h2 { text-align: center; margin-bottom: 20px; }
    .search-bar { text-align: center; margin-bottom: 20px; }
    .search-bar input[type="text"] {
      padding: 10px; width: 80%; max-width: 400px; border: 1px solid #ccc; border-radius: 5px;
    }
    .customer-card {
      background: #fff; padding: 15px; margin-bottom: 15px; border-radius: 10px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .customer-card h3 { margin: 0; font-size: 18px; color: #333; }
    .customer-card p { margin: 5px 0; font-size: 14px; color: #555; }
    .status { font-weight: bold; color: green; }
    .status.disconnected { color: red; }
    .buttons { margin-top: 10px; }
    .buttons a {
      text-decoration: none; padding: 8px 12px; margin-right: 5px;
      background: #007bff; color: white; border-radius: 5px; font-size: 13px;
    }
    .buttons a:hover { background: #0056b3; }
  </style>
</head>
<body>

<h2>Assigned Customers</h2>

<div class="search-bar">
  <form method="get">
    <input type="text" name="search" placeholder="Search by name or meter number..." value="<?= htmlspecialchars($search) ?>">
  </form>
</div>

<?php if (count($customers) === 0): ?>
  <p style="text-align:center;">No customers found.</p>
<?php else: ?>
  <?php foreach ($customers as $cust): ?>
    <div class="customer-card">
      <h3><?= htmlspecialchars($cust['name']) ?> (<?= htmlspecialchars($cust['meter_number']) ?>)</h3>
      <p>📍 <?= htmlspecialchars($cust['location']) ?></p>
      <p>📞 <?= htmlspecialchars($cust['phone']) ?></p>
      <p>Status: 
        <span class="status <?= $cust['status'] === 'disconnected' ? 'disconnected' : '' ?>">
          <?= ucfirst($cust['status']) ?>
        </span>
      </p>
      <p>Last Reading: <?= $cust['last_reading'] !== null ? htmlspecialchars($cust['last_reading']) : 'N/A' ?> 
        on <?= $cust['last_reading_date'] !== null ? date('d M Y', strtotime($cust['last_reading_date'])) : 'N/A' ?></p>
      <div class="buttons">
        <a href="submit_reading.php?customer_id=<?= $cust['customer_id'] ?>">📝 Reading</a>
        <a href="reading_history.php?customer_id=<?= $cust['customer_id'] ?>">📊 History</a>
        <a href="report_issue.php?customer_id=<?= $cust['customer_id'] ?>">⚠️ Issue</a>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
