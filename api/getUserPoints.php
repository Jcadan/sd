<?php
require_once 'dbconnection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve usr_id from the POST request
    $usr_id = isset($_POST['usr_id']) ? intval($_POST['usr_id']) : 0;

    if ($usr_id > 0) {
        try {
            // Fetch points from the database
            $stmt = $conn->prepare("SELECT usr_points, charging_minutes FROM tbl_points WHERE usr_id = ?");
            $stmt->bind_param("i", $usr_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $usr_points = (int) $user['usr_points']; // Ensure usr_points is treated as an integer
                $charging_minutes = $user['charging_minutes']; 
                // Successfully fetched points
                header('Content-Type: application/json');
                http_response_code(200);
                echo json_encode(["status" => "success", "points" => $usr_points,"charging_minutes" => $charging_minutes]);
            } else {
                // User not found
                header('Content-Type: application/json');
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "User not found"]);
            }

            $stmt->close();
        } catch (Exception $e) {
            // Handle any errors
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Internal server error"]);
        }
    } else {
        // Invalid user ID
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid user ID"]);
    }
} else {
    // Method not allowed
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Only POST requests are allowed"]);
}

$conn->close();
?>