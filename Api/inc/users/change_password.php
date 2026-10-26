<?php
$id = $_GET["id"] ?? null;
if (!$id) {
  deliver_response(400, ["error" => "User id required"]);
}

$body = getJsonBody();
check_required_fields($body, ["old_password", "new_password"]);

$old = $body["old_password"];
$new = $body["new_password"];

$sel = $conn->prepare("SELECT password FROM app_users WHERE id=?");
$sel->bind_param("i", $id);
$sel->execute();
$res = $sel->get_result();
$row = $res->fetch_assoc();

if (!$row) {
  deliver_response(404, ["error" => "User not found"]);
}

$storedPassword = $row["password"];
$info = password_get_info($storedPassword);
$isHashed = ($info["algo"] ?? 0) !== 0;

$validOldPassword = $isHashed
  ? password_verify($old, $storedPassword)
  : hash_equals((string)$storedPassword, (string)$old);

if (!$validOldPassword) {
  deliver_response(401, ["error" => "Old password is incorrect"]);
}

$newHash = password_hash($new, PASSWORD_DEFAULT);
$up = $conn->prepare("UPDATE app_users SET password=? WHERE id=?");
$up->bind_param("si", $newHash, $id);

if ($up->execute()) {
  deliver_response(200, ["message" => "Password changed"]);
} else {
  deliver_response(500, ["error" => $up->error]);
}
