<?php
/**
 * Get Dashboard Statistics
 * Returns stats data from database
 */

header("Content-Type: application/json");

require_once '../../db.php';
require_once 'auth_helper.php';

// Check authentication
if (!isAuthenticated()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$stats = [];

// Total active archers
$result = $conn->query("SELECT COUNT(*) as count FROM archer");
$row = $result->fetch_assoc();
$stats['total_archers'] = $row['count'];

// Total scores
$result = $conn->query("SELECT COUNT(*) as count FROM score");
$row = $result->fetch_assoc();
$stats['total_scores'] = $row['count'];

// Active competitions
$result = $conn->query("SELECT COUNT(*) as count FROM competition WHERE competition_date >= CURDATE()");
$row = $result->fetch_assoc();
$stats['active_competitions'] = $row['count'];

// Recent scores (this week)
$result = $conn->query("SELECT COUNT(*) as count FROM score WHERE score_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$row = $result->fetch_assoc();
$stats['recent_scores'] = $row['count'];

echo json_encode([
    'success' => true,
    'stats' => $stats
]);

$conn->close();
?>

