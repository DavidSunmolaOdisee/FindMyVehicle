<?php
// api/parkings.php
require __DIR__.'/inc/dbcon.php';
require __DIR__.'/inc/base.php';


handle_preflight();

// Extra pad-detectie: /parkings/close  (POST)
$path = trim($_SERVER["PATH_INFO"] ?? "", "/");

if ($path === "close") {
  if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require(__DIR__ . "/inc/parkings/close.php");
  } else {
    deliver_response(405, ["error" => "Method not allowed for /parkings/close"]);
  }
  exit;
}

switch ($_SERVER["REQUEST_METHOD"]) {
  case "GET":
    require(__DIR__ . "/inc/parkings/get.php");
    break;
  case "POST":
    require(__DIR__ . "/inc/parkings/add.php");
    break;
  case "PUT":
    require(__DIR__ . "/inc/parkings/update.php");
    break;
  case "DELETE":
    require(__DIR__ . "/inc/parkings/delete.php");
    break;
  default:
    deliver_response(405, ["error" => "Method not allowed"]);
}
?>