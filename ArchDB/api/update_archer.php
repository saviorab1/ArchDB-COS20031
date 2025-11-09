<?php
require_once 'auth_helper.php';

header('Content-Type: application/json');

// Check if user is authenticated
$user = checkAuthentication();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Get POST data
    $archerId = $_POST['archer_id'] ?? null;
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phoneNumber = trim($_POST['phone_number'] ?? '');

    // Basic validation
    if (!$archerId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Archer ID is required']);
        exit;
    }

    if (empty($firstName) || empty($lastName)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'First name and last name are required']);
        exit;
    }

    // Check permissions
    $userRole = $user['role'];
    $userId = $user['id'];

    if ($userRole === 'archer') {
        // Archers can only edit their own profile
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND archer_id = ?");
        $stmt->execute([$userId, $archerId]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You can only edit your own profile']);
            exit;
        }
    }
    // Managers can edit any archer profile

    // Check if email is already taken by another archer
    if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT id FROM archer WHERE email = ? AND id != ?");
        $stmt->execute([$email, $archerId]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email address is already in use']);
            exit;
        }
    }

    // Update archer profile
    $stmt = $pdo->prepare("
        UPDATE archer
        SET first_name = ?, last_name = ?, email = ?, phone_number = ?, updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([$firstName, $lastName, $email, $phoneNumber, $archerId]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Archer not found']);
        exit;
    }

    // Update user email if it was changed and this is the archer's own profile
    if (!empty($email) && $userRole === 'archer') {
        $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->execute([$email, $userId]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
