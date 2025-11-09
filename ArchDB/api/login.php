<?php
/**
 * Login API Endpoint
 * Authenticates user and creates session
 */

header("Content-Type: application/json");

require_once '../../db.php';
require_once 'auth_helper.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

// Check DB connection
if (!$conn) {
    header('HTTP/1.1 503 Service Unavailable');
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

// Get input from POST JSON
$data = json_decode(file_get_contents('php://input'), true);
$username = sanitizeInput($data['username'] ?? '');
$password = $data['password'] ?? '';

// Validate input
if (empty($username) || empty($password)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode([
        'success' => false,
        'message' => 'Username and password are required'
    ]);
    exit;
}

// Query user from database
$stmt = $conn->prepare('SELECT id, username, password_hash, email, role, archer_id, manager_id, is_active FROM users WHERE username = ? AND is_active = TRUE');

if (!$stmt) {
    error_log("Prepare error: " . $conn->error);
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid username or password'
    ]);
    $stmt->close();
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// Verify password
if (!password_verify($password, $user['password_hash'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid username or password'
    ]);
    exit;
}

// Create session
if (!createSession(
    $user['id'],
    $user['role'],
    $user['archer_id'],
    $user['manager_id']
)) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create session'
    ]);
    exit;
}

// Update last login timestamp
$updateStmt = $conn->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
if ($updateStmt) {
    $updateStmt->bind_param("i", $user['id']);
    $updateStmt->execute();
    $updateStmt->close();
}

// Log activity
logActivity($conn, $user['id'], 'LOGIN', 'User logged in from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

header('HTTP/1.1 200 OK');
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role'],
        'archer_id' => $user['archer_id'],
        'manager_id' => $user['manager_id']
    ]
]);

$conn->close();
?>

