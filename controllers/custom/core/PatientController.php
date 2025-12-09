<?php

function getPatients($id, $return = false)
{
    $id = abs(filter_var($id, FILTER_SANITIZE_NUMBER_INT));
    $sql = "SELECT patient.*, communes.id as communeId, communes.name as communeName, willaya.id as willayaId, willaya.willaya FROM patient LEFT JOIN communes ON communes.id = patient.commune_id LEFT JOIN willaya ON willaya.id = communes.id_willaya WHERE patient.deleted = 0 AND patient.id = $id";
    $response = $GLOBALS['db']->select($sql);
    $GLOBALS['db'] = null;
    if ($return)
        return $response;
    else
        echo json_encode($response);
}

function getRdvPatient()
{
    $id = abs(filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT));
    $sql = "SELECT patient.*, communes.name as communeName, willaya.willaya FROM rdv LEFT JOIN patient ON patient.id = rdv.patient_id LEFT JOIN communes ON communes.id = patient.commune_id LEFT JOIN willaya ON willaya.id = communes.id_willaya WHERE rdv.id = $id";
    $response = $GLOBALS['db']->select($sql);
    $GLOBALS['db'] = null;
    echo json_encode($response);
}

function quick_add_patient($DB)
{
    if (!isset($_SESSION['user']['id'])) {
        echo json_encode(["state" => "false", "message" => "Auth required"]);
        return;
    }

    $first_name = filter_var($_POST['first_name'] ?? '', FILTER_SANITIZE_STRING);
    $last_name = filter_var($_POST['last_name'] ?? '', FILTER_SANITIZE_STRING);
    $phone = filter_var($_POST['phone'] ?? '', FILTER_SANITIZE_STRING);

    if (empty($first_name) || empty($last_name) || empty($phone)) {
        echo json_encode(["state" => "false", "message" => "Champs obligatoires manquants"]);
        return;
    }

    try {
        $DB->table = 'patient';
        $DB->data = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone' => $phone,
            'cabinet_id' => $_SESSION['user']['cabinet_id'] ?? null,
            'created_by' => $_SESSION['user']['id']
        ];

        $id = $DB->insert();

        if ($id) {
            echo json_encode([
                "state" => "true",
                "data" => [
                    "id" => $id,
                    "text" => "$first_name $last_name ($phone)"
                ]
            ]);
        } else {
            echo json_encode(["state" => "false", "message" => "Erreur BDD"]);
        }
    } catch (Exception $e) {
        echo json_encode(["state" => "false", "message" => $e->getMessage()]);
    }
}
?>