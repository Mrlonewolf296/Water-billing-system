<?php
// Start session and error reporting
session_start();
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('display_errors', 1);
require_once '../config/db_con.php';

if (!$pdo) {
    error_log("Failed to get database connection.");
    $error = "A system error occurred. Please try again later.";
    return;
}

$error = '';
$success_message = '';

// Display registration success
if (isset($_SESSION['registration_success'])) {
    $success_message = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
}

// Form submission handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs and trim
    $full_name = trim($_POST['full_name']);
    $user_name = trim($_POST['user_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = isset($_POST['role']) ? $_POST['role'] : 'staff';

    // Basic validation
    if (empty($full_name) || empty($user_name) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Hash the password securely
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Prepare and execute insert statement WITHOUT phone field
            $stmt = $pdo->prepare("
                INSERT INTO users 
                (full_name, user_name, password, role, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $full_name,
                $user_name,
                $hashedPassword,
                $role
            ]);

            // Redirect to login page
            $_SESSION['registration_success'] = "User successfully registered.";
            header("Location: login.php");
            exit();
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Register New User</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <div class="login-container">
    <h2>Register User</h2>
    <?php if (!empty($error)): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form action="register.php" method="POST">
      <input type="text" name="full_name" placeholder="Full Name" required>
      <input type="text" name="user_name" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <input type="password" name="confirm_password" placeholder="Confirm Password" required>
      <select name="role" required>
        <option value="staff">Staff</option>
        <option value="field_officer">Field Officer</option>
        <option value="admin">Super Admin</option>
      </select>
      <button type="submit">Register</button>
    </form>
 <p class="p"><strong>IF YOU HAVE AN ACCOUNT PLEASE LOGIN <a href="login.php">here</strong></a>
  </div>
</body>
</html>