<?php
$user_id = $_GET["user_id"] ?? null;
$active  = $_GET["active"]  ?? null;
$id      = $_GET["id"]      ?? null;

if (!$user_id) deliver_response(400, ["error" => "user_id is required"]);

if ($id) {
  $sql = "SELECT * FROM parkings WHERE id = ? AND user_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ii", $id, $user_id);
} else if ($active === "1") {
  $sql = "SELECT * FROM parkings WHERE user_id = ? AND closed_at IS NULL ORDER BY started_at DESC";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $user_id);
} else {
  $sql = "SELECT * FROM parkings WHERE user_id = ? ORDER BY started_at DESC";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
$rows = [];
while ($row = $result->fetch_assoc()) {
  if ($row["photo"] !== null) {
    $row["photo"] = base64_encode($row["photo"]);
  }
  $rows[] = $row;
}
deliver_response(200, $rows);