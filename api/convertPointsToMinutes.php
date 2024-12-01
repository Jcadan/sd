<?php
require_once 'dbconnection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve usr_id from the POST request
    $usr_id = isset($_POST['usr_id']) ? intval($_POST['usr_id']) : 0;

    if ($usr_id > 0) {
        try {
            // Fetch current points from the database
            $stmt = $conn->prepare("SELECT usr_points, charging_minutes FROM tbl_points WHERE usr_id = ?");
            $stmt->bind_param("i", $usr_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $usr_points = (int) $user['usr_points'];
                $charging_minutes = $user['charging_minutes']; // existing charging minutes (stored as TIME)

                // Ensure the user has at least 10 points for conversion
                if ($usr_points >= 10) {
                    // Define the conversion rate (10 points = 3 minutes)
                    $points_per_minute = 10;
                    $minutes_per_conversion = 3;

                    // Convert 10 points to minutes
                    $charging_minutes_added = $minutes_per_conversion;

                    // Convert current time stored in TIME format to minutes
                    $current_time_parts = explode(":", $charging_minutes);
                    $current_minutes = ($current_time_parts[0] * 60) + $current_time_parts[1]; // Convert hours:minutes to total minutes

                    // Add the new minutes to the current minutes
                    $new_minutes = $current_minutes + $charging_minutes_added;

                    // Convert total minutes back to HH:MM:SS format
                    $hours = floor($new_minutes / 60);
                    $minutes = $new_minutes % 60;
                    $seconds = 0; // Assuming seconds are not needed or are always zero
                    $new_time = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);

                    // Subtract 10 points from the user's account
                    $remaining_points = $usr_points - $points_per_minute;

                    // Update the database with new points and charging minutes
                    $updateStmt = $conn->prepare("UPDATE tbl_points SET usr_points = ?, charging_minutes = ? WHERE usr_id = ?");
                    $updateStmt->bind_param("isi", $remaining_points, $new_time, $usr_id);
                    $updateStmt->execute();

                    if ($updateStmt->affected_rows > 0) {
                        $notification_message = "Converted 10 points";
                        $notifStmt = $conn->prepare("INSERT INTO `tbl_notification`(`usr_id`, `message`) VALUES (?, ?)");
                        $notifStmt->bind_param("is", $usr_id, $notification_message);
                        $notifStmt->execute();
                        $notifStmt->close();

                        $resetStmt = $conn->prepare("UPDATE tbl_points SET charging_minutes = '00:00:00' WHERE usr_id = ?");
                        $resetStmt->bind_param("i", $usr_id);
                        $resetStmt->execute();
                        $resetStmt->close();
                        // Successfully updated points and charging minutes
                        header('Content-Type: application/json');
                        echo json_encode([
                            "status" => "success",
                            "charging_minutes" => $new_time,
                            "remaining_points" => $remaining_points
                        ]);
                    } else {
                        // Failed to update points
                        header('Content-Type: application/json');
                        echo json_encode(["status" => "error", "message" => "Failed to update points"]);
                    }

                    $updateStmt->close();
                } else {
                    // Not enough points for conversion
                    header('Content-Type: application/json');
                    echo json_encode(["status" => "error", "message" => "Insufficient points for conversion"]);
                }
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