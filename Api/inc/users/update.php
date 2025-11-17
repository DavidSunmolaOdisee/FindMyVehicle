<?php
// PUT /users?id=...
$id = $_GET["id"] ?? null;
if (!$id) {
  deliver_response(400, ["error" => "User id required"]);
}

$body = getJsonBody();
$username = $body["username"] ?? null;
$email    = $body["email"]    ?? null;

// unique check als username wijzigt
if (!is_null($username)) {
  $chk = $conn->prepare("SELECT id FROM app_users WHERE username = ? AND id <> ?");
  $chk->bind_param("si", $username, $id);
  $chk->execute();
  if ($chk->get_result()->fetch_assoc()) {
    deliver_response(409, ["error" => "Username already exists"]);
  }
  $chk->close();
}

$sql = "UPDATE app_users
        SET username = COALESCE(?, username),
            email    = COALESCE(?, email)
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $username, $email, $id);

if ($stmt->execute()) {
  deliver_response(200, ["message" => "User updated"]);
} else {
  deliver_response(500, ["error" => $stmt->error]);
}
