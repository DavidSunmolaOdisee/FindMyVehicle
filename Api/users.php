<?php
// api/users.php
require __DIR__.'/inc/dbcon.php';
require __DIR__.'/inc/base.php';

// Preflight CORS (in base.php wordt op OPTIONS al correct geantwoord)
handle_preflight();

switch ($_SERVER["REQUEST_METHOD"]) {
  case "GET":
    require(__DIR__ . "/inc/users/get.php");
    break;
  case "POST":
    require(__DIR__ . "/inc/users/add.php");
    break;
  case "PUT":
    require(__DIR__ . "/inc/users/update.php");
    break;
  case "DELETE":
    require(__DIR__ . "/inc/users/delete.php");
    break;
  default:
    deliver_response(405, ["error" => "Method not allowed"]);
}
