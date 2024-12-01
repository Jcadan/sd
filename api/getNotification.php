<?php
require_once 'dbconnection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usr_id = isset($_POST['usr_id']) ? intval($_POST['usr_id']) : 0;

    if ($usr_id > 0) {
        try {
            // Fetch recent notifications for the user
            $stmt = $conn->prepare("SELECT message, created_at FROM tbl_notification WHERE usr_id = ? ORDER BY created_at DESC LIMIT 3");
            $stmt->bind_param("i", $usr_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $notifications = [];
            while ($row = $result->fetch_assoc()) {
                $notifications[] = $row;
            }

            header('Content-Type: application/json');
            echo json_encode(["status" => "success", "notifications" => $notifications]);
            $stmt->close();
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Internal server error"]);
        }
    } else {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid user ID"]);
    }
} else {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Only POST requests are allowed"]);
}

$conn->close();
?>