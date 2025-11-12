<?php
// inputs: { user_id, type, brand?, model?, notes?, photo_url? }
$body = getJsonBody();
check_required_fields($body, ["user_id", "type"]);

$user_id   = (int)($body["user_id"]);
$type      = $body["type"];
$brand     = $body["brand"]     ?? null;
$model     = $body["model"]     ?? null;
$notes     = $body["notes"]     ?? null;
$photo_url = $body["photo_url"] ?? null;

$sql = "INSERT INTO vehicles (user_id, type, brand, model, notes, photo_url)
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isssss", $user_id, $type, $brand, $model, $notes, $photo_url);

if ($stmt->execute()) {
  deliver_response(201, ["message" => "Vehicle added", "id" => $stmt->insert_id]);
} else {
  deliver_response(500, ["error" => $stmt->error]);
}
