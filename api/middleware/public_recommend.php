<?php
// api/middleware/public_recommend.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

try {
    $payload = json_decode(file_get_contents('php://input'));

    if (!isset($payload->doctor_id)) {
        echo json_encode(["state" => "false", "message" => "ID missing"]);
        exit();
    }

    $doctor_id = intval($payload->doctor_id);
    $sessionKey = 'has_recommended_' . $doctor_id;

    if (isset($_SESSION[$sessionKey])) {
        echo json_encode(["state" => "false", "message" => "Déjà recommandé"]);
        exit();
    }

    // Secure Query
    $currentQuery = "SELECT recomondation FROM users WHERE id = ?";
    $currentData = $GLOBALS['db']->select($currentQuery, [$doctor_id]);

    $currentVal = 0;
    if (!empty($currentData) && isset($currentData[0]['recomondation'])) {
        $currentVal = intval($currentData[0]['recomondation']);
    }

    $newCount = $currentVal + 1;

    $GLOBALS['db']->table = 'users';
    $GLOBALS['db']->data = array('recomondation' => $newCount);
    $GLOBALS['db']->where = "id = " . $doctor_id; // ID is intval safe
    $GLOBALS['db']->update();

    $_SESSION[$sessionKey] = true;

    echo json_encode([
        "state" => "true",
        "new_count" => $newCount
    ]);

} catch (Exception $e) {
    error_log("Recommendation Error: " . $e->getMessage());
    echo json_encode(["state" => "false", "message" => "Server Error"]);
}
?>