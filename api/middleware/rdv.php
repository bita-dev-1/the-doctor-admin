<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

try {
    $payload = file_get_contents('php://input');
    $payload = json_decode($payload);

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            $params = [];
            $where_clause = " WHERE rdv.deleted=0 ";

            if (isset($payload->idUser)) {
                $where_clause .= " AND rdv.patient_id = ? ";
                $params[] = intval($payload->idUser);
            }

            if (isset($payload->specialty_id)) {
                $where_clause .= " AND doctor.specialty_id = ? ";
                $params[] = intval($payload->specialty_id);
            }
            if (isset($payload->commune_id)) {
                $where_clause .= " AND doctor.commune_id = ? ";
                $params[] = intval($payload->commune_id);
            }
            if (isset($payload->state)) {
                $state = intval($payload->state);
                if ($state == 0 || $state == 1) {
                    $where_clause .= " AND rdv.state = ? AND rdv.date >= DATE(NOW()) ";
                    $params[] = $state;
                } else {
                    $where_clause .= " AND rdv.state = ? ";
                    $params[] = $state;
                }
            }

            $limit = isset($payload->limit) ? intval($payload->limit) : 200;
            $offset = isset($payload->offset) ? intval($payload->offset) : 0;

            // Subquery for 'previous' count (if needed)
            $count_select = "";
            if (stripos(request_path(), 'rdv/me') !== false) {
                // Note: This subquery is complex to parameterize fully in this structure without major refactor.
                // We keep it simple but safe by ensuring inputs are ints.
                $count_select = "(SELECT COUNT(r1.id) FROM rdv r1 WHERE r1.date = rdv.date AND r1.doctor_id = rdv.doctor_id AND r1.rdv_num < rdv.rdv_num AND r1.state = 1 AND r1.deleted = 0) AS previous, ";
                $where_clause .= " AND rdv.date = CURRENT_DATE() AND state = 1 ";
            }

            $query = "SELECT $count_select doctor.*, rdv.id as idRdv, rdv.patient_id, rdv.rdv_num, rdv.date, rdv.hours, rdv.state, specialty.namefr AS specialty, willaya.willaya, communes.name 
                      FROM `rdv` 
                      LEFT JOIN doctor ON doctor.id = rdv.doctor_id 
                      LEFT JOIN specialty ON specialty.id = doctor.specialty_id 
                      LEFT JOIN communes ON communes.id = doctor.commune_id 
                      LEFT JOIN willaya ON willaya.id = communes.id_willaya 
                      $where_clause 
                      ORDER BY rdv.id DESC LIMIT $limit OFFSET $offset";

            $data = $GLOBALS['db']->select($query, $params);

            echo json_encode(["data" => $data]);
            break;

        default:
            echo json_encode(array("messages" => "Bad Request"));
            break;
    }

    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 200 OK ');

} catch (Exception $e) {
    error_log("API RDV Error: " . $e->getMessage());
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 200 Internal Server Error ');
}
?>