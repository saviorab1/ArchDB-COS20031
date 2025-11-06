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

// Get input from JSON
$input = json_decode(file_get_contents("php://input"), true);

// All field are require
$requireFields = ["user_id","full_name","club_id","dob","gender"];
foreach ($requireFields as $field){
    if(!isset($input[$field]) || trim($input[$field]) === ''){
        echo json_encode(["success"=> false,"message"=> "Field '$field' is required"]);
        exit;
    }
}

//
$user_id = intval($input["user_id"]);
$full_name = trim($input["full_name"]);
$club_id = intval($input["club_id"]);
$dob = $input["dob"];;
$gender = strtoupper(trim($input["gender"]));

// Validate DOB (YYYY-MM-DD, FE can use calendar)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob) || strtotime($dob) === false) {
    echo json_encode(["success" => false, "message" => "Invalid date format for dob, expected YYYY-MM-DD"]);
    exit;
}
if (strtotime($dob) > time()) {
    echo json_encode(["success" => false, "message" => "dob cannot be in the future"]);
    exit;
}

// Validate gender (FE can use dropbox)
$validGenders = ['M', 'F', 'Other'];
if (!in_array($gender, $validGenders)) {
    echo json_encode(["success" => false, "message" => "Invalid gender value"]);
    exit;
}

// Check if userID valid or not
$stmt = $conn->prepare("SELECT id FROM users WHERE id=?");
$stmt->bind_param("i",$user_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0){
    echo json_encode(["success"=>false,"message"=>"User not found"]);
    exit;
}
$stmt->close();

// Insert archer
$stmt = $conn->prepare("INSERT INTO archers (user_id, club_id, full_name, dob, gender) VALUES (?,?,?,?,?)");
$stmt->bind_param("iisss",$user_id,$club_id,$full_name,$dob,$gender);
if($stmt->execute()){
    echo json_encode([
        "success"=>true,
        "archer_id"=>$stmt->insert_id,
        "message"=>"Archer created successfully"
    ]);
}else{
    echo json_encode(["success"=>false,"message"=>$stmt->error]);
}
$stmt->close();
$conn->close();

?>



