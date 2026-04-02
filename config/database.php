<?php
/**
 * Database Configuration
 * PetFounds - Pet Finder Network
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'petfounds_db');

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
