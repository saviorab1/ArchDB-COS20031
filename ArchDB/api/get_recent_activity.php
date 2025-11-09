<?php
/**
 * Get Recent Activity
 * Returns recent scores and upcoming competitions from database
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

$activity = [];

// Get recent scores
$recentScores = [];
$sql = "SELECT a.first_name, a.last_name, s.total_points, r.name as round_name, s.score_date
        FROM score s
        JOIN archer a ON s.archer_id = a.id
        JOIN round r ON s.round_id = r.id
        ORDER BY s.score_date DESC
        LIMIT 4";

$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $recentScores[] = [
        'archer_name' => $row['first_name'] . ' ' . $row['last_name'],
        'points' => $row['total_points'],
        'round_name' => $row['round_name'],
        'date' => $row['score_date']
    ];
}

// Get upcoming competitions
$upcomingCompetitions = [];
$sql = "SELECT name, competition_date, location
        FROM competition
        WHERE competition_date >= CURDATE()
        ORDER BY competition_date ASC
        LIMIT 3";

$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $upcomingCompetitions[] = [
        'name' => $row['name'],
        'date' => $row['competition_date'],
        'location' => $row['location']
    ];
}

echo json_encode([
    'success' => true,
    'recent_scores' => $recentScores,
    'upcoming_competitions' => $upcomingCompetitions
]);

$conn->close();
?>

