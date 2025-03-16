<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "user_db";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error){
    die("Connection Failed: ". $conn->_error );
}
?>