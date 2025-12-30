<?php


function postRdv()
{
    $patient_id = filter_var(($_POST['patient'] ?? null), FILTER_SANITIZE_NUMBER_INT);
    $first_name = filter_var(($_POST['first_name'] ?? ""), FILTER_SANITIZE_STRING);
    $last_name = filter_var(($_POST['last_name'] ?? ""), FILTER_SANITIZE_STRING);
    $phone = filter_var(($_POST['phone'] ?? ""), FILTER_SANITIZE_STRING);
    // New Field
    $motif_id = filter_var(($_POST['motif_id'] ?? null), FILTER_SANITIZE_NUMBER_INT);

    if (empty($patient_id)) {
        if (!empty($first_name) && !empty($last_name)) {
            $patient_data = [
                "first_name" => $first_name,
                "last_name" => $last_name,
                "phone" => $phone,
                "created_by" => $_SESSION['user']['id'],
                "cabinet_id" => $_SESSION['user']['cabinet_id'] ?? null
            ];
            $GLOBALS['db']->table = 'patient';
            $GLOBALS['db']->data = $patient_data;
            $patient_id = $GLOBALS['db']->insert();

            if (!$patient_id) {
                echo json_encode(["state" => "false", "message" => "Erreur lors de la création du nouveau patient."]);
                return;
            }
        } else {
            echo json_encode(["state" => "false", "message" => "Les informations du patient sont requises."]);
            return;
        }
    }

    $data = [
        "doctor_id" => filter_var(($_POST['doctor'] ?? 0), FILTER_SANITIZE_NUMBER_INT),
        "patient_id" => $patient_id,
        "motif_id" => !empty($motif_id) ? $motif_id : null, // Save Motif
        "date" => filter_var(($_POST['date'] ?? date("Y-m-d")), FILTER_SANITIZE_STRING),
        "first_name" => $first_name,
        "last_name" => $last_name,
        "phone" => $phone,
        "rdv_num" => filter_var(($_POST['rdv_num'] ?? 0), FILTER_SANITIZE_NUMBER_INT),
        "state" => 1,
        "created_by" => $_SESSION['user']['id'],
        "cabinet_id" => $_SESSION['user']['cabinet_id'] ?? null
    ];

    $GLOBALS['db']->table = 'rdv';
    $GLOBALS['db']->data = $data;
    $res = $GLOBALS['db']->insert();

    if ($res)
        echo json_encode(["state" => "true", "message" => $GLOBALS['language']['Added successfully']]);
    else
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['something went wrong reload page and try again']]);
}

function get_RDV($id = NULL, $return = false)
{
    $user_role = $_SESSION['user']['role'] ?? null;
    $user_cabinet_id = $_SESSION['user']['cabinet_id'] ?? null;
    $user_id = $_SESSION['user']['id'] ?? 0;

    $id_filter = ($id != NULL ? " AND rdv.id = " . intval($id) : "");

    $where_clause = "";
    if ($user_role === 'admin' && !empty($user_cabinet_id)) {
        $where_clause = " AND (rdv.cabinet_id = " . intval($user_cabinet_id) .
            " OR rdv.doctor_id IN (SELECT id FROM users WHERE cabinet_id = " . intval($user_cabinet_id) . "))";

    } elseif ($user_role === 'doctor' || $user_role === 'nurse') {
        $where_clause = " AND rdv.doctor_id = " . intval($user_id);
    }

    $filters = (isset($_POST['filters']) && !empty($_POST['filters']) ? " AND rdv.state IN (" . implode(', ', array_map('intval', $_POST['filters'])) . ")" : " AND rdv.state >= -1");

    // Updated Query to include Motif Title and ID
    $sql = "SELECT rdv.id, rdv.patient_id, rdv.date as Date_RDV, rdv.state, rdv.rdv_num, rdv.phone, rdv.motif_id,
            COALESCE(CONCAT_WS(' ', patient.first_name, patient.last_name), CONCAT_WS(' ', rdv.first_name, rdv.last_name)) AS patient_name,
            rs.payment_status,
            dm.title as motif_title
            FROM rdv 
            LEFT JOIN patient ON patient.id = rdv.patient_id
            LEFT JOIN reeducation_sessions rs ON rdv.reeducation_session_id = rs.id
            LEFT JOIN doctor_motifs dm ON rdv.motif_id = dm.id
            WHERE rdv.deleted = 0 $where_clause $id_filter $filters";

    $res = $GLOBALS['db']->select($sql);

    $convertedData = [];
    if (!empty($res)) {
        foreach ($res as $items) {
            $title = $items['patient_name'];

            // Append Motif to title for Calendar Visibility
            if (!empty($items['motif_title'])) {
                $title .= ' - ' . $items['motif_title'];
            }

            if ($items['payment_status'] === 'paid') {
                $title .= ' (Payé ✔️)';
            } elseif ($items['payment_status'] === 'unpaid') {
                $title .= ' (Impayé ❌)';
            }

            $arrayData = [
                'id' => $items['id'],
                'title' => $title,
                'allDay' => true,
                'start' => $items['Date_RDV'],
                'end' => $items['Date_RDV'],
                'extendedProps' => [
                    'calendar' => match ((int) $items['state']) {
                        0 => 'warning', 1 => 'info', 2 => 'success', 3 => 'danger',
                        default => 'secondary'
                    },
                    'state_id' => (int) $items['state'],
                    'phone' => ($items['phone'] ?? ''),
                    'num_rdv' => ($items['rdv_num'] ?? ''),
                    'motif_id' => ($items['motif_id'] ?? ''), // Pass Motif ID for editing
                    'Client' => ["id" => $items['patient_id'], "name" => $items['patient_name']]
                ]
            ];
            $convertedData[] = $arrayData;
        }
    }

    if (empty($convertedData)) {
        $arrayData = [
            'id' => '0',
            'title' => 'start calendar',
            'allDay' => false,
            'start' => '1970-01-01',
            'end' => '1970-01-01',
            'extendedProps' => ['calendar' => 'secondary', 'state_id' => 0, 'Client_id' => 0]
        ];
        $convertedData[] = $arrayData;
    }

    if ($return) {
        return $convertedData;
    }

    echo json_encode($convertedData);
}


