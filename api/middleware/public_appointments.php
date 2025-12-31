<?php
// api/middleware/public_appointments.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

try {
    $payload = json_decode(file_get_contents('php://input'));

    if (!isset($payload->ids) || !is_array($payload->ids) || empty($payload->ids)) {
        echo json_encode(["state" => "false", "message" => "No IDs provided"]);
        exit();
    }

    // Sanitize IDs
    $ids = array_map('intval', $payload->ids);

    // Create placeholders (?, ?, ?)
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $query = "SELECT 
                r.id, 
                r.date, 
                r.rdv_num, 
                r.state, 
                u.last_name as doctor_name,
                u.first_name as doctor_firstname
              FROM rdv r
              JOIN users u ON r.doctor_id = u.id
              WHERE r.id IN ($placeholders)";

    $results = $GLOBALS['db']->select($query, $ids);

    echo json_encode([
        "state" => "true",
        "data" => $results
    ]);

} catch (Exception $e) {
    echo json_encode(["state" => "false", "message" => "Server Error"]);
}
?>