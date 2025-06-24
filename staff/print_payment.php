<?php
require_once '../config/db_con.php';

$payment_id = $_GET['payment_id'] ?? null;

if (!$payment_id) {
  die("Payment ID required.");
}

$stmt = $pdo->prepare("SELECT p.*, c.name, c.meter_number FROM payments p
                       JOIN customers c ON p.customer_id = c.customer_id
                       WHERE p.payment_id = ?");
$stmt->execute([$payment_id]);
$payment = $stmt->fetch();

if (!$payment) {
  die("Payment not found.");
}

$total = ($payment['cash'] ?? 0) + ($payment['mpesa'] ?? 0) + ($payment['bank'] ?? 0);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Receipt #<?= $payment['payment_id'] ?></title>
  <style>
    body {
      font-family: monospace;
      font-size: 12px;
      padding: 10px;
      width: 260px;
    }
    .center {
      text-align: center;
    }
    .bold {
      font-weight: bold;
    }
    .line {
      border-top: 1px dashed #000;
      margin: 8px 0;
    }
    .right {
      text-align: right;
    }
    @media print {
      body {
        width: auto;
      }
      button {
        display: none;
      }
    }
  </style>
</head>
<body>
  <div class="center bold">WATER COMPANY LTD</div>
  <div class="center">P.O. Box 1234 - 00100</div>
  <div class="center">Nairobi, Kenya</div>
  <div class="line"></div>
  <div>Receipt #: <span class="right"><?= $payment['payment_id'] ?></span></div>
  <div>Date: <span class="right"><?= date('d M Y', strtotime($payment['payment_date'])) ?></span></div>
  <div>Customer: <span class="right"><?= htmlspecialchars($payment['name']) ?></span></div>
  <div>Meter No: <span class="right"><?= htmlspecialchars($payment['meter_number']) ?></span></div>
  <?php if ($payment['invoice_id']): ?>
    <div>Invoice: <span class="right">#<?= $payment['invoice_id'] ?></span></div>
  <?php endif; ?>
  <div class="line"></div>
  <?php if ($payment['cash']): ?>
    <div>Cash: <span class="right">KES <?= number_format($payment['cash'], 2) ?></span></div>
  <?php endif; ?>
  <?php if ($payment['mpesa']): ?>
    <div>M-Pesa: <span class="right">KES <?= number_format($payment['mpesa'], 2) ?></span></div>
  <?php endif; ?>
  <?php if ($payment['bank']): ?>
    <div>Bank: <span class="right">KES <?= number_format($payment['bank'], 2) ?></span></div>
  <?php endif; ?>
  <div class="line"></div>
  <div class="bold">Total Paid: <span class="right">KES <?= number_format($total, 2) ?></span></div>
  <div class="line"></div>
  <div class="center">Thank you!</div>
  <div class="center">Powered by WBS</div>
  <div class="line"></div>
  <div class="center">
    <button onclick="window.print()">Print</button>
  </div>
  <script>
  window.onload = () => {
    window.print();
  };
</script>

</body>
</html>