function updateEvent($DB)
{
    if (isset($_POST['id']) && !empty($_POST['id'])) {

        $table = 'rdv';
        $unique_val = $_POST['id'];
        $csrf = null;
        $new_state = null; // متغير لتتبع الحالة الجديدة

        $array_data = array();

        if (isset($_POST['data']) && is_array($_POST['data'])) {
            foreach ($_POST['data'] as $data) {
                if (!isset($data['name']) || !isset($data['value']))
                    continue;

                if (strpos($data['name'], '__') !== false) {
                    $parts = explode('__', $data['name']);
                    $table_key = $parts[0];
                    $column = $parts[1];

                    if ($table_key === $table) {
                        // التقاط الحالة الجديدة للتحقق منها لاحقاً
                        if ($column === 'state') {
                            $new_state = $data['value'];
                        }

                        // Handle empty motif as NULL
                        if ($column === 'motif_id' && empty($data['value'])) {
                            $array_data[$column] = null;
                        } else {
                            $array_data[$column] = $data['value'];
                        }
                    }
                } else if (stripos($data['name'], 'csrf') !== false) {
                    $csrf = $data['value'];
                }
            }
        }

        if (isset($csrf)) {
            $decrypted_csrf = customDecrypt($csrf);
            if (!is_csrf_valid($decrypted_csrf)) {
                echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
                exit();
            }
        } else {
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
            exit();
        }

        $array_data["modified_at"] = date('Y-m-d H:i:s');
        $array_data["modified_by"] = $_SESSION['user']['id'] ?? 0;

        $DB->table = $table;
        $DB->data = $array_data;
        $DB->where = 'id = ' . $unique_val;

        try {
            $updated = $DB->update();

            if ($updated) {
                if (function_exists('push_notificationRDV')) {
                    push_notificationRDV($unique_val);
                }

                // --- START: Email Logic for updateEvent ---
                // التحقق مما إذا كانت الحالة الجديدة هي 1 (مقبول)
                if ($new_state == 1) {
                    if (function_exists('writeToLog')) {
                        writeToLog("[updateEvent] State changed to 1 for RDV ID: $unique_val. Preparing email...");
                    }

                    $sql = "SELECT r.date, r.rdv_num, r.hours,
                                   p.email, p.first_name, p.last_name,
                                   u.first_name as doc_fname, u.last_name as doc_lname
                            FROM rdv r
                            JOIN patient p ON r.patient_id = p.id
                            JOIN users u ON r.doctor_id = u.id
                            WHERE r.id = $unique_val";

                    $rdvData = $GLOBALS['db']->select($sql);

                    if (!empty($rdvData) && !empty($rdvData[0]['email'])) {
                        $info = $rdvData[0];
                        $patientName = $info['first_name'] . ' ' . $info['last_name'];
                        $doctorName = $info['doc_fname'] . ' ' . $info['doc_lname'];
                        $rdvDate = date('d/m/Y', strtotime($info['date']));

                        $subject = "Confirmation de votre rendez-vous - The Doctor";
                        $body = "
                            <div style='font-family: Arial, sans-serif; color: #333;'>
                                <h3>Bonjour {$patientName},</h3>
                                <p>Nous avons le plaisir de vous informer que votre demande de rendez-vous a été <strong style='color: #28c76f;'>acceptée</strong>.</p>
                                <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px solid #e9ecef;'>
                                    <p style='margin: 5px 0;'><strong>Médecin :</strong> Dr. {$doctorName}</p>
                                    <p style='margin: 5px 0;'><strong>Date :</strong> {$rdvDate}</p>
                                    <p style='margin: 5px 0;'><strong>Numéro de ticket :</strong> <span style='font-size: 1.2em; font-weight: bold; color: #0071bc;'>{$info['rdv_num']}</span></p>
                                </div>
                                <p>Merci de votre confiance.</p>
                                <small style='color: #999;'>Ceci est un message automatique, merci de ne pas répondre.</small>
                            </div>
                        ";

                        $result = sendEmail($info['email'], $patientName, $subject, $body);

                        if (function_exists('writeToLog')) {
                            writeToLog("[updateEvent] Email result: " . ($result === true ? "SUCCESS" : "FAILED - $result"));
                        }
                    } else {
                        if (function_exists('writeToLog')) {
                            writeToLog("[updateEvent] Skipped email: Patient email not found or empty.");
                        }
                    }
                }
                // --- END: Email Logic ---

                echo json_encode(["state" => "true", "message" => $GLOBALS['language']['Edited successfully']]);
            } else {
                echo json_encode(["state" => "false", "message" => "Erreur lors de la mise à jour BDD"]);
            }
        } catch (Exception $e) {
            if (function_exists('writeToLog'))
                writeToLog("[updateEvent] Exception: " . $e->getMessage());
            echo json_encode(["state" => "false", "message" => "Exception: " . $e->getMessage()]);
        }

    } else {
        echo json_encode(["state" => "false", "message" => "missing id"]);
    }
    $DB = null;
}



