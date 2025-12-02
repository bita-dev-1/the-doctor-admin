<?php
// التحقق من الصلاحيات
if (!isset($_SESSION['user']['id'])) {
    header('location:' . SITE_URL . '/login');
    exit();
}
include_once 'header.php';

$table = 'reeducation_dossiers';
$btn_text = 'Créer le dossier';
$result = false;
$where = "";
$breadcrumb = 'Nouveau';
$is_edit_mode = (isset($id) && !empty($id));

$user_role = $_SESSION['user']['role'];
$is_read_only = ($user_role === 'doctor');

// تهيئة المتغيرات
$tech_working_days_js = '[]';
$tech_tickets_day_js = '{}';
$global_bookings_js = '{}';
$existing_rdv_dates = [];

if ($is_edit_mode) {
    $btn_text = 'Enregistrer les modifications';
    $breadcrumb = 'Modifier';
    $where = array("column" => "id", "val" => $id);
    $result = dataById($where, $table)[0] ?? false;

    if ($result) {
        // جلب البيانات الإضافية
        $count_sql = "SELECT COUNT(*) as total FROM reeducation_sessions WHERE dossier_id = " . intval($id) . " AND status = 'completed'";
        $count_res = $GLOBALS['db']->select($count_sql);
        $result['sessions_completed'] = $count_res[0]['total'] ?? 0;

        if (!empty($result['technician_id'])) {
            $tech_id = intval($result['technician_id']);
            $tech_data = $GLOBALS['db']->select("SELECT travel_hours, tickets_day FROM users WHERE id = $tech_id")[0] ?? null;

            if ($tech_data) {
                if (!empty($tech_data['travel_hours'])) {
                    $schedule = json_decode($tech_data['travel_hours'], true);
                    $day_map = ["Dimanche" => 0, "Lundi" => 1, "Mardi" => 2, "Mercredi" => 3, "Jeudi" => 4, "Vendredi" => 5, "Samedi" => 6];
                    $working_days_indices = [];
                    if (is_array($schedule)) {
                        foreach ($schedule as $day_name => $hours) {
                            if (!empty($hours['from']) && !empty($hours['to'])) {
                                if (isset($day_map[$day_name]))
                                    $working_days_indices[] = $day_map[$day_name];
                            }
                        }
                    }
                    $tech_working_days_js = json_encode($working_days_indices);
                }
                if (!empty($tech_data['tickets_day'])) {
                    $decoded_tickets = json_decode($tech_data['tickets_day'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_tickets)) {
                        $tech_tickets_day_js = json_encode($decoded_tickets);
                    }
                }
                $sql_bookings = "SELECT DATE(date) as rdv_date, COUNT(*) as total FROM rdv WHERE doctor_id = $tech_id AND deleted = 0 AND state != 3 AND date >= CURDATE() GROUP BY DATE(date)";
                $res_bookings = $GLOBALS['db']->select($sql_bookings);
                $bookings_map = [];
                foreach ($res_bookings as $row) {
                    $bookings_map[$row['rdv_date']] = $row['total'];
                }
                $global_bookings_js = json_encode($bookings_map);
            }
        }
        $sql_dates = "SELECT DATE(r.date) as simple_date FROM rdv r INNER JOIN reeducation_sessions rs ON r.reeducation_session_id = rs.id WHERE rs.dossier_id = " . intval($id) . " AND r.deleted = 0";
        $res_dates = $GLOBALS['db']->select($sql_dates);
        if (!empty($res_dates)) {
            foreach ($res_dates as $row) {
                $existing_rdv_dates[] = $row['simple_date'];
            }
        }
    }
} else {
    if ($is_read_only) {
        echo "<script>window.location.href='" . SITE_URL . "/reeducation';</script>";
        exit;
    }
    // افتراضياً كل الأيام متاحة عند الإنشاء حتى يتم الحفظ
    $tech_working_days_js = '[0,1,2,3,4,5,6]';
}

$readonly_attr = $is_read_only ? "disabled='disabled'" : "";
$pricing_readonly = "readonly='readonly'";
$calendarCSS = SITE_URL . '/app-assets/vendors/css/calendars/fullcalendar.min.css';
?>
<link rel="stylesheet" type="text/css" href="<?= $calendarCSS ?>">

