<?php
// Route: PUT /users?id=...
// Body (alles optioneel): { username?, email? }
$id = $_GET["id"] ?? null;
if (!$id) {
  deliver_response(400, ["error" => "User id required"]);
}

$body     = getJsonBody();
$username = $body["username"] ?? null;
$email    = $body["email"]    ?? null;

/* Als username wijzigt: uniekheid bewaken */
if (!is_null($username)) {
  $chk = $conn->prepare("SELECT id FROM app_users WHERE username = ? AND id <> ?");
  $chk->bind_param("si", $username, $id);
  $chk->execute();
  $exists = $chk->get_result()->fetch_assoc();
  $chk->close();

  if ($exists) {
    deliver_response(409, ["error" => "Username already exists"]);
  }
}

/* Update met COALESCE zodat null = niet wijzigen */
$sql = "UPDATE app_users
        SET
          username = COALESCE(?, username),
          email    = COALESCE(?, email)
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $username, $email, $id);

if ($stmt->execute()) {
  deliver_response(200, ["message" => "User updated"]);
} else {
  deliver_response(500, ["error" => $stmt->error]);
}
