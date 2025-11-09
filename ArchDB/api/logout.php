<?php
/**
 * Logout API Endpoint
 * Destroys user session and logs out
 */

header("Content-Type: application/json");

require_once '../../db.php';
require_once 'auth_helper.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

// Check if user is authenticated
if (!isAuthenticated()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode([
        'success' => false,
        'message' => 'Not authenticated'
    ]);
    exit;
}

// Get user ID before destroying session (for logging)
$userId = getCurrentUserId();

// Destroy session
if (destroySession()) {
    // Log activity if database connection exists
    if ($conn) {
        logActivity($conn, $userId, 'LOGOUT', 'User logged out');
        $conn->close();
    }
    
    header('HTTP/1.1 200 OK');
    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully'
    ]);
} else {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'message' => 'Failed to logout'
    ]);
}

?>

