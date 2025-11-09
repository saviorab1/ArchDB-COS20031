<?php
header("Content-Type: application/json");
include '../db.php';


error_reporting(E_ALL); // Use these command to see the log clearly instead of only '1'
ini_set('display_errors', 1);

//check DB connection
if (!$conn) {
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit;
}

// Check If inject archer_id or session_id success or nah
$archer_id = isset($_GET['archer_id']) ? intval($_GET['archer_id']) :null;
$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : null;

//Need only one so use &&
if(!$archer_id && !$session_id){
    echo json_encode([
        'success' => false,
        'message' => 'Missing archer_id or session_id'
    ]);
    exit;
}

// Get 'arrows' details by 'session_id'
if ($session_id) {
    $query = "
         SELECT 
            a.id AS arrow_id, a.arrow_index, a.score, a.recorded_at,
            s.id AS session_id, s.total_score, s.session_date,
            r.id AS round_id, r.name AS round_name, r.target_distance, r.arrows_per_end, r.ends,
            c.id AS competition_id, c.name AS competition_name,
            ar.id AS archer_id, ar.full_name,
            u.username,
            cl.id AS club_id, cl.name AS club_name
        FROM arrows a
        JOIN score_sessions s ON a.session_id = s.id
        JOIN rounds r ON s.round_id = r.id
        JOIN competitions c ON r.competition_id = c.id
        JOIN archers ar ON s.archer_id = ar.id
        LEFT JOIN users u ON ar.user_id = u.id
        LEFT JOIN clubs cl ON ar.club_id = cl.id
        WHERE a.session_id = ?
        ORDER BY a.arrow_index ASC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $session_id);
}

// Get all 'session' of 'archer'
elseif ($archer_id) {
    $query = "
    SELECT 
            s.id AS session_id,
            s.total_score,
            s.session_date,
            r.id AS round_id,
            r.name AS round_name,
            r.target_distance,
            r.arrows_per_end,
            r.ends,
            c.id AS competition_id,
            c.name AS competition_name,
            cl.id AS club_id,
            cl.name AS club_name,
            u.username,
            ar.full_name
        FROM score_sessions s
        JOIN rounds r ON s.round_id = r.id
        JOIN competitions c ON r.competition_id = c.id
        LEFT JOIN archers ar ON s.archer_id = ar.id
        LEFT JOIN clubs cl ON ar.club_id = cl.id
        LEFT JOIN users u ON ar.user_id = u.id
        WHERE s.archer_id = ?
        ORDER BY s.session_date DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $archer_id); 
}
// execute and fetch
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);

// If there is no data
if (empty($data)) {
    echo json_encode(["success"=> true,"data" =>[],"message"=> "No records found"]) ;
    $stmt->close();
    $conn->close();
    exit;
}
// Json Echo
echo json_encode(["success"=> true,"data"=>$data]);
$stmt->close();
$conn->close();
exit;

?>