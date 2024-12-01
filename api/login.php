<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
error_reporting(0);

include("dbconnection.php");

$username = $_POST['username'];
$email = $_POST['email'];
$password = $_POST['password'];

$sql = "SELECT * FROM tbl_account WHERE usr_username = ? OR usr_email = ? ";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["success" => 0, "message" => "SQL prepare error: " . mysqli_error($conn)]);
    exit;
}

$stmt->bind_param("ss", $username, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $usr_id = $user['usr_id'];
    $usr_username = $user['usr_username'];
    $usr_email = $user['usr_email'];


    if (password_verify($password, $user['usr_password'])) {
        echo json_encode(["success" => 1, "message" => "Login successful", "usr_id" => $usr_id, "usr_username" => $usr_username, "usr_email" => $usr_email]);
    } else {
        echo json_encode(["success" => 0, "message" => "Invalid Username or Password"]);
    }
} else {
    echo json_encode(["success" => 0, "message" => "Invalid Username or Password"]);
}

$stmt->close();
$conn->close();
?>