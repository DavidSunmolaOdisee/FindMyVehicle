<?php
$id = $_GET["id"] ?? null;
if (!$id) deliver_response(400, ["error" => "Vehicle id required"]);

$body        = getJsonBody();
$type        = $body["type"]       ?? null;
$brand       = $body["brand"]      ?? null;
$model       = $body["model"]      ?? null;
$notes       = $body["notes"]      ?? null;
$photo_b64   = $body["photo"]      ?? null;
$photo_mime  = $body["photo_mime"] ?? null;

$photo = $photo_b64 ? base64_decode($photo_b64) : null;

$sql = "UPDATE vehicles
        SET
          type       = COALESCE(?, type),
          brand      = COALESCE(?, brand),
          model      = COALESCE(?, model),
          notes      = COALESCE(?, notes),
          photo      = COALESCE(?, photo),
          photo_mime = COALESCE(?, photo_mime)
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssbsi", $type, $brand, $model, $notes, $photo, $photo_mime, $id);
if ($photo !== null) {
    $stmt->send_long_data(4, $photo);
}

if ($stmt->execute()) {
  deliver_response(200, ["message" => "Vehicle updated"]);
} else {
  deliver_response(500, ["error" => $stmt->error]);
}