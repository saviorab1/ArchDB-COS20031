<?php
/**
 * Insert Sample Competition Data
 * Use this to populate the database with sample competitions
 */

header("Content-Type: application/json");

require_once '../../db.php';

// Insert sample competitions if they don't exist
$competitions = [
    ['Winter League Round 1', '2025-01-15', 'Archery Range A'],
    ['Winter League Round 2', '2025-01-29', 'Archery Range B'],
    ['Winter League Round 3', '2025-02-12', 'Archery Range A'],
    ['Club Championship', '2025-02-28', 'Archery Range A'],
    ['Spring Open', '2025-03-15', 'Archery Range B']
];

$inserted = 0;
foreach ($competitions as $comp) {
    $stmt = $conn->prepare("INSERT IGNORE INTO competition (name, competition_date, location, is_club_championship) VALUES (?, ?, ?, ?)");
    $is_championship = ($comp[0] == 'Club Championship') ? 1 : 0;
    $stmt->bind_param("sssi", $comp[0], $comp[1], $comp[2], $is_championship);
    if ($stmt->execute()) {
        $inserted++;
    }
    $stmt->close();
}

echo json_encode([
    'success' => true,
    'message' => "Inserted $inserted sample competitions"
]);

$conn->close();
?>

