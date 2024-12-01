<?php
require_once 'dbconnection.php';

$response = array();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usr_id = $_POST['usr_id'];
    $username = $_POST['usr_username'];
    $email = $_POST['usr_email'];

    // Validate input
    if (!empty($usr_id) && !empty($username) && !empty($email)) {
        // Check if the username already exists for a different user
        $checkUsernameSql = "SELECT usr_id FROM tbl_account WHERE usr_username = ? AND usr_id != ?";
        $checkUsernameStmt = $conn->prepare($checkUsernameSql);
        $checkUsernameStmt->bind_param("si", $username, $usr_id);
        $checkUsernameStmt->execute();
        $checkUsernameStmt->store_result();

        if ($checkUsernameStmt->num_rows > 0) {
            // Username already exists
            $response['status'] = 0;
            $response['message'] = "Username already exists.";
        } else {
            // Check if the email already exists for a different user
            $checkEmailSql = "SELECT usr_id FROM tbl_account WHERE usr_email = ? AND usr_id != ?";
            $checkEmailStmt = $conn->prepare($checkEmailSql);
            $checkEmailStmt->bind_param("si", $email, $usr_id);
            $checkEmailStmt->execute();
            $checkEmailStmt->store_result();

            if ($checkEmailStmt->num_rows > 0) {
                // Email already exists
                $response['status'] = 0;
                $response['message'] = "Email already exists.";
            } else {
                // Prepare the SQL statement to update user information
                $sql = "UPDATE tbl_account SET usr_username = ?, usr_email = ? WHERE usr_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssi", $username, $email, $usr_id);

                if ($stmt->execute()) {
                    // Return updated user information
                    $response['status'] = 1;
                    $response['message'] = "User information updated successfully.";
                    $response['usr_username'] = $username; // Return updated username
                    $response['usr_email'] = $email;       // Return updated email
                } else {
                    $response['status'] = 0;
                    $response['message'] = "Failed to update user information.";
                }
                $stmt->close();
            }
            $checkEmailStmt->close();
        }
        $checkUsernameStmt->close();
    } else {
        $response['status'] = 0;
        $response['message'] = "All fields are required.";
    }
} else {
    $response['status'] = 0;
    $response['message'] = "Invalid request method.";
}

echo json_encode($response);
$conn->close();
?>