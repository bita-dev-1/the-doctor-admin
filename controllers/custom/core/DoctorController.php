<?php

/**
 * Fetch Doctor Motifs
 */
function get_doctor_motifs($DB)
{
    // 1. Security Check
    if (!isset($_SESSION['user']['id'])) {
        echo json_encode(["state" => "false", "message" => "Auth required"]);
        return;
    }

    // 2. Determine Target Doctor
    $doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : $_SESSION['user']['id'];

    // 3. Authorization Check
    if ($_SESSION['user']['role'] !== 'admin' && $_SESSION['user']['id'] != $doctor_id) {
        echo json_encode(["state" => "false", "message" => "Access denied"]);
        return;
    }

    // 4. Fetch Data
    $sql = "SELECT * FROM doctor_motifs WHERE doctor_id = ? AND deleted = 0 ORDER BY id DESC";
    $data = $DB->select($sql, [$doctor_id]);

    echo json_encode(["state" => "true", "data" => $data]);
}

/**
 * Add or Update a Motif
 */
function save_doctor_motif($DB)
{
    if (!isset($_SESSION['user']['id'])) {
        echo json_encode(["state" => "false", "message" => "Auth required"]);
        return;
    }

    $id = isset($_POST['motif_id']) ? intval($_POST['motif_id']) : 0;
    $doctor_id = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : $_SESSION['user']['id'];

    // Sanitize Inputs
    $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
    $duration = intval($_POST['duration']);
    $price = floatval($_POST['price']);

    if (empty($title)) {
        echo json_encode(["state" => "false", "message" => "Le titre est obligatoire"]);
        return;
    }

    $DB->table = 'doctor_motifs';
    $DB->data = [
        'doctor_id' => $doctor_id,
        'title' => $title,
        'duration' => $duration,
        'price' => $price
    ];

    if ($id > 0) {
        // Update
        $DB->where = "id = $id";
        $result = $DB->update();
    } else {
        // Insert
        $result = $DB->insert();
    }

    if ($result) {
        echo json_encode(["state" => "true", "message" => "Enregistré avec succès"]);
    } else {
        echo json_encode(["state" => "false", "message" => "Erreur lors de l'enregistrement"]);
    }
}

/**
 * Soft Delete a Motif
 */
function delete_doctor_motif($DB)
{
    if (!isset($_SESSION['user']['id'])) {
        echo json_encode(["state" => "false", "message" => "Auth required"]);
        return;
    }

    $id = intval($_POST['id']);

    $DB->table = 'doctor_motifs';
    $DB->data = ['deleted' => 1];
    $DB->where = "id = $id";

    if ($DB->update()) {
        echo json_encode(["state" => "true", "message" => "Supprimé avec succès"]);
    } else {
        echo json_encode(["state" => "false", "message" => "Erreur lors de la suppression"]);
    }
}
?>