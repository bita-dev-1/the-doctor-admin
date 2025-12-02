<?php
// منع ظهور أخطاء PHP كنص HTML لضمان وصول استجابة JSON نظيفة
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (isset($_POST['method']) && !empty($_POST['method'])) {
    include_once 'config/DB.php';
    include_once 'includes/lang.php';
    include_once 'controllers/custom/functions.core.php';

    global $db;
    $db = new DB();

    switch ($_POST['method']) {
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
        case 'updateState':
            updateState();
            break;
        case 'getPatients':
            getPatients(($_POST['id'] ?? null));
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
        case 'forget_password':
            forget_password();
            break;
        case 'adminResetPassword':
            adminResetPassword();
            break;
        case 'generate_sessions_auto':
            generate_sessions_auto($db);
            break;
        case 'validate_session':
            validate_session($db);
            break;
        case 'get_dossier_payment_info':
            get_dossier_payment_info($db);
            break;
        case 'record_payment':
            record_payment($db);
            break;
        case 'reschedule_session':
            reschedule_session($db);
            break;
        case 'quick_add_patient':
            quick_add_patient($db);
            break;
        case 'get_service_pricing_details':
            get_service_pricing_details($db);
            break;
        // --- الدالة الجديدة المصححة ---
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
        case 'get_calendar_stats':
            get_calendar_stats($db);
            break;

        case 'get_daily_calendar_stats':
            get_daily_calendar_stats($db);
            break;
        // ADD THIS NEW CASE
        case 'postReeducationDossier':
            postReeducationDossier($db);
            break;

    }
}


function postReeducationDossier($DB)
{
    try {
        if (!isset($_SESSION['user']['id'])) {
            throw new Exception("Auth required");
        }

        $DB->pdo->beginTransaction();

        // 1. Prepare Dossier Data
        $array_data = array();
        $table = 'reeducation_dossiers';

        // Parse form data (handling the __ prefix convention)
        foreach ($_POST['data'] as $data) {
            if (strpos($data['name'], '__') !== false) {
                $table_key = explode('__', $data['name'])[0];
                $column = explode('__', $data['name'])[1];
                if ($table_key === $table) {
                    $array_data[$column] = $data['value'];
                }
            } else if (stripos($data['name'], 'csrf') !== false) {
                $csrf = $data['value'];
            }
        }

        // CSRF Check
        if (isset($csrf)) {
            $csrf = customDecrypt($csrf);
            if (!is_csrf_valid($csrf)) {
                throw new Exception($GLOBALS['language']['The form is forged']);
            }
        }

        // Add System Fields
        $array_data['created_by'] = $_SESSION['user']['id'];
        $array_data['status'] = 'active'; // Default status

        // Insert Dossier
        $DB->table = $table;
        $DB->data = $array_data;
        $dossier_id = $DB->insert();

        if (!$dossier_id) {
            throw new Exception("Erreur lors de la création du dossier.");
        }

        // 2. Process Selected Dates (if any)
        $dates_json = $_POST['initial_sessions_dates'] ?? '[]';
        $dates = json_decode($dates_json, true);

        if (!empty($dates) && is_array($dates)) {

            // Get Technician Cabinet ID (needed for RDV)
            $tech_id = $array_data['technician_id'];
            $tech_data = $DB->select("SELECT cabinet_id FROM users WHERE id = $tech_id")[0] ?? null;
            $cabinet_id = $tech_data['cabinet_id'] ?? ($_SESSION['user']['cabinet_id'] ?? null);

            foreach ($dates as $date_str) {
                if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date_str))
                    continue;

                // A. Create RDV
                $rdv_data = [
                    'patient_id' => $array_data['patient_id'],
                    'doctor_id' => $tech_id,
                    'cabinet_id' => $cabinet_id,
                    'date' => $date_str,
                    'state' => 0, // Created
                    'created_by' => $_SESSION['user']['id'],
                ];
                $DB->table = 'rdv';
                $DB->data = $rdv_data;
                $rdv_id = $DB->insert();

                if (!$rdv_id)
                    throw new Exception("Erreur création RDV pour le $date_str");

                // B. Create Session
                $session_data = [
                    'dossier_id' => $dossier_id,
                    'rdv_id' => $rdv_id,
                    'status' => 'planned',
                ];
                $DB->table = 'reeducation_sessions';
                $DB->data = $session_data;
                $session_id = $DB->insert();

                // C. Link RDV to Session
                $DB->table = 'rdv';
                $DB->data = ['reeducation_session_id' => $session_id];
                $DB->where = 'id = ' . $rdv_id;
                $DB->update();
            }
        }

        $DB->pdo->commit();
        echo json_encode(["state" => "true", "id" => $dossier_id, "message" => "Dossier créé avec " . count($dates) . " séances planifiées."]);

    } catch (Exception $e) {
        if ($DB->pdo->inTransaction()) {
            $DB->pdo->rollBack();
        }
        echo json_encode(["state" => "false", "message" => $e->getMessage()]);
    }
}


