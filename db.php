<?php
/**
 * Database Configuration and Connection
 * Handles database connection with proper error handling
 */

// Database Configuration
$servername = "localhost";
$username = "root";  // Update with your MySQL username
$password = "";      // Update with your MySQL password
$dbname = "archery_score_db";  // Updated database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->autocommit(TRUE);
$conn->set_charset("utf8mb4");

// Check connection
if ($conn->connect_error) {
    // Log error to file instead of displaying
    error_log("Database connection failed: " . $conn->connect_error);
    
    // Return error response (will be caught by auth middleware)
    header('HTTP/1.1 503 Service Unavailable');
    die(json_encode(["success" => false, "message" => "Database connection failed. Please try again later."]));
}

// Enable error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);  // Don't display errors, log them instead
ini_set('log_errors', 1);

?>

