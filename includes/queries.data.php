<?php
global $queries;
$queries = array(); // تهيئة المصفوفة لتجنب الأخطاء في حال عدم وجود جلسة

if (isset($_SESSION['user'])) {

    $user_role = $_SESSION['user']['role'] ?? null;
    $user_cabinet_id = $_SESSION['user']['cabinet_id'] ?? null;

    // --- تهيئة متغيرات الشروط بقيم فارغة لتجنب أخطاء Undefined Variable ---
    $users_cabinet_condition = "";
    $rdv_cabinet_condition = "";
    $service_join_condition = "1=1"; // شرط افتراضي صحيح دائماً

    // --- بناء الشروط بناءً على الصلاحيات ---
    if ($user_role === 'admin' && !empty($user_cabinet_id)) {
        // أدمن العيادة: يرى فقط مستخدمي ومواعيد عيادته
        $users_cabinet_condition = " AND users.cabinet_id = " . intval($user_cabinet_id);
        $rdv_cabinet_condition = " AND rdv.cabinet_id = " . intval($user_cabinet_id);
        $service_join_condition = "cs.cabinet_id = " . intval($user_cabinet_id);
    } elseif ($user_role === 'doctor' || $user_role === 'nurse') {
        // الطبيب/الممرض: يرون مواعيدهم أو مواعيد عيادتهم
        if (!empty($user_cabinet_id)) {
            $rdv_cabinet_condition = " AND rdv.cabinet_id = " . intval($user_cabinet_id);
        } else {
            $rdv_cabinet_condition = " AND rdv.cabinet_id IS NULL";
        }
    }

    // --- مصفوفة الاستعلامات ---
    $queries = array(
        "qr_cabinets_table" => "SELECT id, name, address, phone, created_at, id as __action FROM cabinets WHERE deleted = 0",

        "qr_users_table" => "SELECT 
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
                                  WHERE users.deleted = 0 $users_cabinet_condition",

        "qr_doctors_table" => "SELECT users.id, users.image1 as _photo, CONCAT( users.first_name,' ',users.last_name) as full_name, users.phone, users.email, specialty.namefr as specialty, users.id as _stateId, users.rdv as __enableRdv , communes.name as commune, willaya.willaya , users.recomondation, users.views, users.status as _state , users.id as __action FROM users LEFT JOIN specialty ON specialty.id = users.specialty_id LEFT JOIN communes ON communes.id = users.commune_id LEFT JOIN willaya ON willaya.id = communes.id_willaya WHERE users.deleted = 0 AND users.status = 'active' AND users.role = 'doctor' $users_cabinet_condition",

        "qr_patients_table" => "SELECT patient.id, patient.image as _photo, patient.username, CONCAT( patient.first_name,' ',patient.last_name) as full_name, patient.phone, patient.email, communes.name as commune, willaya.willaya , patient.id as __action FROM patient LEFT JOIN communes ON communes.id = patient.commune_id LEFT JOIN willaya ON willaya.id = communes.id_willaya WHERE patient.deleted = 0",

        "qr_specialities_table" => "SELECT specialty.id, specialty.image as _photo, specialty.namefr, specialty.namear, specialty.id as __action FROM `specialty` WHERE specialty.deleted = 0 ",

        "qr_rdv_table" => "SELECT rdv.id, CONCAT_WS(' ', users.first_name, users.last_name) as doctor, CONCAT_WS(' ', rdv.first_name, rdv.last_name) as patient, rdv.phone, rdv.rdv_num, rdv.date, rdv.id as _stateId, rdv.state as __rdvstate FROM rdv LEFT JOIN patient ON patient.id = rdv.patient_id LEFT JOIN users ON users.id = rdv.doctor_id WHERE rdv.deleted = 0 $rdv_cabinet_condition",

        "qr_waitingList_table" => "SELECT rdv.id, CONCAT_WS(' ', users.first_name, users.last_name) as doctor, CONCAT_WS(' ', rdv.first_name, rdv.last_name) as patient, rdv.phone, rdv.rdv_num, rdv.date, rdv.id as _stateId, rdv.state as __rdvstate FROM rdv LEFT JOIN patient ON patient.id = rdv.patient_id LEFT JOIN users ON users.id = rdv.doctor_id WHERE rdv.deleted = 0 AND rdv.state > 0 AND rdv.date = '" . date("Y-m-d") . "' $rdv_cabinet_condition",

        "qr_reeducation_dossiers_table" => "SELECT 
            rd.id, 
            CONCAT(p.first_name, ' ', p.last_name) as patient, 
            rt.name as type_reeducation, 
            rd.sessions_prescribed, 
            rd.sessions_completed as sessions_realisees, 
            (rd.sessions_prescribed - rd.sessions_completed) as sessions_restantes, 
            rd.status,
            rd.id as __action
            FROM reeducation_dossiers rd
            LEFT JOIN patient p ON rd.patient_id = p.id
            LEFT JOIN reeducation_types rt ON rd.reeducation_type_id = rt.id
            WHERE rd.deleted = 0",

        "qr_technician_today_sessions" => "SELECT 
            rs.id,
            r.hours,
            CONCAT(p.first_name, ' ', p.last_name) as patient,
            rt.name as type_reeducation,
            (SELECT COUNT(*) + 1 FROM reeducation_sessions rs2 WHERE rs2.dossier_id = rd.id AND rs2.status = 'completed') as session_num,
            (rd.sessions_prescribed - rd.sessions_completed) as sessions_restantes,
            rs.payment_status as statut_paiement,
            CONCAT(
                '<button class=\"btn btn-icon btn-flat-success validate-session-btn\" data-id=\"', rs.id, '\" title=\"Valider\"><svg xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"M22 11.08V12a10 10 0 1 1-5.93-9.14\"></path><polyline points=\"22 4 12 14.01 9 11.01\"></polyline></svg></button>',
                '<button class=\"btn btn-icon btn-flat-info reschedule-session-btn ms-1\" data-id=\"', rs.id, '\" data-rdv=\"', r.id, '\" data-date=\"', r.date, '\" data-time=\"', IFNULL(r.hours, ''), '\" title=\"Modifier Date/Heure\"><svg xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><rect x=\"3\" y=\"4\" width=\"18\" height=\"18\" rx=\"2\" ry=\"2\"></rect><line x1=\"16\" y1=\"2\" x2=\"16\" y2=\"6\"></line><line x1=\"8\" y1=\"2\" x2=\"8\" y2=\"6\"></line><line x1=\"3\" y1=\"10\" x2=\"21\" y2=\"10\"></line></svg></button>'
            ) as __action
        FROM reeducation_sessions rs
        JOIN reeducation_dossiers rd ON rs.dossier_id = rd.id
        JOIN patient p ON rd.patient_id = p.id
        LEFT JOIN reeducation_types rt ON rd.reeducation_type_id = rt.id
        LEFT JOIN rdv r ON rs.rdv_id = r.id
        WHERE rs.status = 'planned' AND r.date = CURDATE() AND rd.technician_id = " . intval($_SESSION['user']['id']),

        "qr_reeducation_types_table" => "SELECT id, name, id as __action FROM reeducation_types WHERE deleted = 0",

        "qr_caisse_transactions_table" => "SELECT 
                                        ct.id,
                                        CONCAT(p.first_name, ' ', p.last_name) as patient,
                                        rd.id as dossier_id,
                                        ct.amount_paid,
                                        ct.payment_date,
                                        CONCAT(u.first_name, ' ', u.last_name) as recorded_by
                                    FROM caisse_transactions ct
                                    JOIN reeducation_dossiers rd ON ct.dossier_id = rd.id
                                    JOIN patient p ON rd.patient_id = p.id
                                    JOIN users u ON ct.recorded_by = u.id 
                                    WHERE 1=1",

        "qr_cabinet_services_table" => "SELECT 
            cs.id, 
            cs.custom_name, 
            rt.name as type_reeducation, 
            cs.pricing_model, 
            CONCAT(cs.commission_value, IF(cs.commission_type='percent', '%', ' DA')) as commission_display,
            cs.id as __action 
        FROM cabinet_services cs 
        JOIN reeducation_types rt ON cs.reeducation_type_id = rt.id 
        WHERE cs.deleted = 0 AND cs.cabinet_id = " . intval($_SESSION['user']['cabinet_id'] ?? 0),

    );

}
?>