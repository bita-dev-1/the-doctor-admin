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
$is_read_only = false; // السماح للجميع بالكتابة

// تهيئة المتغيرات الافتراضية
$tech_working_days_js = '[]';
$tech_tickets_day_js = '{}';
$global_bookings_js = '{}';
$existing_rdv_dates = [];
$target_tech_id = 0; // المعرف المستهدف لجلب البيانات

// 1. تحديد وضع الصفحة (تعديل أم إنشاء) وتحديد التقني المستهدف
if ($is_edit_mode) {
    $btn_text = 'Enregistrer les modifications';
    $breadcrumb = 'Modifier';
    $where = array("column" => "id", "val" => $id);
    $result = dataById($where, $table)[0] ?? false;

    if ($result) {
        $count_sql = "SELECT COUNT(*) as total FROM reeducation_sessions WHERE dossier_id = " . intval($id) . " AND status = 'completed'";
        $count_res = $GLOBALS['db']->select($count_sql);
        $result['sessions_completed'] = $count_res[0]['total'] ?? 0;

        // تحديد التقني من الملف الموجود
        if (!empty($result['technician_id'])) {
            $target_tech_id = intval($result['technician_id']);
        }

        // جلب التواريخ المحجوزة مسبقاً لهذا الملف
        $sql_dates = "SELECT DATE(r.date) as simple_date FROM rdv r INNER JOIN reeducation_sessions rs ON r.reeducation_session_id = rs.id WHERE rs.dossier_id = " . intval($id) . " AND r.deleted = 0";
        $res_dates = $GLOBALS['db']->select($sql_dates);
        if (!empty($res_dates)) {
            foreach ($res_dates as $row) {
                $existing_rdv_dates[] = $row['simple_date'];
            }
        }
    }
} else {
    // وضع الإنشاء: إذا كان المستخدم طبيباً، فهو التقني المستهدف
    if ($user_role === 'doctor') {
        $target_tech_id = intval($_SESSION['user']['id']);
    }
}

// 2. جلب بيانات الجدولة (التذاكر وأيام العمل) إذا تم تحديد تقني
if ($target_tech_id > 0) {
    $tech_data = $GLOBALS['db']->select("SELECT travel_hours, tickets_day FROM users WHERE id = $target_tech_id")[0] ?? null;

    if ($tech_data) {
        // أ. معالجة ساعات العمل
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

        // ب. معالجة عدد التذاكر (المقاعد)
        if (!empty($tech_data['tickets_day'])) {
            $decoded_tickets = json_decode($tech_data['tickets_day'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_tickets)) {
                $tech_tickets_day_js = json_encode($decoded_tickets);
            }
        }

        // ج. جلب الحجوزات العامة لهذا التقني لحساب المتاح
        $sql_bookings = "SELECT DATE(date) as rdv_date, COUNT(*) as total FROM rdv WHERE doctor_id = $target_tech_id AND deleted = 0 AND state != 3 AND date >= CURDATE() GROUP BY DATE(date)";
        $res_bookings = $GLOBALS['db']->select($sql_bookings);
        $bookings_map = [];
        foreach ($res_bookings as $row) {
            $bookings_map[$row['rdv_date']] = $row['total'];
        }
        $global_bookings_js = json_encode($bookings_map);
    }
} else {
    // قيم افتراضية إذا لم يتم تحديد تقني (مثلاً للأدمن عند فتح صفحة جديدة)
    $tech_working_days_js = '[0,1,2,3,4,5,6]';
}

$readonly_attr = $is_read_only ? "disabled='disabled'" : "";
$pricing_readonly = "readonly='readonly'";
$calendarCSS = SITE_URL . '/app-assets/vendors/css/calendars/fullcalendar.min.css';
?>

<!-- Include External CSS -->
<link rel="stylesheet" type="text/css" href="<?= $calendarCSS ?>">
<link rel="stylesheet" type="text/css" href="<?= SITE_URL ?>/assets/css/pages/reeducation-form.css">

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
                    <?php include 'views/reeducation/partials/_details.php'; ?>
                </div>

                <?php if ($is_edit_mode): ?>
                    <div class="tab-pane" id="history" role="tabpanel">
                        <?php include 'views/reeducation/partials/_history.php'; ?>
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

<!-- Pass PHP Data to JS -->
<script>
    window.ReeducationConfig = {
        siteUrl: '<?= SITE_URL ?>',
        isEditMode: <?= $is_edit_mode ? 'true' : 'false' ?>,
        isReadOnly: <?= $is_read_only ? 'true' : 'false' ?>,
        encryptedTable: '<?= customEncryption($table) ?>',
        encryptedWhere: '<?= customEncryption(json_encode($where)) ?>',
        selectedDates: <?= !empty($existing_rdv_dates) ? json_encode($existing_rdv_dates) : '[]' ?>,
        workingDays: <?= !empty($tech_working_days_js) ? $tech_working_days_js : '[]' ?>,
        ticketsPerDay: <?= !empty($tech_tickets_day_js) ? $tech_tickets_day_js : '{}' ?>,
        globalBookings: <?= !empty($global_bookings_js) ? $global_bookings_js : '{}' ?>
    };
</script>

<!-- Include External JS -->
<script src="<?= SITE_URL ?>/assets/js/pages/reeducation-form.js"></script>