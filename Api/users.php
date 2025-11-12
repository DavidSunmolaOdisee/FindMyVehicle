<?php
require __DIR__ . '/inc/base.php';
require __DIR__ . '/inc/dbcon.php';
handle_preflight();

/* Extra paden */
$path = trim($_SERVER["PATH_INFO"] ?? "", "/");

if ($path === "login") {
  if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require __DIR__ . "/inc/users/login.php";
  } else {
    deliver_response(405, ["error" => "Method not allowed for /users/login"]);
  }
  exit;
}

if ($path === "change_password") {
  if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require __DIR__ . "/inc/users/change_password.php";
  } else {
    deliver_response(405, ["error" => "Method not allowed for /users/change_password"]);
  }
  exit;
}

/* Standaard CRUD */
switch ($_SERVER['REQUEST_METHOD']) {
  case 'GET':    require __DIR__ . '/inc/users/get.php';    break;
  case 'POST':   require __DIR__ . '/inc/users/add.php';    break;   // registratie
  case 'PUT':    require __DIR__ . '/inc/users/update.php'; break;   // username/email
  case 'DELETE': require __DIR__ . '/inc/users/delete.php'; break;
  default:       deliver_response(405, ['error' => 'Method not allowed']);
}
