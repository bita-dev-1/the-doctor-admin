<?php
// api/middleware/public_availability.php

ob_start();

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

try {
    global $db;
    if (!$db) {
        if (isset($GLOBALS['db'])) {
            $db = $GLOBALS['db'];
        } else {
            if (!class_exists('DB')) {
                $configPath = dirname(__DIR__, 2) . '/config/DB.php';
                if (file_exists($configPath))
                    require_once $configPath;
            }
            $db = new DB();
        }
    }

    $doctor_id = 0;
    $date = "";

    if (isset($_GET['doctor_id']) && isset($_GET['date'])) {
        $doctor_id = intval($_GET['doctor_id']);
        $date = $_GET['date'];
    } else {
        $input = file_get_contents('php://input');
        $payload = json_decode($input);
        if (isset($payload->doctor_id) && isset($payload->date)) {
            $doctor_id = intval($payload->doctor_id);
            $date = $payload->date;
        }
    }

    if ($doctor_id <= 0 || empty($date)) {
        throw new Exception("Missing parameters (doctor_id or date)");
    }

    // Secure Query
    $query = "SELECT tickets_day, travel_hours FROM users WHERE id = ? AND role IN ('doctor', 'admin') AND deleted = 0";
    $doctorData = $db->select($query, [$doctor_id]);

    if (empty($doctorData)) {
        throw new Exception("Doctor not found");
    }

    $tickets_config = json_decode($doctorData[0]['tickets_day'] ?? '[]', true);
    $hours_config = json_decode($doctorData[0]['travel_hours'] ?? '[]', true);

    $timestamp = strtotime($date);
    $day_english = date('l', $timestamp);

    $days_map = [
        'Saturday' => 'Samedi',
        'Sunday' => 'Dimanche',
        'Monday' => 'Lundi',
        'Tuesday' => 'Mardi',
        'Wednesday' => 'Mercredi',
        'Thursday' => 'Jeudi',
        'Friday' => 'Vendredi'
    ];

    $day_french = $days_map[$day_english] ?? '';
    $response = [];

    if (empty($day_french) || empty($hours_config[$day_french]['from']) || empty($hours_config[$day_french]['to'])) {
        $response = ["state" => "true", "available" => false, "reason" => "Day off"];
    } else {
        $max_tickets = intval($tickets_config[$day_french] ?? 0);

        if ($max_tickets <= 0) {
            $response = ["state" => "true", "available" => false, "reason" => "No tickets configured"];
        } else {
            // Secure Query
            $sql_booked = "SELECT rdv_num FROM rdv WHERE doctor_id = ? AND date = ? AND state != 3 AND deleted = 0";
            $booked_result = $db->select($sql_booked, [$doctor_id, $date]);

            $booked_tickets = [];
            foreach ($booked_result as $row) {
                $booked_tickets[] = intval($row['rdv_num']);
            }

            $available_slots = [];
            for ($i = 1; $i <= $max_tickets; $i++) {
                if (!in_array($i, $booked_tickets)) {
                    $available_slots[] = $i;
                }
            }

            $response = [
                "state" => "true",
                "available" => true,
                "day" => $day_french,
                "max_tickets" => $max_tickets,
                "booked_count" => count($booked_tickets),
                "slots" => $available_slots
            ];
        }
    }

    ob_end_clean();
    echo json_encode($response);

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        "state" => "false",
        "message" => "Server Error: " . $e->getMessage()
    ]);
}
?>