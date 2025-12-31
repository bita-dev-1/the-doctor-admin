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
            $where_clause = " WHERE doctor.deleted = 0 ";

            if (isset($payload->specialty_id)) {
                $where_clause .= " AND doctor.specialty_id = ? ";
                $params[] = intval($payload->specialty_id);
            }
            if (isset($payload->commune_id)) {
                $where_clause .= " AND doctor.commune_id = ? ";
                $params[] = intval($payload->commune_id);
            }

            $limit = isset($payload->limit) ? intval($payload->limit) : 20;
            $offset = isset($payload->offset) ? intval($payload->offset) : 0;

            $query = "SELECT doctor.*, specialty.namefr AS specialty, willaya.willaya, communes.name 
                      FROM doctor 
                      LEFT JOIN specialty ON specialty.id = doctor.specialty_id 
                      LEFT JOIN communes ON communes.id = doctor.commune_id 
                      LEFT JOIN willaya ON willaya.id = communes.id_willaya  
                      $where_clause 
                      LIMIT $limit OFFSET $offset";

            $data = $GLOBALS['db']->select($query, $params);

            echo json_encode(["data" => $data]);
            break;

        default:
            echo json_encode(array("messages" => "Bad Request"));
            break;
    }

    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 200 OK ');

} catch (Exception $e) {
    error_log("API Doctors Error: " . $e->getMessage());
    header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' 500 Internal Server Error ');
}
?>