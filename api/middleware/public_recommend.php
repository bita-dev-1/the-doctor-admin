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

    // مفتاح الجلسة لمنع التكرار
    $sessionKey = 'has_recommended_' . $doctor_id;

    if (isset($_SESSION[$sessionKey])) {
        echo json_encode(["state" => "false", "message" => "Déjà recommandé"]);
        exit();
    }

    // --- التصحيح: استخدام دوال الكلاس DB بدلاً من الوصول المباشر لـ PDO ---

    // 1. جلب القيمة الحالية
    $currentQuery = "SELECT recomondation FROM users WHERE id = $doctor_id";
    $currentData = $GLOBALS['db']->select($currentQuery);

    $currentVal = 0;
    if (!empty($currentData) && isset($currentData[0]['recomondation'])) {
        $currentVal = intval($currentData[0]['recomondation']);
    }

    // 2. حساب القيمة الجديدة
    $newCount = $currentVal + 1;

    // 3. تحديث القيمة باستخدام دالة التحديث الخاصة بالكلاس
    $GLOBALS['db']->table = 'users';
    $GLOBALS['db']->data = array('recomondation' => $newCount);
    $GLOBALS['db']->where = "id = $doctor_id";
    $GLOBALS['db']->update();

    // تسجيل الجلسة
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