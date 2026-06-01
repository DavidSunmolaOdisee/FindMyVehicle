<?php
$body = getJsonBody();
check_required_fields($body, ["user_id", "vehicle_id", "latitude", "longitude"]);

$user_id     = (int)$body["user_id"];
$vehicle_id  = (int)$body["vehicle_id"];
$latitude    = (float)$body["latitude"];
$longitude   = (float)$body["longitude"];
$level       = $body["level"]       ?? null;
$description = $body["description"] ?? null;
$expires_at  = $body["expires_at"]  ?? null;
$photo_b64   = $body["photo"]       ?? null;
$photo_mime  = $body["photo_mime"]  ?? null;

$photo = $photo_b64 ? base64_decode($photo_b64) : null;

$sql = "INSERT INTO parkings (user_id, vehicle_id, latitude, longitude, level, description, expires_at, photo, photo_mime)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiddsssbs", $user_id, $vehicle_id, $latitude, $longitude, $level, $description, $expires_at, $photo, $photo_mime);
$stmt->send_long_data(7, $photo);

if ($stmt->execute()) {
  deliver_response(201, ["message" => "Parking started", "id" => $stmt->insert_id]);
} else {
  deliver_response(500, ["error" => $stmt->error]);
}