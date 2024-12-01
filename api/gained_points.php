<?php
require_once 'dbconnection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve usr_id and points from the POST request
    $usr_id = isset($_POST['usr_id']) ? intval($_POST['usr_id']) : 0; // Convert to integer
    $points = isset($_POST['usr_points']) ? intval($_POST['usr_points']) : 0; // Convert to integer

    // Validate usr_id and points
    if ($usr_id > 0 && $points >= 0) {
        // First, check if the usr_id already exists in the tbl_points table
        $checkStmt = $conn->prepare("SELECT usr_points FROM tbl_points WHERE usr_id = ?");
        $checkStmt->bind_param("i", $usr_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            // If the usr_id exists, update the points by adding the new ones
            $updateStmt = $conn->prepare("UPDATE tbl_points SET usr_points = usr_points + ? WHERE usr_id = ?");
            $updateStmt->bind_param("ii", $points, $usr_id);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            // If the usr_id does not exist, insert a new record
            $insertStmt = $conn->prepare("INSERT INTO tbl_points (usr_id, usr_points) VALUES (?, ?)");
            $insertStmt->bind_param("ii", $usr_id, $points);
            $insertStmt->execute();
            $insertStmt->close();
        }

        // Now, insert a notification
        $notification_message = "Gained $points points";
        $notifStmt = $conn->prepare("INSERT INTO tbl_notification (usr_id, message) VALUES (?, ?)");
        $notifStmt->bind_param("is", $usr_id, $notification_message);
        $notifStmt->execute();
        $notifStmt->close();

        // Check if the point update/insert was successful
        if ($checkResult->num_rows > 0 || $checkStmt->affected_rows > 0) {
            echo "Points successfully updated or inserted for user ID: " . $usr_id;
        } else {
            echo "Error: " . $checkStmt->error;
        }

        // Close the check statement
        $checkStmt->close();
    } else {
        echo "Invalid usr_id or points.";
    }
} else {
    echo "Only POST requests are allowed.";
}

// Close the database connection
$conn->close();
?>