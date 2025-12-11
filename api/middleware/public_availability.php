<?php
// api/middleware/public_availability.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

try {
    // 1. Receive Data
    $doctor_id = 0;
    $date = "";

    if (isset($_GET['doctor_id']) && isset($_GET['date'])) {
        $doctor_id = intval($_GET['doctor_id']);
        $date = $_GET['date'];
    } else {
        $payload = json_decode(file_get_contents('php://input'));
        if (isset($payload->doctor_id) && isset($payload->date)) {
            $doctor_id = intval($payload->doctor_id);
            $date = $payload->date;
        }
    }

    if ($doctor_id <= 0 || empty($date)) {
        echo json_encode(["state" => "false", "message" => "Missing parameters"]);
        exit();
    }

    // 2. Get Doctor Settings (Schedule & Tickets)
    $query = "SELECT tickets_day, travel_hours FROM users WHERE id = $doctor_id AND role = 'doctor' AND deleted = 0";
    $doctorData = $GLOBALS['db']->select($query);

    if (empty($doctorData)) {
        echo json_encode(["state" => "false", "message" => "Doctor not found"]);
        exit();
    }

    $tickets_config = json_decode($doctorData[0]['tickets_day'] ?? '[]', true);
    $hours_config = json_decode($doctorData[0]['travel_hours'] ?? '[]', true);

    // 3. Determine Day of Week (Map English to French keys in DB)
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

    // 4. Check if Doctor works on this day
    if (empty($day_french) || empty($hours_config[$day_french]['from']) || empty($hours_config[$day_french]['to'])) {
        echo json_encode(["state" => "true", "available" => false, "reason" => "Day off"]);
        exit();
    }

    // 5. Get Max Tickets for this day
    $max_tickets = intval($tickets_config[$day_french] ?? 0);

    if ($max_tickets <= 0) {
        echo json_encode(["state" => "true", "available" => false, "reason" => "No tickets configured"]);
        exit();
    }

    // 6. Get Already Booked Tickets
    // We exclude canceled appointments (state = 3)
    $sql_booked = "SELECT rdv_num FROM rdv WHERE doctor_id = $doctor_id AND date = '$date' AND state != 3 AND deleted = 0";
    $booked_result = $GLOBALS['db']->select($sql_booked);

    $booked_tickets = [];
    foreach ($booked_result as $row) {
        $booked_tickets[] = intval($row['rdv_num']);
    }

    // 7. Calculate Available Slots
    $available_slots = [];
    for ($i = 1; $i <= $max_tickets; $i++) {
        if (!in_array($i, $booked_tickets)) {
            $available_slots[] = $i;
        }
    }

    echo json_encode([
        "state" => "true",
        "available" => true,
        "day" => $day_french,
        "max_tickets" => $max_tickets,
        "booked_count" => count($booked_tickets),
        "slots" => $available_slots
    ]);

} catch (Exception $e) {
    echo json_encode(["state" => "false", "message" => "Server Error: " . $e->getMessage()]);
}
?>