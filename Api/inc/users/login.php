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
  deliver_response(401, ["error" => "Invalid username or password"]);
}

$storedPassword = $user["password"];
$info = password_get_info($storedPassword);
$isHashed = ($info["algo"] ?? 0) !== 0;

if ($isHashed) {
  $validPassword = password_verify($password, $storedPassword);
} else {
  // Backwards compatibility for accounts created before password hashing was added.
  $validPassword = hash_equals((string)$storedPassword, (string)$password);

  // Transparently upgrade a legacy plaintext password after a successful login.
  if ($validPassword) {
    $upgradedHash = password_hash($password, PASSWORD_DEFAULT);
    $upgrade = $conn->prepare("UPDATE app_users SET password=? WHERE id=?");
    $upgrade->bind_param("si", $upgradedHash, $user["id"]);
    $upgrade->execute();
    $upgrade->close();
  }
}

if (!$validPassword) {
  deliver_response(401, ["error" => "Invalid username or password"]);
}

deliver_response(200, [
  "message" => "Login successful",
  "user" => ["id" => (int)$user["id"], "username" => $user["username"]]
]);
