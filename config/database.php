<?php
/**
 * Database Configuration
 * PetFounds - Pet Finder Network
 */

// Load environment variables from .env file
function loadEnvFile($filePath = null) {
    if (!$filePath) {
        $filePath = dirname(dirname(__FILE__)) . '/.env';
    }
    
    if (!file_exists($filePath)) {
        return false;
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE format
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
                $value = substr($value, 1, -1);
            }
            
            $_ENV[$key] = $value;
            if (!getenv($key)) {
                putenv("$key=$value");
            }
        }
    }
    
    return true;
}

// Load .env file
loadEnvFile();

// Database configuration (from .env or defaults)
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'petfounds_db');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost/lost-and-found-pet');

date_default_timezone_set('Asia/Jakarta');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Create connection
$connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($connection->connect_error) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Koneksi database gagal: ' . $connection->connect_error
    ]));
}

// Set charset to UTF-8
$connection->set_charset("utf8mb4");

// Function to close connection
function closeConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}

?>