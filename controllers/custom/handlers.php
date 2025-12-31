<?php
// controllers/custom/handlers.php

// Disable error display in response to prevent JSON breaking
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (isset($_POST['method']) && !empty($_POST['method'])) {

    // 1. Load Core Config & Encryption
    $rootPath = dirname(__DIR__, 2); // Go up to project root
    if (file_exists($rootPath . '/inc.php')) {
        require_once($rootPath . '/inc.php');
    }

    include_once __DIR__ . '/../../config/encryption.core.php';
    include_once __DIR__ . '/../../config/DB.php';
    include_once __DIR__ . '/../../includes/lang.php';

    // 2. Load Functions & Controllers
    include_once 'functions.core.php';
    include_once 'core/RdvController.php';
    include_once 'core/ReeducationController.php';
    include_once 'core/FinanceController.php';
    include_once 'core/UserController.php';
    include_once 'core/ChatController.php';
    include_once 'core/PatientController.php';
    include_once 'core/DoctorController.php';

    // Include Auth for specific checks if needed
    include_once __DIR__ . '/../core/Auth.php';

    global $db;
    $db = new DB();

    switch ($_POST['method']) {
        // Chat
        case 'acountState':
            acountState();
            break;
        case 'chat':
            chat();
            break;
        case 'send_msg':
            send_msg();
            break;
        case 'post_conversation':
            post_conversation();
            break;

        // RDV / Appointments
        case 'updateState':
            updateState();
            break;
        case 'getRdvPatient':
            getRdvPatient();
            break;
        case 'postRdv':
            postRdv();
            break;
        case 'handleRdv_nbr':
            handleRdv_nbr();
            break;
        case 'get_RDV':
            get_RDV();
            break;
        case 'postEvent':
            postEvent($db);
            break;
        case 'updateEvent':
            updateEvent($db);
            break;
        case 'moveEvent':
            moveEvent($db);
            break;
        case 'removeEvent':
            removeEvent($db);
            break;
        case 'get_calendar_stats':
            get_calendar_stats($db);
            break;
        case 'get_daily_calendar_stats':
            get_daily_calendar_stats($db);
            break;

        // Patients
        case 'getPatients':
            getPatients(($_POST['id'] ?? null));
            break;
        case 'quick_add_patient':
            quick_add_patient($db);
            break;

        // Users / Auth
        case 'forget_password':
            forget_password();
            break;
        case 'adminResetPassword':
            adminResetPassword();
            break;
        case 'postuser':
            postuser();
            break;
        case 'updateuser':
            updateuser();
            break;
        case 'get_user':
            get_user();
            break;
        case 'checkFieldAvailability':
            checkFieldAvailability($db);
            break;
        case 'completeGoogleRegistration':
            completeGoogleRegistration($db);
            break;

        // Reeducation (Kiné)
        case 'generate_sessions_auto':
            generate_sessions_auto($db);
            break;
        case 'validate_session':
            validate_session($db);
            break;
        case 'reschedule_session':
            reschedule_session($db);
            break;
        case 'generate_sessions_manual':
            generate_sessions_manual($db);
            break;
        case 'get_technician_report_details':
            get_technician_report_details($db);
            break;
        case 'get_kine_queue':
            get_kine_queue($db);
            break;
        case 'get_kine_workspace_data':
            get_kine_workspace_data($db);
            break;
        case 'postReeducationDossier':
            postReeducationDossier($db);
            break;
        case 'get_technician_planning_data':
            get_technician_planning_data($db);
            break;

        // Finance / Payments
        case 'get_dossier_payment_info':
            get_dossier_payment_info($db);
            break;
        case 'record_payment':
            record_payment($db);
            break;
        case 'get_service_pricing_details':
            get_service_pricing_details($db);
            break;
        case 'postCodes':
            postCodes();
            break;
        case 'updateCodes':
            updateCodes();
            break;
        case 'postPayment':
            postPayment();
            break;
        case 'get_card':
            get_card();
            break;
        case 'get_codes':
            get_codes();
            break;
        case 'state_operation':
            state_operation();
            break;
        case 'get_product':
            get_product();
            break;

        // Doctor Settings
        case 'get_doctor_motifs':
            get_doctor_motifs($db);
            break;
        case 'save_doctor_motif':
            save_doctor_motif($db);
            break;
        case 'delete_doctor_motif':
            delete_doctor_motif($db);
            break;
    }
}


?>