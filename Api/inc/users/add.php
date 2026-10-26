<?php
$body = getJsonBody();
check_required_fields($body, ["username", "password"]);

$username = $body["username"];
$email    = $body["email"] ?? null;
$password = password_hash($body["password"], PASSWORD_DEFAULT);

$chk = $conn->prepare("SELECT id FROM app_users WHERE username=?");
$chk->bind_param("s", $username);
$chk->execute();
$exists = $chk->get_result()->fetch_assoc();
$chk->close();

if ($exists) {
  deliver_response(409, ["error" => "Username already exists"]);
}

$sql = "INSERT INTO app_users (username, password, email) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $username, $password, $email);

if ($stmt->execute()) {
  deliver_response(201, ["message" => "User added", "id" => $stmt->insert_id]);
} else {
  deliver_response(500, ["error" => $stmt->error]);
}
