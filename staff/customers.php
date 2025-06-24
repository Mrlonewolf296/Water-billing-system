<?php
session_start();
ob_start();
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('display_errors', 1);
require_once '../config/db_con.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../index.php');
    exit();
}

$search = $_GET['search'] ?? '';
$searchQuery = "%$search%";

$stmt = $pdo->prepare("SELECT * FROM customers WHERE name LIKE ? OR meter_number LIKE ? ORDER BY name ASC");
$stmt->execute([$searchQuery, $searchQuery]);
$customers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers</title>
    <link rel="stylesheet" href="../assets/css/staff.css">

</head>

<body>
    <a class="back-link" href="dashst.php">← Back to Dashboard</a>
    <h1>Customer Records</h1>
<div>
    <form class="search-bar" method="GET">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or meter number" required>
        <button type="submit">Search</button>
    </form>
</div>
    <!--button to add new customer-->
    <div class="add_customer" style="float: right; margin-bottom: 20px;">
        <a href="add_customers.php" class="button">+ Add New Customer</a>
    </div>
<div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Connection Type</th>
                <th>Meter No.</th>
                <th>Acc No.</th>
                <th>Phone</th>
                <th>Location</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customers as $index => $customer): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($customer['name']) ?></td>
                    <td><?= ucfirst($customer['billing_type']) ?></td>
                    <td><?= htmlspecialchars($customer['meter_number']) ?: 'N/A' ?></td>
                    <td><?= htmlspecialchars($customer['account_number']) ?></td>
                    <td><?= htmlspecialchars($customer['phone']) ?></td>
                    <td><?= htmlspecialchars($customer['location']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (count($customers) === 0): ?>
                <tr>
                    <td colspan="6">No customers found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>

</html>