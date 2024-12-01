<?php
require_once 'dbconnection.php';

$response = array();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_POST['usr_id'];
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];

    // Validate input
    if (!empty($userId) && !empty($old_password) && !empty($new_password)) {
        // Retrieve the current hashed password from the database
        $sql = "SELECT usr_password FROM tbl_account WHERE usr_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $hashed_password = $user['usr_password'];

            // Verify the old password
            if (password_verify($old_password, $hashed_password)) {
                // Hash the new password
                $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update the password in the database
                $sql = "UPDATE tbl_account SET usr_password = ? WHERE usr_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $new_hashed_password, $userId);

                if ($stmt->execute()) {
                    $response['status'] = 1;
                    $response['message'] = "Password updated successfully.";
                } else {
                    $response['status'] = 0;
                    $response['message'] = "Failed to update password.";
                }
            } else {
                $response['status'] = 0;
                $response['message'] = "Old password is incorrect.";
            }
        } else {
            $response['status'] = 0;
            $response['message'] = "User not found.";
        }
    } else {
        $response['status'] = 0;
        $response['message'] = "User ID, old password, and new password are required.";
    }
} else {
    $response['status'] = 0;
    $response['message'] = "Invalid request method.";
}

echo json_encode($response);
?>