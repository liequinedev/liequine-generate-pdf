<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "lieq_staging_1";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
