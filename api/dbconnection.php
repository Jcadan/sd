<?php

header('Access-Control-Allow-Origin: *');
$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "coa_project";
try {
    $conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
} catch (Exception $e) {
    echo "Connection Failed: " . $e->getMessage();
}

?>