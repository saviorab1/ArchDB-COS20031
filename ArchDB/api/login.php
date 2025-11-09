<?php
header("Content-Type: application/json");
include '../db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// check DB connection
if (!$conn) {
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit;
}

// Get input from POST Json
$data = json_decode(file_get_contents('php://input'), true);
$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';

if (!$username || !$password) {
    echo json_encode(['success' => false, 'message' => 'Missing username or password']);
    exit;
}

// get User from DB
$sql = 'SELECT * FROM users WHERE username = ?';
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // compare password hash
    if (password_verify($password, $row['password_hash'])) {
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $row['id'],
                'username' => $row['username'],
                'role' => $row['role']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'User not found']);
}

$stmt->close();
$conn->close();
?>
