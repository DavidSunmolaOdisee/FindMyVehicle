<?php
$id = $_GET["id"] ?? null;

if ($id) {
  $sql  = "SELECT id, username, email, created_at FROM app_users WHERE id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id);
} else {
  $sql  = "SELECT id, username, email, created_at FROM app_users ORDER BY created_at DESC";
  $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();
$data   = getJsonObjFromResult($result);
deliver_response(200, $data);
