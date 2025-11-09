<?php
/**
 * User Registration API Endpoint
 * Creates new user accounts for archers or managers
 * SIMPLIFIED for proof of concept
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

// Get input from JSON
$input = json_decode(file_get_contents("php://input"), true);

// Check required fields
if (!isset($input['username']) || !isset($input['password']) || !isset($input['role'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Username, password, and role are required']);
    exit;
}

$username = trim($input['username']);
$password = $input['password'];
$role = trim($input['role']);

// Minimal validation - just check role is valid
if (!in_array($role, ['archer', 'manager'])) {
    echo json_encode(['success' => false, 'message' => "Invalid role. Must be 'archer' or 'manager'"]);
    exit;
}

// Check if username already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);  
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Username already exists']);
    $stmt->close();
    exit;
}
$stmt->close();

// Hash password
$password_hash = password_hash($password, PASSWORD_DEFAULT);
$email = $username . '@archery.local'; // Auto-generate email

$archerId = null;
$managerId = null;

// Create archer or manager record if needed
if ($role === 'archer') {
    $firstName = trim($input['first_name'] ?? 'Archer');
    $lastName = trim($input['last_name'] ?? $username);
    $gender = trim($input['gender'] ?? 'M');
    $dateOfBirth = $input['date_of_birth'] ?? '1990-01-01';
    $equipment = 'RECURVE';
    
    $stmt = $conn->prepare("INSERT INTO archer (first_name, last_name, gender, date_of_birth, email, equipment) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("ssssss", $firstName, $lastName, $gender, $dateOfBirth, $email, $equipment);
    if ($stmt->execute()) {
        $archerId = $stmt->insert_id;
    }
    $stmt->close();
    
} else if ($role === 'manager') {
    $firstName = trim($input['first_name'] ?? 'Manager');
    $lastName = trim($input['last_name'] ?? $username);
    
    $stmt = $conn->prepare("INSERT INTO manager (first_name, last_name, email) VALUES (?,?,?)");
    $stmt->bind_param("sss", $firstName, $lastName, $email);
    if ($stmt->execute()) {
        $managerId = $stmt->insert_id;
    }
    $stmt->close();
}

// Insert user account
$stmt = $conn->prepare("INSERT INTO users (username, password_hash, email, role, archer_id, manager_id, is_active) VALUES (?,?,?,?,?,?,TRUE)");
$stmt->bind_param("sssiii", $username, $password_hash, $email, $role, $archerId, $managerId);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Registration failed']);
    $stmt->close();
    exit;
}

$userId = $stmt->insert_id;
$stmt->close();

header('HTTP/1.1 201 Created');
echo json_encode([
    'success' => true,
    'user_id' => $userId,
    'message' => ucfirst($role) . ' account created successfully'
]);

$conn->close(); 
?>
