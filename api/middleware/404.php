<?php
// api/middleware/404.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
http_response_code(404);

echo json_encode([
    "state" => "false",
    "message" => "API Route Not Found (404)",
    "debug_uri" => $_SERVER['REQUEST_URI'] ?? 'unknown'
]);
?>