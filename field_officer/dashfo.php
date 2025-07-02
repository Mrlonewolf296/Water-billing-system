<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'field_officer') {
  header("Location: ../auths/login.php");
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Field Officer Home</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f9f9f9;
      padding: 20px;
    }

    h2 {
      text-align: center;
      color: #333;
    }

    .menu {
      display: grid;
      gap: 15px;
      margin-top: 30px;
    }

    .menu a {
      display: block;
      padding: 15px;
      background: #007bff;
      color: #fff;
      text-align: center;
      text-decoration: none;
      font-size: 18px;
      border-radius: 8px;
    }

    .menu a:hover {
      background: #0056b3;
    }

    @media (max-width: 600px) {
      .menu a {
        font-size: 16px;
        padding: 12px;
      }
    }
  </style>
</head>
<body>

<h2>Welcome, Field Officer</h2>

<div class="menu">
  <a href="submit_reading.php">➕ Submit Meter Reading</a>
  <a href="view_customers.php">👥 View Customers</a>
  <a href="report_issue.php">⚠️ Report Fault or Tampering</a>
  <a href="print_receipt.php">🧾 Print Receipt</a>
  <a href="../auths/logout.php">🚪 Logout</a>
</div>

</body>
</html>
