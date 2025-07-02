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

if (!$customer) {
    header('Location: billing.php');
    exit();
}

// Get latest reading for metered customers
$latest_reading = null;
if ($customer['billing_type'] === 'metered') {
    $stmt = $pdo->prepare("SELECT * FROM readings WHERE customer_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$customer_id]);
    $latest_reading = $stmt->fetch();
}

// Get tariffs
$tariffs = $pdo->query("SELECT * FROM tariffs ORDER BY min_unit ASC")->fetchAll();

// Get outstanding charges
$stmt = $pdo->prepare("SELECT * FROM charges WHERE customer_id = ? AND status != 'paid'");
$stmt->execute([$customer_id]);
$outstanding_charges = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    
    try {
        $bill_date = $_POST['bill_date'] ?? date('Y-m-d');
        $due_date = $_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days'));
        
        // Calculate consumption charges
        $consumption_amount = 0;
        if ($customer['billing_type'] === 'metered' && isset($_POST['current_reading'])) {
            $current_reading = floatval($_POST['current_reading']);
            $previous_reading = $latest_reading ? $latest_reading['curr_reading'] : 0;
            $usage = max(0, $current_reading - $previous_reading);
            
            // Calculate cost using tariffs
            foreach ($tariffs as $tariff) {
                $min = $tariff['min_unit'];
                $max = $tariff['max_unit'] ?? PHP_INT_MAX;
                $rate = $tariff['rate_per_unit'];
                
                if ($usage > $min) {
                    $applicable_units = min($usage, $max) - $min;
                    $consumption_amount += $applicable_units * $rate;
                }
            }
            
            // Insert new reading
            $stmt = $pdo->prepare("INSERT INTO readings (customer_id, prev_reading, curr_reading, usage, usage_cost, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$customer_id, $previous_reading, $current_reading, $usage, $consumption_amount]);
        } else {
            // Flat rate billing
            $consumption_amount = floatval($_POST['flat_rate_amount'] ?? 0);
        }
        
        // Additional charges
        $service_charge = floatval($_POST['service_charge'] ?? 0);
        $reconnection_fee = floatval($_POST['reconnection_fee'] ?? 0);
        $penalty_amount = floatval($_POST['penalty_amount'] ?? 0);
        $arrears_amount = floatval($_POST['arrears_amount'] ?? 0);
        $remission_amount = floatval($_POST['remission_amount'] ?? 0);
        $other_charges = floatval($_POST['other_charges'] ?? 0);
        
        // Calculate total
        $total_amount = $consumption_amount + $service_charge + $reconnection_fee + 
                       $penalty_amount + $arrears_amount + $other_charges - $remission_amount;
        
        // Create invoice
        $stmt = $pdo->prepare("INSERT INTO invoices (customer_id, amt, status, created_at, due_date) VALUES (?, ?, 'unpaid', ?, ?)");
        $stmt->execute([$customer_id, $total_amount, $bill_date, $due_date]);
        $invoice_id = $pdo->lastInsertId();
        
        // Insert individual charges
        $charges = [
            ['type' => 'consumption', 'amount' => $consumption_amount, 'desc' => 'Water consumption charges'],
            ['type' => 'service_fee', 'amount' => $service_charge, 'desc' => 'Service charge'],
            ['type' => 'reconnection', 'amount' => $reconnection_fee, 'desc' => 'Reconnection fee'],
            ['type' => 'penalty', 'amount' => $penalty_amount, 'desc' => 'Late payment penalty'],
            ['type' => 'arrears', 'amount' => $arrears_amount, 'desc' => 'Outstanding arrears'],
            ['type' => 'remission', 'amount' => -$remission_amount, 'desc' => 'Remission/Discount'],
            ['type' => 'other', 'amount' => $other_charges, 'desc' => $_POST['other_description'] ?? 'Other charges']
        ];
        
        foreach ($charges as $charge) {
            if ($charge['amount'] != 0) {
                $stmt = $pdo->prepare("INSERT INTO charges (customer_id, invoice_id, charge_type, description, amt, charge_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?, 'unpaid', ?)");
                $stmt->execute([$customer_id, $invoice_id, $charge['type'], $charge['desc'], $charge['amount'], $bill_date, $_SESSION['user_id']]);
            }
        }
        
        $pdo->commit();
        header("Location: view_statement.php?customer_id=$customer_id&invoice_id=$invoice_id");
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error creating bill: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Bill - <?= htmlspecialchars($customer['name']) ?></title>
    <link rel="stylesheet" href="../assets/css/add_customer.css">
    <style>
        .bill-section {
            background: #f8f9fa;
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            border-left: 4px solid #0077cc;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .total-display {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: #0077cc;
        }
    </style>
</head>
<body>
    <a class="back-link" href="billing.php">← Back to Billing</a>
    <h1>Create Bill for <?= htmlspecialchars($customer['name']) ?></h1>
    
    <?php if (isset($error)): ?>
        <div style="color: red; margin: 10px 0;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST" id="billForm">
        <div class="bill-section">
            <h3>Bill Information</h3>
            <div class="form-row">
                <div>
                    <label>Bill Date</label>
                    <input type="date" name="bill_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div>
                    <label>Due Date</label>
                    <input type="date" name="due_date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
                </div>
            </div>
        </div>

        <?php if ($customer['billing_type'] === 'metered'): ?>
        <div class="bill-section">
            <h3>Meter Reading</h3>
            <div class="form-row">
                <div>
                    <label>Previous Reading</label>
                    <input type="number" value="<?= $latest_reading ? $latest_reading['curr_reading'] : 0 ?>" readonly>
                </div>
                <div>
                    <label>Current Reading</label>
                    <input type="number" name="current_reading" step="0.01" required onchange="calculateTotal()">
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="bill-section">
            <h3>Flat Rate Billing</h3>
            <label>Monthly Rate (KES)</label>
            <input type="number" name="flat_rate_amount" step="0.01" value="500" onchange="calculateTotal()">
        </div>
        <?php endif; ?>

        <div class="bill-section">
            <h3>Additional Charges</h3>
            <div class="form-row">
                <div>
                    <label>Service Charge (KES)</label>
                    <input type="number" name="service_charge" step="0.01" value="0" onchange="calculateTotal()">
                </div>
                <div>
                    <label>Reconnection Fee (KES)</label>
                    <input type="number" name="reconnection_fee" step="0.01" value="0" onchange="calculateTotal()">
                </div>
            </div>
            <div class="form-row">
                <div>
                    <label>Penalty Amount (KES)</label>
                    <input type="number" name="penalty_amount" step="0.01" value="0" onchange="calculateTotal()">
                </div>
                <div>
                    <label>Arrears Amount (KES)</label>
                    <input type="number" name="arrears_amount" step="0.01" value="0" onchange="calculateTotal()">
                </div>
            </div>
            <div class="form-row">
                <div>
                    <label>Remission/Discount (KES)</label>
                    <input type="number" name="remission_amount" step="0.01" value="0" onchange="calculateTotal()">
                </div>
                <div>
                    <label>Other Charges (KES)</label>
                    <input type="number" name="other_charges" step="0.01" value="0" onchange="calculateTotal()">
                </div>
            </div>
            <label>Other Charges Description</label>
            <input type="text" name="other_description" placeholder="Description for other charges">
        </div>

        <div class="total-display" id="totalDisplay">
            Total Amount: KES 0.00
        </div>

        <button type="submit">Create Bill</button>
    </form>

    <script>
        const tariffs = <?= json_encode($tariffs) ?>;
        
        function calculateConsumption(usage) {
            let cost = 0;
            for (let tariff of tariffs) {
                const min = tariff.min_unit;
                const max = tariff.max_unit || 999999;
                const rate = parseFloat(tariff.rate_per_unit);
                
                if (usage > min) {
                    const applicableUnits = Math.min(usage, max) - min;
                    cost += applicableUnits * rate;
                }
            }
            return cost;
        }
        
        function calculateTotal() {
            let total = 0;
            
            // Consumption charges
            <?php if ($customer['billing_type'] === 'metered'): ?>
            const currentReading = parseFloat(document.querySelector('[name="current_reading"]').value) || 0;
            const previousReading = <?= $latest_reading ? $latest_reading['curr_reading'] : 0 ?>;
            const usage = Math.max(0, currentReading - previousReading);
            total += calculateConsumption(usage);
            <?php else: ?>
            total += parseFloat(document.querySelector('[name="flat_rate_amount"]').value) || 0;
            <?php endif; ?>
            
            // Additional charges
            total += parseFloat(document.querySelector('[name="service_charge"]').value) || 0;
            total += parseFloat(document.querySelector('[name="reconnection_fee"]').value) || 0;
            total += parseFloat(document.querySelector('[name="penalty_amount"]').value) || 0;
            total += parseFloat(document.querySelector('[name="arrears_amount"]').value) || 0;
            total += parseFloat(document.querySelector('[name="other_charges"]').value) || 0;
            total -= parseFloat(document.querySelector('[name="remission_amount"]').value) || 0;
            
            document.getElementById('totalDisplay').textContent = `Total Amount: KES ${total.toFixed(2)}`;
        }
        
        // Initialize calculation
        calculateTotal();
    </script>
</body>
</html>