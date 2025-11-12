<?php
// route: PUT /vehicles?id=...
// body: { type?, brand?, model?, notes?, photo_url? }
$id = $_GET["id"] ?? null;
if (!$id) {
  deliver_response(400, ["error" => "Vehicle id required"]);
}

$body = getJsonBody();
$type      = $body["type"]      ?? null;
$brand     = $body["brand"]     ?? null;
$model     = $body["model"]     ?? null;
$notes     = $body["notes"]     ?? null;
$photo_url = $body["photo_url"] ?? null;

$sql = "UPDATE vehicles
        SET
          type      = COALESCE(?, type),
          brand     = COALESCE(?, brand),
          model     = COALESCE(?, model),
          notes     = COALESCE(?, notes),
          photo_url = COALESCE(?, photo_url)
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssi", $type, $brand, $model, $notes, $photo_url, $id);

if ($stmt->execute()) {
  deliver_response(200, ["message" => "Vehicle updated"]);
} else {
  deliver_response(500, ["error" => $stmt->error]);
}
