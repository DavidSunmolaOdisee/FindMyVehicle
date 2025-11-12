<?php
// Query:  ?id=...  (user id)
// Body: { old_password, new_password }
$id = $_GET["id"] ?? null;
if (!$id) {
  deliver_response(400, ["error" => "User id required"]);
}

$body = getJsonBody();
check_required_fields($body, ["old_password", "new_password"]);

$old = $body["old_password"];
$new = $body["new_password"];

/* Haal huidige hash op */
$sql = "SELECT password_hash FROM app_users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

if (!$row) {
  deliver_response(404, ["error" => "User not found"]);
}

if (!password_verify($old, $row["password_hash"])) {
  deliver_response(401, ["error" => "Old password is incorrect"]);
}

/* Update met nieuwe hash */
$newhash = password_hash($new, PASSWORD_DEFAULT);
$up = $conn->prepare("UPDATE app_users SET password_hash = ? WHERE id = ?");
$up->bind_param("si", $newhash, $id);

if ($up->execute()) {
  deliver_response(200, ["message" => "Password changed"]);
} else {
  deliver_response(500, ["error" => $up->error]);
}
