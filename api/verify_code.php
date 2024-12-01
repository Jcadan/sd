<?php

require_once 'dbconnection.php';

// Get the code from the POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $code = intval($_POST['code']);

    if (!empty($email && !empty($code))) {

        // Get the user ID based on the email
        $sql = "SELECT usr_id FROM tbl_account WHERE usr_email = ? ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $usrId = $user['usr_id'];

            // Check if the verification code exists and is valid
            $sql = "SELECT * FROM tbl_verification WHERE usr_id = ? AND verification_code = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $usrId, $code);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $verification_code = $result->fetch_assoc();
                $codeExpiry = $verification_code['code_expiry'];

                // Check if the code has expired
                if (strtotime($codeExpiry) > time()) {

                    // Code is valid and not expired
                    // Delete the verification code from the database
                    $deleteSql = "DELETE FROM tbl_verification WHERE usr_id = ? AND verification_code = ?";
                    $deleteStmt = $conn->prepare($deleteSql);
                    $deleteStmt->bind_param("ii", $usrId, $code);

                    if ($deleteStmt->execute()) {
                        // Successfully deleted the code
                        echo json_encode(['status' => 'success', 'message' => 'Code verified successfully and deleted.']);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Failed to delete verification code.']);
                    }

                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Code has expired.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid code.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No user found with that email.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Email and code are required.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}

?>