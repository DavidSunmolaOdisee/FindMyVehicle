<?php
// Route: DELETE /parkings?id=...
$id = $_GET["id"] ?? null;
if (!$id) {
  deliver_response(400, ["error" => "Parking id required"]);
}

$sql = "DELETE FROM parkings WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
  deliver_response(200, ["message" => "Parking deleted"]);
} else {
  deliver_response(500, ["error" => $stmt->error]);
}
