<?php
$host = "127.0.0.1:3306";
$user = "u717011923_lottery_system";
$pass = "rty12345+A";
$dbname = "u717011923_lottery_system";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
