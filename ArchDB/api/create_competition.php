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

// Only managers can create competitions
if ($user['role'] !== 'manager') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only managers can create competitions']);
    exit;
}

try {
    // Get POST data
    $name = trim($_POST['name'] ?? '');
    $competitionDate = $_POST['date'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $roundId = $_POST['round'] ?? null;
    $description = trim($_POST['description'] ?? '');
    $isClubChampionship = isset($_POST['isClubChampionship']) ? 1 : 0;

    // Basic validation
    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Competition name is required']);
        exit;
    }

    if (empty($competitionDate)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Competition date is required']);
        exit;
    }

    if (empty($location)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Location is required']);
        exit;
    }

    // Validate date is not in the past
    $inputDate = new DateTime($competitionDate);
    $today = new DateTime();
    $today->setTime(0, 0, 0);

    if ($inputDate < $today) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Competition date cannot be in the past']);
        exit;
    }

    // Check if competition name already exists on the same date
    $stmt = $pdo->prepare("SELECT id FROM competition WHERE name = ? AND competition_date = ?");
    $stmt->execute([$name, $competitionDate]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'A competition with this name already exists on the selected date']);
        exit;
    }

    // Validate round exists if provided
    if ($roundId) {
        $stmt = $pdo->prepare("SELECT id FROM round WHERE id = ?");
        $stmt->execute([$roundId]);
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid round selected']);
            exit;
        }
    }

    // Create competition
    $stmt = $pdo->prepare("
        INSERT INTO competition (name, competition_date, location, description, is_club_championship, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([$name, $competitionDate, $location, $description, $isClubChampionship]);

    $competitionId = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Competition created successfully',
        'competition_id' => $competitionId
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
