<?php
/**
 * Check Auth API Endpoint
 * Returns authentication status and user info
 */

header("Content-Type: application/json");

require_once 'auth_helper.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

// Check if user is authenticated
if (!isAuthenticated() || !validateSessionTimeout()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode([
        'success' => false,
        'authenticated' => false,
        'message' => 'Not authenticated'
    ]);
    exit;
}

// Return user info
header('HTTP/1.1 200 OK');
echo json_encode([
    'success' => true,
    'authenticated' => true,
    'user' => [
        'id' => getCurrentUserId(),
        'role' => getCurrentUserRole(),
        'archer_id' => getCurrentArcherId(),
        'manager_id' => getCurrentManagerId()
    ]
]);

?>

