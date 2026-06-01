<?php
$id = $_GET["id"] ?? null;
if (!$id) deliver_response(400, ["error" => "Parking id required"]);

$body        = getJsonBody();
$level       = $body["level"]       ?? null;
$description = $body["description"] ?? null;
$expires_at  = $body["expires_at"]  ?? null;
$latitude    = array_key_exists("latitude",  $body) ? (float)$body["latitude"]  : null;
$longitude   = array_key_exists("longitude", $body) ? (float)$body["longitude"] : null;
$photo_b64   = $body["photo"]       ?? null;
$photo_mime  = $body["photo_mime"]  ?? null;

$photo = $photo_b64 ? base64_decode($photo_b64) : null;

$sql = "UPDATE parkings
        SET
          level       = COALESCE(?, level),
          description = COALESCE(?, description),
          expires_at  = COALESCE(?, expires_at),
          latitude    = COALESCE(?, latitude),
          longitude   = COALESCE(?, longitude),
          photo       = COALESCE(?, photo),
          photo_mime  = COALESCE(?, photo_mime)
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssddbsi", $level, $description, $expires_at, $latitude, $longitude, $photo, $photo_mime, $id);
if ($photo !== null) {
    $stmt->send_long_data(5, $photo);
}

if ($stmt->execute()) {
  deliver_response(200, ["message" => "Parking updated"]);
} else {
  deliver_response(500, ["error" => $stmt->error]);
}