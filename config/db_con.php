<?php
/*
 Database Configuration and Connection Class
 
  This script handles secure database connections using PDO with error handling
 
*/
// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'wbs'); // Updated to match the prompt
define('DB_USER', 'root');
define('DB_PASS', 'Mr.l0n3wolf1.');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private $pdo;
    private static $instance = null;

    // Private constructor to prevent direct instantiation
    private function __construct() {
        try {
            // DSN (Data Source Name)
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            
            // PDO options
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => true
            ];
            
            // Create PDO instance
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Set timezone if needed
            $this->pdo->exec("SET time_zone = '+00:00'");
            
        } catch (PDOException $e) {
            // Log error and display user-friendly message
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("Database connection failed. Please try again later.");
        }
    }

    // Singleton pattern to ensure only one connection
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // Get the PDO connection
    public function getConnection() {
        return $this->pdo;
    }

    // Prevent cloning and serialization
    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize database connection");
    }
}

function getDBConnection() {
    try {
        $db = Database::getInstance();
        return $db->getConnection();
    } catch (Exception $e) {
        error_log("Database connection error: " . $e->getMessage());
        error_log("Database connection error: " . $e->getTraceAsString());
        throw new Exception("Database connection failed. Please try again later.");
    }
}


/*Helper function to get database connection
function getDBConnection() {
    try {
        $db = Database::getInstance();
        return $db->getConnection();
    } catch (Exception $e) {
        // Handle error appropriately (log, display error page, etc.)
        die("Database connection error. Please contact the administrator.");
    }
}

 Test connection (remove in production) */
try {
    $pdo = getDBConnection();
   // echo "Database connection successful!";
} catch (Exception $e) {
    echo $e->getMessage();
}

?>

<?php
/*
try {
    $pdo = new PDO("mysql:host=localhost;dbname=POS", "root", "Mr.l0n3wolf1.");
    echo "Connected!";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
*/
?>

