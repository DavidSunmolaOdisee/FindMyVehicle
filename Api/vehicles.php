<?php
require __DIR__ . '/inc/base.php';
require __DIR__ . '/inc/dbcon.php';  // maakt $conn

handle_preflight();

switch ($_SERVER['REQUEST_METHOD']) {
  case 'GET':    require __DIR__ . '/inc/vehicles/get.php';    break;
  case 'POST':   require __DIR__ . '/inc/vehicles/add.php';    break;
  case 'PUT':    require __DIR__ . '/inc/vehicles/update.php'; break;
  case 'DELETE': require __DIR__ . '/inc/vehicles/delete.php'; break;
  default:       deliver_response(405, ['error' => 'Method not allowed']);
}
