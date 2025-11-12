<?php
// route: DELETE /vehicles?id=...
$id = $_GET["id"] ?? null;
if (!$id) {
  deliver_response(400, ["error" => "Vehicle id required"]);
}

$sql = "DELETE FROM vehicles WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
  deliver_response(200, ["message" => "Vehicle deleted"]);
} else {
  deliver_response(500, ["error" => $stmt->error]);
}
