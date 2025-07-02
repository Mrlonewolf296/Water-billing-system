<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../config/db_con.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../auths/login.php');
    exit();
}

$customer_id = $_GET['customer_id'] ?? null;
if (!$customer_id) {
    header('Location: billing.php');
    exit();
}

// Get customer details
$stmt = $pdo->prepare("SELECT * FROM customers WHERE customer_id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();

// Get all charges
$stmt = $pdo->prepare("
    SELECT c.*, i.invoice_id 
    FROM charges c 
    LEFT JOIN invoices i ON c.invoice_id = i.invoice_id 
    WHERE c.customer_id = ? 
    ORDER BY c.charge_date DESC
");
$stmt->execute([$customer_id]);
$charges = $stmt->fetchAll();

// Get all payments
$stmt = $pdo->prepare("
    SELECT p.*, (p.cash + p.mpesa + p.bank) as total_amount
    FROM payments p 
    WHERE p.customer_id = ? 
    ORDER BY p.created_at DESC
");
$stmt->execute([$customer_id]);
$payments = $stmt->fetchAll();

// Get readings for metered customers
$readings = [];
if ($customer['billing_type'] === 'metered') {
    $stmt = $pdo->prepare("SELECT * FROM readings WHERE customer_id = ? ORDER BY created_at DESC LIMIT 12");
    $stmt->execute([$customer_id]);
    $readings = $stmt->fetchAll();
}

// Calculate totals
$total_charges = array_sum(array_column($charges, 'amt'));
$total_payments = array_sum(array_column($payments, 'total_amount'));
$outstanding_balance = $total_charges - $total_payments;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Statement - <?= htmlspecialchars($customer['name']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f8f9fa; }
        .statement-header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .statement-section { background: white; padding: 15px; border-radius: 8px; margin-bottom: 15px; }
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .summary-card { background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; }
        .outstanding { background: #ffe6e6; color: #d63384; }
        .paid { background: #e6f7e6; color: #198754; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        th { background: #0077cc; color: white; }
        .print-btn { background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print">
        <a href="billing.php">← Back to Billing</a>
        <button class="print-btn" onclick="window.print()">🖨️ Print Statement</button>
    </div>
    
    <div class="statement-header">
        <h1>Customer Statement</h1>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <h3><?= htmlspecialchars($customer['name']) ?></h3>
                <p>Account: <?= htmlspecialchars($customer['account_number']) ?></p>
                <p>Meter: <?= htmlspecialchars($customer['meter_number'] ?: 'Flat Rate') ?></p>
                <p>Location: <?= htmlspecialchars($customer['location']) ?></p>
                <p>Phone: <?= htmlspecialchars($customer['phone']) ?></p>
            </div>
            <div>
                <p>Statement Date: <?= date('d M Y') ?></p>
                <p>Billing Type: <?= ucfirst($customer['billing_type']) ?></p>
            </div>
        </div>
    </div>

    <div class="statement-section">
        <h3>Account Summary</h3>
        <div class="summary-grid">
            <div class="summary-card">
                <strong>Total Charges</strong><br>
                KES <?= number_format($total_charges, 2) ?>
            </div>
            <div class="summary-card">
                <strong>Total Payments</strong><br>
                KES <?= number_format($total_payments, 2) ?>
            </div>
            <div class="summary-card <?= $outstanding_balance > 0 ? 'outstanding' : 'paid' ?>">
                <strong>Outstanding Balance</strong><br>
                KES <?= number_format($outstanding_balance, 2) ?>
            </div>
        </div>
    </div>

    <?php if (!empty($readings)): ?>
    <div class="statement-section">
        <h3>Recent Meter Readings</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Previous</th>
                    <th>Current</th>
                    <th>Usage</th>
                    <th>Cost (KES)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($readings as $reading): ?>
                <tr>
                    <td><?= date('d M Y', strtotime($reading['created_at'])) ?></td>
                    <td><?= $reading['prev_reading'] ?></td>
                    <td><?= $reading['curr_reading'] ?></td>
                    <td><?= $reading['usage'] ?></td>
                    <td><?= number_format($reading['usage_cost'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="statement-section">
        <h3>Charges History</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Amount (KES)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($charges as $charge): ?>
                <tr>
                    <td><?= date('d M Y', strtotime($charge['charge_date'])) ?></td>
                    <td><?= ucfirst(str_replace('_', ' ', $charge['charge_type'])) ?></td>
                    <td><?= htmlspecialchars($charge['description']) ?></td>
                    <td><?= number_format($charge['amt'], 2) ?></td>
                    <td><?= ucfirst($charge['status']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="statement-section">
        <h3>Payment History</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Amount (KES)</th>
                    <th>Method</th>
                    <th>Reference</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                <tr>
                    <td><?= date('d M Y', strtotime($payment['created_at'])) ?></td>
                    <td><?= number_format($payment['total_amount'], 2) ?></td>
                    <td>
                        <?php
                        $methods = [];
                        if ($payment['cash'] > 0) $methods[] = 'Cash';
                        if ($payment['mpesa'] > 0) $methods[] = 'M-Pesa';
                        if ($payment['bank'] > 0) $methods[] = 'Bank';
                        echo implode(', ', $methods);
                        ?>
                    </td>
                    <td><?= htmlspecialchars($payment['reference']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>