function handleRdv_nbr()
{
    try {
        $response = [];
        if (isset($_POST['doctor']) && !empty($_POST['doctor'])) {
            $doctor_id = filter_var(($_POST['doctor']), FILTER_SANITIZE_NUMBER_INT);
            $dateString = filter_var(($_POST['date'] ?? date('Y-m-d')), FILTER_SANITIZE_STRING);

            // --- FIX: Use static array mapping for days ---
            $date = new DateTime($dateString);
            $dayIndex = $date->format('w'); // 0 (Sunday) to 6 (Saturday)

            // أسماء الأيام كما هي مخزنة في قاعدة البيانات (JSON)
            $daysMap = [
                0 => "Dimanche",
                1 => "Lundi",
                2 => "Mardi",
                3 => "Mercredi",
                4 => "Jeudi",
                5 => "Vendredi",
                6 => "Samedi"
            ];
            $dayName = $daysMap[$dayIndex];
            // ----------------------------------------------

            $doctor_info_sql = "SELECT tickets_day FROM users WHERE id = ?";
            $stmt = $GLOBALS['db']->prepare($doctor_info_sql);
            $stmt->execute([$doctor_id]);
            $doctor_response = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($doctor_response) {
                $tickets_day_json = $doctor_response['tickets_day'] ?? '[]';
                $tickets_day_array = json_decode($tickets_day_json, true);

                // جلب عدد التذاكر لليوم المحدد
                $nbrTickets = isset($tickets_day_array[$dayName]) ? intval($tickets_day_array[$dayName]) : 0;
                $restTickets = [];

                if ($nbrTickets > 0) {
                    $all_possible_tickets = range(1, $nbrTickets);
                    // استثناء التذاكر المحجوزة (ما عدا الملغاة state=3)
                    $reserved_sql = "SELECT rdv_num FROM `rdv` WHERE doctor_id = ? AND state != 3 AND date = ?";
                    $stmt_reserved = $GLOBALS['db']->prepare($reserved_sql);
                    $stmt_reserved->execute([$doctor_id, $dateString]);
                    $reservedTickets = $stmt_reserved->fetchAll(PDO::FETCH_COLUMN);

                    $restTickets = array_diff($all_possible_tickets, $reservedTickets);
                }

                foreach ($restTickets as $ticket_num) {
                    $response[] = array(
                        "id" => $ticket_num,
                        "text" => $ticket_num
                    );
                }
            }
        }
        echo json_encode($response);
    } catch (Throwable $th) {
        echo json_encode([]);
    }
}



