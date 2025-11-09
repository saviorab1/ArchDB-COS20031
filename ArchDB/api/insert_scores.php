<?php
header("Content-Type: application/json");
include '../db.php';

error_reporting(E_ALL); // Use these command to see the log clearly instead of only '1'
ini_set('display_errors', 1);

//check DB connection
if (!$conn) {
    echo json_encode(["success"=>false,"message"=>"DB connection failed"]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents("php://input"), true);

// Required fields
$requireFields = ["archer_id","round_id","scores"];
foreach($requireFields as $field){
    if(!isset($input[$field]) || empty($input[$field])){
        echo json_encode(["success"=>false,"message"=>"Field '$field' is required"]);
        exit;
    }
}

$archer_id = intval($input["archer_id"]);
$round_id = intval($input["round_id"]);
$scores = $input["scores"]; // array

if(!is_array($scores) || count($scores) === 0){
    echo json_encode(["success"=>false,"message"=>"Scores must be a non-empty array"]);
    exit;
}

// Check archer exists
$stmt = $conn->prepare("SELECT id FROM archers WHERE id=?");
$stmt->bind_param("i",$archer_id);
$stmt->execute();
$stmt->store_result();
if($stmt->num_rows === 0){
    echo json_encode(["success"=>false,"message"=>"Archer not found"]);
    exit;
}
$stmt->close();

// Check round exists
$stmt = $conn->prepare("SELECT id FROM rounds WHERE id=?");
$stmt->bind_param("i",$round_id);
$stmt->execute();
$stmt->store_result();
if($stmt->num_rows === 0){
    echo json_encode(["success"=>false,"message"=>"Round not found"]);
    exit;
}
$stmt->close();

// Calculate total_score
$total_score = array_sum($scores);

// Insert into score_sessions
$stmt = $conn->prepare("INSERT INTO score_sessions (archer_id, round_id, total_score) VALUES (?,?,?)");
$stmt->bind_param("iii",$archer_id,$round_id,$total_score);
if(!$stmt->execute()){
    echo json_encode(["success"=>false,"message"=>$stmt->error]);
    exit;
}
$session_id = $stmt->insert_id;
$stmt->close();

// Insert arrows
$stmt = $conn->prepare("INSERT INTO arrows (session_id, arrow_index, score) VALUES (?,?,?)");
foreach($scores as $index => $score){
    $arrow_index = $index + 1;
    $stmt->bind_param("iii",$session_id,$arrow_index,$score);
    $stmt->execute();
}
$stmt->close();

echo json_encode([
    "success"=>true,
    "session_id"=>$session_id,
    "total_score"=>$total_score,
    "message"=>"Scores inserted successfully"
]);

$conn->close();
?>
