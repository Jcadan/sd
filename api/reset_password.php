<?php
require_once 'dbconnection.php';

$response = array();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $new_password = $_POST['new_password'];

    // Validate input
    if (!empty($email) && !empty($new_password)) {
        // Get the user ID from the database using the email
        $sql = "SELECT usr_id FROM tbl_account WHERE usr_email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $usrId = $user['usr_id'];

            // Hash the new password for security
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update the password in the database
            $sql = "UPDATE tbl_account SET usr_password = ? WHERE usr_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $hashed_password, $usrId);

            if ($stmt->execute()) {
                $response['status'] = 1;
                $response['message'] = "Password updated successfully.";
            } else {
                $response['status'] = 0;
                $response['message'] = "Failed to update password.";
            }
        } else {
            $response['status'] = 0;
            $response['message'] = "Email not found.";
        }
    } else {
        $response['status'] = 0;
        $response['message'] = "Email and new password are required.";
    }
} else {
    $response['status'] = 0;
    $response['message'] = "Invalid request method.";
}

echo json_encode($response);
?>