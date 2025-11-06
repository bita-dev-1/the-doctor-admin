<?php
global $queries ;
if(isset($_SESSION['user'])){

    $user_role = $_SESSION['user']['role'] ?? null;
    $user_cabinet_id = $_SESSION['user']['cabinet_id'] ?? null;

    // --- START: Dynamic Cabinet Conditions ---
    $users_cabinet_condition = ""; // For the 'users' table
    $patients_cabinet_condition = ""; // For the 'patient' table
    $rdv_cabinet_condition = ""; // For the 'rdv' table

    // Apply filter only if the user is an admin tied to a specific cabinet
    if ($user_role === 'admin' && !empty($user_cabinet_id)) {
        $users_cabinet_condition = " AND users.cabinet_id = " . intval($user_cabinet_id);
        $patients_cabinet_condition = " AND patient.cabinet_id = " . intval($user_cabinet_id);
        $rdv_cabinet_condition = " AND rdv.cabinet_id = " . intval($user_cabinet_id);
    } 
    // For doctors/nurses, they should only see data related to their own cabinet
    elseif ($user_role === 'doctor' || $user_role === 'nurse') {
        if (!empty($user_cabinet_id)) {
            $patients_cabinet_condition = " AND patient.cabinet_id = " . intval($user_cabinet_id);
            $rdv_cabinet_condition = " AND rdv.cabinet_id = " . intval($user_cabinet_id);
        } else {
            // Fallback for a doctor without a cabinet, should not see any patient/rdv data from other cabinets
            $patients_cabinet_condition = " AND patient.cabinet_id IS NULL"; 
            $rdv_cabinet_condition = " AND rdv.cabinet_id IS NULL";
        }
    }
    // For Super Admin (admin with null cabinet_id), the conditions remain empty, showing all data.
    // --- END: Dynamic Cabinet Conditions ---
        
    $queries = array(
        // --- NEW QUERY FOR CABINETS TABLE ---
        "qr_cabinets_table"        => "SELECT id, name, address, phone, created_at, id as __action FROM cabinets WHERE deleted = 0",

        "qr_users_table"          => "SELECT 
                                        users.id, 
                                        users.image1 as _photo, 
                                        CONCAT(users.first_name,' ',users.last_name) as full_name, 
                                        users.phone, 
                                        users.email, 
                                        users.role, 
                                        specialty.namefr as specialty, 
                                        communes.name as commune, 
                                        willaya.willaya,
                                        users.id as _stateId,
                                        users.rdv as __enableRdv,
                                        users.status as _state, 
                                        users.id as __action 
                                      FROM users 
                                      LEFT JOIN specialty ON specialty.id = users.specialty_id 
                                      LEFT JOIN communes ON communes.id = users.commune_id 
                                      LEFT JOIN willaya ON willaya.id = communes.id_willaya 
                                      WHERE users.deleted = 0 AND users.role != 'admin' $users_cabinet_condition",
        
        "qr_doctors_table"         => "SELECT users.id, users.image1 as _photo, CONCAT( users.first_name,' ',users.last_name) as full_name, users.phone, users.email, specialty.namefr as specialty, users.id as _stateId, users.rdv as __enableRdv , communes.name as commune, willaya.willaya , users.recomondation, users.views, users.status as _state , users.id as __action FROM users LEFT JOIN specialty ON specialty.id = users.specialty_id LEFT JOIN communes ON communes.id = users.commune_id LEFT JOIN willaya ON willaya.id = communes.id_willaya WHERE users.deleted = 0 AND users.role = 'doctor' $users_cabinet_condition",
        
        "qr_patients_table"        => "SELECT patient.id, patient.image as _photo, patient.username, CONCAT( patient.first_name,' ',patient.last_name) as full_name, patient.phone, patient.email, communes.name as commune, willaya.willaya , patient.id as __action FROM patient LEFT JOIN communes ON communes.id = patient.commune_id LEFT JOIN willaya ON willaya.id = communes.id_willaya WHERE patient.deleted = 0 $patients_cabinet_condition",
        
        "qr_specialities_table"    => "SELECT specialty.id, specialty.image as _photo, specialty.namefr, specialty.namear, specialty.id as __action FROM `specialty` WHERE specialty.deleted = 0 ",

        "qr_rdv_table"             => "SELECT rdv.id, CONCAT_WS(' ', users.first_name, users.last_name) as doctor, CONCAT_WS(' ', rdv.first_name, rdv.last_name) as patient, rdv.phone, rdv.rdv_num, rdv.date, rdv.id as _stateId, rdv.state as __rdvstate FROM rdv LEFT JOIN patient ON patient.id = rdv.patient_id LEFT JOIN users ON users.id = rdv.doctor_id WHERE rdv.deleted = 0 $rdv_cabinet_condition",
        
        "qr_waitingList_table"     => "SELECT rdv.id, CONCAT_WS(' ', users.first_name, users.last_name) as doctor, CONCAT_WS(' ', rdv.first_name, rdv.last_name) as patient, rdv.phone, rdv.rdv_num, rdv.date, rdv.id as _stateId, rdv.state as __rdvstate FROM rdv LEFT JOIN patient ON patient.id = rdv.patient_id LEFT JOIN users ON users.id = rdv.doctor_id WHERE rdv.deleted = 0 AND rdv.state > 0 AND rdv.date = '".date("Y-m-d")."' $rdv_cabinet_condition",
        

    );     
}
        
?>