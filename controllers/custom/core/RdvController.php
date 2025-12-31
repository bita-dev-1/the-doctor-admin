<?php

function postRdv()
{
    $patient_id = filter_var(($_POST['patient'] ?? null), FILTER_SANITIZE_NUMBER_INT);
    $first_name = filter_var(($_POST['first_name'] ?? ""), FILTER_SANITIZE_STRING);
    $last_name = filter_var(($_POST['last_name'] ?? ""), FILTER_SANITIZE_STRING);
    $phone = filter_var(($_POST['phone'] ?? ""), FILTER_SANITIZE_STRING);
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
        "motif_id" => !empty($motif_id) ? $motif_id : null,
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

    $params = [];
    $where_clause = "";

    // 1. Authorization Filter
    if ($user_role === 'admin' && !empty($user_cabinet_id)) {
        $where_clause = " AND (rdv.cabinet_id = ? OR rdv.doctor_id IN (SELECT id FROM users WHERE cabinet_id = ?))";
        $params[] = $user_cabinet_id;
        $params[] = $user_cabinet_id;
    } elseif ($user_role === 'doctor' || $user_role === 'nurse') {
        $where_clause = " AND rdv.doctor_id = ?";
        $params[] = $user_id;
    }

    // 2. ID Filter
    if ($id != NULL) {
        $where_clause .= " AND rdv.id = ?";
        $params[] = intval($id);
    }

    // 3. State Filter
    if (isset($_POST['filters']) && !empty($_POST['filters'])) {
        $filters = array_map('intval', $_POST['filters']);
        $placeholders = implode(',', array_fill(0, count($filters), '?'));
        $where_clause .= " AND rdv.state IN ($placeholders)";
        $params = array_merge($params, $filters);
    } else {
        $where_clause .= " AND rdv.state >= -1";
    }

    $sql = "SELECT rdv.id, rdv.patient_id, rdv.date as Date_RDV, rdv.state, rdv.rdv_num, rdv.phone, rdv.motif_id,
            COALESCE(CONCAT_WS(' ', patient.first_name, patient.last_name), CONCAT_WS(' ', rdv.first_name, rdv.last_name)) AS patient_name,
            rs.payment_status,
            dm.title as motif_title
            FROM rdv 
            LEFT JOIN patient ON patient.id = rdv.patient_id
            LEFT JOIN reeducation_sessions rs ON rdv.reeducation_session_id = rs.id
            LEFT JOIN doctor_motifs dm ON rdv.motif_id = dm.id
            WHERE rdv.deleted = 0 $where_clause";

    $res = $GLOBALS['db']->select($sql, $params);

    $convertedData = [];
    if (!empty($res)) {
        foreach ($res as $items) {
            $title = $items['patient_name'];
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
                'title' => htmlspecialchars($title),
                'allDay' => true,
                'start' => $items['Date_RDV'],
                'end' => $items['Date_RDV'],
                'extendedProps' => [
                    'calendar' => match ((int) $items['state']) {
                        0 => 'warning', 1 => 'info', 2 => 'success', 3 => 'danger',
                        default => 'secondary'
                    },
                    'state_id' => (int) $items['state'],
                    'phone' => htmlspecialchars($items['phone'] ?? ''),
                    'num_rdv' => ($items['rdv_num'] ?? ''),
                    'motif_id' => ($items['motif_id'] ?? ''),
                    'Client' => ["id" => $items['patient_id'], "name" => htmlspecialchars($items['patient_name'])]
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
        $unique_val = intval($_POST['id']);
        $csrf = null;
        $new_state = null;
        $array_data = array();

        // Extract Data
        if (isset($_POST['data']) && is_array($_POST['data'])) {
            foreach ($_POST['data'] as $data) {
                if (!isset($data['name']) || !isset($data['value']))
                    continue;
                if (strpos($data['name'], '__') !== false) {
                    $parts = explode('__', $data['name']);
                    $column = $parts[1];
                    if ($column === 'state')
                        $new_state = $data['value'];
                    if ($column === 'motif_id' && empty($data['value'])) {
                        $array_data[$column] = null;
                    } else {
                        $array_data[$column] = $data['value'];
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

        // IDOR Protection: Check ownership
        $check_sql = "SELECT id FROM rdv WHERE id = ?";
        $check_params = [$unique_val];

        if ($_SESSION['user']['role'] !== 'admin') {
            $check_sql .= " AND doctor_id = ?";
            $check_params[] = $_SESSION['user']['id'];
        } elseif (!empty($_SESSION['user']['cabinet_id'])) {
            $check_sql .= " AND cabinet_id = ?";
            $check_params[] = $_SESSION['user']['cabinet_id'];
        }

        $exists = $DB->select($check_sql, $check_params);
        if (empty($exists)) {
            echo json_encode(["state" => "false", "message" => "Access Denied or RDV not found"]);
            exit();
        }

        $array_data["modified_at"] = date('Y-m-d H:i:s');
        $array_data["modified_by"] = $_SESSION['user']['id'];

        $DB->table = 'rdv';
        $DB->data = $array_data;
        $DB->where = 'id = ' . $unique_val;

        try {
            $updated = $DB->update();

            if ($updated) {
                if (function_exists('push_notificationRDV')) {
                    push_notificationRDV($unique_val);
                }


                // Email Logic (Inside updateEvent)
                if ($new_state == 1) {
                    $sql = "SELECT r.date, r.rdv_num, r.hours,
                                   p.email, p.first_name, p.last_name,
                                   u.first_name as doc_fname, u.last_name as doc_lname
                            FROM rdv r
                            JOIN patient p ON r.patient_id = p.id
                            JOIN users u ON r.doctor_id = u.id
                            WHERE r.id = ?";
                    $rdvData = $GLOBALS['db']->select($sql, [$unique_val]);

                    if (!empty($rdvData) && !empty($rdvData[0]['email'])) {
                        $info = $rdvData[0];
                        $patientName = $info['first_name'] . ' ' . $info['last_name'];
                        $doctorName = $info['doc_fname'] . ' ' . $info['doc_lname'];
                        $rdvDate = date('d/m/Y', strtotime($info['date']));

                        $subject = "Confirmation de votre rendez-vous - The Doctor";
                        $body = "<p>Bonjour {$patientName},</p><p>Votre rendez-vous avec Dr. {$doctorName} le {$rdvDate} est confirmé. Ticket: {$info['rdv_num']}</p>";

                        // استخدام الدالة الجديدة (سريعة)
                        queue_email($info['email'], $patientName, $subject, $body);
                    }
                }

                echo json_encode(["state" => "true", "message" => $GLOBALS['language']['Edited successfully']]);
            } else {
                echo json_encode(["state" => "false", "message" => "Erreur lors de la mise à jour BDD"]);
            }
        } catch (Exception $e) {
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

            $date = new DateTime($dateString);
            $dayIndex = $date->format('w');
            $daysMap = [0 => "Dimanche", 1 => "Lundi", 2 => "Mardi", 3 => "Mercredi", 4 => "Jeudi", 5 => "Vendredi", 6 => "Samedi"];
            $dayName = $daysMap[$dayIndex];

            // Secure Query
            $doctor_info_sql = "SELECT tickets_day FROM users WHERE id = ?";
            $doctor_response = $GLOBALS['db']->select($doctor_info_sql, [$doctor_id]);

            if (!empty($doctor_response)) {
                $tickets_day_json = $doctor_response[0]['tickets_day'] ?? '[]';
                $tickets_day_array = json_decode($tickets_day_json, true);
                $nbrTickets = isset($tickets_day_array[$dayName]) ? intval($tickets_day_array[$dayName]) : 0;
                $restTickets = [];

                if ($nbrTickets > 0) {
                    $all_possible_tickets = range(1, $nbrTickets);

                    // Secure Query
                    $reserved_sql = "SELECT rdv_num FROM `rdv` WHERE doctor_id = ? AND state != 3 AND date = ?";
                    $reserved_res = $GLOBALS['db']->select($reserved_sql, [$doctor_id, $dateString]);

                    $reservedTickets = array_column($reserved_res, 'rdv_num');
                    $restTickets = array_diff($all_possible_tickets, $reservedTickets);
                }

                foreach ($restTickets as $ticket_num) {
                    $response[] = array("id" => $ticket_num, "text" => $ticket_num);
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

        // IDOR Check
        $check_sql = "SELECT id FROM rdv WHERE id = ?";
        $check_params = [$id];
        if ($_SESSION['user']['role'] !== 'admin') {
            $check_sql .= " AND doctor_id = ?";
            $check_params[] = $_SESSION['user']['id'];
        } elseif (!empty($_SESSION['user']['cabinet_id'])) {
            $check_sql .= " AND cabinet_id = ?";
            $check_params[] = $_SESSION['user']['cabinet_id'];
        }
        $exists = $GLOBALS['db']->select($check_sql, $check_params);
        if (empty($exists)) {
            echo json_encode(["state" => "false", "message" => "Access Denied"]);
            return;
        }

        $GLOBALS['db']->table = 'rdv';
        $GLOBALS['db']->data = array("state" => "$state", "modified_at" => "$datetime", "modified_by" => $_SESSION['user']['id']);
        $GLOBALS['db']->where = "id = $id";
        $updated = $GLOBALS['db']->update();

        if ($updated) {
            // --- NEW: Email Logic for List View ---
            if ($state == 1) {
                $sql = "SELECT r.date, r.rdv_num,
                               p.email, p.first_name, p.last_name,
                               u.first_name as doc_fname, u.last_name as doc_lname
                        FROM rdv r
                        JOIN patient p ON r.patient_id = p.id
                        JOIN users u ON r.doctor_id = u.id
                        WHERE r.id = ?";
                $rdvData = $GLOBALS['db']->select($sql, [$id]);

                if (!empty($rdvData) && !empty($rdvData[0]['email'])) {
                    $info = $rdvData[0];
                    $patientName = $info['first_name'] . ' ' . $info['last_name'];
                    $doctorName = $info['doc_fname'] . ' ' . $info['doc_lname'];
                    $rdvDate = date('d/m/Y', strtotime($info['date']));

                    $subject = "Confirmation de votre rendez-vous - The Doctor";
                    $body = "<p>Bonjour {$patientName},</p><p>Votre rendez-vous avec Dr. {$doctorName} le {$rdvDate} est confirmé. Ticket: {$info['rdv_num']}</p>";

                    // إرسال في الخلفية
                    queue_email($info['email'], $patientName, $subject, $body);
                }
            }
            // --------------------------------------

            echo json_encode(["state" => $updated, "message" => $GLOBALS['language']['Edited successfully']]);
        } else {
            echo json_encode(["state" => "false", "message" => $updated]);
        }
    } else {
        echo json_encode(["state" => "false", "message" => "missing id"]);
    }
}
function moveEvent($DB)
{
    if (isset($_POST['id']) && !empty($_POST['id']) && isset($_POST['date'])) {
        $id = intval($_POST['id']);

        // IDOR Check
        $check_sql = "SELECT id FROM rdv WHERE id = ?";
        $check_params = [$id];
        if ($_SESSION['user']['role'] !== 'admin') {
            $check_sql .= " AND doctor_id = ?";
            $check_params[] = $_SESSION['user']['id'];
        }
        $exists = $DB->select($check_sql, $check_params);
        if (empty($exists)) {
            echo json_encode(["state" => "false", "message" => "Access Denied"]);
            return;
        }

        $data = array("date" => $_POST['date'], "modified_at" => date('Y-m-d H:i:s'), "modified_by" => $_SESSION['user']['id']);
        $DB->table = 'rdv';
        $DB->data = $data;
        $DB->where = 'id = ' . $id;
        $updated = $DB->update();

        if ($updated)
            echo json_encode(["state" => "true"]);
        else
            echo json_encode(["state" => "false"]);
    } else {
        echo json_encode(["state" => "false", "message" => "missing data"]);
    }
}

function removeEvent($DB)
{
    if (isset($_POST['id'])) {
        $id = intval($_POST['id']);

        // IDOR Check
        $check_sql = "SELECT id FROM rdv WHERE id = ?";
        $check_params = [$id];
        if ($_SESSION['user']['role'] !== 'admin') {
            $check_sql .= " AND doctor_id = ?";
            $check_params[] = $_SESSION['user']['id'];
        }
        $exists = $DB->select($check_sql, $check_params);
        if (empty($exists)) {
            echo json_encode(["state" => "false", "message" => "Access Denied"]);
            return;
        }

        $data = array("deleted" => 1, "modified_at" => date('Y-m-d H:i:s'), "modified_by" => $_SESSION['user']['id']);
        $DB->table = 'rdv';
        $DB->data = $data;
        $DB->where = 'id = ' . $id;
        $updated = $DB->update();

        if ($updated)
            echo json_encode(["state" => "true", "message" => $GLOBALS['language']['Successfully Deleted']]);
        else
            echo json_encode(["state" => "false", "message" => "Error"]);
    } else {
        echo json_encode(["state" => "false", "message" => "missing id"]);
    }
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
        $doc = $DB->select("SELECT tickets_day, travel_hours FROM users WHERE id = ?", [$target_doctor_id])[0] ?? null;
        if ($doc) {
            $doctor_settings['tickets'] = json_decode($doc['tickets_day'] ?? '[]', true);
            $doctor_settings['hours'] = json_decode($doc['travel_hours'] ?? '[]', true);
        }
    }

    $where_clause = "rdv.deleted = 0 AND rdv.date BETWEEN ? AND ?";
    $params = [$start_date, $end_date];

    if ($target_doctor_id) {
        $where_clause .= " AND rdv.doctor_id = ?";
        $params[] = $target_doctor_id;
    } elseif (!empty($_SESSION['user']['cabinet_id'])) {
        $where_clause .= " AND rdv.cabinet_id = ?";
        $params[] = intval($_SESSION['user']['cabinet_id']);
    }

    $sql = "SELECT DATE(date) as day_date, state, COUNT(*) as count 
            FROM rdv 
            WHERE $where_clause 
            GROUP BY DATE(date), state";

    $results = $DB->select($sql, $params);

    $stats = [];
    foreach ($results as $row) {
        $date = $row['day_date'];
        $state = intval($row['state']);
        $count = intval($row['count']);

        if (!isset($stats[$date])) {
            $stats[$date] = ['total' => 0, 'details' => [0 => 0, 1 => 0, 2 => 0, 3 => 0]];
        }

        if ($state != 3) {
            $stats[$date]['total'] += $count;
        }
        $stats[$date]['details'][$state] = $count;
    }

    echo json_encode(['bookings' => $stats, 'settings' => $doctor_settings]);
}

function get_calendar_stats($DB)
{
    $start_date = $_POST['start'] ?? date('Y-m-01');
    $end_date = $_POST['end'] ?? date('Y-m-t');
    $doctor_id = $_POST['doctor_id'] ?? '';

    $where_clause = "rdv.deleted = 0 AND rdv.date BETWEEN ? AND ?";
    $params = [$start_date, $end_date];

    if ($_SESSION['user']['role'] === 'doctor' || $_SESSION['user']['role'] === 'nurse') {
        $where_clause .= " AND rdv.doctor_id = ?";
        $params[] = $_SESSION['user']['id'];
    } elseif (!empty($doctor_id)) {
        $where_clause .= " AND rdv.doctor_id = ?";
        $params[] = intval($doctor_id);
    } elseif (!empty($_SESSION['user']['cabinet_id'])) {
        $where_clause .= " AND rdv.cabinet_id = ?";
        $params[] = intval($_SESSION['user']['cabinet_id']);
    }

    $sql = "SELECT state, COUNT(*) as count FROM rdv WHERE $where_clause GROUP BY state";
    $results = $DB->select($sql, $params);

    $stats = ['total' => 0, 'created' => 0, 'confirmed' => 0, 'completed' => 0, 'canceled' => 0];

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