function get_kine_queue($DB)
{
    // 1. التحقق من هوية المستخدم
    if (!isset($_SESSION['user']['id'])) {
        echo json_encode([]);
        return;
    }

    $tech_id = $_SESSION['user']['id'];
    $today = date('Y-m-d');

    // 2. جلب قائمة "اليوم" (Aujourd'hui)
    // المنطق هنا كان صحيحاً (يعتمد على ID)، لذا نتركه كما هو
    $sql_today = "SELECT 
                rs.id as session_id,
                rs.status,
                r.hours as time,
                CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                LEFT(p.first_name, 1) as f_init, LEFT(p.last_name, 1) as l_init,
                rt.name as act_name,
                -- حساب دقيق: عدد الجلسات التي تسبق أو تساوي الجلسة الحالية
                (SELECT COUNT(*) FROM reeducation_sessions s2 WHERE s2.dossier_id = rs.dossier_id AND s2.id <= rs.id) as session_num,
                rd.sessions_prescribed as total_sessions
            FROM reeducation_sessions rs
            JOIN rdv r ON rs.rdv_id = r.id
            JOIN reeducation_dossiers rd ON rs.dossier_id = rd.id
            JOIN patient p ON rd.patient_id = p.id
            LEFT JOIN reeducation_types rt ON rd.reeducation_type_id = rt.id
            WHERE r.date = '$today' 
            AND rd.technician_id = $tech_id
            AND r.deleted = 0
            ORDER BY r.hours ASC";

    $data_today = $DB->select($sql_today);

    foreach ($data_today as &$row) {
        $row['initials'] = strtoupper(($row['f_init'] ?? '') . ($row['l_init'] ?? ''));
    }

    // 3. جلب قائمة "الملفات النشطة" (En cours)
    // التعديل الجذري هنا: استخدام Subquery لحساب الترتيب الدقيق للجلسة المختارة
    $sql_active = "SELECT 
                    derived.*,
                    -- الحساب الدقيق لرقم الجلسة بناءً على الـ ID الذي تم اختياره
                    (SELECT COUNT(*) FROM reeducation_sessions s2 WHERE s2.dossier_id = derived.dossier_id AND s2.id <= derived.session_id) as session_num
                FROM (
                    SELECT 
                        rd.id as dossier_id,
                        -- تحديد الجلسة المستهدفة (القادمة أو الأخيرة)
                        COALESCE(
                            (SELECT id FROM reeducation_sessions WHERE dossier_id = rd.id AND status = 'planned' ORDER BY id ASC LIMIT 1),
                            (SELECT id FROM reeducation_sessions WHERE dossier_id = rd.id ORDER BY id DESC LIMIT 1)
                        ) as session_id,
                        
                        'active' as status,
                        '' as time,
                        CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                        LEFT(p.first_name, 1) as f_init, LEFT(p.last_name, 1) as l_init,
                        rt.name as act_name,
                        rd.sessions_prescribed as total_sessions
                    FROM reeducation_dossiers rd
                    JOIN patient p ON rd.patient_id = p.id
                    LEFT JOIN reeducation_types rt ON rd.reeducation_type_id = rt.id
                    WHERE rd.technician_id = $tech_id
                    AND rd.status = 'active'
                    AND rd.deleted = 0
                ) as derived
                WHERE derived.session_id IS NOT NULL
                ORDER BY derived.dossier_id DESC
                LIMIT 50";

    $data_active = $DB->select($sql_active);

    foreach ($data_active as &$row) {
        $row['initials'] = strtoupper(($row['f_init'] ?? '') . ($row['l_init'] ?? ''));
    }

    // 4. إرجاع النتيجة
    echo json_encode([
        "state" => "true",
        "data" => [
            "today" => $data_today,
            "active" => $data_active
        ]
    ]);
}
/* --- 1. تحديث دالة get_RDV --- */
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

    $sql = "SELECT rdv.id, rdv.patient_id, rdv.date as Date_RDV, rdv.state, rdv.rdv_num, rdv.phone,
            COALESCE(CONCAT_WS(' ', patient.first_name, patient.last_name), CONCAT_WS(' ', rdv.first_name, rdv.last_name)) AS patient_name,
            rs.payment_status
            FROM rdv 
            LEFT JOIN patient ON patient.id = rdv.patient_id
            LEFT JOIN reeducation_sessions rs ON rdv.reeducation_session_id = rs.id
            WHERE rdv.deleted = 0 $where_clause $id_filter $filters";

    $res = $GLOBALS['db']->select($sql);

    $convertedData = [];
    if (!empty($res)) {
        foreach ($res as $items) {
            $title = $items['patient_name'];
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
                    // +++++++ هذا هو السطر المهم جداً الذي كان ناقصاً +++++++
                    'state_id' => (int) $items['state'],
                    // ++++++++++++++++++++++++++++++++++++++++++++++++++++++
                    'phone' => ($items['phone'] ?? ''),
                    'num_rdv' => ($items['rdv_num'] ?? ''),
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


function get_daily_calendar_stats($DB)
{
    $start_date = $_POST['start'];
    $end_date = $_POST['end'];
    $doctor_id = $_POST['doctor_id'] ?? '';

    // 1. تحديد الطبيب
    $target_doctor_id = 0;
    if ($_SESSION['user']['role'] === 'doctor' || $_SESSION['user']['role'] === 'nurse') {
        $target_doctor_id = $_SESSION['user']['id'];
    } elseif (!empty($doctor_id)) {
        $target_doctor_id = intval($doctor_id);
    }

    // جلب الإعدادات
    $doctor_settings = [];
    if ($target_doctor_id) {
        $doc = $DB->select("SELECT tickets_day, travel_hours FROM users WHERE id = $target_doctor_id")[0] ?? null;
        if ($doc) {
            $doctor_settings['tickets'] = json_decode($doc['tickets_day'] ?? '[]', true);
            $doctor_settings['hours'] = json_decode($doc['travel_hours'] ?? '[]', true);
        }
    }

    // 2. جلب المواعيد مجمعة حسب اليوم والحالة
    $where_clause = "rdv.deleted = 0 AND rdv.date BETWEEN '$start_date' AND '$end_date'";
    if ($target_doctor_id) {
        $where_clause .= " AND rdv.doctor_id = $target_doctor_id";
    } elseif (!empty($_SESSION['user']['cabinet_id'])) {
        $where_clause .= " AND rdv.cabinet_id = " . intval($_SESSION['user']['cabinet_id']);
    }

    // التجميع حسب التاريخ والحالة
    $sql = "SELECT DATE(date) as day_date, state, COUNT(*) as count 
            FROM rdv 
            WHERE $where_clause 
            GROUP BY DATE(date), state";

    $results = $DB->select($sql);

    // تنسيق البيانات: [التاريخ] => [الإجمالي، تفاصيل الحالات]
    $stats = [];
    foreach ($results as $row) {
        $date = $row['day_date'];
        $state = intval($row['state']);
        $count = intval($row['count']);

        if (!isset($stats[$date])) {
            $stats[$date] = [
                'total' => 0,
                'details' => [0 => 0, 1 => 0, 2 => 0, 3 => 0] // 0:Created, 1:Accepted, 2:Completed, 3:Canceled
            ];
        }

        // لا نحسب الملغاة في الإجمالي الخاص بالسعة، لكن نحتفظ بها في التفاصيل
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
    // تحديد النطاق الزمني (من التقويم)
    $start_date = $_POST['start'] ?? date('Y-m-01');
    $end_date = $_POST['end'] ?? date('Y-m-t');
    $doctor_id = $_POST['doctor_id'] ?? '';

    // شروط الفلترة حسب الصلاحيات
    $where_clause = "rdv.deleted = 0 AND rdv.date BETWEEN '$start_date' AND '$end_date'";

    if ($_SESSION['user']['role'] === 'doctor' || $_SESSION['user']['role'] === 'nurse') {
        $where_clause .= " AND rdv.doctor_id = " . $_SESSION['user']['id'];
    } elseif (!empty($doctor_id)) {
        $where_clause .= " AND rdv.doctor_id = " . intval($doctor_id);
    } elseif (!empty($_SESSION['user']['cabinet_id'])) {
        $where_clause .= " AND rdv.cabinet_id = " . intval($_SESSION['user']['cabinet_id']);
    }

    // جلب الإحصائيات مجمعة حسب الحالة
    $sql = "SELECT state, COUNT(*) as count FROM rdv WHERE $where_clause GROUP BY state";
    $results = $DB->select($sql);

    // تهيئة القيم الافتراضية
    $stats = [
        'total' => 0,
        'created' => 0,   // state 0
        'confirmed' => 0, // state 1
        'completed' => 0, // state 2
        'canceled' => 0   // state 3
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


function get_kine_workspace_data($DB)
{
    $session_id = filter_var($_POST['session_id'], FILTER_SANITIZE_NUMBER_INT);

    if (empty($session_id)) {
        echo '<div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
                <div class="mb-2">
                    <i data-feather="alert-circle" style="width: 50px; height: 50px;"></i>
                </div>
                <h4>Aucune séance trouvée</h4>
                <p>Ce dossier ne contient pas encore de séances planifiées.</p>
              </div>
              <script>if(feather) feather.replace();</script>';
        return;
    }

    $sql = "SELECT 
                rs.id as session_id, rs.status, rs.payment_status, rs.observations, rs.pain_scale, 
                rs.duration, rs.exercises_performed, rs.rdv_id,
                r.date as rdv_date, r.hours as rdv_time,
                rd.id as dossier_id, rd.price, rd.payment_mode, rd.sessions_prescribed, rd.sessions_completed,
                CONCAT(p.first_name, ' ', p.last_name) as patient_name, p.phone, p.id as patient_id,
                rt.name as act_name,
                (SELECT SUM(amount_paid) FROM caisse_transactions WHERE dossier_id = rd.id) as total_paid,
                (SELECT COUNT(*) FROM reeducation_sessions sub WHERE sub.dossier_id = rs.dossier_id AND sub.id <= rs.id) as current_rank
            FROM reeducation_sessions rs
            JOIN reeducation_dossiers rd ON rs.dossier_id = rd.id
            JOIN patient p ON rd.patient_id = p.id
            LEFT JOIN rdv r ON rs.rdv_id = r.id
            LEFT JOIN reeducation_types rt ON rd.reeducation_type_id = rt.id
            WHERE rs.id = $session_id";

    $data = $DB->select($sql)[0] ?? null;

    if (!$data) {
        echo '<div class="alert alert-danger m-2">Erreur : Session introuvable ou supprimée.</div>';
        return;
    }

    $total_price = (float) $data['price'];
    $total_paid = (float) ($data['total_paid'] ?? 0);
    $remaining = $total_price - $total_paid;
    if ($remaining < 0) $remaining = 0;

    $session_price = $data['sessions_prescribed'] > 0 ? ($total_price / $data['sessions_prescribed']) : 0;

    $current_rank = $data['current_rank'];
    $total_sessions = $data['sessions_prescribed'];
    $progress_percent = $total_sessions > 0 ? ($current_rank / $total_sessions) * 100 : 0;

    $is_completed = ($data['status'] === 'completed');
    $readonly_attr = $is_completed ? 'disabled' : '';
    $is_session_paid = ($data['payment_status'] === 'paid');

    $rdv_date_display = date('d/m/Y', strtotime($data['rdv_date']));
    
    // *** التعديل الهام هنا: ضمان صيغة التاريخ الصافية ***
    $rdv_date_iso = date('Y-m-d', strtotime($data['rdv_date'])); 

    ?>
        <input type="hidden" id="workspace_session_id" value="<?= $data['session_id'] ?>">
        <!-- القيمة هنا أصبحت YYYY-MM-DD فقط -->
        <input type="hidden" id="workspace_rdv_date" value="<?= $rdv_date_iso ?>">

        <div class="card mb-2 border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="avatar bg-light-primary p-50 me-2" style="width: 50px; height: 50px;">
                            <span class="avatar-content fs-4"><?= strtoupper(substr($data['patient_name'], 0, 2)) ?></span>
                        </div>
                        <div>
                            <h4 class="mb-0 text-primary fw-bolder"><?= $data['patient_name'] ?></h4>
                            <div class="mt-1">
                                <span class="badge bg-light-secondary"><?= $data['act_name'] ?></span>
                                <span class="badge bg-light-info ms-1">
                                    <i data-feather="calendar" style="width: 12px; height: 12px;"></i> <?= $rdv_date_display ?>
                                </span>
                                <small class="text-muted ms-1"><i data-feather="phone"></i> <?= $data['phone'] ?></small>
                            </div>
                        </div>
                    </div>

                    <div class="text-end" id="action-buttons-container">
                        <?php if (!$is_completed): ?>
                                <button class="btn btn-outline-info me-1 reschedule-session-btn" data-rdv="<?= $data['rdv_id'] ?>"
                                    data-date="<?= $data['rdv_date'] ?>" data-time="<?= $data['rdv_time'] ?>">
                                    <i data-feather="calendar"></i> Reporter
                                </button>

                                <button class="btn btn-success shadow validate-session-btn" data-id="<?= $data['session_id'] ?>">
                                    <i data-feather="check-circle"></i> Terminer
                                </button>
                        <?php else: ?>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="badge bg-success fs-6 p-2"><i data-feather="check"></i> Terminée</span>
                                    <button class="btn btn-outline-secondary btn-sm" onclick="enableEditMode()">
                                        <i data-feather="edit-2"></i> Modifier
                                    </button>
                                    <button class="btn btn-primary btn-sm d-none" id="btn-save-edit"
                                        onclick="$('.validate-session-btn').click()">
                                        Enregistrer
                                    </button>
                                    <button class="d-none validate-session-btn" data-id="<?= $data['session_id'] ?>"></button>
                                </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-2">
                    <div class="d-flex justify-content-between mb-50">
                        <span class="fw-bold text-dark">Séance actuelle : <span
                                class="text-primary fs-5"><?= $current_rank ?></span> / <?= $total_sessions ?></span>
                        <span class="text-muted"><?= round($progress_percent) ?>%</span>
                    </div>
                    <div class="progress progress-bar-primary" style="height: 12px">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                            style="width: <?= $progress_percent ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row match-height">
            <div class="col-md-7">
                <div class="card h-100">
                    <div class="card-header border-bottom">
                        <h5 class="card-title">Détails Cliniques</h5>
                    </div>
                    <div class="card-body pt-2">
                        <form id="session-notes-form">
                            <div class="row">
                                <div class="col-md-6 mb-1">
                                    <label class="form-label fw-bold">Durée (min)</label>
                                    <input type="number" class="form-control" id="ws-duration" name="duration"
                                        value="<?= $data['duration'] ?? '' ?>" placeholder="Ex: 45" <?= $readonly_attr ?>>
                                </div>
                                <div class="col-md-6 mb-1">
                                    <label class="form-label fw-bold">Évaluation Douleur (0-10)</label>
                                    <div class="d-flex align-items-center">
                                        <input type="range" class="form-range me-2" min="0" max="10" step="1" id="ws-pain"
                                            value="<?= $data['pain_scale'] ?? 0 ?>" oninput="$('#pain-val').text(this.value)"
                                            <?= $readonly_attr ?>>
                                        <span class="fw-bold text-primary fs-4"
                                            id="pain-val"><?= $data['pain_scale'] ?? 0 ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-1">
                                <label class="form-label fw-bold">Exercices Effectués</label>
                                <textarea class="form-control" rows="2" id="ws-exercises" name="exercises_performed"
                                    placeholder="Liste des exercices..." <?= $readonly_attr ?>><?= $data['exercises_performed'] ?></textarea>
                            </div>

                            <div class="mb-1">
                                <label class="form-label fw-bold">Notes & Observations</label>
                                <textarea class="form-control" rows="3" id="ws-observations" name="observations"
                                    placeholder="Progrès, réactions du patient..." <?= $readonly_attr ?>><?= $data['observations'] ?></textarea>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <?php
                $borderColor = ($remaining == 0 || $is_session_paid) ? 'success' : 'danger';
                $bgColor = ($remaining == 0 || $is_session_paid) ? 'bg-light-success' : 'bg-light-danger';
                $textColor = ($remaining == 0 || $is_session_paid) ? 'text-success' : 'text-danger';
                ?>
                <div class="card h-100 border-<?= $borderColor ?>">
                    <div class="card-header <?= $bgColor ?>">
                        <h5 class="card-title <?= $textColor ?>">Finance</h5>
                    </div>
                    <div class="card-body pt-2 text-center">

                        <?php if ($is_session_paid): ?>
                                <div class="text-success mb-2">
                                    <i data-feather="check-circle" style="width: 60px; height: 60px; stroke-width: 1.5;"></i>
                                    <h3 class="text-success mt-1">Séance Payée</h3>
                                    <?php if ($remaining > 0): ?>
                                            <p class="text-muted mt-1 mb-0">Reste total sur le dossier :
                                                <br><strong><?= number_format($remaining, 0) ?> DA</strong>
                                            </p>
                                    <?php endif; ?>
                                </div>
                        <?php elseif ($remaining == 0): ?>
                                <div class="text-success mb-2">
                                    <i data-feather="check-circle" style="width: 60px; height: 60px; stroke-width: 1.5;"></i>
                                    <h3 class="text-success mt-1">Dossier Réglé</h3>
                                    <p class="text-muted">Aucun paiement en attente.</p>
                                </div>
                        <?php else: ?>
                                <h6 class="text-muted">Reste à payer</h6>
                                <h1 class="fw-bolder mb-2 display-6"><?= number_format($remaining, 0) ?> <small class="fs-6">DA</small>
                                </h1>
                                <div class="d-grid gap-1">
                                    <button class="btn btn-primary"
                                        onclick="processQuickPay(<?= $data['dossier_id'] ?>, <?= number_format($session_price, 2, '.', '') ?>)">
                                        <i data-feather="dollar-sign"></i> Payer la séance (<?= number_format($session_price, 0) ?> DA)
                                    </button>
                                    <?php if ($remaining > $session_price): ?>
                                            <button class="btn btn-outline-secondary"
                                                onclick="processQuickPay(<?= $data['dossier_id'] ?>, <?= number_format($remaining, 2, '.', '') ?>)">
                                                Tout solder (<?= number_format($remaining, 0) ?> DA)
                                            </button>
                                    <?php endif; ?>
                                </div>
                        <?php endif; ?>

                        <div class="mt-2 text-start bg-white border p-1 rounded">
                            <div class="d-flex justify-content-between">
                                <small>Total Dossier:</small>
                                <small class="fw-bold"><?= number_format($total_price, 0) ?> DA</small>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small>Déjà payé:</small>
                                <small class="fw-bold text-success"><?= number_format($total_paid, 0) ?> DA</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
}



function get_technician_report_details($DB)
{
    // 1. التحقق من الصلاحيات
    if (!isset($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'admin') {
        echo json_encode(["state" => "false", "message" => "Auth required"]);
        return;
    }

    // 2. استقبال وتأمين المدخلات
    $tech_id = filter_var($_POST['tech_id'], FILTER_SANITIZE_NUMBER_INT);
    $date_from = $_POST['date_from'];
    $date_to = $_POST['date_to'];

    // 3. تحديد شرط ربط الخدمات (لضمان جلب إعدادات العيادة الصحيحة)
    $cabinet_id = $_SESSION['user']['cabinet_id'] ?? 0;

    // إذا كان أدمن عيادة، نربط بإعدادات عيادته. 
    // إذا كان سوبر أدمن، نحاول الربط بإعدادات عيادة التقني.
    $service_join_condition = $cabinet_id
        ? "cs.cabinet_id = $cabinet_id"
        : "cs.cabinet_id = (SELECT cabinet_id FROM users WHERE id = rd.technician_id)";

    // 4. استعلام SQL مع المنطق الهجين
    $sql = "SELECT 
                rs.completed_at,
                CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                rt.name as act_name,
                
                -- سعر الجلسة الواحدة (للعرض)
                (rd.price / GREATEST(rd.sessions_prescribed, 1)) as session_price,
                
                -- القيمة الخام للعمولة (للعرض في العمود Règle Com.)
                rd.technician_percentage as raw_commission_value,
                cs.commission_type,
                
                -- *** حساب صافي أجر التقني للجلسة ***
                CASE 
                    -- 1. الأولوية للقيمة المحفوظة (نظام الاستحقاق الجديد)
                    WHEN rs.commission_amount > 0 THEN rs.commission_amount
                    
                    -- 2. إذا كانت البيانات قديمة: حساب المبلغ الثابت (تقسيم الإجمالي على عدد الحصص)
                    WHEN cs.commission_type = 'fixed' THEN (rd.technician_percentage / GREATEST(rd.sessions_prescribed, 1))
                    
                    -- 3. إذا كانت البيانات قديمة: حساب النسبة المئوية
                    ELSE ((rd.price / GREATEST(rd.sessions_prescribed, 1)) * (rd.technician_percentage / 100))
                END as commission_amount
                
            FROM reeducation_sessions rs
            JOIN reeducation_dossiers rd ON rs.dossier_id = rd.id
            JOIN patient p ON rd.patient_id = p.id
            LEFT JOIN reeducation_types rt ON rd.reeducation_type_id = rt.id
            LEFT JOIN cabinet_services cs ON rd.reeducation_type_id = cs.reeducation_type_id AND $service_join_condition AND cs.deleted = 0
            WHERE rs.status = 'completed' 
            AND rd.technician_id = :tech_id
            AND rs.completed_at BETWEEN :date_from AND :date_to
            ORDER BY rs.completed_at DESC";

    // 5. تنفيذ الاستعلام
    $stmt = $DB->prepare($sql);
    $stmt->execute([
        ':tech_id' => $tech_id,
        ':date_from' => $date_from . ' 00:00:00',
        ':date_to' => $date_to . ' 23:59:59'
    ]);

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. إرجاع النتيجة
    echo json_encode(["state" => "true", "data" => $data]);
}
// --- دالة توليد الجلسات يدوياً (المصححة) ---
function generate_sessions_manual($DB)
{
    // 1. التحقق من البيانات
    if (!isset($_POST['dossier_id']) || !isset($_POST['dates']) || !is_array($_POST['dates'])) {
        echo json_encode(["state" => "false", "message" => "Données manquantes."]);
        return;
    }

    $dossier_id = filter_var($_POST['dossier_id'], FILTER_SANITIZE_NUMBER_INT);
    $dates = $_POST['dates'];

    // 2. جلب معلومات الملف والتقني
    $sql = "SELECT rd.*, u.cabinet_id as technician_cabinet_id
            FROM reeducation_dossiers rd 
            JOIN users u ON rd.technician_id = u.id 
            WHERE rd.id = :dossier_id";
    $stmt = $DB->prepare($sql);
    $stmt->execute([':dossier_id' => $dossier_id]);
    $dossier = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$dossier) {
        echo json_encode(["state" => "false", "message" => "Dossier non trouvé."]);
        return;
    }

    // 3. التحقق من الجلسات المقفلة (المدفوعة أو المكتملة)
    $sql_check_locked = "SELECT COUNT(*) FROM reeducation_sessions 
                         WHERE dossier_id = :dossier_id 
                         AND (status = 'completed' OR payment_status = 'paid')";
    $stmt_locked = $DB->prepare($sql_check_locked);
    $stmt_locked->execute([':dossier_id' => $dossier_id]);

    // إذا لم تكن هناك جلسات مكتملة، نحذف الجلسات المخططة القديمة لتجنب التكرار والفوضى
    if ($stmt_locked->fetchColumn() == 0) {
        $sql_delete_rdv = "DELETE FROM rdv WHERE reeducation_session_id IN (SELECT id FROM reeducation_sessions WHERE dossier_id = :dossier_id AND status = 'planned')";
        $stmt_del_rdv = $DB->prepare($sql_delete_rdv);
        $stmt_del_rdv->execute([':dossier_id' => $dossier_id]);

        $sql_delete_sessions = "DELETE FROM reeducation_sessions WHERE dossier_id = :dossier_id AND status = 'planned'";
        $stmt_del_sess = $DB->prepare($sql_delete_sessions);
        $stmt_del_sess->execute([':dossier_id' => $dossier_id]);
    }

    try {
        // 4. بدء المعاملة (Transaction)
        if (!$DB->pdo->inTransaction()) {
            $DB->pdo->beginTransaction();
        }

        $sessions_created_count = 0;
        $cabinet_to_assign = $dossier['technician_cabinet_id'] ?? ($dossier['cabinet_id'] ?? null);

        foreach ($dates as $date_str) {
            // التحقق من صيغة التاريخ
            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date_str))
                continue;

            // التحقق من عدم وجود جلسة مسبقة لنفس الملف في نفس اليوم (لتجنب التكرار)
            $check_sql = "SELECT COUNT(*) FROM rdv r 
                          JOIN reeducation_sessions rs ON r.reeducation_session_id = rs.id 
                          WHERE rs.dossier_id = :dossier_id AND r.date = :date";
            $stmt_check = $DB->prepare($check_sql);
            $stmt_check->execute([':dossier_id' => $dossier_id, ':date' => $date_str]);

            if ($stmt_check->fetchColumn() > 0) {
                continue; // تخطي إذا كانت موجودة
            }

            // أ. إنشاء الموعد (RDV)
            $rdv_data = [
                'patient_id' => $dossier['patient_id'],
                'doctor_id' => $dossier['technician_id'],
                'cabinet_id' => $cabinet_to_assign,
                'date' => $date_str,
                'state' => 0, // Created
                'created_by' => $_SESSION['user']['id'],
            ];
            $DB->table = 'rdv';
            $DB->data = $rdv_data;
            $rdv_id = $DB->insert();

            if (!$rdv_id) {
                throw new Exception("Erreur lors de la création du RDV pour la date $date_str");
            }

            // ب. إنشاء الجلسة (Session)
            $session_data = [
                'dossier_id' => $dossier_id,
                'rdv_id' => $rdv_id,
                'status' => 'planned',
            ];
            $DB->table = 'reeducation_sessions';
            $DB->data = $session_data;
            $session_id = $DB->insert();

            if (!$session_id) {
                throw new Exception("Erreur lors de la création de la session pour la date $date_str");
            }

            // ج. ربط الموعد بالجلسة
            $DB->table = 'rdv';
            $DB->data = ['reeducation_session_id' => $session_id];
            $DB->where = 'id = ' . $rdv_id;
            $DB->update();

            $sessions_created_count++;
        }

        $DB->pdo->commit();
        echo json_encode(["state" => "true", "message" => "$sessions_created_count séances ont été planifiées avec succès."]);

    } catch (\Throwable $e) { // استخدام Throwable لالتقاط كل الأخطاء
        if ($DB->pdo->inTransaction()) {
            $DB->pdo->rollBack();
        }
        // إرجاع رسالة الخطأ كـ JSON
        echo json_encode(["state" => "false", "message" => "Erreur système: " . $e->getMessage()]);
    }
}

// --- باقي الدوال المساعدة ---

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

function get_service_pricing_details($DB)
{
    if (!isset($_SESSION['user']['cabinet_id'])) {
        echo json_encode(["state" => "false", "message" => "Cabinet non identifié"]);
        return;
    }

    $reeducation_type_id = filter_var($_POST['reeducation_type_id'], FILTER_SANITIZE_NUMBER_INT);
    $sessions_count = filter_var($_POST['sessions_count'], FILTER_SANITIZE_NUMBER_INT);
    $cabinet_id = $_SESSION['user']['cabinet_id'];

    $sql = "SELECT * FROM cabinet_services 
            WHERE cabinet_id = $cabinet_id 
            AND reeducation_type_id = $reeducation_type_id 
            AND deleted = 0";

    $config = $DB->select($sql)[0] ?? null;

    if (!$config) {
        echo json_encode(["state" => "false", "message" => "Service non configuré pour ce cabinet"]);
        return;
    }

    $effective_sessions = $sessions_count;

    $total_price = 0;
    $rules = json_decode($config['pricing_rules'], true);
    usort($rules, function ($a, $b) {
        return $a['limit'] - $b['limit'];
    });

    $remaining_sessions = $effective_sessions;
    $previous_limit = 0;

    foreach ($rules as $rule) {
        if ($remaining_sessions <= 0)
            break;

        $tier_capacity = $rule['limit'] - $previous_limit;
        $sessions_in_tier = min($remaining_sessions, $tier_capacity);

        $total_price += $sessions_in_tier * floatval($rule['price']);

        $remaining_sessions -= $sessions_in_tier;
        $previous_limit = $rule['limit'];
    }

    $commission_total = 0;
    if ($config['commission_type'] === 'fixed') {
        $commission_total = $effective_sessions * floatval($config['commission_value']);
    } else {
        $commission_total = ($total_price * floatval($config['commission_value'])) / 100;
    }

    echo json_encode([
        "state" => "true",
        "data" => [
            "total_price" => $total_price,
            "commission_total" => $commission_total,
            "payment_model" => $config['pricing_model'],
            "duration" => $config['session_duration'],
            "package_capacity" => $config['package_capacity']
        ]
    ]);
}

function reschedule_session($DB)
{
    if (!isset($_SESSION['user']['id'])) {
        echo json_encode(["state" => "false", "message" => "Accès non autorisé."]);
        return;
    }

    if (!isset($_POST['rdv_id']) || !isset($_POST['new_date']) || empty($_POST['new_date'])) {
        echo json_encode(["state" => "false", "message" => "Données manquantes."]);
        return;
    }

    $rdv_id = filter_var($_POST['rdv_id'], FILTER_SANITIZE_NUMBER_INT);
    $new_date = filter_var($_POST['new_date'], FILTER_SANITIZE_STRING);
    $new_time = isset($_POST['new_time']) ? filter_var($_POST['new_time'], FILTER_SANITIZE_STRING) : null;

    try {
        $data = [
            'date' => $new_date,
            'modified_at' => date('Y-m-d H:i:s'),
            'modified_by' => $_SESSION['user']['id']
        ];

        if ($new_time) {
            $data['hours'] = $new_time;
        }

        $DB->table = 'rdv';
        $DB->data = $data;
        $DB->where = 'id = ' . $rdv_id;
        $updated = $DB->update();

        if ($updated) {
            echo json_encode(["state" => "true", "message" => "Séance reprogrammée avec succès."]);
        } else {
            echo json_encode(["state" => "false", "message" => "Échec de la mise à jour."]);
        }

    } catch (Exception $e) {
        echo json_encode(["state" => "false", "message" => $e->getMessage()]);
    }
}




function validate_session($DB)
{
    // 1. التحقق من الصلاحيات
    if (!isset($_SESSION['user']['id']) || !in_array($_SESSION['user']['role'], ['doctor', 'nurse', 'admin'])) {
        echo json_encode(["state" => "false", "message" => "Accès non autorisé."]);
        return;
    }
    if (!isset($_POST['session_id'])) {
        echo json_encode(["state" => "false", "message" => "ID manquant."]);
        return;
    }

    $session_id = filter_var($_POST['session_id'], FILTER_SANITIZE_NUMBER_INT);
    $session_status = $_POST['session_status'] ?? 'completed';
    $completed_at = date('Y-m-d H:i:s');

    // 2. جلب الحالة السابقة للجلسة (لمعرفة هل هي تعديل أم إنهاء جديد)
    $old_session_sql = "SELECT status, dossier_id FROM reeducation_sessions WHERE id = $session_id";
    $old_session = $DB->select($old_session_sql)[0] ?? null;

    if (!$old_session) {
        echo json_encode(["state" => "false", "message" => "Session introuvable."]);
        return;
    }

    // 3. جلب تفاصيل الملف وقواعد العمولة
    $sql_info = "SELECT 
                    rs.dossier_id, 
                    rd.price, 
                    rd.sessions_prescribed, 
                    rd.technician_percentage,
                    rd.technician_id,
                    cs.commission_type
                 FROM reeducation_sessions rs 
                 JOIN reeducation_dossiers rd ON rs.dossier_id = rd.id
                 -- نربط مع المستخدم لجلب العيادة، ثم مع الخدمات لجلب نوع العمولة
                 LEFT JOIN users u ON rd.technician_id = u.id
                 LEFT JOIN cabinet_services cs ON rd.reeducation_type_id = cs.reeducation_type_id 
                    AND cs.cabinet_id = u.cabinet_id
                    AND cs.deleted = 0
                 WHERE rs.id = $session_id";

    $info = $DB->select($sql_info)[0] ?? null;

    if (!$info) {
        echo json_encode(["state" => "false", "message" => "Dossier introuvable."]);
        return;
    }

    $DB->pdo->beginTransaction();
    try {
        $commission_amount = 0;

        // 4. حساب العمولة (يتم الحساب في كل مرة لضمان الدقة عند التعديل)
        if ($session_status === 'completed') {
            $sessions_count = (int) $info['sessions_prescribed'];
            if ($sessions_count <= 0)
                $sessions_count = 1;

            if (isset($info['commission_type']) && $info['commission_type'] === 'fixed') {
                // إذا كان المبلغ ثابتاً للملف، نقسمه على عدد الحصص
                $commission_amount = (float) $info['technician_percentage'] / $sessions_count;
            } else {
                // إذا كان نسبة مئوية
                $session_price = (float) $info['price'] / $sessions_count;
                $commission_amount = $session_price * ((float) $info['technician_percentage'] / 100);
            }
        }

        // 5. تحديث بيانات الجلسة
        $session_data = [
            'status' => $session_status,
            // نحدث التاريخ فقط إذا لم تكن مكتملة من قبل، أو يمكنك تحديثه دائماً حسب الرغبة (هنا نحدثه دائماً لتوثيق آخر تعديل)
            'completed_at' => $completed_at,
            'exercises_performed' => $_POST['exercises_performed'] ?? null,
            'pain_scale' => $_POST['pain_scale'] ?? null,
            'observations' => $_POST['observations'] ?? null,
            'duration' => $_POST['duration'] ?? null,
            'commission_amount' => number_format($commission_amount, 2, '.', '') // الحفظ في قاعدة البيانات
        ];

        // تحديث القائم بالعملية فقط إذا كانت جلسة جديدة
        if ($old_session['status'] !== 'completed') {
            $session_data['completed_by'] = $_SESSION['user']['id'];
        }

        $DB->table = 'reeducation_sessions';
        $DB->data = $session_data;
        $DB->where = 'id = ' . $session_id;

        if (!$DB->update()) {
            throw new Exception("Erreur lors de la mise à jour de la session.");
        }

        // 6. تحديث عداد الملف وحالة الدفع (فقط إذا كانت الجلسة جديدة وليست تعديل)
        if ($old_session['status'] !== 'completed' && $session_status === 'completed') {

            // أ. زيادة العداد
            $DB->update('reeducation_dossiers', [], "id=" . $info['dossier_id'], "sessions_completed = sessions_completed + 1");

            // ب. تحديث حالة الدفع للجلسات (Paid/Unpaid) بناءً على الرصيد
            $dossier_id = $info['dossier_id'];

            // جلب المدفوعات
            $total_paid_query = $DB->select("SELECT SUM(amount_paid) as total FROM caisse_transactions WHERE dossier_id = $dossier_id")[0];
            $total_paid = $total_paid_query['total'] ?? 0;

            // حساب سعر الجلسة الواحدة (الصافي)
            // ملاحظة: الخصم يطبق على الإجمالي، لذا سعر الجلسة = (السعر - الخصم) / العدد
            $dossier_data = $DB->select("SELECT price, discount_amount, sessions_prescribed, payment_mode FROM reeducation_dossiers WHERE id = $dossier_id")[0];

            $net_price = (float) $dossier_data['price'] - (float) $dossier_data['discount_amount'];
            $count = (int) $dossier_data['sessions_prescribed'] > 0 ? (int) $dossier_data['sessions_prescribed'] : 1;
            $price_per_session = $net_price / $count;

            // عدد الجلسات المغطاة
            $sessions_covered = ($price_per_session > 0) ? floor(($total_paid + 0.1) / $price_per_session) : 999;

            // إعادة تعيين الكل إلى Unpaid لضمان الترتيب
            $DB->update('reeducation_sessions', ['payment_status' => 'unpaid'], "dossier_id = $dossier_id");

            // تحديث الجلسات المغطاة إلى Paid
            if ($sessions_covered > 0) {
                $limit = intval($sessions_covered);
                // نحدث أقدم الجلسات أولاً
                $sql_pay = "UPDATE reeducation_sessions SET payment_status = 'paid' WHERE dossier_id = $dossier_id ORDER BY id ASC LIMIT $limit";
                $stmt = $DB->prepare($sql_pay);
                $stmt->execute();
            }
        }

        $DB->pdo->commit();

        $msg = ($old_session['status'] === 'completed') ? "Modifications enregistrées." : "Séance terminée avec succès.";
        echo json_encode(["state" => "true", "message" => $msg]);

    } catch (Exception $e) {
        $DB->pdo->rollBack();
        echo json_encode(["state" => "false", "message" => $e->getMessage()]);
    }
}

function generate_sessions_auto($DB)
{
    // هذه الدالة القديمة، تم استبدالها بـ generate_sessions_manual ولكن نبقيها للتوافق إذا لزم الأمر
    // ... (يمكنك إبقاؤها أو حذفها إذا لم تعد مستخدمة)
    echo json_encode(["state" => "false", "message" => "Deprecated function."]);
}




function get_dossier_payment_info($DB)
{
    if (!isset($_POST['dossier_id'])) {
        echo json_encode(["state" => "false"]);
        return;
    }
    $dossier_id = filter_var($_POST['dossier_id'], FILTER_SANITIZE_NUMBER_INT);

    $sql = "SELECT 
                rd.*, 
                CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                (SELECT SUM(amount_paid) FROM caisse_transactions WHERE dossier_id = rd.id) as total_paid
            FROM reeducation_dossiers rd
            JOIN patient p ON rd.patient_id = p.id
            WHERE rd.id = $dossier_id";

    $dossier = $DB->select($sql)[0] ?? null;

    if ($dossier) {
        // 1. استخراج القيم الأساسية
        $stored_price = (float) $dossier['price']; // هذا هو السعر الإجمالي دائماً حسب نموذج الإدخال
        $sessions_count = (int) ($dossier['sessions_prescribed'] > 0 ? $dossier['sessions_prescribed'] : 1);
        $total_paid = (float) ($dossier['total_paid'] ?? 0);
        $discount = (float) ($dossier['discount_amount'] ?? 0);

        // 2. تحديد الإجمالي الخام (Gross Total)
        // بما أن المستخدم يدخل "Tarif Total" في النموذج، فلا نحتاج للضرب
        $gross_total = $stored_price;

        // 3. حساب سعر الجلسة الواحدة (للعرض والاقتراح)
        $price_per_session = $gross_total / $sessions_count;

        // 4. حساب الصافي (Net Total) بعد الخصم
        $net_total = $gross_total - $discount;
        if ($net_total < 0)
            $net_total = 0;

        // 5. حساب المتبقي (Remaining)
        $remaining = $net_total - $total_paid;

        // تصحيح أخطاء الفواصل العشرية الصغيرة
        if ($remaining < 0.01)
            $remaining = 0;

        // 6. تحديد المبلغ المقترح للدفع (Amount to Pay)
        if ($dossier['payment_mode'] == 'package') {
            // في حالة الفوفت، نقترح دفع كامل المتبقي
            $amount_to_pay = $remaining;
        } else {
            // في حالة الدفع بالجلسة، نقترح سعر الجلسة الواحدة
            // ولكن إذا كان المتبقي أقل من سعر الجلسة (الدفعة الأخيرة)، نقترح المتبقي فقط
            $amount_to_pay = ($remaining < $price_per_session) ? $remaining : $price_per_session;
        }

        // إرسال البيانات للواجهة
        $dossier['total_paid'] = $total_paid;
        $dossier['gross_total'] = $gross_total;
        $dossier['net_total'] = $net_total;
        $dossier['remaining_balance'] = $remaining;
        $dossier['amount_to_pay'] = $amount_to_pay;
        $dossier['unit_price'] = $price_per_session; // نرسل سعر الجلسة المحسوب للعرض

        echo json_encode(["state" => "true", "data" => $dossier]);
    } else {
        echo json_encode(["state" => "false"]);
    }
}


function record_payment($DB)
{
    // التعديل هنا: إضافة 'doctor' إلى المصفوفة
    if (!isset($_SESSION['user']['id']) || !in_array($_SESSION['user']['role'], ['admin', 'nurse', 'doctor'])) {
        echo json_encode(["state" => "false", "message" => "Accès non autorisé."]);
        return;
    }
    if (!isset($_POST['dossier_id']) || !isset($_POST['amount_paid'])) {
        echo json_encode(["state" => "false", "message" => "Données manquantes."]);
        return;
    }

    $dossier_id = filter_var($_POST['dossier_id'], FILTER_SANITIZE_NUMBER_INT);
    $amount_paid = filter_var($_POST['amount_paid'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    try {
        // 1. تسجيل المعاملة المالية
        if ($amount_paid > 0) {
            $data = [
                'dossier_id' => $dossier_id,
                'amount_paid' => $amount_paid,
                'recorded_by' => $_SESSION['user']['id']
            ];
            $DB->table = 'caisse_transactions';
            $DB->data = $data;
            $DB->insert();
        }

        // 2. جلب معلومات الملف
        $sql_dossier = "SELECT price, payment_mode, discount_amount, sessions_prescribed FROM reeducation_dossiers WHERE id = :id";
        $stmt_dossier = $DB->prepare($sql_dossier);
        $stmt_dossier->execute([':id' => $dossier_id]);
        $dossier = $stmt_dossier->fetch(PDO::FETCH_ASSOC);

        // 3. جلب مجموع المدفوعات الكلي
        $sql_total = "SELECT SUM(amount_paid) as total FROM caisse_transactions WHERE dossier_id = :id";
        $stmt_total = $DB->prepare($sql_total);
        $stmt_total->execute([':id' => $dossier_id]);
        $total_paid = $stmt_total->fetchColumn() ?: 0;

        // 4. حساب سعر الجلسة الواحدة الفعلي (التصحيح هنا)
        $total_price_net = (float) $dossier['price'] - (float) $dossier['discount_amount']; // السعر الإجمالي الصافي
        $sessions_count = (int) $dossier['sessions_prescribed'];
        if ($sessions_count <= 0)
            $sessions_count = 1; // لتجنب القسمة على صفر

        // سعر الجلسة = الإجمالي الصافي / عدد الجلسات
        $price_per_session = $total_price_net / $sessions_count;

        // 5. حساب عدد الجلسات التي تغطيها المدفوعات
        $sessions_covered = 0;
        if ($price_per_session > 0) {
            // نضيف هامش بسيط (0.1) لتجنب مشاكل الفواصل العشرية (مثلاً 1.9999 تصبح 2)
            $sessions_covered = floor(($total_paid + 0.1) / $price_per_session);
        } else {
            // إذا كان السعر صفر، فكل الجلسات مدفوعة
            $sessions_covered = 999;
        }

        // التأكد من عدم تجاوز العدد الكلي للجلسات
        if ($sessions_covered > $sessions_count) {
            $sessions_covered = $sessions_count;
        }

        // 6. تحديث حالة الجلسات

        // أولاً: تصفير الجميع إلى "غير مدفوع" (لإعادة الحساب بدقة)
        $sql_reset = "UPDATE reeducation_sessions SET payment_status = 'unpaid' WHERE dossier_id = $dossier_id";
        $stmt_reset = $DB->prepare($sql_reset);
        $stmt_reset->execute();

        // ثانياً: تحديث الجلسات المغطاة إلى "مدفوع"
        if ($sessions_covered > 0) {
            $limit = intval($sessions_covered);
            $sql_update = "UPDATE reeducation_sessions 
                           SET payment_status = 'paid' 
                           WHERE dossier_id = $dossier_id 
                           ORDER BY id ASC 
                           LIMIT $limit";
            $stmt_update = $DB->prepare($sql_update);
            $stmt_update->execute();
        }

        echo json_encode(["state" => "true", "message" => "Synchronisation réussie. $sessions_covered séances marquées comme payées."]);

    } catch (Exception $e) {
        echo json_encode(["state" => "false", "message" => $e->getMessage()]);
    }
}

function moveEvent($DB)
{
    if (isset($_POST['id']) && !empty($_POST['id']) && isset($_POST['date']) && !empty($_POST['date'])) {
        $table = 'planning';
        $data = array("Date_RDV" => $_POST['date'], "modified_at" => date('Y-m-d H:i:s'), "modified_by" => $_SESSION['user']['data'][0]['Id']);
        $DB->table = $table;
        $DB->data = $data;
        $DB->where = 'id = ' . $_POST['id'];
        $updated = true && $DB->update();
        push_notificationRDV($_POST['id']);
    } else {
        echo json_encode(["state" => "false", "message" => "missing data"]);
    }
    $DB = null;
}

function removeEvent($DB)
{
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $table = 'planning';
        $data = array("deleted" => 1, "modified_at" => date('Y-m-d H:i:s'), "modified_by" => $_SESSION['user']['data'][0]['Id']);
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

function updateEvent($DB)
{
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $table = 'planning';
        $unique_val = $_POST['id'];
        $array_data = array();
        foreach ($_POST['data'] as $data) {
            if (strpos($data['name'], '__') !== false) {
                $table_key = explode('__', $data['name'])[0];
                $column = explode('__', $data['name'])[1];
                if (stripos($column, 'password') !== false || stripos($column, 'pass') !== false) {
                    $array_data[$table_key][$column] = sha1($data['value']);
                } else {
                    if (isset($array_data[$table_key][$column]) && is_array($array_data[$table_key][$column])) {
                        $array_data[$table_key][$column][] = $data['value'];
                    } else {
                        if (isset($array_data[$table_key][$column])) {
                            $array_data[$table_key][$column] = [$array_data[$table_key][$column], $data['value']];
                        } else {
                            $array_data[$table_key][$column] = $data['value'];
                        }
                    }
                }
            } else if (stripos($data['name'], 'csrf') !== false) {
                $csrf = $data['value'];
                unset($data['csrf']);
            }
        }
        if (isset($csrf)) {
            $csrf = customDecrypt($csrf);
            if (!is_csrf_valid($csrf)) {
                echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
                exit();
            }
        } else {
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
            exit();
        }
        $filteredData = array_filter($array_data, function ($key) use ($table) {
            return $key != $table;
        }, ARRAY_FILTER_USE_KEY);
        $restData = array_diff_key($array_data, $filteredData);
        $restData = array_values($restData)[0];
        $restData = array_merge($restData, array("modified_at" => date('Y-m-d H:i:s'), "modified_by" => $_SESSION['user']['data'][0]['Id']));
        $DB->table = $table;
        $DB->data = $restData;
        $DB->where = 'id = ' . $unique_val;
        $updated = true && $DB->update();
        if ($updated && !isset($_POST['is_quote'])) {
            $DB->table = 'planning_services';
            $DB->where = array('planning_id' => $unique_val);
            $DB->Delete();
        }
        if (is_array($filteredData) && !empty($filteredData)) {
            $unique_id = 'planning_id';
            foreach ($filteredData as $table_name => $data) {
                $DB->table = $table_name;
                if (is_array($data['service_id'])) {
                    $extraData = array("$unique_id" => $unique_val);
                    $data = array_map(function ($service_id) use ($extraData) {
                        return array_merge($extraData, ['service_id' => $service_id]);
                    }, $data['service_id']);
                    $DB->multi = true;
                } else {
                    $data = array_merge($data, array("$unique_id" => $unique_val));
                }
                $DB->data = $data;
                $updated = $updated && $DB->insert();
            }
        }
        if ($updated) {
            push_notificationRDV($unique_val);
            echo json_encode(["state" => "true", "message" => $GLOBALS['language']['Edited successfully']]);
        }
    } else {
        echo json_encode(["state" => "false", "message" => "missing id"]);
    }
    $DB = null;
}

function postEvent($DB)
{
    $array_data = array();
    $table = 'planning';
    foreach ($_POST['data'] as $data) {
        if (strpos($data['name'], '__') !== false) {
            $table_key = explode('__', $data['name'])[0];
            $column = explode('__', $data['name'])[1];
            if (stripos($column, 'password') !== false || stripos($column, 'pass') !== false) {
                $array_data[$table_key][$column] = sha1($data['value']);
            } else {
                if (isset($array_data[$table_key][$column]) && is_array($array_data[$table_key][$column])) {
                    $array_data[$table_key][$column][] = $data['value'];
                } else {
                    if (isset($array_data[$table_key][$column])) {
                        $array_data[$table_key][$column] = [$array_data[$table_key][$column], $data['value']];
                    } else {
                        $array_data[$table_key][$column] = $data['value'];
                    }
                }
            }
        } else if (stripos($data['name'], 'csrf') !== false) {
            $csrf = $data['value'];
            unset($data['csrf']);
        }
    }
    if (isset($csrf)) {
        $csrf = customDecrypt($csrf);
        if (!is_csrf_valid($csrf)) {
            echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
            exit();
        }
    } else {
        echo json_encode(["state" => "false", "message" => $GLOBALS['language']['The form is forged']]);
        exit();
    }
    $filteredData = array_filter($array_data, function ($key) use ($table) {
        return $key != $table;
    }, ARRAY_FILTER_USE_KEY);
    $restData = array_diff_key($array_data, $filteredData);
    $restData = array_values($restData)[0];
    $restData = array_merge($restData, array("Garage_id" => $_SESSION['user']['data'][0]['Id'], "created_by" => $_SESSION['user']['data'][0]['Id']));
    $DB->table = $table;
    $DB->data = $restData;
    $last_id = $DB->insert();
    $inserted = true && $last_id;
    if (is_array($filteredData) && !empty($filteredData)) {
        $unique_id = ((substr($table, -1) === 's') ? substr($table, 0, -1) : $table) . '_id';
        foreach ($filteredData as $table_name => $data) {
            $DB->table = $table_name;
            if (is_array($data['service_id'])) {
                $extraData = array("$unique_id" => $last_id);
                $data = array_map(function ($service_id) use ($extraData) {
                    return array_merge($extraData, ['service_id' => $service_id]);
                }, $data['service_id']);
                $DB->multi = true;
            } else {
                $data = array_merge($data, array("$unique_id" => $last_id));
            }
            $DB->data = $data;
            $inserted = $inserted && $DB->insert();
        }
    }
    if ($inserted) {
        echo json_encode(["state" => "true", "message" => $GLOBALS['language']['Added successfully']]);
    } else {
        echo json_encode(["state" => "false", "message" => $inserted]);
    }
    $DB = null;
}



function postRdv()
{
    $patient_id = filter_var(($_POST['patient'] ?? null), FILTER_SANITIZE_NUMBER_INT);
    $first_name = filter_var(($_POST['first_name'] ?? ""), FILTER_SANITIZE_STRING);
    $last_name = filter_var(($_POST['last_name'] ?? ""), FILTER_SANITIZE_STRING);
    $phone = filter_var(($_POST['phone'] ?? ""), FILTER_SANITIZE_STRING);

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
        "date" => filter_var(($_POST['date'] ?? date("Y-m-d")), FILTER_SANITIZE_STRING),
        "first_name" => $first_name,
        "last_name" => $last_name,
        "phone" => $phone,
        "rdv_num" => filter_var(($_POST['rdv_num'] ?? 0), FILTER_SANITIZE_NUMBER_INT),
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

function updateState()
{
    if (isset($_SESSION['user']['id']) && !empty($_SESSION['user']['id'])) {
        $id = abs(filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT));
        $state = abs(filter_var($_POST['state'], FILTER_SANITIZE_NUMBER_INT));
        $datetime = date('Y-m-d H:i:s');
        $GLOBALS['db']->table = 'rdv';
        $GLOBALS['db']->data = array("state" => "$state", "modified_at" => "$datetime", "modified_by" => $_SESSION['user']['id']);
        $GLOBALS['db']->where = "id = $id";
        $updated = $GLOBALS['db']->update();
        if ($updated) {
            echo json_encode(["state" => $updated, "message" => $GLOBALS['language']['Edited successfully']]);
        } else {
            echo json_encode(["state" => "false", "message" => $updated]);
        }
    } else
        echo json_encode(["state" => "false", "message" => "missing id"]);
}

function getRdvPatient()
{
    $id = abs(filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT));
    $sql = "SELECT patient.*, communes.name as communeName, willaya.willaya FROM rdv LEFT JOIN patient ON patient.id = rdv.patient_id LEFT JOIN communes ON communes.id = patient.commune_id LEFT JOIN willaya ON willaya.id = communes.id_willaya WHERE rdv.id = $id";
    $response = $GLOBALS['db']->select($sql);
    $GLOBALS['db'] = null;
    echo json_encode($response);
}

function handleRdv_nbr()
{
    try {
        $response = [];
        if (isset($_POST['doctor']) && !empty($_POST['doctor'])) {
            $doctor_id = filter_var(($_POST['doctor']), FILTER_SANITIZE_NUMBER_INT);
            $dateString = filter_var(($_POST['date'] ?? date('Y-m-d')), FILTER_SANITIZE_STRING);
            $date = new DateTime($dateString);
            setlocale(LC_TIME, 'fr_FR.UTF-8', 'fra');
            $dayName = ucwords(strftime('%A', $date->getTimestamp()));
            $doctor_info_sql = "SELECT tickets_day FROM users WHERE id = ?";
            $stmt = $GLOBALS['db']->prepare($doctor_info_sql);
            $stmt->execute([$doctor_id]);
            $doctor_response = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($doctor_response) {
                $tickets_day_json = $doctor_response['tickets_day'] ?? '[]';
                $tickets_day_array = json_decode($tickets_day_json, true);
                $nbrTickets = isset($tickets_day_array[$dayName]) ? intval($tickets_day_array[$dayName]) : 0;
                $restTickets = [];
                if ($nbrTickets > 0) {
                    $all_possible_tickets = range(1, $nbrTickets);
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

function forget_password()
{
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $sql = "SELECT * FROM `users` WHERE `deleted` = 0 AND `email` = ?";
    $stmt = $GLOBALS['db']->prepare($sql);
    $stmt->execute([$email]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_data) {
        $newPassword = generateRandomPassword();
        $password_hash = sha1($newPassword);

        $fullName = $user_data['first_name'] . ' ' . $user_data['last_name'];
        $subject = 'Réinitialisation de votre mot de passe - The Doctor App';
        $body = "
            <h3>Réinitialisation de Mot de Passe</h3>
            <p>Bonjour {$fullName},</p>
            <p>Votre mot de passe a été réinitialisé. Voici vos nouvelles informations de connexion :</p>
            <p><strong>Nouveau mot de passe :</strong> {$newPassword}</p>
            <p>Nous vous recommandons de changer ce mot de passe après votre première connexion.</p>
            <p>Merci,<br>L'équipe The Doctor</p>
        ";

        $emailSent = sendEmail($email, $fullName, $subject, $body);

        if ($emailSent === true) {
            $GLOBALS['db']->table = "users";
            $GLOBALS['db']->data = array("password" => $password_hash);
            $GLOBALS['db']->where = 'id = ' . $user_data['id'];
            if ($GLOBALS['db']->update()) {
                echo json_encode(["state" => "true", "message" => "Un nouveau mot de passe a été envoyé à votre adresse e-mail."]);
            } else {
                echo json_encode(["state" => "false", "message" => "Erreur lors de la mise à jour du mot de passe."]);
            }
        } else {
            echo json_encode(["state" => "false", "message" => "Impossible d'envoyer l'e-mail. Veuillez contacter le support."]);
        }
    } else {
        echo json_encode(["state" => "false", "message" => "Aucun compte trouvé avec cette adresse e-mail."]);
    }
}

function adminResetPassword()
{
    if (!isset($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'admin') {
        echo json_encode(["state" => "false", "message" => "Accès non autorisé."]);
        return;
    }

    $target_user_id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
    $admin_id = $_SESSION['user']['id'];
    $admin_cabinet_id = $_SESSION['user']['cabinet_id'] ?? null;
    $is_super_admin = empty($admin_cabinet_id);

    $sql = "SELECT * FROM `users` WHERE `id` = ?";
    $stmt = $GLOBALS['db']->prepare($sql);
    $stmt->execute([$target_user_id]);
    $target_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$target_user) {
        echo json_encode(["state" => "false", "message" => "Utilisateur non trouvé."]);
        return;
    }

    if (!$is_super_admin && $target_user['cabinet_id'] != $admin_cabinet_id) {
        echo json_encode(["state" => "false", "message" => "Vous n'avez pas la permission de réinitialiser le mot de passe de cet utilisateur."]);
        return;
    }

    $newPassword = generateRandomPassword();
    $password_hash = sha1($newPassword);

    $fullName = $target_user['first_name'] . ' ' . $target_user['last_name'];
    $subject = 'Votre mot de passe a été réinitialisé par un administrateur';
    $body = "
        <h3>Réinitialisation de Mot de Passe</h3>
        <p>Bonjour {$fullName},</p>
        <p>Votre mot de passe a été réinitialisé par un administrateur. Voici vos nouvelles informations de connexion :</p>
        <p><strong>Nouveau mot de passe :</strong> {$newPassword}</p>
        <p>Nous vous recommandons de changer ce mot de passe après votre prochaine connexion.</p>
        <p>Merci,<br>L'équipe The Doctor</p>
    ";

    $emailSent = sendEmail($target_user['email'], $fullName, $subject, $body);

    if ($emailSent === true) {
        $GLOBALS['db']->table = "users";
        $GLOBALS['db']->data = array("password" => $password_hash, "modified_by" => $admin_id, "modified_at" => date('Y-m-d H:i:s'));
        $GLOBALS['db']->where = 'id = ' . $target_user_id;
        if ($GLOBALS['db']->update()) {
            echo json_encode(["state" => "true", "message" => "Le mot de passe de l'utilisateur a été réinitialisé et envoyé par e-mail."]);
        } else {
            echo json_encode(["state" => "false", "message" => "Erreur lors de la mise à jour du mot de passe dans la base de données."]);
        }
    } else {
        echo json_encode(["state" => "false", "message" => "Impossible d'envoyer l'e-mail de réinitialisation."]);
    }
}

function acountState()
{
    if (isset($_SESSION['user']) && !empty($_SESSION['user']['id'])):
        $conversationId = NULL;
        if (isset($_POST['conversation']) && !empty($_POST['conversation'])) {
            $conversationId = ((int) str_replace('conversationId-', '', ($_POST['conversation'])));
            $conversationId = is_numeric($conversationId) ? $conversationId : NULL;
        }
        $results = conversationsRoom($_SESSION['user']['id']);
        $global_data['chat_list'] = $results;
        $global_data['data']['messages'] = (($conversationId != NULL) ? messages($conversationId, (isset($_POST['last']) ? ($_POST['last']) : NULL)) : array());
        $global_data['data']['users'] = (($conversationId != NULL) ? getConversationParticipants($conversationId) : array());
        echo json_encode($global_data);
    else:
        echo json_encode(array());
    endif;
}

function chat_list($conversationId = NULL)
{
    if (isset($_SESSION['user']) && !empty($_SESSION['user']['id'])):
        $conversationId = is_numeric(str_replace('conversationId-', '', ($conversationId))) ? str_replace('conversationId-', '', ($conversationId)) : NULL;
        $results = conversationsRoom($_SESSION['user']['id']);
        $global_data['chat_list'] = $results;
        $global_data['data']['messages'] = (($conversationId != NULL) ? messages($conversationId) : array());
        $global_data['data']['users'] = (($conversationId != NULL) ? getConversationParticipants($conversationId) : array());
        return $global_data;
    endif;
    return array();
}

function conversationsRoom($user_id, $limit = 20, $offset = 0)
{
    $query = "
    SELECT DISTINCT 
        conversation.*, 
        (
            SELECT CONCAT('[', GROUP_CONCAT(
                JSON_OBJECT(
                    'userId', CASE WHEN p.my_particib = {$user_id} THEN p.id_particib ELSE p.my_particib END,
                    'user', CASE WHEN p.my_particib = {$user_id} THEN CONCAT(patient.first_name, ' ', patient.last_name) ELSE CONCAT(users.first_name, ' ', users.last_name) END,
                    'photo', CASE WHEN p.my_particib = {$user_id} THEN patient.image ELSE users.image1 END
                )
            ), ']')
            FROM participant p
            LEFT JOIN users ON users.id = p.my_particib
            LEFT JOIN patient ON patient.id = p.id_particib
            WHERE p.id_conversation = conversation.id AND (p.my_particib = {$user_id} OR p.id_particib = {$user_id})
        ) AS participants,
        (
            SELECT m.date_send 
            FROM messages m 
            WHERE m.id_conversation = conversation.id 
            ORDER BY m.date_send DESC 
            LIMIT 1
        ) AS date_sendLast_msg,
        (
            SELECT JSON_OBJECT(
                'id', m.id,
                'message', m.message,
                'type', m.type,
                'date_send', m.date_send,
                'userId', sender.id,
                'user', CONCAT(sender.first_name, ' ', sender.last_name),
                'photo', sender.image1
            )
            FROM messages m
            LEFT JOIN users AS sender ON m.id_sender = sender.id
            WHERE m.id_conversation = conversation.id
            ORDER BY m.date_send DESC
            LIMIT 1
        ) AS last_msg
    FROM conversation
    INNER JOIN participant ON conversation.id = participant.id_conversation
    WHERE 
        conversation.deleted = 0 
        AND participant.deleted = 0 
        AND (participant.my_particib = {$user_id} OR participant.id_particib = {$user_id})
    ORDER BY date_sendLast_msg DESC 
    LIMIT {$limit} OFFSET {$offset}";

    $results = $GLOBALS['db']->select($query);
    array_walk_recursive($results, function (&$item, $key) {
        if ($key == 'date_sendLast_msg' && !is_null($item))
            $item = time_ago($item);
        if ($key == 'participants')
            $item = json_decode($item, true);
        if ($key == 'last_msg') {
            if (is_array(json_decode($item, true))) {
                $item = json_decode($item, true);
                $item['message'] = ($item['message']);
            }
        }
        if ($key == 'id')
            $item = ('conversationId-' . $item);
    });
    return $results;
}

function chat()
{
    $results = ((isset($_POST['conversation']) && is_numeric(str_replace('conversationId-', '', ($_POST['conversation'])))) ? messages(str_replace('conversationId-', '', ($_POST['conversation']))) : array());
    echo json_encode($results);
}

function messages($conversationId, $messageId = NULL, $limit = 40, $offset = 0)
{
    $afterId = ($messageId != NULL && $messageId != 0 ? " AND messages.id > $messageId" : '');
    $query = "SELECT DISTINCT messages.id, messages.type, messages.message,messages.id_sender,participant.my_particib, participant.id_particib, patient.image,
        	patient.id AS lhId FROM conversation INNER JOIN participant ON participant.id_conversation = conversation.id INNER JOIN messages ON (messages.id_sender = participant.id_particib OR messages.id_sender = participant.my_particib  )  AND messages.id_conversation = conversation.id INNER JOIN patient ON patient.id = participant.id_particib WHERE participant.deleted = 0 AND conversation.id = '$conversationId' $afterId ORDER BY messages.id";
    $results = $GLOBALS['db']->select($query);
    array_walk_recursive($results, function (&$item, $key) {
        if ($key == 'id')
            $item = ($item);
        if ($key == 'message')
            $item = ($item);
    });
    return $results;
}

function getConversationParticipants($conversationId)
{
    $current_user_id = $_SESSION['user']['id'] ?? 0;
    if ($current_user_id === 0) {
        return [];
    }
    $query = "SELECT 
                    CASE 
                        WHEN p.my_particib = ? THEN p.id_particib 
                        ELSE p.my_particib 
                    END as participant_id,
                    CASE 
                        WHEN p.my_particib = ? THEN 'patient' 
                        ELSE 'user' 
                    END as participant_type
                FROM participant p
                WHERE p.id_conversation = ? 
                AND (p.my_particib = ? OR p.id_particib = ?)";
    $stmt = $GLOBALS['db']->prepare($query);
    $stmt->execute([$current_user_id, $current_user_id, $conversationId, $current_user_id, $current_user_id]);
    $participants_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $participants_details = [];
    foreach ($participants_info as $info) {
        if ($info['participant_id'] != $current_user_id) {
            if ($info['participant_type'] === 'patient') {
                $sql_details = "SELECT id, CONCAT(first_name, ' ', last_name) as full_name, image FROM patient WHERE id = ?";
            } else {
                $sql_details = "SELECT id, CONCAT(first_name, ' ', last_name) as full_name, image1 as image FROM users WHERE id = ?";
            }
            $stmt_details = $GLOBALS['db']->prepare($sql_details);
            $stmt_details->execute([$info['participant_id']]);
            $details = $stmt_details->fetch(PDO::FETCH_ASSOC);
            if ($details) {
                $participants_details[] = $details;
            }
        }
    }
    return $participants_details;
}

function is_image($path)
{
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $imageExtensions = array(
        'png',
        'jpe',
        'jpeg',
        'jpg',
        'gif',
        'bmp',
        'ico',
        'tiff',
        'tif',
    );
    if (in_array($extension, $imageExtensions)) {
        return true;
    }
    return false;
}

function is_fileExt($path)
{
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $filesExtensions = array(
        'txt',
        'json',
        'zip',
        'rar',
        'mp3',
        'pdf',
        'psd',
        'ai',
        'eps',
        'ps',
        'doc',
        'rtf',
        'xls',
        'ppt',
        'docx',
        'xlsx',
        'pptx',
    );
    if (in_array($extension, $filesExtensions)) {
        return true;
    }
    return false;
}

function send_msg()
{
    if (isset($_SESSION['user']) && !empty($_SESSION['user']['id'])) {
        if (isset($_POST['conversation']) && !empty($_POST['conversation'])) {
            $conversationId = str_replace('conversationId-', '', ($_POST['conversation']));
        } else {
            $GLOBALS['db']->table = 'conversation';
            $GLOBALS['db']->data = array("id_creator" => $_SESSION['user']['id']);
            $conversationId = $GLOBALS['db']->insert();
            if ($conversationId) {
            }
        }
        if ($conversationId && is_numeric($conversationId)) {
            $message_content = '';
            $message_type = 0;
            if (isset($_POST['file']) && $_POST['file'] === 'true' && isset($_POST['file_path'])) {
                $message_content = $_POST['file_path'];
                $message_type = is_image($message_content) ? 1 : (is_fileExt($message_content) ? 2 : 0);
            } elseif (isset($_POST['message'])) {
                $message_content = $_POST['message'];
                $message_type = 0;
            } else {
                echo json_encode(array("state" => "false", "message" => "Message content is missing"));
                return;
            }
            $data = array(
                "id_conversation" => $conversationId,
                "id_sender" => $_SESSION['user']['id'],
                "message" => $message_content,
                "type" => $message_type
            );
            $GLOBALS['db']->table = 'messages';
            $GLOBALS['db']->data = $data;
            $inserted = $GLOBALS['db']->insert();
            if ($inserted) {
                $results = messages($conversationId, (isset($_POST['last']) ? ($_POST['last']) : 0));
                echo json_encode(array("state" => "true", "data" => $results));
            } else
                echo json_encode(array("state" => "false", "message" => "une erreur s'est produite, veuillez actualiser la page et réessayer"));
        } else
            echo json_encode(array("state" => "false", "message" => "une erreur s'est produite, veuillez actualiser la page et réessayer"));
    } else
        echo json_encode(array("state" => "false", "message" => "une erreur s'est produite, veuillez actualiser la page et réessayer"));
}

function post_conversation()
{
    $user_id = $_SESSION['user']['id'] ?? 0;
    $data = array("id_creator" => $user_id);
    if (isset($_POST['name']) && !empty($_POST['name']))
        $data = array_merge($data, ["name" => $_POST['name']]);
    if (isset($_POST['csrf'])) {
        $csrf_token = customDecrypt($_POST['csrf']);
        if (!is_csrf_valid($csrf_token)) {
            echo json_encode(["state" => "false", "message" => 'The form is forged']);
            exit();
        }
    } else {
        echo json_encode(["state" => "false", "message" => 'The form is forged']);
        exit();
    }
    $GLOBALS['db']->table = 'conversation';
    $GLOBALS['db']->data = $data;
    $inserted_conversation_id = $GLOBALS['db']->insert();
    if ($inserted_conversation_id) {
        if (isset($_POST['participants']) && !empty($_POST['participants'])) {
            $subData = [];
            foreach ($_POST['participants'] as $participant_id) {
                $subData[] = [
                    'id_conversation' => $inserted_conversation_id,
                    'my_particib' => $user_id,
                    'id_particib' => $participant_id
                ];
                $subData[] = [
                    'id_conversation' => $inserted_conversation_id,
                    'my_particib' => $participant_id,
                    'id_particib' => $user_id
                ];
            }
            $GLOBALS['db']->table = 'participant';
            $GLOBALS['db']->data = $subData;
            $GLOBALS['db']->multi = true;
            $secondinsert = $GLOBALS['db']->insert();
            if ($secondinsert) {
                echo json_encode(["state" => "true", "message" => 'Added successfully']);
            } else
                echo json_encode(["state" => "false", "message" => "something went wrong while adding participants"]);
        } else
            echo json_encode(["state" => "true", "message" => 'Added successfully']);
    } else {
        echo json_encode(["state" => "false", "message" => "something went wrong while creating conversation"]);
    }
}

function subscribeToTopic($tokens, $topic)
{
    foreach ($tokens as $token) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://iid.googleapis.com/iid/v1/$token[token]/rel/topics/$topic",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer AAAAPiEtOI4:APA91bHdSiAII41N4XyIPgvWG8mSapghX1KiLWHycZsTQpcHuyqixmropj3T2Iav-6yny77FwOMbu63YPnEBlkxBCF7CizuqIOn5EW-NglsMN5S_4nFVFntjL_NKTtSP-k7HqK7Ruqoz'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
    }
    return $response;
}

function push_notification($conversationId, $messageId)
{
    $query = "SELECT DISTINCT messages.*, lhuissier.username FROM conversation INNER JOIN participant ON participant.id_conversation = conversation.id INNER JOIN messages ON messages.id_sender = participant.id_particib AND messages.id_conversation = conversation.id INNER JOIN lhuissier ON lhuissier.id = participant.id_particib WHERE participant.deleted = 0 AND conversation.id = '$conversationId'  AND messages.id = $messageId";
    $message = $GLOBALS['db']->select($query);
    $message = $message[0];
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://fcm.googleapis.com/fcm/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>
            '{
			"to": "/topics/chat_' . $conversationId . '",
			"notification": {
			
				"title": "You received a message from ' . $message['username'] . '"
			},	"data": ' . json_encode($message) . ',"content_available": true,
		}',
        CURLOPT_HTTPHEADER => array(
            'Authorization: key= AAAAPiEtOI4:APA91bHdSiAII41N4XyIPgvWG8mSapghX1KiLWHycZsTQpcHuyqixmropj3T2Iav-6yny77FwOMbu63YPnEBlkxBCF7CizuqIOn5EW-NglsMN5S_4nFVFntjL_NKTtSP-k7HqK7Ruqoz',
            'Content-Type: application/json'
        ),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}
?>