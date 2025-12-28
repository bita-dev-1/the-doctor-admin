<?php
// api/middleware/public_motifs.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

try {
    // 1. Get Doctor ID
    $doctor_id = 0;
    if (isset($_GET['doctor_id'])) {
        $doctor_id = intval($_GET['doctor_id']);
    }

    if ($doctor_id <= 0) {
        echo json_encode(["state" => "false", "message" => "Doctor ID missing"]);
        exit();
    }

    // 2. Fetch Motifs
    // Ensure we only get active motifs (deleted = 0)
    $query = "SELECT id, title, duration, price FROM doctor_motifs WHERE doctor_id = $doctor_id AND deleted = 0 ORDER BY id DESC";

    $motifs = $GLOBALS['db']->select($query);

    echo json_encode([
        "state" => "true",
        "data" => $motifs
    ]);

} catch (Exception $e) {
    echo json_encode(["state" => "false", "message" => "Server Error"]);
}
?>