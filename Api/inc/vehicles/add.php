<?php
$body = getJsonBody();
check_required_fields($body, ["user_id", "type"]);

$user_id     = (int)$body["user_id"];
$type        = $body["type"];
$brand       = $body["brand"]       ?? null;
$model       = $body["model"]       ?? null;
$notes       = $body["notes"]       ?? null;
$photo_b64   = $body["photo"]       ?? null;
$photo_mime  = $body["photo_mime"]  ?? null;

$photo = $photo_b64 ? base64_decode($photo_b64) : null;

$sql = "INSERT INTO vehicles (user_id, type, brand, model, notes, photo, photo_mime)
        VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issssbs", $user_id, $type, $brand, $model, $notes, $photo, $photo_mime);
if ($photo !== null) {
    $stmt->send_long_data(5, $photo);
}

if ($stmt->execute()) {
  deliver_response(201, ["message" => "Vehicle added", "id" => $stmt->insert_id]);
} else {
  deliver_response(500, ["error" => $stmt->error]);
}