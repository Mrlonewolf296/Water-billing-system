<?php
session_start();
ob_start();
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('display_errors', 1);

require_once '../config/db_con.php'; // Ensure this contains getDBConnection()

$error = '';
$user_name = '';
$success_message = '';

// ✅ Database connection
try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("System error. Please contact the administrator."); // Avoid showing raw error in production
}

/* ✅ Redirect logged-in user by role
if (isset($_SESSION['usrs_id'])) {
    redirectBasedOnRole($_SESSION['role']);
}
*/
// ✅ Role-based redirection function
function redirectBasedOnRole($role)
{
    $rolePages = [
        'admin'   => '../admin/dash.php',
        'field_officer' => '../field_officer/dashfo.php',
        'staff'   => '../staff/dashst.php',
    ];

    header("Location: " . ($rolePages[$role] ?? './login.php'));
    exit();
}

// ✅ Check for registration success message
if (isset($_SESSION['registration_success'])) {
    $success_message = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
}

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_name = trim($_POST['user_name'] ?? '');
    $password = $_POST['password'] ?? '';

    //$branch_id = $_POST['branch_id'] ?? '';

    if (empty($user_name) || empty($password)) {
        $error = 'Please enter username and password.';
    } else {
        // ... after password verification:
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_name = ?");
        $stmt->execute([$user_name]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']     = $user['user_id'];
            $_SESSION['role']        = $user['role'];
            $_SESSION['full_name']  = $user['full_name'];
            $_SESSION['last_login']  = time();
            //$_SESSION['branch_id']   = $user['branch_id']; // <-- fetch from DB, not POST

            redirectBasedOnRole($user['role']);
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>

<!DOCTYPE HTML>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Water Billing Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="login-container">
        <h2>Water Billing System</h2>
        <form action="login.php" method="POST">
            <input type="text" name="user_name" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p class="p"><strong>Don't have an account?</strong> <a href="register.php">Register here</a></p>
    </div>
</body>

</html>