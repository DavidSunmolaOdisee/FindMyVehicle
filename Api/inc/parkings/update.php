<?php
// Route: PUT /parkings?id=...
// Body (alles optioneel): { level?, description?, expires_at?, latitude?, longitude? }
$id = $_GET["id"] ?? null;
if (!$id) {
  deliver_response(400, ["error" => "Parking id required"]);
}

$body = getJsonBody();
$level       = $body["level"]       ?? null;
$description = $body["description"] ?? null;
$expires_at  = $body["expires_at"]  ?? null;

$latitude  = array_key_exists("latitude",  $body) ? (float)$body["latitude"]  : null;
$longitude = array_key_exists("longitude", $body) ? (float)$body["longitude"] : null;

$sql = "UPDATE parkings
        SET
          level       = COALESCE(?, level),
          description = COALESCE(?, description),
          expires_at  = COALESCE(?, expires_at),
          latitude    = COALESCE(?, latitude),
          longitude   = COALESCE(?, longitude)
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssddi", $level, $description, $expires_at, $latitude, $longitude, $id);

if ($stmt->execute()) {
  deliver_response(200, ["message" => "Parking updated"]);
} else {
  deliver_response(500, ["error" => $stmt->error]);
}
