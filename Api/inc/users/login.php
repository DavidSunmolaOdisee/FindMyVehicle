<?php
$body = getJsonBody();
check_required_fields($body, ["username", "password"]);

$username = $body["username"];
$password = $body["password"];

$sql = "SELECT id, username, password FROM app_users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

if (!$user) {
  deliver_response(401, ["error" => "User not found"]);
}

if ($password !== $user["password"]) {
  deliver_response(401, ["error" => "Invalid password"]);
}

deliver_response(200, [
  "message" => "Login successful",
  "user" => ["id" => (int)$user["id"], "username" => $user["username"]]
]);
