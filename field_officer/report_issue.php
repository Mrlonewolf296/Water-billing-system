<?php
session_start();
require_once '../config/db_con.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'field_officer') {
  header("Location: ../auths/login.php");
  exit();
}

$officer_id = $_SESSION['users_id'];
$customer_id = $_GET['customer_id'] ?? null;

if (!$customer_id) {
  die("Invalid customer.");
}

// Fetch customer details
$stmt = $pdo->prepare("SELECT name, meter_number FROM customers WHERE customer_id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();

if (!$customer) {
  die("Customer not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $issue_type = $_POST['issue_type'];
  $details = $_POST['details'] ?? null;

  $insert = $pdo->prepare("INSERT INTO reported_issues (customer_id, officer_id, issue_type, details)
                           VALUES (?, ?, ?, ?)");
  $insert->execute([$customer_id, $officer_id, $issue_type, $details]);

  $success = "Issue reported successfully.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Report Issue</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f0f2f5;
      padding: 20px;
    }

    form {
      max-width: 500px;
      margin: auto;
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 5px rgba(0,0,0,0.1);
    }

    h2 { text-align: center; }

    label {
      display: block;
      margin-top: 15px;
      font-weight: bold;
    }

    input, select, textarea {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border-radius: 5px;
      border: 1px solid #ccc;
    }

    button {
      margin-top: 20px;
      width: 100%;
      padding: 12px;
      background: #dc3545;
      color: white;
      font-size: 16px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
    }

    .message {
      text-align: center;
      color: green;
      margin-bottom: 15px;
    }
  </style>
</head>
<body>

<h2>Report Issue for <?= htmlspecialchars($customer['name']) ?> (<?= htmlspecialchars($customer['meter_number']) ?>)</h2>

<?php if (isset($success)): ?>
  <div class="message"><?= $success ?></div>
<?php endif; ?>

<form method="POST">
  <label for="issue_type">Issue Type</label>
  <select name="issue_type" required>
    <option value="">-- Select Issue Type --</option>
    <option>Meter Fault</option>
    <option>Blocked Access</option>
    <option>Tampering</option>
    <option>No Reading</option>
    <option>Other</option>
  </select>

  <label for="details">Details / Notes</label>
  <textarea name="details" rows="4" placeholder="Describe the issue..."></textarea>

  <button type="submit">Submit Report</button>
</form>

</body>
</html>
