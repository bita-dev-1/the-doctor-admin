<?php
// api/middleware/public_booking.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

try {
    $payload = json_decode(file_get_contents('php://input'));

    // 1. Validate Input
    if (!isset($payload->doctor_id) || !isset($payload->date) || !isset($payload->first_name) || !isset($payload->phone)) {
        echo json_encode(["state" => "false", "message" => "Missing required fields"]);
        exit();
    }

    $doctor_id = intval($payload->doctor_id);
    $date = $payload->date; // YYYY-MM-DD
    $ticket_num = isset($payload->ticket_number) ? intval($payload->ticket_number) : 0;

    // Sanitize Patient Info
    $first_name = filter_var($payload->first_name, FILTER_SANITIZE_STRING);
    $last_name = filter_var($payload->last_name ?? '', FILTER_SANITIZE_STRING);
    $phone = filter_var($payload->phone, FILTER_SANITIZE_STRING);

    // --- NEW FIELDS ---
    $email = filter_var($payload->email ?? '', FILTER_SANITIZE_EMAIL);
    $description = filter_var($payload->description ?? '', FILTER_SANITIZE_STRING);
    $motif_id = isset($payload->motif_id) ? intval($payload->motif_id) : null;
    $commune_id = isset($payload->commune_id) ? intval($payload->commune_id) : null;
    // ------------------

    // 2. Get Doctor's Cabinet ID
    $docQuery = "SELECT cabinet_id FROM users WHERE id = $doctor_id";
    $docData = $GLOBALS['db']->select($docQuery);
    $cabinet_id = $docData[0]['cabinet_id'] ?? 'NULL';

    // 3. Handle Patient (Find or Create)
    $checkPatient = "SELECT id FROM patient WHERE phone = '$phone' LIMIT 1";
    $existingPatient = $GLOBALS['db']->select($checkPatient);

    $patient_id = 0;

    if (!empty($existingPatient)) {
        $patient_id = $existingPatient[0]['id'];

        // Optional: Update patient email if missing
        if (!empty($email)) {
            $GLOBALS['db']->table = 'patient';
            $GLOBALS['db']->data = ['email' => $email];
            $GLOBALS['db']->where = "id = $patient_id AND (email IS NULL OR email = '')";
            $GLOBALS['db']->update();
        }

    } else {
        // Create new patient
        $GLOBALS['db']->table = 'patient';
        $GLOBALS['db']->data = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone' => $phone,
            'email' => $email, // Save email here too
            'commune_id' => $commune_id, // Save commune here too
            'cabinet_id' => ($cabinet_id === 'NULL' ? null : $cabinet_id),
            'created_by' => 0
        ];
        $patient_id = $GLOBALS['db']->insert();
    }

    if (!$patient_id) {
        echo json_encode(["state" => "false", "message" => "Failed to process patient data"]);
        exit();
    }

    // 4. Assign Ticket Number
    if ($ticket_num <= 0) {
        $sql_max = "SELECT MAX(rdv_num) as max_num FROM rdv WHERE doctor_id = $doctor_id AND date = '$date' AND state != 3";
        $res_max = $GLOBALS['db']->select($sql_max);
        $ticket_num = intval($res_max[0]['max_num'] ?? 0) + 1;
    } else {
        $check_ticket = "SELECT id FROM rdv WHERE doctor_id = $doctor_id AND date = '$date' AND rdv_num = $ticket_num AND state != 3";
        if ($GLOBALS['db']->rowsCount($check_ticket) > 0) {
            echo json_encode(["state" => "false", "message" => "This ticket is already booked"]);
            exit();
        }
    }

    // 5. Create Appointment (RDV) with NEW FIELDS
    $GLOBALS['db']->table = 'rdv';
    $GLOBALS['db']->data = [
        'doctor_id' => $doctor_id,
        'patient_id' => $patient_id,
        'cabinet_id' => ($cabinet_id === 'NULL' ? null : $cabinet_id),
        'date' => $date,
        'rdv_num' => $ticket_num,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'phone' => $phone,

        // New Data
        'email' => $email,
        'commune_id' => $commune_id,
        'motif_id' => ($motif_id > 0 ? $motif_id : null),
        'description' => $description,

        'state' => 0,
        'created_by' => 0
    ];

    $rdv_id = $GLOBALS['db']->insert();

    if ($rdv_id) {
        echo json_encode([
            "state" => "true",
            "message" => "Booking confirmed",
            "rdv_id" => $rdv_id,
            "ticket_number" => $ticket_num
        ]);
    } else {
        echo json_encode(["state" => "false", "message" => "Database error during booking"]);
    }

} catch (Exception $e) {
    echo json_encode(["state" => "false", "message" => "Server Error: " . $e->getMessage()]);
}
?>