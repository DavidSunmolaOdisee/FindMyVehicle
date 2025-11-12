<?php
// Route: DELETE /users?id=...
$id = $_GET["id"] ?? null;
if (!$id) {
  deliver_response(400, ["error" => "User id required"]);
}

$stmt = $conn->prepare("DELETE FROM app_users WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
  deliver_response(200, ["message" => "User deleted"]);
} else {
  deliver_response(500, ["error" => $stmt->error]);
}