function updateState()
{
    if (isset($_SESSION['user']['id']) && !empty($_SESSION['user']['id'])) {
        $id = abs(filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT));
        $state = abs(filter_var($_POST['state'], FILTER_SANITIZE_NUMBER_INT));
        $datetime = date('Y-m-d H:i:s');

        // Log Start
        if (function_exists('writeToLog')) {
            writeToLog("--- UpdateState Called for RDV ID: $id with State: $state ---");
        }

        $GLOBALS['db']->table = 'rdv';
        $GLOBALS['db']->data = array("state" => "$state", "modified_at" => "$datetime", "modified_by" => $_SESSION['user']['id']);
        $GLOBALS['db']->where = "id = $id";
        $updated = $GLOBALS['db']->update();

        if ($updated) {
            // --- START: Send Email Notification on Acceptance ---
            if ($state == 1) {
                writeToLog("State is 1 (Accepted). Fetching RDV details...");

                // Fetch appointment, patient, and doctor details
                $sql = "SELECT r.date, r.rdv_num, r.hours,
                               p.email, p.first_name, p.last_name,
                               u.first_name as doc_fname, u.last_name as doc_lname
                        FROM rdv r
                        JOIN patient p ON r.patient_id = p.id
                        JOIN users u ON r.doctor_id = u.id
                        WHERE r.id = $id";

                $rdvData = $GLOBALS['db']->select($sql);

                if (!empty($rdvData)) {
                    $info = $rdvData[0];
                    $email = $info['email'];

                    writeToLog("Data fetched. Patient Email: " . ($email ? $email : "EMPTY"));

                    if (!empty($email)) {
                        $patientName = $info['first_name'] . ' ' . $info['last_name'];
                        $doctorName = $info['doc_fname'] . ' ' . $info['doc_lname'];
                        $rdvDate = date('d/m/Y', strtotime($info['date']));

                        $subject = "Confirmation de votre rendez-vous - The Doctor";
                        $body = "
                            <div style='font-family: Arial, sans-serif; color: #333;'>
                                <h3>Bonjour {$patientName},</h3>
                                <p>Nous avons le plaisir de vous informer que votre demande de rendez-vous a été <strong style='color: #28c76f;'>acceptée</strong>.</p>
                                <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px solid #e9ecef;'>
                                    <p style='margin: 5px 0;'><strong>Médecin :</strong> Dr. {$doctorName}</p>
                                    <p style='margin: 5px 0;'><strong>Date :</strong> {$rdvDate}</p>
                                    <p style='margin: 5px 0;'><strong>Numéro de ticket :</strong> <span style='font-size: 1.2em; font-weight: bold; color: #0071bc;'>{$info['rdv_num']}</span></p>
                                </div>
                                <p>Merci de votre confiance.</p>
                                <small style='color: #999;'>Ceci est un message automatique, merci de ne pas répondre.</small>
                            </div>
                        ";

                        $result = sendEmail($email, $patientName, $subject, $body);
                        writeToLog("SendEmail Result: " . ($result === true ? "SUCCESS" : "FAILED - " . $result));
                    } else {
                        writeToLog("Skipping email: Patient email is empty.");
                    }
                } else {
                    writeToLog("Error: Could not fetch RDV data for ID: $id");
                }
            }
            // --- END: Send Email Notification ---

            echo json_encode(["state" => $updated, "message" => $GLOBALS['language']['Edited successfully']]);
        } else {
            if (function_exists('writeToLog'))
                writeToLog("Database Update Failed for ID: $id");
            echo json_encode(["state" => "false", "message" => $updated]);
        }
    } else {
        echo json_encode(["state" => "false", "message" => "missing id"]);
    }
}
function moveEvent($DB)
{
    if (isset($_POST['id']) && !empty($_POST['id']) && isset($_POST['date']) && !empty($_POST['date'])) {
        $table = 'rdv';
        $data = array("date" => $_POST['date'], "modified_at" => date('Y-m-d H:i:s'), "modified_by" => $_SESSION['user']['id']);
        $DB->table = $table;
        $DB->data = $data;
        $DB->where = 'id = ' . $_POST['id'];
        $updated = true && $DB->update();
        if ($updated)
            echo json_encode(["state" => "true"]);
        else
            echo json_encode(["state" => "false"]);
    } else {
        echo json_encode(["state" => "false", "message" => "missing data"]);
    }
    $DB = null;
}

