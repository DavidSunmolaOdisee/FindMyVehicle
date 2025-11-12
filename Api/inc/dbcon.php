<?php
$servername = "ID476851_DavidOlusegunWM.db.webhosting.be";
$username = "ID476851_DavidOlusegunWM"; // username (zie Hosting)
$password = "XXXXX"; // paswoord DATABANK (zie hosting)
$dbname = "ID476851_DavidOlusegunWM"; // naam databank (zie hosting ; zelf gekozen)

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    // Als jouw base.php deliver_response gebruikt, kan je deze lijn gebruiken:
    // deliver_response(500, ["error" => "Database connection failed: " . $conn->connect_error]);

    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
    exit;
}
$conn->set_charset("utf8mb4");
