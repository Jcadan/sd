<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

require_once 'dbconnection.php';

$response = array();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // Step 1: Check if the email exists in the account table
    $sql = "SELECT usr_id FROM tbl_account WHERE usr_email = ? ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) { // Check if any user is found
        $user = $result->fetch_assoc();
        error_log("User found: " . json_encode($user));

        // Step 2: Check for an existing verification code
        $sql = "SELECT verification_code, code_expiry FROM tbl_verification WHERE usr_id = ? ORDER BY code_expiry DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user['usr_id']);
        $stmt->execute();
        $verificationResult = $stmt->get_result();

        // If an existing code is found
        if ($verificationResult->num_rows > 0) {
            $verificationData = $verificationResult->fetch_assoc();
            $expiryTime = $verificationData['code_expiry'];

            // Check if the existing code is still valid
            if (strtotime($expiryTime) > time()) {
                // Code is still valid, do not send a new one
                $response["status"] = 1; // Status for existing valid code
                $response["message"] = "A verification code has already been sent and is still valid.";
                $response["existing_code"] = $verificationData['verification_code']; // Optional: send back the existing code
            } else {
                // Existing code has expired, generate a new one
                $verification_code = random_int(100000, 999999); // More secure random code generation
                $code_expiry = date("Y-m-d H:i:s", strtotime("+3 minutes"));

                // Update the existing code in the database
                $sql = "UPDATE tbl_verification SET verification_code = ?, code_expiry = ? WHERE usr_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssi", $verification_code, $code_expiry, $user['usr_id']);

                if ($stmt->execute()) {
                    // Step 3: Send the verification code via email
                    sendVerificationEmail($email, $verification_code);
                    $response["status"] = 1;
                    $response["message"] = "New verification code sent to your email.";
                } else {
                    $response["status"] = 0;
                    $response["message"] = "Failed to update verification code.";
                }
            }
        } else {
            // No existing verification code found, generate a new one
            $verification_code = random_int(100000, 999999); // More secure random code generation
            $code_expiry = date("Y-m-d H:i:s", strtotime("+3 minutes"));

            // Insert the new verification code into the database
            $sql = "INSERT INTO tbl_verification(usr_Id, verification_code, code_expiry) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iss", $user['usr_id'], $verification_code, $code_expiry);

            if ($stmt->execute()) {
                // Step 3: Send the verification code via email
                sendVerificationEmail($email, $verification_code);
                $response["status"] = 1;
                $response["message"] = "Verification code sent to your email.";
            } else {
                $response["status"] = 0;
                $response["message"] = "Failed to save verification code.";
            }
        }
    } else {
        $response["status"] = 0;
        $response["message"] = "Email not found.";
    }
}

echo json_encode($response);

// Function to send the verification email
function sendVerificationEmail($email, $verification_code)
{
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = 0;
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';  // Replace with your SMTP server
    $mail->SMTPAuth = true;
    $mail->Username = 'pointcycle1@gmail.com';
    $mail->Password = 'ossg eagz yked wlha';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('pointcycle1@gmail.com', 'PointCycle');
    $mail->addAddress($email);
    $mail->isHTML(true);

    $mail->Subject = "Your Verification Code";
    $mail->Body = "Your verification code is: <strong>$verification_code</strong>";

    if (!$mail->send()) {
        throw new Exception("Mailer Error: " . $mail->ErrorInfo);
    }
}
?>