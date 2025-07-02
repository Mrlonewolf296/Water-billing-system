<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config/db_con.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../auths/login.php');
    exit();
}

$search = $_GET['search'] ?? '';
$searchQuery = "%$search%";

// Get customers with their latest billing info
$stmt = $pdo->prepare("
    SELECT c.*, 
           COALESCE(SUM(CASE WHEN i.status != 'paid' THEN i.amt END), 0) as outstanding_balance,
           MAX(r.created_at) as last_reading_date,
           MAX(r.curr_reading) as last_reading
    FROM customers c
    LEFT JOIN invoices i ON c.customer_id = i.customer_id
    LEFT JOIN readings r ON c.customer_id = r.customer_id
    WHERE c.name LIKE ? OR c.meter_number LIKE ? OR c.account_number LIKE ?
    GROUP BY c.customer_id
    ORDER BY c.name ASC
");
$stmt->execute([$searchQuery, $searchQuery, $searchQuery]);
$customers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Billing Management</title>
    <link rel="stylesheet" href="../assets/css/staff.css">
    <style>
        .billing-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .customer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .balance-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin: 10px 0;
        }
        .balance-item {
            text-align: center;
            padding: 8px;
            border-radius: 4px;
        }
        .outstanding { background: #ffe6e6; color: #d63384; }
        .paid { background: #e6f7e6; color: #198754; }
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            cursor: pointer;
        }
        .btn-primary { background: #0077cc; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-info { background: #17a2b8; color: white; }
    </style>
</head>
<body>
    <a class="back-link" href="dashst.php">← Back to Dashboard</a>
    <h1>Customer Billing Management</h1>
    
    <div class="top-bar">
        <form class="search-bar" method="GET">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                   placeholder="Search customers...">
            <button type="submit">Search</button>
        </form>
    </div>

    <?php foreach ($customers as $customer): ?>
    <div class="billing-card">
        <div class="customer-header">
            <div>
                <h3><?= htmlspecialchars($customer['name']) ?></h3>
                <small>
                    <?= htmlspecialchars($customer['account_number']) ?> | 
                    <?= htmlspecialchars($customer['meter_number'] ?: 'Flat Rate') ?> |
                    <?= htmlspecialchars($customer['location']) ?>
                </small>
            </div>
            <div class="balance-info">
                <div class="balance-item <?= $customer['outstanding_balance'] > 0 ? 'outstanding' : 'paid' ?>">
                    <strong>KES <?= number_format($customer['outstanding_balance'], 2) ?></strong><br>
                    <small>Outstanding</small>
                </div>
            </div>
        </div>
        
        <div class="actions">
            <a href="create_bill.php?customer_id=<?= $customer['customer_id'] ?>" class="btn btn-primary">
                📄 Create Bill
            </a>
            <a href="add_reading.php?customer_id=<?= $customer['customer_id'] ?>" class="btn btn-success">
                📊 Add Reading
            </a>
            <a href="add_charge.php?customer_id=<?= $customer['customer_id'] ?>" class="btn btn-warning">
                💰 Add Charge
            </a>
            <a href="add_payment.php?customer_id=<?= $customer['customer_id'] ?>" class="btn btn-info">
                💳 Record Payment
            </a>
            <a href="customer_statement.php?customer_id=<?= $customer['customer_id'] ?>" class="btn btn-primary">
                📋 Statement
            </a>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($customers)): ?>
    <div class="billing-card">
        <p>No customers found.</p>
    </div>
    <?php endif; ?>
</body>
</html>