<?php
global $queries ;
if(isset($_SESSION['user'])){
        
    $queries = array(
        "qr_admins_table"          => "SELECT doctor.id, doctor.image1 as _photo, CONCAT( doctor.first_name,' ',doctor.last_name) as full_name, doctor.phone, doctor.email, specialty.namefr as specialty, communes.name as commune, willaya.willaya , doctor.id as __action FROM doctor LEFT JOIN specialty ON specialty.id = doctor.specialty_id LEFT JOIN communes ON communes.id = doctor.commune_id LEFT JOIN willaya ON willaya.id = communes.id_willaya WHERE doctor.deleted = 0 AND doctor.type = 1 ",
        "qr_doctors_table"         => "SELECT doctor.id, doctor.image1 as _photo, CONCAT( doctor.first_name,' ',doctor.last_name) as full_name, doctor.phone, doctor.email, specialty.namefr as specialty, doctor.id as _stateId, doctor.rdv as __enableRdv , communes.name as commune, willaya.willaya , doctor.recomondation, doctor.views, doctor.state as _state , doctor.id as __action FROM doctor LEFT JOIN specialty ON specialty.id = doctor.specialty_id LEFT JOIN communes ON communes.id = doctor.commune_id LEFT JOIN willaya ON willaya.id = communes.id_willaya WHERE doctor.deleted = 0 AND doctor.type = 0 ",
        "qr_patients_table"        => "SELECT patient.id, patient.image as _photo, patient.username, CONCAT( patient.first_name,' ',patient.last_name) as full_name, patient.phone, patient.email, communes.name as commune, willaya.willaya , patient.id as __action FROM patient LEFT JOIN communes ON communes.id = patient.commune_id LEFT JOIN willaya ON willaya.id = communes.id_willaya WHERE patient.deleted = 0 ",
        
        
        "qr_specialities_table"    => "SELECT specialty.id, specialty.image as _photo, specialty.namefr, specialty.namear, specialty.id as __action FROM `specialty` WHERE specialty.deleted = 0 ",

        "qr_rdv_table"             => "SELECT rdv.id, CONCAT_WS(' ', doctor.first_name, doctor.last_name) as doctor, CONCAT_WS(' ', rdv.first_name, rdv.last_name) as patient, rdv.phone, rdv.rdv_num, rdv.date, rdv.id as _stateId, rdv.state as __rdvstate FROM rdv LEFT JOIN patient ON patient.id = rdv.patient_id LEFT JOIN doctor ON doctor.id = rdv.doctor_id WHERE rdv.deleted = 0 ",
        "qr_waitingList_table"     => "SELECT rdv.id, ". ( $_SESSION['user']['data'][0]['type'] == 1 ? "CONCAT_WS(' ', doctor.first_name, doctor.last_name) as doctor," : '' )." CONCAT_WS(' ', rdv.first_name, rdv.last_name) as patient, rdv.phone, rdv.rdv_num, rdv.date, rdv.id as _stateId, rdv.state as __rdvstate FROM rdv LEFT JOIN patient ON patient.id = rdv.patient_id LEFT JOIN doctor ON doctor.id = rdv.doctor_id WHERE rdv.deleted = 0 AND rdv.state > 0 AND rdv.date = '".date("Y-m-d")."'" ,
        

    );     
}
        
?>