function removeEvent($DB)
{
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $table = 'rdv';
        $data = array("deleted" => 1, "modified_at" => date('Y-m-d H:i:s'), "modified_by" => $_SESSION['user']['id']);
        $DB->table = $table;
        $DB->data = $data;
        $DB->where = 'id = ' . $_POST['id'];
        $updated = true && $DB->update();
        if ($updated)
            echo json_encode(["state" => "true", "message" => $GLOBALS['language']['Successfully Deleted']]);
        else
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']['something went wrong reload page and try again']]);
    } else {
        echo json_encode(["state" => "false", "message" => "missing id"]);
    }
    $DB = null;
}



function postEvent($DB)
{
    echo json_encode(["state" => "false", "message" => "Not implemented yet for RDV"]);
}

function get_daily_calendar_stats($DB)
{
    $start_date = $_POST['start'];
    $end_date = $_POST['end'];
    $doctor_id = $_POST['doctor_id'] ?? '';

    $target_doctor_id = 0;
    if ($_SESSION['user']['role'] === 'doctor' || $_SESSION['user']['role'] === 'nurse') {
        $target_doctor_id = $_SESSION['user']['id'];
    } elseif (!empty($doctor_id)) {
        $target_doctor_id = intval($doctor_id);
    }

    $doctor_settings = [];
    if ($target_doctor_id) {
        $doc = $DB->select("SELECT tickets_day, travel_hours FROM users WHERE id = $target_doctor_id")[0] ?? null;
        if ($doc) {
            $doctor_settings['tickets'] = json_decode($doc['tickets_day'] ?? '[]', true);
            $doctor_settings['hours'] = json_decode($doc['travel_hours'] ?? '[]', true);
        }
    }

    $where_clause = "rdv.deleted = 0 AND rdv.date BETWEEN '$start_date' AND '$end_date'";
    if ($target_doctor_id) {
        $where_clause .= " AND rdv.doctor_id = $target_doctor_id";
    } elseif (!empty($_SESSION['user']['cabinet_id'])) {
        $where_clause .= " AND rdv.cabinet_id = " . intval($_SESSION['user']['cabinet_id']);
    }

    $sql = "SELECT DATE(date) as day_date, state, COUNT(*) as count 
            FROM rdv 
            WHERE $where_clause 
            GROUP BY DATE(date), state";

    $results = $DB->select($sql);

    $stats = [];
    foreach ($results as $row) {
        $date = $row['day_date'];
        $state = intval($row['state']);
        $count = intval($row['count']);

        if (!isset($stats[$date])) {
            $stats[$date] = [
                'total' => 0,
                'details' => [0 => 0, 1 => 0, 2 => 0, 3 => 0]
            ];
        }

        if ($state != 3) {
            $stats[$date]['total'] += $count;
        }
        $stats[$date]['details'][$state] = $count;
    }

    echo json_encode([
        'bookings' => $stats,
        'settings' => $doctor_settings
    ]);
}

function get_calendar_stats($DB)
{
    $start_date = $_POST['start'] ?? date('Y-m-01');
    $end_date = $_POST['end'] ?? date('Y-m-t');
    $doctor_id = $_POST['doctor_id'] ?? '';

    $where_clause = "rdv.deleted = 0 AND rdv.date BETWEEN '$start_date' AND '$end_date'";

    if ($_SESSION['user']['role'] === 'doctor' || $_SESSION['user']['role'] === 'nurse') {
        $where_clause .= " AND rdv.doctor_id = " . $_SESSION['user']['id'];
    } elseif (!empty($doctor_id)) {
        $where_clause .= " AND rdv.doctor_id = " . intval($doctor_id);
    } elseif (!empty($_SESSION['user']['cabinet_id'])) {
        $where_clause .= " AND rdv.cabinet_id = " . intval($_SESSION['user']['cabinet_id']);
    }

    $sql = "SELECT state, COUNT(*) as count FROM rdv WHERE $where_clause GROUP BY state";
    $results = $DB->select($sql);

    $stats = [
        'total' => 0,
        'created' => 0,
        'confirmed' => 0,
        'completed' => 0,
        'canceled' => 0
    ];

    foreach ($results as $row) {
        $count = intval($row['count']);
        $stats['total'] += $count;

        switch ($row['state']) {
            case 0:
                $stats['created'] = $count;
                break;
            case 1:
                $stats['confirmed'] = $count;
                break;
            case 2:
                $stats['completed'] = $count;
                break;
            case 3:
                $stats['canceled'] = $count;
                break;
        }
    }

    echo json_encode($stats);
}
?>