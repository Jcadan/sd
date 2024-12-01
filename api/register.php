<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

include('dbconnection.php');

$username = $_POST["username"];
$email = $_POST["email"];
$password = password_hash($_POST["password"], PASSWORD_BCRYPT);
$response = array();

// Prepare the statement to find if the username or email already exists
$findexist = $conn->prepare("SELECT * FROM tbl_account WHERE usr_username = ? OR usr_email = ?");
$findexist->bind_param("ss", $username, $email);
$findexist->execute();
$resultsearch = $findexist->get_result();

if ($resultsearch->num_rows > 0) {
    $row = $resultsearch->fetch_assoc();

    if ($row['usr_username'] == $username) {
        $response['success'] = 0;
        $response['message'] = "Username already exists";
    }
    if ($row['usr_email'] == $email) {
        $response['success'] = 0;
        $response['message'] = "Email already exists";
    }
    echo json_encode($response);
} else {
    // Prepare the statement to insert the new user
    $stmt = $conn->prepare("INSERT INTO `tbl_account`(`usr_username`, `usr_email`, `usr_password`) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $password);

    if ($stmt->execute()) {
        $response['success'] = 1;
        $response['message'] = 'Registration Successful';
        sendmessage($email);
    } else {
        $response['success'] = 0;
        $response['message'] = 'Error in Registration';
    }
    echo json_encode($response);
    $stmt->close();
}

mysqli_close($conn);

function sendmessage($email)
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

    $mail->Subject = "Success Creating Account";
    $mail->Body = "<strong>Success Creating Account</strong>";

    if (!$mail->send()) {
        throw new Exception("Mailer Error: " . $mail->ErrorInfo);
    }
}
?>