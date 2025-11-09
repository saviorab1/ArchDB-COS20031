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

// Only archers can register for competitions
if ($user['role'] !== 'archer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only archers can register for competitions']);
    exit;
}

try {
    // Get POST data
    $competitionId = $_POST['competition_id'] ?? null;
    $equipmentUsed = $_POST['equipment_used'] ?? null;

    if (!$competitionId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Competition ID is required']);
        exit;
    }

    // Get archer ID for this user
    $stmt = $pdo->prepare("SELECT archer_id FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $archerData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$archerData || !$archerData['archer_id']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Archer profile not found']);
        exit;
    }

    $archerId = $archerData['archer_id'];

    // Check if competition exists and is upcoming
    $stmt = $pdo->prepare("SELECT id, name, competition_date FROM competition WHERE id = ? AND competition_date >= CURDATE()");
    $stmt->execute([$competitionId]);
    $competition = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$competition) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Competition not found or has already passed']);
        exit;
    }

    // Check if already registered
    $stmt = $pdo->prepare("SELECT id, status FROM registration WHERE archer_id = ? AND competition_id = ?");
    $stmt->execute([$archerId, $competitionId]);
    $existingRegistration = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingRegistration) {
        if ($existingRegistration['status'] === 'approved') {
            echo json_encode(['success' => false, 'message' => 'You are already registered and approved for this competition']);
        } elseif ($existingRegistration['status'] === 'pending') {
            echo json_encode(['success' => false, 'message' => 'Your registration is pending approval']);
        } elseif ($existingRegistration['status'] === 'rejected') {
            echo json_encode(['success' => false, 'message' => 'Your previous registration was rejected. Please contact the competition manager.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'You have already registered for this competition']);
        }
        exit;
    }

    // Get archer's default equipment if not specified
    if (!$equipmentUsed) {
        $stmt = $pdo->prepare("SELECT equipment FROM archer WHERE id = ?");
        $stmt->execute([$archerId]);
        $archerInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        $equipmentUsed = $archerInfo['equipment'] ?? 'RECURVE';
    }

    // Register for the competition
    $stmt = $pdo->prepare("
        INSERT INTO registration (archer_id, competition_id, equipment_used, status)
        VALUES (?, ?, ?, 'pending')
    ");
    $stmt->execute([$archerId, $competitionId, $equipmentUsed]);

    echo json_encode([
        'success' => true,
        'message' => 'Registration submitted successfully. Please wait for approval from the competition manager.'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
