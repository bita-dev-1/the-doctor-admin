<?php
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

// تهيئة المتغيرات بقيم افتراضية آمنة
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
        // 1. جلب عدد الحصص المكتملة
        $count_sql = "SELECT COUNT(*) as total FROM reeducation_sessions WHERE dossier_id = " . intval($id) . " AND status = 'completed'";
        $count_res = $GLOBALS['db']->select($count_sql);
        $result['sessions_completed'] = $count_res[0]['total'] ?? 0;

        // 2. جلب جدول عمل التقني + الحد الأقصى للتذاكر
        if (!empty($result['technician_id'])) {
            $tech_id = intval($result['technician_id']);
            $tech_data = $GLOBALS['db']->select("SELECT travel_hours, tickets_day FROM users WHERE id = $tech_id")[0] ?? null;

            if ($tech_data) {
                // أيام العمل
                if (!empty($tech_data['travel_hours'])) {
                    $schedule = json_decode($tech_data['travel_hours'], true);
                    $day_map = ["Dimanche" => 0, "Lundi" => 1, "Mardi" => 2, "Mercredi" => 3, "Jeudi" => 4, "Vendredi" => 5, "Samedi" => 6];
                    $working_days_indices = [];
                    if (is_array($schedule)) {
                        foreach ($schedule as $day_name => $hours) {
                            if (!empty($hours['from']) && !empty($hours['to'])) {
                                if (isset($day_map[$day_name])) {
                                    $working_days_indices[] = $day_map[$day_name];
                                }
                            }
                        }
                    }
                    $tech_working_days_js = json_encode($working_days_indices);
                }

                // الحد الأقصى اليومي (Tickets per day) - معالجة JSON لتجنب الأخطاء
                if (!empty($tech_data['tickets_day'])) {
                    $decoded_tickets = json_decode($tech_data['tickets_day'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_tickets)) {
                        $tech_tickets_day_js = json_encode($decoded_tickets);
                    }
                }

                // جلب الحجوزات الحالية (لحساب المتبقي)
                $sql_bookings = "SELECT DATE(date) as rdv_date, COUNT(*) as total 
                                 FROM rdv 
                                 WHERE doctor_id = $tech_id 
                                 AND deleted = 0 
                                 AND state != 3 
                                 AND date >= CURDATE() 
                                 GROUP BY DATE(date)";
                $res_bookings = $GLOBALS['db']->select($sql_bookings);
                $bookings_map = [];
                foreach ($res_bookings as $row) {
                    $bookings_map[$row['rdv_date']] = $row['total'];
                }
                $global_bookings_js = json_encode($bookings_map);
            }
        }

        // 3. جلب التواريخ المحجوزة مسبقاً لهذا الملف
        $sql_dates = "SELECT DATE(r.date) as simple_date 
                      FROM rdv r 
                      INNER JOIN reeducation_sessions rs ON r.reeducation_session_id = rs.id 
                      WHERE rs.dossier_id = " . intval($id) . " AND r.deleted = 0";
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
}

