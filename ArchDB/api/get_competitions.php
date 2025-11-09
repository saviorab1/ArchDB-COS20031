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
    // Get user role and info
    $userRole = $user['role'];
    $userId = $user['id'];

    // Prepare query based on user role
    if ($userRole === 'archer') {
        // For archers: show competitions they can register for with their registration status
        $stmt = $pdo->prepare("
            SELECT
                c.id,
                c.name,
                c.competition_date,
                c.location,
                c.description,
                c.is_club_championship,
                c.created_at,
                COALESCE(r.status, 'not_registered') as registration_status,
                r.registration_date,
                r.equipment_used,
                CASE
                    WHEN c.competition_date >= CURDATE() THEN 'upcoming'
                    WHEN c.competition_date < CURDATE() THEN 'past'
                    ELSE 'current'
                END as competition_status
            FROM competition c
            LEFT JOIN registration r ON c.id = r.competition_id AND r.archer_id = (SELECT archer_id FROM users WHERE id = ?)
            ORDER BY c.competition_date DESC
        ");
        $stmt->execute([$userId]);
    } else {
        // For managers: show all competitions with registration counts
        $stmt = $pdo->prepare("
            SELECT
                c.id,
                c.name,
                c.competition_date,
                c.location,
                c.description,
                c.is_club_championship,
                c.created_at,
                COUNT(CASE WHEN r.status = 'approved' THEN 1 END) as approved_registrations,
                COUNT(CASE WHEN r.status = 'pending' THEN 1 END) as pending_registrations,
                COUNT(r.id) as total_registrations,
                CASE
                    WHEN c.competition_date >= CURDATE() THEN 'upcoming'
                    WHEN c.competition_date < CURDATE() THEN 'past'
                    ELSE 'current'
                END as competition_status
            FROM competition c
            LEFT JOIN registration r ON c.id = r.competition_id
            GROUP BY c.id, c.name, c.competition_date, c.location, c.description, c.is_club_championship, c.created_at
            ORDER BY c.competition_date DESC
        ");
        $stmt->execute();
    }

    $competitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'competitions' => $competitions,
        'user_role' => $userRole
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
