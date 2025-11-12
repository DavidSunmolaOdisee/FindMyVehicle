<?php
// query: ?user_id=... [&id=...]
$user_id = $_GET["user_id"] ?? null;
$id      = $_GET["id"]      ?? null;

if (!$user_id) {
  deliver_response(400, ["error" => "user_id is required"]);
}

if ($id) {
  $sql = "SELECT * FROM vehicles WHERE id = ? AND user_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ii", $id, $user_id);
} else {
  $sql = "SELECT * FROM vehicles WHERE user_id = ? ORDER BY created_at DESC";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
$data = getJsonObjFromResult($result);
deliver_response(200, $data);
