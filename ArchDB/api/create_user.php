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

//Check require fields
$requireFields = ['username', 'password', 'email'];
foreach($requireFields as $field) {
    if(!isset($input[$field]) || trim($input[$field]) === '') {
        echo json_encode(['success'=> false,'message'=> "Field '$field' is require" ]);
        exit;
    }
}

//
$username = trim($input["username"]);
$password = $input["password"];
$email = trim($input["email"]);

// Check if user exited ?
$stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
$stmt->bind_param("s", $username);  
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(["success"=> false,"message"=> "User already exists"]);
    exit;
}

$stmt->close();

// Hash pass
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Default role for User
$role = "user";

//Insert User
$stmt = $conn->prepare("INSERT INTO users (username, password_hash, email, role) VALUES (?,?,?,?)");
$stmt->bind_param("ssss", $username, $password_hash, $email, $role);
if ($stmt->execute()){
    echo json_encode([
    "success"=> true, "user_id" => $stmt->insert_id, "message"=> "User created successfully"
]);
}else{
    echo json_encode(["success"=> false,"message"=> $stmt->error]);
}

$stmt->close();
$conn->close(); 

?>
