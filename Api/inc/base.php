<?php
// inc/base.php

header("Content-Type: application/json; charset=UTF-8");

/**
 * 1) Handle CORS preflight
 */
function handle_preflight() {
    if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        http_response_code(204);
        exit;
    }

    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
}

/**
 * 2) JSON response helper
 */
function deliver_response(int $status, $data) {
  http_response_code($status);
  header("Content-Type: application/json; charset=UTF-8");
  echo json_encode($data, JSON_PRETTY_PRINT);
  exit;
}
function deliverJSON(int $status, $data) { deliver_response($status, $data); }


/**
 * 3) Parse JSON body
 */
function getJsonBody() {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        deliver_response(400, ["error" => "Invalid or missing JSON body"]);
    }
    return $data;
}

/**
 * 4) Check verplichte velden
 */
function check_required_fields(array $body, array $fields) {
    $missing = [];
    foreach ($fields as $f) {
        if (!isset($body[$f]) || $body[$f] === "") {
            $missing[] = $f;
        }
    }
    if (!empty($missing)) {
        deliver_response(400, ["error" => "Missing required fields", "fields" => $missing]);
    }
}

/**
 * 5) Zet mysqli-result om naar nette JSON
 *    (houdt numerieke waarden numeriek)
 */
function getJsonObjFromResult($result) {
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        foreach ($row as $key => $val) {
            if (is_numeric($val) && !is_string($val)) {
                $row[$key] = (float)$val;
            }
        }
        $rows[] = $row;
    }
    return $rows;
}