$readonly_attr = $is_read_only ? "disabled='disabled'" : "";
$pricing_readonly = "readonly='readonly'";
$calendarCSS = SITE_URL . '/app-assets/vendors/css/calendars/fullcalendar.min.css';
?>
<link rel="stylesheet" type="text/css" href="<?= $calendarCSS ?>">
<style>
    /* --- Calendar Customization --- */
    .planning-calendar-wrapper {
        background: #fff;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 4px 24px 0 rgba(34, 41, 47, 0.1);
    }

    .fc-toolbar-title {
        font-size: 1.1rem !important;
        font-weight: 600;
        color: #5e5873;
        text-transform: capitalize;
    }

    .fc .fc-daygrid-day-frame {
        min-height: 50px !important;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    /* Selection Styles */
    td.fc-daygrid-day.fc-day-selected {
        background-color: #28c76f !important;
        transition: background-color 0.2s ease;
    }

    td.fc-daygrid-day.fc-day-selected .fc-daygrid-day-number {
        color: white !important;
        font-weight: bold;
        z-index: 2;
    }

    td.fc-daygrid-day.fc-day-selected .fc-daygrid-day-frame::after {
        content: '✔';
        position: absolute;
        top: 2px;
        right: 2px;
        font-size: 10px;
        color: white;
        opacity: 0.8;
    }

    /* Full / Unavailable Styles */
    .fc-day-full {
        background-color: #ffedeb !important;
        color: #ea5455 !important;
        cursor: not-allowed !important;
        position: relative;
    }

    .fc-day-full .fc-daygrid-day-frame::before {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background-color: #ea5455;
    }

    .fc-non-working-day {
        background-color: #f8f8f8 !important;
        opacity: 0.6;
        cursor: not-allowed !important;
        background-image: repeating-linear-gradient(45deg, transparent, transparent 5px, #eeeeee 5px, #eeeeee 10px);
    }

    .fc-day-past {
        opacity: 0.4;
        cursor: not-allowed !important;
    }

    .fc-daygrid-day:not(.fc-non-working-day):not(.fc-day-past):not(.fc-day-full):not(.fc-day-selected):hover {
        background-color: #dff7e9 !important;
        cursor: pointer;
    }

    /* Slots Badge Style */
    .slots-badge {
        font-size: 0.7rem;
        padding: 1px 4px;
        border-radius: 4px;
        margin-top: 2px;
        background-color: #e0e0e0;
        color: #555;
        position: absolute;
        bottom: 2px;
        left: 2px;
    }

    .fc-day-selected .slots-badge {
        background-color: rgba(255, 255, 255, 0.3);
        color: white;
    }

    .fc-day-full .slots-badge {
        background-color: #ea5455;
        color: white;
    }

    #calendar-bottom .fc-col-header {
        display: none;
    }

    .calendar-divider {
        height: 1px;
        background-color: #ebe9f1;
        margin: 10px 0;
    }

    html.dark-layout .planning-calendar-wrapper {
        background-color: #283046;
        border-color: #3b4253;
    }

    html.dark-layout .fc-toolbar-title {
        color: #d0d2d6;
    }

    html.dark-layout .fc-daygrid-day-number {
        color: #b4b7bd;
    }

    html.dark-layout .fc-non-working-day {
        background-color: #343d55 !important;
        background-image: none;
    }

    html.dark-layout .fc-day-full {
        background-color: #462e2e !important;
        color: #ff6b6b !important;
    }
</style>

<div class="app-content content">
    <div class="content-wrapper p-0">
        <div class="content-header row">
            <div class="content-header-left col-md-9 col-12 mb-2">
                <div class="row breadcrumbs-top">
                    <div class="col-12">
                        <h2 class="content-header-title float-start mb-0">
                            <i data-feather="activity" class="font-medium-5 me-1"></i><?= $breadcrumb; ?> Dossier
                            Rééducation
                        </h2>
                    </div>
                </div>
            </div>
            <?php if ($is_edit_mode && $result['status'] === 'active' && !$is_read_only): ?>
                <div class="content-header-right text-md-end col-md-3 col-12 d-md-block d-none">
                    <button type="button" class="btn btn-success shadow" id="complete-dossier-btn">
                        <i data-feather="check-circle"></i> Clôturer le dossier
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <div class="content-body">
            <?php if ($is_edit_mode): ?>
                <div class="card mb-2 border-primary">
                    <div class="card-body p-1">
                        <?php
                        $prescribed = (int) ($result['sessions_prescribed'] ?? 0);
                        $completed = (int) ($result['sessions_completed'] ?? 0);
                        $progress_percentage = $prescribed > 0 ? ($completed / $prescribed) * 100 : 0;
                        $visual_percentage = min($progress_percentage, 100);
                        ?>
                        <div class="d-flex justify-content-between mb-50">
                            <span class="fw-bolder text-primary"><i data-feather="bar-chart-2"></i> Progression du
                                traitement</span>
                            <span class="badge badge-light-primary fs-5"><?= $completed ?> / <?= $prescribed ?>
                                Séances</span>
                        </div>
                        <div class="progress progress-bar-primary" style="height: 20px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                aria-valuenow="<?= $visual_percentage ?>" aria-valuemin="0" aria-valuemax="100"
                                style="width: <?= $visual_percentage ?>%">
                                <?= round($visual_percentage) ?>%
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <ul class="nav nav-pills mb-2" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="details-tab" data-bs-toggle="tab" href="#details"
                        aria-controls="details" role="tab" aria-selected="true">
                        <i data-feather="file-text"></i> Détails & Configuration
                    </a>
                </li>
                <?php if ($is_edit_mode): ?>
                    <li class="nav-item">
                        <a class="nav-link" id="history-tab" data-bs-toggle="tab" href="#history" aria-controls="history"
                            role="tab" aria-selected="false">
                            <i data-feather="list"></i> Historique & Paiements
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="tab-content">
                <div class="tab-pane active" id="details" aria-labelledby="details-tab" role="tabpanel">
                    <form class="codexForm" method="post" role="form" data-express="<?= customEncryption($table); ?>"
                        data-update="<?= customEncryption(json_encode($where)); ?>">
                        <?php set_csrf() ?>

                        <div class="row match-height">
                            <div class="col-lg-8 col-12">
                                <div class="card">
                                    <div class="card-header border-bottom bg-light-primary">
                                        <h4 class="card-title text-primary">1. Patient & Orientation</h4>
                                    </div>
                                    <div class="card-body pt-2">
                                        <div class="row">
                                            <div class="col-12 mb-1">
                                                <label class="form-label"
                                                    for="<?= $table; ?>__patient_id">Patient</label>
                                                <div class="input-group">
                                                    <div style="flex-grow: 1;">
                                                        <?php
                                                        draw_select([
                                                            "label" => "",
                                                            "name_id" => "{$table}__patient_id",
                                                            "placeholder" => "Rechercher un patient (Nom, Prénom...)",
                                                            "attr" => $readonly_attr,
                                                            "serverSide" => [
                                                                "table" => "patient",
                                                                "value" => "id",
                                                                "text" => ["first_name", "last_name"],
                                                                "selected" => $result['patient_id'] ?? null,
                                                                "where" => "deleted=0"
                                                            ]
                                                        ]);
                                                        ?>
                                                    </div>
                                                    <?php if (!$is_read_only): ?>
                                                        <button class="btn btn-primary" type="button"
                                                            id="btn-add-quick-patient" title="Nouveau Patient">
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
                                                    "clientSide" => [
                                                        ["option_text" => "Externe (Sur ordonnance)", "value" => "externe"],
                                                        ["option_text" => "Interne (Consultation)", "value" => "interne"]
                                                    ]
                                                ]); ?>
                                            </div>
                                            <div class="col-md-6 col-12 mb-1 external-only-field">
                                                <?php draw_input([
                                                    "label" => "Médecin Prescripteur",
                                                    "type" => "text",
                                                    "name_id" => "{$table}__oriented_by",
                                                    "attr" => $readonly_attr,
                                                    "placeholder" => "Nom du médecin...",
                                                    "value" => $result['oriented_by'] ?? ''
                                                ]); ?>
                                            </div>
                                            <div class="col-12 mb-1 external-only-field">
                                                <?php if (!$is_read_only): ?>
                                                    <label class="form-label">Lettre d'Orientation (Scanner/Photo)</label>
                                                    <div class="codexFileUp">
                                                        <input type="file" class="form-control codexInputFile"
                                                            id="medical_letter_input" accept=".pdf, .png, .jpg, .jpeg">
                                                        <input type="hidden" class="codexFileData"
                                                            name="<?= $table; ?>__medical_letter_path"
                                                            value="<?= $result['medical_letter_path'] ?? '' ?>">
                                                        <div class="mt-1 codexMultiPreviewImage">
                                                            <?php if (!empty($result['medical_letter_path'])): ?>
                                                                <div class="d-flex align-items-center border p-1 rounded">
                                                                    <i data-feather="file-text" class="text-primary me-1"></i>
                                                                    <a href="<?= $result['medical_letter_path'] ?>"
                                                                        target="_blank" class="fw-bold text-truncate">Document
                                                                        actuel</a>
                                                                    <span
                                                                        class="removePic ms-auto cursor-pointer text-danger p-1"><i
                                                                            data-feather="trash-2"></i></span>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php elseif (!empty($result['medical_letter_path'])): ?>
                                                    <label class="form-label">Lettre d'Orientation</label>
                                                    <div class="p-1 border rounded">
                                                        <i data-feather="file-text"></i>
                                                        <a href="<?= $result['medical_letter_path'] ?>" target="_blank"
                                                            class="fw-bold">Voir le document</a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card">
                                    <div class="card-header border-bottom bg-light-success">
                                        <h4 class="card-title text-success">2. Configuration Financière</h4>
                                    </div>
                                    <div class="card-body pt-2">
                                        <div class="row">
                                            <div class="col-12 mb-1">
                                                <?php
                                                draw_select([
                                                    "label" => "Service & Tarification",
                                                    "name_id" => "{$table}__reeducation_type_id",
                                                    "attr" => "required $readonly_attr",
                                                    "placeholder" => "Sélectionnez le type de soin...",
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
                                            <div class="col-md-6 col-12 mb-1">
                                                <?php draw_input(["label" => "Nombre de Séances", "type" => "number", "name_id" => "{$table}__sessions_prescribed", "attr" => "required min='1' $readonly_attr", "value" => $result['sessions_prescribed'] ?? 1]); ?>
                                            </div>
                                            <div class="col-md-6 col-12 mb-1">
                                                <label class="form-label">Mode de Paiement</label>
                                                <?php
                                                $stored_mode = $result['payment_mode'] ?? '';
                                                $display_text = ($stored_mode === 'package') ? 'Forfait Global' : (($stored_mode === 'per_session') ? 'Par Séance' : '');
                                                ?>
                                                <input type="text" class="form-control" id="display_payment_mode"
                                                    readonly value="<?= $display_text ?>">
                                                <input type="hidden" name="<?= $table ?>__payment_mode"
                                                    id="<?= $table ?>__payment_mode"
                                                    value="<?= $result['payment_mode'] ?? '' ?>">
                                            </div>
                                            <div class="col-md-6 col-12 mb-1">
                                                <label class="form-label">Tarif Total (DA)</label>
                                                <div class="input-group">
                                                    <input type="number" name="<?= $table; ?>__price"
                                                        id="<?= $table; ?>__price"
                                                        class="form-control fw-bolder text-success"
                                                        value="<?= $result['price'] ?? '0.00' ?>" step="0.01"
                                                        <?= $pricing_readonly ?>>
                                                    <span class="input-group-text">DA</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6 col-12 mb-1">
                                                <label class="form-label">Remise (DA)</label>
                                                <div class="input-group">
                                                    <input type="number" name="<?= $table; ?>__discount_amount"
                                                        id="<?= $table; ?>__discount_amount" class="form-control"
                                                        value="<?= $result['discount_amount'] ?? '0.00' ?>" step="0.01"
                                                        <?= $readonly_attr ?>>
                                                    <span class="input-group-text">DA</span>
                                                </div>
                                            </div>
                                            <div class="col-12 mb-1 d-none" id="duration_info_div">
                                                <div class="alert alert-info p-1 mb-0">
                                                    <i data-feather="clock" class="me-50"></i> Durée estimée par séance
                                                    : <span id="session_duration_display" class="fw-bold"></span>
                                                    minutes.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 col-12">
                                <div class="card">
                                    <div class="card-header border-bottom bg-light-secondary">
                                        <h4 class="card-title">3. Praticien & Validation</h4>
                                    </div>
                                    <div class="card-body pt-2">
                                        <div class="mb-1">
                                            <?php draw_select(["label" => "Technicien Assigné", "name_id" => "{$table}__technician_id", "attr" => $readonly_attr, "placeholder" => "Choisir...", "serverSide" => ["table" => "users", "value" => "id", "text" => ["first_name", "last_name"], "selected" => $result['technician_id'] ?? null, "where" => "role='doctor' AND deleted=0 AND cabinet_id=" . intval($_SESSION['user']['cabinet_id'])]]); ?>
                                        </div>
                                        <div class="mb-2">
                                            <?php draw_input(["label" => "Commission Technicien (Auto)", "type" => "number", "name_id" => "{$table}__technician_percentage", "attr" => "$pricing_readonly", "value" => $result['technician_percentage'] ?? '0']); ?>
                                        </div>
                                        <?php if (!$is_read_only): ?>
                                            <div class="d-grid gap-2">
                                                <?php draw_button(["text" => $btn_text, "type" => "submit", "name_id" => "submit", "class" => "btn-primary btn-lg waves-effect waves-float waves-light"]); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if ($is_edit_mode && !$is_read_only): ?>
                                    <div class="card border-info">
                                        <div class="card-header bg-light-info">
                                            <h4 class="card-title text-info"><i data-feather="calendar"></i> Planification
                                            </h4>
                                        </div>
                                        <div class="card-body pt-2">
                                            <p class="card-text font-small-3 mb-1">Sélectionnez les dates des séances.</p>
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <button type="button" class="btn btn-icon btn-outline-secondary btn-sm"
                                                    id="cal-prev" title="Mois précédent"><i
                                                        data-feather="chevron-left"></i></button>
                                                <span class="fw-bold">Navigation</span>
                                                <button type="button" class="btn btn-icon btn-outline-secondary btn-sm"
                                                    id="cal-next" title="Mois suivant"><i
                                                        data-feather="chevron-right"></i></button>
                                            </div>

                                            <div class="planning-calendar-wrapper mb-2">
                                                <div id="calendar-top" class="mb-1"></div>
                                                <div class="calendar-divider"></div>
                                                <div id="calendar-bottom"></div>
                                            </div>

                                            <!-- NEW: Area to display remaining tickets -->
                                            <div id="day-info-alert" class="alert alert-primary p-1 mb-1 d-none"
                                                role="alert">
                                                <i data-feather="info" class="me-50"></i>
                                                <span id="day-info-text"></span>
                                            </div>

                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="fw-bold">Sélectionnées: <span id="selected-count"
                                                        class="text-primary">0</span> / <span id="max-sessions-display"
                                                        class="text-dark"><?= $result['sessions_prescribed'] ?? 0 ?></span></span>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    id="clear-selection-btn">Effacer</button>
                                            </div>
                                            <div class="d-grid">
                                                <button type="button" class="btn btn-outline-info"
                                                    id="generate-sessions-btn" data-dossier-id="<?= $id ?>">
                                                    <i data-feather="refresh-cw"></i> Générer les séances
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

                <?php if ($is_edit_mode): ?>
                    <div class="tab-pane" id="history" aria-labelledby="history-tab" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-7 col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h4 class="card-title">Historique des Séances</h4>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped">
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
                                                        echo "<tr>
                                                            <td>" . ($sess['rdv_date'] ?? '-') . "</td>
                                                            <td>{$status_badge}</td>
                                                            <td>" . ($sess['duration'] ? $sess['duration'] . ' min' : '-') . "</td>
                                                            <td title='" . htmlspecialchars($sess['observations'] ?? '') . "'>" . htmlspecialchars(substr($sess['observations'] ?? '', 0, 25)) . "...</td>
                                                          </tr>";
                                                    }
                                                } else {
                                                    echo '<tr><td colspan="4" class="text-center text-muted p-2">Aucune séance enregistrée</td></tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php if (!$is_read_only): ?>
                                <div class="col-lg-5 col-12">
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h4 class="card-title">Historique Paiements</h4>
                                            <a href="<?= SITE_URL ?>/caisse?dossier_id=<?= $id ?>"
                                                class="btn btn-sm btn-primary"><i data-feather="dollar-sign"></i> Gérer</a>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Montant</th>
                                                        <th>Agent</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $history_payments = $GLOBALS['db']->select("SELECT ct.*, CONCAT(u.first_name, ' ', u.last_name) as recorded_by_name FROM caisse_transactions ct LEFT JOIN users u ON ct.recorded_by = u.id WHERE ct.dossier_id = $id ORDER BY ct.id DESC LIMIT 10");
                                                    if (!empty($history_payments)) {
                                                        foreach ($history_payments as $pay) {
                                                            echo "<tr>
                                                            <td>" . date('d/m/Y', strtotime($pay['payment_date'])) . "</td>
                                                            <td class='fw-bolder text-success'>" . number_format($pay['amount_paid'], 2) . " DA</td>
                                                            <td><small>{$pay['recorded_by_name']}</small></td>
                                                          </tr>";
                                                        }
                                                    } else {
                                                        echo '<tr><td colspan="3" class="text-center text-muted p-2">Aucun paiement</td></tr>';
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
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un Nouveau Patient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickAddPatientForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-6 mb-1">
                            <label class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="col-6 mb-1">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                        <div class="col-12 mb-1">
                            <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="phone" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
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
                                if (isPackage && data.duration) {
                                    $('#duration_info_div').removeClass('d-none');
                                    $('#session_duration_display').text(data.duration);
                                } else { $('#duration_info_div').addClass('d-none'); }
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
                            Swal.fire('Succès', 'Patient ajouté avec succès', 'success');
                        } else { Swal.fire('Erreur', res.message, 'error'); }
                    }, complete: function () { btn.attr('disabled', false).text('Enregistrer'); }
                });
            });

            // --- NEW: DUAL CALENDAR LOGIC (DEBUGGED & FULLY FUNCTIONAL) ---
            <?php if ($is_edit_mode): ?>
                var selectedDates = <?= !empty($existing_rdv_dates) ? json_encode($existing_rdv_dates) : '[]' ?>;
                var workingDays = <?= $tech_working_days_js ?>;
                var ticketsPerDay = <?= $tech_tickets_day_js ?>;
                var globalBookings = <?= $global_bookings_js ?>;
                var dayNamesMap = ["Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi"];

                console.log("Initial Selected Dates:", selectedDates);
                console.log("Tickets Per Day:", ticketsPerDay);
                console.log("Global Bookings:", globalBookings);

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

                            // 1. تلوين أيام العطلة
                            if (!workingDays.includes(dayIndex)) {
                                info.el.classList.add('fc-non-working-day');
                                return;
                            }

                            // 2. التحقق من الامتلاء
                            var limit = parseInt(ticketsPerDay[dayName]) || 999;
                            var booked = globalBookings[dateStr] || 0;
                            var available = limit - booked;

                            // عرض عدد الأماكن المتبقية داخل الخلية (Badge)
                            if (limit < 999) {
                                var badgeClass = available > 0 ? 'bg-light-primary text-primary' : 'bg-light-danger text-danger';
                                var badgeText = available > 0 ? available : '0';
                                var counterHtml = '<div class="slots-badge badge ' + badgeClass + '">' + badgeText + '</div>';
                                info.el.querySelector('.fc-daygrid-day-frame').insertAdjacentHTML('beforeend', counterHtml);
                            }

                            var isSelectedHere = selectedDates.includes(dateStr);

                            if (available <= 0 && !isSelectedHere) {
                                info.el.classList.add('fc-day-full');
                            }

                            // 3. تلوين الأيام المحددة
                            if (isSelectedHere) {
                                info.el.classList.add('fc-day-selected');
                            }
                        },

                        dateClick: function (info) {
                            handleDateClick(info);
                        },
                        events: function (fetchInfo, successCallback, failureCallback) {
                            successCallback([]);
                        }
                    };
                }

                function handleDateClick(info) {
                    var dateStr = info.dateStr;
                    var dateObj = new Date(dateStr + 'T00:00:00');
                    var dayIndex = dateObj.getDay();
                    var dayName = dayNamesMap[dayIndex];

                    console.log("User clicked:", dateStr);

                    // --- حساب الأماكن المتبقية للعرض ---
                    var limit = parseInt(ticketsPerDay[dayName]) || 999;
                    var booked = globalBookings[dateStr] || 0;
                    var available = limit - booked;

                    // عرض المعلومات في الـ Alert أسفل التقويم
                    var infoText = "Le <b>" + dateStr + "</b> (" + dayName + ") : <b>" + available + "</b> places restantes (Max: " + limit + ")";
                    $('#day-info-text').html(infoText);
                    $('#day-info-alert').removeClass('d-none');

                    // 1. منع الأيام الماضية
                    var today = new Date();
                    today.setHours(0, 0, 0, 0);
                    if (dateObj < today) {
                        Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, icon: 'warning', title: 'Date passée.' });
                        return;
                    }

                    // 2. منع أيام العطلة
                    if (!workingDays.includes(dayIndex)) {
                        Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, icon: 'warning', title: 'Jour non travaillé.' });
                        return;
                    }

                    // 3. منع الأيام الممتلئة
                    var isSelected = selectedDates.includes(dateStr);

                    if (!isSelected && booked >= limit) {
                        Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, icon: 'error', title: 'Journée complète (' + limit + ' patients).' });
                        return;
                    }

                    // 4. منطق التحديد
                    var maxSessions = parseInt($('#<?= $table; ?>__sessions_prescribed').val()) || 0;

                    if (isSelected) {
                        selectedDates = selectedDates.filter(d => d !== dateStr);
                        isSelected = false;
                        console.log("Removed date. New list:", selectedDates);
                    } else {
                        if (selectedDates.length >= maxSessions) {
                            Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, icon: 'error', title: 'Limite atteinte (' + maxSessions + ').' });
                            return;
                        }
                        selectedDates.push(dateStr);
                        isSelected = true;
                        console.log("Added date. New list:", selectedDates);
                    }

                    // 5. تحديث الواجهة فوراً
                    var cells = document.querySelectorAll('[data-date="' + dateStr + '"]');
                    cells.forEach(function (cell) {
                        if (isSelected) {
                            cell.classList.add('fc-day-selected');
                        } else {
                            cell.classList.remove('fc-day-selected');
                        }
                    });

                    updateSelectedCount();
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
                });

                $('#generate-sessions-btn').on('click', function (e) {
                    e.preventDefault();
                    var btn = $(this);
                    var dossierId = btn.data('dossier-id');

                    if (selectedDates.length === 0) {
                        Swal.fire({ title: 'Attention', text: 'Sélectionnez au moins une date.', icon: 'warning', customClass: { confirmButton: 'btn btn-primary' }, buttonsStyling: false });
                        return;
                    }

                    Swal.fire({
                        title: 'Confirmer ?',
                        text: "Générer " + selectedDates.length + " séances ?",
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Oui',
                        cancelButtonText: 'Non',
                        customClass: { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-outline-danger ms-1' },
                        buttonsStyling: false
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: '<?= SITE_URL; ?>/handlers', type: 'POST',
                                data: { method: 'generate_sessions_manual', dossier_id: dossierId, dates: selectedDates },
                                dataType: 'json',
                                beforeSend: function () { btn.attr('disabled', 'disabled').html('...'); },
                                success: function (response) {
                                    if (response.state === "true") {
                                        Swal.fire({ title: 'Succès', text: response.message, icon: 'success', customClass: { confirmButton: 'btn btn-success' }, buttonsStyling: false }).then(() => location.reload());
                                    } else {
                                        Swal.fire({ title: 'Erreur', text: response.message, icon: 'error', customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false });
                                    }
                                },
                                complete: function () { btn.removeAttr('disabled').html('<i data-feather="refresh-cw"></i> Générer'); if (feather) feather.replace(); }
                            });
                        }
                    });
                });

                $('#complete-dossier-btn').on('click', function (e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Clôturer le dossier ?', text: "Statut -> Terminé.", icon: 'warning', showCancelButton: true, confirmButtonText: 'Oui', cancelButtonText: 'Non'
                    }).then(function (result) {
                        if (result.value) {
                            $.ajax({
                                url: '<?= SITE_URL; ?>/data', type: 'POST',
                                data: {
                                    method: 'updatForm',
                                    class: '<?= customEncryption($table); ?>',
                                    object: '<?= customEncryption(json_encode($where)); ?>',
                                    csrf: $('input[name="csrf"]').val(),
                                    data: [{ name: '<?= $table; ?>__status', value: 'completed' }]
                                },
                                dataType: 'json',
                                success: function (response) { if (response.state === "true") { Swal.fire('Succès!', 'Dossier clôturé.', 'success').then(() => location.reload()); } }
                            });
                        }
                    });
                });
            <?php endif; ?>
        <?php endif; ?>
    });
</script>