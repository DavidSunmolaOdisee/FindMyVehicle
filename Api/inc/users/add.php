<?php
// Body: { username, email? }
$body = getJsonBody();
check_required_fields($body, ["username"]);

$username = $body["username"];
$email    = $body["email"] ?? null;

/* Unieke username check */
$chk = $conn->prepare("SELECT id FROM app_users WHERE username = ?");
$chk->bind_param("s", $username);
$chk->execute();
$exists = $chk->get_result()->fetch_assoc();
$chk->close();

if ($exists) {
  deliver_response(409, ["error" => "Username already exists"]);
}

/* Insert */
$stmt = $conn->prepare("INSERT INTO app_users (username, email) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $email);

if ($stmt->execute()) {
  deliver_response(201, ["message" => "User added", "id" => $stmt->insert_id]);
} else {
  deliver_response(500, ["error" => $stmt->error]);
}
