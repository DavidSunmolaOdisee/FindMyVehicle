<?php
// Route: POST /parkings/close?id=...
$id = $_GET["id"] ?? null;
if (!$id) {
  deliver_response(400, ["error" => "Parking id required"]);
}

$sql = "UPDATE parkings SET closed_at = NOW() WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
  deliver_response(200, ["message" => "Parking closed"]);
} else {
  deliver_response(500, ["error" => $stmt->error]);
}
