<?php
require __DIR__.'/inc/base.php';
require __DIR__.'/inc/dbcon.php';
handle_preflight();

$path = $_GET['path'] ?? trim($_SERVER['PATH_INFO'] ?? '', '/');

if ($path === 'close') {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/inc/parkings/close.php';
  } else {
    deliver_response(405, ['error' => 'Method not allowed for /parkings/close']);
  }
  exit; // <-- essentieel, anders valt hij alsnog in de switch
}

switch ($_SERVER['REQUEST_METHOD']) {
  case 'GET':    require __DIR__.'/inc/parkings/get.php';    break;
  case 'POST':   require __DIR__.'/inc/parkings/add.php';    break;   // nieuwe parking
  case 'PUT':    require __DIR__.'/inc/parkings/update.php'; break;
  case 'DELETE': require __DIR__.'/inc/parkings/delete.php'; break;
  default:       deliver_response(405, ['error'=>'Method not allowed']);
}