<style>
    /* --- Base Styles --- */
    .card-modern {
        border: none;
        box-shadow: 0 4px 24px 0 rgba(34, 41, 47, 0.05);
        border-radius: 0.75rem;
        transition: all 0.3s ease-in-out;
    }

    .card-modern:hover {
        box-shadow: 0 6px 30px 0 rgba(34, 41, 47, 0.1);
    }

    .section-header {
        display: flex;
        align-items: center;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid #ebe9f1;
        padding-bottom: 0.75rem;
    }

    .section-icon {
        width: 36px;
        height: 36px;
        background: rgba(115, 103, 240, 0.12);
        color: #7367f0;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
    }

    .section-title {
        font-weight: 600;
        font-size: 1.1rem;
        color: #5e5873;
        margin: 0;
    }

    .price-display-box {
        background: #f8f8f8;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        border: 1px dashed #d8d6de;
    }

    .price-amount {
        font-size: 1.8rem;
        font-weight: 800;
        color: #28c76f;
        display: block;
    }

    .form-label {
        font-weight: 500;
        color: #5e5873;
    }

    .planning-calendar-wrapper {
        background: #fff;
        border: 1px solid #ebe9f1;
        border-radius: 8px;
        padding: 10px;
    }

    /* --- Calendar Tweaks --- */
    .fc-toolbar-title {
        font-size: 1rem !important;
    }

    .fc-button {
        padding: 0.2rem 0.5rem !important;
        font-size: 0.8rem !important;
    }

    .fc-daygrid-day-frame {
        min-height: 40px !important;
    }

    /* Selection Styles */
    td.fc-daygrid-day.fc-day-selected {
        background-color: #7367f0 !important;
    }

    td.fc-daygrid-day.fc-day-selected .fc-daygrid-day-number {
        color: white !important;
    }

    .fc-day-full {
        background-color: #ffedeb !important;
        color: #ea5455 !important;
        cursor: not-allowed;
    }

    /* Light Mode Non-Working Day Pattern */
    .fc-non-working-day {
        background-color: #f6f6f6 !important;
        opacity: 0.5;
        cursor: not-allowed;
        background-image: repeating-linear-gradient(45deg, transparent, transparent 5px, #e0e0e0 5px, #e0e0e0 6px);
    }

    .slots-badge {
        font-size: 0.65rem;
        position: absolute;
        bottom: 1px;
        right: 1px;
        padding: 1px 3px;
        border-radius: 3px;
        background: #fff;
        border: 1px solid #eee;
    }

    /* ==========================================================================
       DARK MODE SUPPORT (html.dark-layout)
       ========================================================================== */

    html.dark-layout .card-modern {
        background-color: #283046;
        box-shadow: 0 4px 24px 0 rgba(0, 0, 0, 0.24);
    }

    html.dark-layout .section-header {
        border-bottom-color: #3b4253;
    }

    html.dark-layout .section-title {
        color: #d0d2d6;
    }

    html.dark-layout .form-label {
        color: #d0d2d6;
    }

    html.dark-layout .price-display-box {
        background-color: #161d31;
        border-color: #3b4253;
    }

    html.dark-layout .price-amount input {
        color: #28c76f !important;
        /* Keep green for price */
    }

    html.dark-layout .planning-calendar-wrapper {
        background-color: #283046;
        border-color: #3b4253;
    }

    /* Dark Mode Calendar Overrides */
    html.dark-layout .fc-theme-standard td,
    html.dark-layout .fc-theme-standard th {
        border-color: #3b4253;
    }

    html.dark-layout .fc-toolbar-title {
        color: #d0d2d6;
    }

    html.dark-layout .fc-daygrid-day-number {
        color: #b4b7bd;
    }

    html.dark-layout .fc-col-header-cell-cushion {
        color: #d0d2d6;
    }

    html.dark-layout .fc-button-primary {
        background-color: #7367f0;
        border-color: #7367f0;
    }

    html.dark-layout .fc-day-full {
        background-color: #462e2e !important;
        /* Dark Red */
        color: #ff6b6b !important;
    }

    html.dark-layout .fc-non-working-day {
        background-color: #161d31 !important;
        /* Darker background */
        opacity: 0.4;
        background-image: repeating-linear-gradient(45deg, transparent, transparent 5px, #283046 5px, #283046 6px);
    }

    html.dark-layout .slots-badge {
        background-color: #343d55;
        border-color: #3b4253;
        color: #d0d2d6;
    }

    /* Input & Select Backgrounds in Dark Mode */
    html.dark-layout .form-control,
    html.dark-layout .form-select {
        background-color: #283046;
        border-color: #3b4253;
        color: #d0d2d6;
    }

    html.dark-layout .form-control:focus {
        background-color: #283046;
        border-color: #7367f0;
    }

    html.dark-layout .input-group-text {
        background-color: #3b4253;
        border-color: #3b4253;
        color: #d0d2d6;
    }
</style>

<div class="app-content content">
    <div class="content-wrapper p-0">
        <div class="content-header row">
            <div class="content-header-left col-md-9 col-12 mb-2">
                <div class="row breadcrumbs-top">
                    <div class="col-12">
                        <h2 class="content-header-title float-start mb-0">
                            <i data-feather="folder-plus" class="font-medium-5 me-1"></i><?= $breadcrumb; ?> Dossier
                        </h2>
                    </div>
                </div>
            </div>
            <?php if ($is_edit_mode && $result['status'] === 'active' && !$is_read_only): ?>
                <div class="content-header-right text-md-end col-md-3 col-12 d-md-block d-none">
                    <button type="button" class="btn btn-success shadow-sm" id="complete-dossier-btn">
                        <i data-feather="check-circle" class="me-50"></i> Clôturer le dossier
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <div class="content-body">
            <!-- Progress Bar (Edit Mode Only) -->
            <?php if ($is_edit_mode): ?>
                <div class="card card-modern mb-2">
                    <div class="card-body p-2">
                        <?php
                        $prescribed = (int) ($result['sessions_prescribed'] ?? 0);
                        $completed = (int) ($result['sessions_completed'] ?? 0);
                        $progress_percentage = $prescribed > 0 ? ($completed / $prescribed) * 100 : 0;
                        ?>
                        <div class="d-flex justify-content-between mb-50">
                            <span class="fw-bold text-primary"><i data-feather="activity" class="me-50"></i>
                                Progression</span>
                            <span class="badge bg-light-primary"><?= $completed ?> / <?= $prescribed ?> Séances</span>
                        </div>
                        <div class="progress progress-bar-primary" style="height: 15px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                style="width: <?= min($progress_percentage, 100) ?>%">
                                <?= round($progress_percentage) ?>%
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tabs -->
            <ul class="nav nav-pills mb-2" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="details-tab" data-bs-toggle="tab" href="#details" role="tab">
                        <i data-feather="file-text" class="me-50"></i> Configuration du Dossier
                    </a>
                </li>
                <?php if ($is_edit_mode): ?>
                    <li class="nav-item">
                        <a class="nav-link" id="history-tab" data-bs-toggle="tab" href="#history" role="tab">
                            <i data-feather="clock" class="me-50"></i> Historique & Paiements
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="tab-content">
                <div class="tab-pane active" id="details" role="tabpanel">
                    <form class="codexForm" method="post" role="form" data-express="<?= customEncryption($table); ?>"
                        data-update="<?= customEncryption(json_encode($where)); ?>">
                        <?php set_csrf() ?>

                        <div class="row match-height">

                            <!-- LEFT COLUMN: Patient & Financials -->
                            <div class="col-lg-8 col-12">
                                <!-- 1. Patient Info -->
                                <div class="card card-modern h-100">
                                    <div class="card-body">
                                        <div class="section-header">
                                            <div class="section-icon"><i data-feather="user"></i></div>
                                            <h4 class="section-title">Informations Patient & Orientation</h4>
                                        </div>

                                        <div class="row">
                                            <div class="col-12 mb-1">
                                                <label class="form-label">Patient</label>
                                                <div class="input-group">
                                                    <div style="flex-grow: 1;">
                                                        <?php
                                                        draw_select([
                                                            "label" => "",
                                                            "name_id" => "{$table}__patient_id",
                                                            "placeholder" => "Rechercher un patient...",
                                                            "attr" => $readonly_attr,
                                                            "serverSide" => ["table" => "patient", "value" => "id", "text" => ["first_name", "last_name"], "selected" => $result['patient_id'] ?? null, "where" => "deleted=0"]
                                                        ]);
                                                        ?>
                                                    </div>
                                                    <?php if (!$is_read_only): ?>
                                                        <button class="btn btn-outline-primary" type="button"
                                                            id="btn-add-quick-patient" title="Nouveau">
                                                            <i data-feather="plus"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="col-md-6 col-12 mb-1">
                                                <?php draw_select([
                                                    "label" => "Type de Dossier",
                                                    "name_id" => "{$table}__dossier_type",
                                                    "attr" => $readonly_attr,
                                                    "clientSideSelected" => $result['dossier_type'] ?? 'externe',
                                                    "clientSide" => [["option_text" => "Externe (Ordonnance)", "value" => "externe"], ["option_text" => "Interne (Consultation)", "value" => "interne"]]
                                                ]); ?>
                                            </div>

                                            <div class="col-md-6 col-12 mb-1 external-only-field">
                                                <?php draw_input(["label" => "Médecin Prescripteur", "type" => "text", "name_id" => "{$table}__oriented_by", "attr" => $readonly_attr, "placeholder" => "Nom du médecin...", "value" => $result['oriented_by'] ?? '']); ?>
                                            </div>

                                            <div class="col-12 mb-1 external-only-field">
                                                <label class="form-label">Lettre d'Orientation</label>
                                                <?php if (!$is_read_only): ?>
                                                    <div class="codexFileUp">
                                                        <input type="file" class="form-control codexInputFile"
                                                            id="medical_letter_input" accept=".pdf, .png, .jpg, .jpeg">
                                                        <input type="hidden" class="codexFileData"
                                                            name="<?= $table; ?>__medical_letter_path"
                                                            value="<?= $result['medical_letter_path'] ?? '' ?>">
                                                        <div class="mt-1 codexMultiPreviewImage">
                                                            <?php if (!empty($result['medical_letter_path'])): ?>
                                                                <div class="badge bg-light-secondary p-1"><i
                                                                        data-feather="paperclip"></i> Document joint</div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php elseif (!empty($result['medical_letter_path'])): ?>
                                                    <div><a href="<?= $result['medical_letter_path'] ?>" target="_blank"
                                                            class="btn btn-sm btn-outline-secondary"><i
                                                                data-feather="eye"></i> Voir le document</a></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="section-header mt-2">
                                            <div class="section-icon"
                                                style="background: rgba(40, 199, 111, 0.12); color: #28c76f;"><i
                                                    data-feather="dollar-sign"></i></div>
                                            <h4 class="section-title">Tarification & Service</h4>
                                        </div>

                                        <div class="row">
                                            <div class="col-12 mb-1">
                                                <?php
                                                draw_select([
                                                    "label" => "Service (Acte)",
                                                    "name_id" => "{$table}__reeducation_type_id",
                                                    "attr" => "required $readonly_attr",
                                                    "placeholder" => "Sélectionnez le soin...",
                                                    "serverSide" => [
                                                        "table" => "cabinet_services",
                                                        "join" => [(object) ["type" => "JOIN", "table" => "reeducation_types", "condition" => "cabinet_services.reeducation_type_id=reeducation_types.id"]],
                                                        "value" => "reeducation_types.id",
                                                        "text" => ["IF(cabinet_services.custom_name IS NOT NULL AND cabinet_services.custom_name != '', cabinet_services.custom_name, reeducation_types.name)"],
                                                        "selected" => $result['reeducation_type_id'] ?? null,
                                                        "where" => "cabinet_services.cabinet_id = " . intval($_SESSION['user']['cabinet_id']) . " AND cabinet_services.deleted=0"
                                                    ]
                                                ]);
                                                ?>
                                            </div>

                                            <div class="col-md-4 col-12 mb-1">
                                                <?php draw_input(["label" => "Nombre Séances", "type" => "number", "name_id" => "{$table}__sessions_prescribed", "attr" => "required min='1' $readonly_attr", "value" => $result['sessions_prescribed'] ?? 1]); ?>
                                            </div>

                                            <div class="col-md-4 col-12 mb-1">
                                                <label class="form-label">Remise (DA)</label>
                                                <input type="number" name="<?= $table; ?>__discount_amount"
                                                    id="<?= $table; ?>__discount_amount" class="form-control"
                                                    value="<?= $result['discount_amount'] ?? '0.00' ?>" step="0.01"
                                                    <?= $readonly_attr ?>>
                                            </div>

                                            <div class="col-md-4 col-12 mb-1">
                                                <label class="form-label">Mode Paiement</label>
                                                <input type="text" class="form-control bg-light"
                                                    id="display_payment_mode" readonly
                                                    value="<?= ($result['payment_mode'] ?? '') === 'package' ? 'Forfait' : 'Par Séance' ?>">
                                                <input type="hidden" name="<?= $table ?>__payment_mode"
                                                    id="<?= $table ?>__payment_mode"
                                                    value="<?= $result['payment_mode'] ?? '' ?>">
                                            </div>

                                            <div class="col-12 mt-1">
                                                <div class="price-display-box">
                                                    <span class="text-muted font-small-3">Total à Payer</span>
                                                    <span class="price-amount">
                                                        <input type="number" name="<?= $table; ?>__price"
                                                            id="<?= $table; ?>__price"
                                                            class="form-control border-0 bg-transparent text-center p-0 fw-bolder text-success"
                                                            style="font-size: 1.8rem;"
                                                            value="<?= $result['price'] ?? '0.00' ?>" step="0.01"
                                                            <?= $pricing_readonly ?>>
                                                    </span>
                                                    <span class="text-muted">DZD</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- RIGHT COLUMN: Technician & Planning -->
                            <div class="col-lg-4 col-12">
                                <div class="card card-modern h-100">
                                    <div class="card-body">
                                        <div class="section-header">
                                            <div class="section-icon"
                                                style="background: rgba(0, 207, 232, 0.12); color: #00cfe8;"><i
                                                    data-feather="calendar"></i></div>
                                            <h4 class="section-title">Planification</h4>
                                        </div>

                                        <div class="mb-1">
                                            <?php draw_select(["label" => "Technicien Assigné", "name_id" => "{$table}__technician_id", "attr" => $readonly_attr, "placeholder" => "Choisir...", "serverSide" => ["table" => "users", "value" => "id", "text" => ["first_name", "last_name"], "selected" => $result['technician_id'] ?? null, "where" => "role='doctor' AND deleted=0 AND cabinet_id=" . intval($_SESSION['user']['cabinet_id'])]]); ?>
                                        </div>

                                        <!-- Hidden Commission Field -->
                                        <input type="hidden" name="<?= $table; ?>__technician_percentage"
                                            id="<?= $table; ?>__technician_percentage"
                                            value="<?= $result['technician_percentage'] ?? '0' ?>">

                                        <?php if (!$is_read_only): ?>
                                            <div class="planning-calendar-wrapper mb-2">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <button type="button"
                                                        class="btn btn-icon btn-flat-secondary btn-sm rounded-circle"
                                                        id="cal-prev"><i data-feather="chevron-left"></i></button>
                                                    <span class="fw-bolder font-small-3">Sélection Dates</span>
                                                    <button type="button"
                                                        class="btn btn-icon btn-flat-secondary btn-sm rounded-circle"
                                                        id="cal-next"><i data-feather="chevron-right"></i></button>
                                                </div>

                                                <div id="calendar-top" class="mb-1"></div>
                                                <div class="calendar-divider"></div>
                                                <div id="calendar-bottom"></div>

                                                <!-- Hidden input for dates -->
                                                <input type="hidden" name="initial_sessions_dates"
                                                    id="initial_sessions_dates" value="[]">
                                            </div>

                                            <div id="day-info-alert"
                                                class="alert alert-primary p-50 mb-1 d-none font-small-2" role="alert">
                                                <i data-feather="info" class="me-50"></i> <span id="day-info-text"></span>
                                            </div>

                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="fw-bold font-small-3">Sélection: <span id="selected-count"
                                                        class="text-primary">0</span> / <span
                                                        id="max-sessions-display"><?= $result['sessions_prescribed'] ?? 1 ?></span></span>
                                                <button type="button" class="btn btn-sm btn-flat-danger"
                                                    id="clear-selection-btn">Effacer</button>
                                            </div>

                                            <div class="d-grid gap-1">
                                                <?php if ($is_edit_mode): ?>
                                                    <button type="button" class="btn btn-outline-info"
                                                        id="generate-sessions-btn" data-dossier-id="<?= $id ?>">
                                                        <i data-feather="refresh-cw" class="me-50"></i> Générer Séances
                                                    </button>
                                                <?php endif; ?>

                                                <?php draw_button(["text" => $btn_text, "type" => "submit", "name_id" => "submit", "class" => "btn-primary btn-lg waves-effect waves-float waves-light"]); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>

                <!-- History Tab (Same as before) -->
                <?php if ($is_edit_mode): ?>
                    <div class="tab-pane" id="history" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-7 col-12">
                                <div class="card card-modern">
                                    <div class="card-header">
                                        <h4 class="card-title">Historique des Séances</h4>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Statut</th>
                                                    <th>Durée</th>
                                                    <th>Note</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $history_sessions = $GLOBALS['db']->select("SELECT rs.*, r.date as rdv_date FROM reeducation_sessions rs LEFT JOIN rdv r ON rs.rdv_id = r.id WHERE rs.dossier_id = $id ORDER BY r.date ASC");
                                                if (!empty($history_sessions)) {
                                                    foreach ($history_sessions as $sess) {
                                                        $status_badge = match ($sess['status']) {
                                                            'completed' => '<span class="badge badge-light-success">Complété</span>',
                                                            'absent' => '<span class="badge badge-light-danger">Absent</span>',
                                                            default => '<span class="badge badge-light-secondary">Planifié</span>'
                                                        };
                                                        echo "<tr><td>" . ($sess['rdv_date'] ?? '-') . "</td><td>{$status_badge}</td><td>" . ($sess['duration'] ? $sess['duration'] . ' min' : '-') . "</td><td title='" . htmlspecialchars($sess['observations'] ?? '') . "'>" . htmlspecialchars(substr($sess['observations'] ?? '', 0, 20)) . "</td></tr>";
                                                    }
                                                } else {
                                                    echo '<tr><td colspan="4" class="text-center text-muted p-2">Aucune séance</td></tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php if (!$is_read_only || $user_role === 'doctor'): ?>
                                <div class="col-lg-5 col-12">
                                    <div class="card card-modern">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h4 class="card-title">Paiements</h4>
                                            <a href="<?= SITE_URL ?>/caisse?dossier_id=<?= $id ?>"
                                                class="btn btn-sm btn-primary"><i data-feather="dollar-sign"></i> Gérer</a>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Montant</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $history_payments = $GLOBALS['db']->select("SELECT * FROM caisse_transactions WHERE dossier_id = $id ORDER BY id DESC LIMIT 5");
                                                    if (!empty($history_payments)) {
                                                        foreach ($history_payments as $pay) {
                                                            echo "<tr><td>" . date('d/m/Y', strtotime($pay['payment_date'])) . "</td><td class='fw-bold text-success'>" . number_format($pay['amount_paid'], 2) . " DA</td></tr>";
                                                        }
                                                    } else {
                                                        echo '<tr><td colspan="2" class="text-center text-muted p-2">Aucun paiement</td></tr>';
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Add Patient Modal -->
<div class="modal fade" id="quickAddPatientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-transparent">
                <h5 class="modal-title">Nouveau Patient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickAddPatientForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-6 mb-1"><label class="form-label">Prénom *</label><input type="text"
                                class="form-control" name="first_name" required></div>
                        <div class="col-6 mb-1"><label class="form-label">Nom *</label><input type="text"
                                class="form-control" name="last_name" required></div>
                        <div class="col-12 mb-1"><label class="form-label">Téléphone *</label><input type="text"
                                class="form-control" name="phone" required></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once 'foot.php'; ?>
<script src="<?= SITE_URL; ?>/app-assets/vendors/js/calendar/fullcalendar.min.js"></script>

<script>
    $(document).ready(function () {
        if (feather) { feather.replace({ width: 14, height: 14 }); }

        <?php if (!$is_read_only): ?>
            $('.codexForm').validate({
                rules: {
                    '<?= $table; ?>__patient_id': { required: true },
                    '<?= $table; ?>__sessions_prescribed': { required: true, min: 1 },
                    '<?= $table; ?>__price': { required: true, number: true },
                    '<?= $table; ?>__technician_id': { required: true },
                }
            });

            function toggleOrientationFields() {
                var type = $('#<?= $table; ?>__dossier_type').val();
                if (type === 'interne') { $('.external-only-field').slideUp(); } else { $('.external-only-field').slideDown(); }
            }
            $('#<?= $table; ?>__dossier_type').on('change', toggleOrientationFields);
            toggleOrientationFields();

            var isAutoUpdating = false;
            function calculatePrice(forceUpdateCount = false) {
                var typeId = $('#<?= $table; ?>__reeducation_type_id').val();
                var countInput = $('#<?= $table; ?>__sessions_prescribed');
                var count = countInput.val();

                if (typeId && !isAutoUpdating) {
                    var safeCount = (count && count > 0) ? count : 1;
                    $.ajax({
                        url: '<?= SITE_URL; ?>/handlers', type: 'POST',
                        data: { method: 'get_service_pricing_details', reeducation_type_id: typeId, sessions_count: safeCount },
                        dataType: 'json',
                        success: function (res) {
                            if (res.state === 'true') {
                                var data = res.data;
                                var paymentModel = data.payment_model;
                                var isPackage = (paymentModel === 'package');
                                $('#<?= $table; ?>__price').val(data.total_price);
                                $('#<?= $table; ?>__technician_percentage').val(data.commission_total);
                                $('#<?= $table; ?>__payment_mode').val(paymentModel);
                                $('#display_payment_mode').val(isPackage ? 'Forfait Global' : 'Par Séance');

                                if (forceUpdateCount && isPackage && data.package_capacity > 0) {
                                    var currentVal = parseInt(countInput.val());
                                    var newVal = parseInt(data.package_capacity);
                                    if (currentVal !== newVal) {
                                        isAutoUpdating = true; countInput.val(newVal);
                                        setTimeout(function () { isAutoUpdating = false; calculatePrice(false); }, 100);
                                    }
                                }
                            }
                        }
                    });
                }
            }
            $('#<?= $table; ?>__reeducation_type_id').on('select2:select', function () { calculatePrice(true); });
            $('#<?= $table; ?>__sessions_prescribed').on('input change', function () { calculatePrice(false); $('#max-sessions-display').text($(this).val()); });

            $('#btn-add-quick-patient').on('click', function () { $('#quickAddPatientModal').modal('show'); });
            $('#quickAddPatientForm').on('submit', function (e) {
                e.preventDefault();
                var btn = $(this).find('button[type="submit"]');
                btn.attr('disabled', true).text('...');
                $.ajax({
                    url: '<?= SITE_URL; ?>/handlers', type: 'POST',
                    data: $(this).serialize() + '&method=quick_add_patient', dataType: 'json',
                    success: function (res) {
                        if (res.state === 'true') {
                            var newOption = new Option(res.data.text, res.data.id, true, true);
                            $('#<?= $table; ?>__patient_id').append(newOption).trigger('change');
                            $('#quickAddPatientModal').modal('hide'); $('#quickAddPatientForm')[0].reset();
                            Swal.fire({ icon: 'success', title: 'Succès', text: 'Patient ajouté', timer: 1500, showConfirmButton: false });
                        } else { Swal.fire('Erreur', res.message, 'error'); }
                    }, complete: function () { btn.attr('disabled', false).text('Enregistrer'); }
                });
            });

            // --- CALENDAR LOGIC ---
            var selectedDates = <?= !empty($existing_rdv_dates) ? json_encode($existing_rdv_dates) : '[]' ?>;
            var workingDays = <?= !empty($tech_working_days_js) ? $tech_working_days_js : '[]' ?>;
            var ticketsPerDay = <?= !empty($tech_tickets_day_js) ? $tech_tickets_day_js : '{}' ?>;
            var globalBookings = <?= !empty($global_bookings_js) ? $global_bookings_js : '{}' ?>;
            var dayNamesMap = ["Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi"];

            var calendarTopEl = document.getElementById('calendar-top');
            var calendarBottomEl = document.getElementById('calendar-bottom');
            var calendarTop, calendarBottom;

            function createCalendarConfig(initialDate) {
                return {
                    initialView: 'dayGridMonth',
                    initialDate: initialDate,
                    headerToolbar: { left: '', center: 'title', right: '' },
                    contentHeight: 'auto',
                    locale: 'fr',
                    firstDay: 6,
                    selectable: false,
                    businessHours: { daysOfWeek: workingDays, startTime: '08:00', endTime: '18:00' },
                    dayCellDidMount: function (info) {
                        var dateStr = info.el.getAttribute('data-date');
                        var dayIndex = info.date.getDay();
                        var dayName = dayNamesMap[dayIndex];

                        if (!workingDays.includes(dayIndex)) {
                            info.el.classList.add('fc-non-working-day');
                            return;
                        }

                        var limit = parseInt(ticketsPerDay[dayName]) || 999;
                        var booked = globalBookings[dateStr] || 0;
                        var available = limit - booked;

                        if (limit < 999) {
                            var badgeClass = available > 0 ? 'text-primary' : 'text-danger';
                            var counterHtml = '<div class="slots-badge ' + badgeClass + '">' + available + '</div>';
                            info.el.querySelector('.fc-daygrid-day-frame').insertAdjacentHTML('beforeend', counterHtml);
                        }

                        var isSelectedHere = selectedDates.includes(dateStr);
                        if (available <= 0 && !isSelectedHere) { info.el.classList.add('fc-day-full'); }
                        if (isSelectedHere) { info.el.classList.add('fc-day-selected'); }
                    },
                    dateClick: function (info) { handleDateClick(info); },
                    events: function (fetchInfo, successCallback) { successCallback([]); }
                };
            }

            function handleDateClick(info) {
                var dateStr = info.dateStr;
                var dateObj = new Date(dateStr + 'T00:00:00');
                var dayIndex = dateObj.getDay();
                var dayName = dayNamesMap[dayIndex];

                var limit = parseInt(ticketsPerDay[dayName]) || 999;
                var booked = globalBookings[dateStr] || 0;
                var available = limit - booked;

                var infoText = "Le <b>" + dateStr + "</b> : <b>" + available + "</b> places restantes";
                $('#day-info-text').html(infoText);
                $('#day-info-alert').removeClass('d-none');

                var today = new Date(); today.setHours(0, 0, 0, 0);
                if (dateObj < today) { Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, icon: 'warning', title: 'Date passée.' }); return; }
                if (!workingDays.includes(dayIndex)) { Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, icon: 'warning', title: 'Jour non travaillé.' }); return; }

                var isSelected = selectedDates.includes(dateStr);
                if (!isSelected && booked >= limit) { Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, icon: 'error', title: 'Journée complète.' }); return; }

                var maxSessions = parseInt($('#<?= $table; ?>__sessions_prescribed').val()) || 0;

                if (isSelected) {
                    selectedDates = selectedDates.filter(d => d !== dateStr);
                } else {
                    if (selectedDates.length >= maxSessions) { Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, icon: 'error', title: 'Limite atteinte.' }); return; }
                    selectedDates.push(dateStr);
                }

                var cells = document.querySelectorAll('[data-date="' + dateStr + '"]');
                cells.forEach(function (cell) {
                    if (selectedDates.includes(dateStr)) cell.classList.add('fc-day-selected');
                    else cell.classList.remove('fc-day-selected');
                });

                updateSelectedCount();
                $('#initial_sessions_dates').val(JSON.stringify(selectedDates));
            }

            if (calendarTopEl && calendarBottomEl) {
                var today = new Date();
                var nextMonth = new Date(today.getFullYear(), today.getMonth() + 1, 1);
                calendarTop = new FullCalendar.Calendar(calendarTopEl, createCalendarConfig(today));
                calendarTop.render();
                calendarBottom = new FullCalendar.Calendar(calendarBottomEl, createCalendarConfig(nextMonth));
                calendarBottom.render();
                updateSelectedCount();
            }

            $('#cal-next').on('click', function () { calendarTop.next(); calendarBottom.next(); });
            $('#cal-prev').on('click', function () { calendarTop.prev(); calendarBottom.prev(); });
            function updateSelectedCount() { $('#selected-count').text(selectedDates.length); }
            $('#clear-selection-btn').on('click', function () {
                selectedDates = [];
                $('.fc-day-selected').removeClass('fc-day-selected');
                updateSelectedCount();
                $('#initial_sessions_dates').val('[]');
            });

            // Custom Submit for Creation
            <?php if (!$is_edit_mode): ?>
                $('.codexForm').on('submit', function (e) {
                    e.preventDefault();
                    var form = $(this);
                    if (!form.valid()) return;
                    var btn = form.find('button[type="submit"]');
                    var originalText = btn.text();
                    btn.attr('disabled', 'disabled').html('<span class="spinner-border spinner-border-sm"></span>');
                    var formData = form.serializeArray();
                    var dates = $('#initial_sessions_dates').val();
                    $.ajax({
                        url: '<?= SITE_URL; ?>/handlers', type: 'POST',
                        data: { method: 'postReeducationDossier', data: formData, initial_sessions_dates: dates },
                        dataType: 'json',
                        success: function (response) {
                            if (response.state === "true") {
                                Swal.fire({ title: 'Succès', text: response.message, icon: 'success', confirmButtonText: 'OK', customClass: { confirmButton: 'btn btn-success' } }).then(() => { window.location.href = '<?= SITE_URL; ?>/reeducation'; });
                            } else { Swal.fire('Erreur', response.message, 'error'); btn.removeAttr('disabled').text(originalText); }
                        },
                        error: function () { Swal.fire('Erreur', 'Erreur serveur.', 'error'); btn.removeAttr('disabled').text(originalText); }
                    });
                });
            <?php endif; ?>

            // Generate Button (Edit Mode)
            <?php if ($is_edit_mode): ?>
                $('#generate-sessions-btn').on('click', function (e) {
                    e.preventDefault();
                    var btn = $(this);
                    var dossierId = btn.data('dossier-id');
                    if (selectedDates.length === 0) { Swal.fire({ title: 'Attention', text: 'Sélectionnez au moins une date.', icon: 'warning', customClass: { confirmButton: 'btn btn-primary' } }); return; }
                    Swal.fire({
                        title: 'Confirmer ?', text: "Générer " + selectedDates.length + " séances ?", icon: 'info', showCancelButton: true, confirmButtonText: 'Oui', cancelButtonText: 'Non', customClass: { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-outline-danger ms-1' }
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: '<?= SITE_URL; ?>/handlers', type: 'POST',
                                data: { method: 'generate_sessions_manual', dossier_id: dossierId, dates: selectedDates },
                                dataType: 'json',
                                beforeSend: function () { btn.attr('disabled', 'disabled').html('...'); },
                                success: function (response) {
                                    if (response.state === "true") { Swal.fire({ title: 'Succès', text: response.message, icon: 'success', customClass: { confirmButton: 'btn btn-success' } }).then(() => location.reload()); }
                                    else { Swal.fire({ title: 'Erreur', text: response.message, icon: 'error', customClass: { confirmButton: 'btn btn-danger' } }); }
                                },
                                complete: function () { btn.removeAttr('disabled').html('<i data-feather="refresh-cw"></i> Générer'); }
                            });
                        }
                    });
                });
                $('#complete-dossier-btn').on('click', function (e) {
                    e.preventDefault();
                    Swal.fire({ title: 'Clôturer le dossier ?', text: "Statut -> Terminé.", icon: 'warning', showCancelButton: true, confirmButtonText: 'Oui', cancelButtonText: 'Non' }).then(function (result) {
                        if (result.value) {
                            $.ajax({
                                url: '<?= SITE_URL; ?>/data', type: 'POST',
                                data: { method: 'updatForm', class: '<?= customEncryption($table); ?>', object: '<?= customEncryption(json_encode($where)); ?>', csrf: $('input[name="csrf"]').val(), data: [{ name: '<?= $table; ?>__status', value: 'completed' }] },
                                dataType: 'json',
                                success: function (response) { if (response.state === "true") { Swal.fire('Succès!', 'Dossier clôturé.', 'success').then(() => location.reload()); } }
                            });
                        }
                    });
                });
            <?php endif; ?>
        <?php endif; ?>

        // Observe Theme Change (Light/Dark) to update Calendar colors dynamically
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.attributeName === "class") {
                    // Force calendar render to apply new CSS variables if needed
                    if (calendarTop) calendarTop.render();
                    if (calendarBottom) calendarBottom.render();
                }
            });
        });
        observer.observe(document.documentElement, { attributes: true });
    });
</script>