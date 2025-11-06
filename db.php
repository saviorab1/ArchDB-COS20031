<?php
$servername = "localhost";
$username = "";
$password = "";
$dbname = "ARC_db";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->autocommit(TRUE);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
