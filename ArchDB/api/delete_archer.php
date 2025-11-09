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

// Only managers can delete archers
if ($user['role'] !== 'manager') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only managers can delete archers']);
    exit;
}

try {
    // Get POST data
    $archerId = $_POST['archer_id'] ?? null;

    if (!$archerId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Archer ID is required']);
        exit;
    }

    // Check if archer exists
    $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM archer WHERE id = ?");
    $stmt->execute([$archerId]);
    $archer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$archer) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Archer not found']);
        exit;
    }

    // Check if archer has any scores
    $stmt = $pdo->prepare("SELECT COUNT(*) as score_count FROM score WHERE archer_id = ?");
    $stmt->execute([$archerId]);
    $scoreCount = $stmt->fetch(PDO::FETCH_ASSOC)['score_count'];

    if ($scoreCount > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot delete archer with existing scores. Please contact system administrator.']);
        exit;
    }

    // Check if archer has any registrations
    $stmt = $pdo->prepare("SELECT COUNT(*) as reg_count FROM registration WHERE archer_id = ?");
    $stmt->execute([$archerId]);
    $regCount = $stmt->fetch(PDO::FETCH_ASSOC)['reg_count'];

    if ($regCount > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot delete archer with competition registrations. Please contact system administrator.']);
        exit;
    }

    // Start transaction
    $pdo->beginTransaction();

    // Delete user account first (this will cascade to sessions)
    $stmt = $pdo->prepare("DELETE FROM users WHERE archer_id = ?");
    $stmt->execute([$archerId]);

    // Delete archer
    $stmt = $pdo->prepare("DELETE FROM archer WHERE id = ?");
    $stmt->execute([$archerId]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Archer deleted successfully'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
