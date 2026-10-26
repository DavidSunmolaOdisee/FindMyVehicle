<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "findmyvehicle";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    deliver_response(500, ["error" => "Database connection failed"]);
}

$conn->set_charset("utf8mb4");
