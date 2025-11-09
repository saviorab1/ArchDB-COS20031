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
        // Archers can only see their own profile
        $stmt = $pdo->prepare("
            SELECT
                a.id,
                a.first_name,
                a.last_name,
                a.gender,
                a.date_of_birth,
                a.phone_number,
                a.email,
                a.equipment,
                a.created_at,
                a.updated_at,
                CONCAT(a.first_name, ' ', a.last_name) as full_name,
                TIMESTAMPDIFF(YEAR, a.date_of_birth, CURDATE()) as age,
                CASE
                    WHEN TIMESTAMPDIFF(YEAR, a.date_of_birth, CURDATE()) < 14 THEN 'U14'
                    WHEN TIMESTAMPDIFF(YEAR, a.date_of_birth, CURDATE()) < 16 THEN 'U16'
                    WHEN TIMESTAMPDIFF(YEAR, a.date_of_birth, CURDATE()) < 18 THEN 'U18'
                    WHEN TIMESTAMPDIFF(YEAR, a.date_of_birth, CURDATE()) < 21 THEN 'U21'
                    WHEN TIMESTAMPDIFF(YEAR, a.date_of_birth, CURDATE()) >= 70 THEN '70+'
                    WHEN TIMESTAMPDIFF(YEAR, a.date_of_birth, CURDATE()) >= 60 THEN '60+'
                    WHEN TIMESTAMPDIFF(YEAR, a.date_of_birth, CURDATE()) >= 50 THEN '50+'
                    ELSE 'Open'
                END as age_group,
                CASE
                    WHEN a.gender = 'M' THEN 'Male'
                    ELSE 'Female'
                END as gender_display,
                CASE
                    WHEN a.equipment = 'RECURVE' THEN 'Recurve'
                    WHEN a.equipment = 'COMPOUND' THEN 'Compound'
                    WHEN a.equipment = 'RECURVE_BAREBOW' THEN 'Barebow (Recurve)'
                    WHEN a.equipment = 'COMPOUND_BAREBOW' THEN 'Barebow (Compound)'
                    WHEN a.equipment = 'LONGBOW' THEN 'Longbow'
                    ELSE a.equipment
                END as equipment_display
            FROM archer a
            INNER JOIN users u ON a.id = u.archer_id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
    } else {
        // Managers can see all archers
        $stmt = $pdo->prepare("
            SELECT
                a.id,
                a.first_name,
                a.last_name,
                a.gender,
                a.date_of_birth,
                a.phone_number,
                a.email,
                a.equipment,
                a.created_at,
                a.updated_at,
                CONCAT(a.first_name, ' ', a.last_name) as full_name,
                TIMESTAMPDIFF(YEAR, a.date_of_birth, CURDATE()) as age,
                CASE
                    WHEN TIMESTAMPDIFF(YEAR, a.date_of_birth, CURDATE()) < 14 THEN 'U14'
                    WHEN TIMESTAMPDIFF(YEAR, a.date_of_birth, CURDATE()) < 16 THEN 'U16'
                    WHEN TIMESTAMPDIFF(YEAR, a.date_of_birth, CURDATE()) < 18 THEN 'U18'
                    WHEN TIMESTAMPDIFF(YEAR, a.date_of_birth, CURDATE()) < 21 THEN 'U21'
                    WHEN TIMESTAMPDIFF(YEAR, a.date_of_birth, CURDATE()) >= 70 THEN '70+'
                    WHEN TIMESTAMPDIFF(YEAR, a.date_of_birth, CURDATE()) >= 60 THEN '60+'
                    WHEN TIMESTAMPDIFF(YEAR, a.date_of_birth, CURDATE()) >= 50 THEN '50+'
                    ELSE 'Open'
                END as age_group,
                CASE
                    WHEN a.gender = 'M' THEN 'Male'
                    ELSE 'Female'
                END as gender_display,
                CASE
                    WHEN a.equipment = 'RECURVE' THEN 'Recurve'
                    WHEN a.equipment = 'COMPOUND' THEN 'Compound'
                    WHEN a.equipment = 'RECURVE_BAREBOW' THEN 'Barebow (Recurve)'
                    WHEN a.equipment = 'COMPOUND_BAREBOW' THEN 'Barebow (Compound)'
                    WHEN a.equipment = 'LONGBOW' THEN 'Longbow'
                    ELSE a.equipment
                END as equipment_display,
                CONCAT(
                    CASE
                        WHEN TIMESTAMPDIFF(YEAR, a.date_of_birth, CURDATE()) < 14 THEN 'U14'
                        WHEN TIMESTAMPDIFF(YEAR, a.date_of_birth, CURDATE()) < 16 THEN 'U16'
                        WHEN TIMESTAMPDIFF(YEAR, a.date_of_birth, CURDATE()) < 18 THEN 'U18'
                        WHEN TIMESTAMPDIFF(YEAR, a.date_of_birth, CURDATE()) < 21 THEN 'U21'
                        WHEN TIMESTAMPDIFF(YEAR, a.date_of_birth, CURDATE()) >= 70 THEN '70+'
                        WHEN TIMESTAMPDIFF(YEAR, a.date_of_birth, CURDATE()) >= 60 THEN '60+'
                        WHEN TIMESTAMPDIFF(YEAR, a.date_of_birth, CURDATE()) >= 50 THEN '50+'
                        ELSE 'Open'
                    END,
                    ' ',
                    CASE WHEN a.gender = 'M' THEN 'Male' ELSE 'Female' END,
                    ' ',
                    CASE
                        WHEN a.equipment = 'RECURVE' THEN 'Recurve'
                        WHEN a.equipment = 'COMPOUND' THEN 'Compound'
                        WHEN a.equipment = 'RECURVE_BAREBOW' THEN 'Barebow (Recurve)'
                        WHEN a.equipment = 'COMPOUND_BAREBOW' THEN 'Barebow (Compound)'
                        WHEN a.equipment = 'LONGBOW' THEN 'Longbow'
                        ELSE a.equipment
                    END
                ) as category_display
            FROM archer a
            ORDER BY a.last_name, a.first_name
        ");
        $stmt->execute();
    }

    $archers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'archers' => $archers,
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
