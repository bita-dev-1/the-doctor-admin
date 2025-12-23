<?php
// التحقق من الجلسة
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header('location:' . SITE_URL . '/login');
    exit();
}
include_once "header.php";

$user_role = $_SESSION['user']['role'] ?? 'doctor';
$user_id = $_SESSION['user']['id'] ?? 0;
$user_cabinet_id = $_SESSION['user']['cabinet_id'] ?? null;
$is_super_admin = ($user_role === 'admin' && empty($user_cabinet_id));

// --- جلب الإحصائيات ---
$stats = [];

if ($is_super_admin) {
    // إحصائيات السوبر أدمن
    $stats['cabinets'] = $GLOBALS['db']->select("SELECT COUNT(id) as total FROM cabinets WHERE deleted = 0")[0]['total'] ?? 0;
    $stats['doctors'] = $GLOBALS['db']->select("SELECT COUNT(id) as total FROM users WHERE role = 'doctor' AND deleted = 0")[0]['total'] ?? 0;
    $stats['admins'] = $GLOBALS['db']->select("SELECT COUNT(id) as total FROM users WHERE role = 'admin' AND cabinet_id IS NOT NULL AND deleted = 0")[0]['total'] ?? 0;
    $stats['specialties'] = $GLOBALS['db']->select("SELECT COUNT(id) as total FROM specialty WHERE deleted = 0")[0]['total'] ?? 0;
} else {
    // إحصائيات العيادة / الطبيب
    $users_where_clause = " WHERE deleted = 0 ";
    $patients_where_clause = " WHERE deleted = 0 ";
    $rdv_where_clause = " WHERE deleted = 0 ";

    if ($user_role === 'admin') {
        $cabinet_filter = " AND cabinet_id = " . intval($user_cabinet_id);
        $users_where_clause .= $cabinet_filter;
        $patients_where_clause .= $cabinet_filter;
        $rdv_where_clause .= $cabinet_filter;

        $users_count = $GLOBALS['db']->select("SELECT SUM(CASE WHEN role = 'doctor' THEN 1 ELSE 0 END) AS doctors FROM users " . str_replace('deleted = 0', 'deleted = 0 AND role != \'admin\'', $users_where_clause))[0] ?? ['doctors' => 0];
        $stats['doctors'] = $users_count['doctors'];
    }

    if ($user_role === 'doctor' || $user_role === 'nurse') {
        $rdv_where_clause .= " AND doctor_id = " . intval($user_id);
        if (!empty($user_cabinet_id)) {
            $patients_where_clause .= " AND cabinet_id = " . intval($user_cabinet_id);
        } else {
            $patients_where_clause .= " AND cabinet_id IS NULL";
        }
    }

    $rdv = $GLOBALS['db']->select("SELECT COUNT(id) AS total, 
            SUM(CASE WHEN state = 0 THEN 1 ELSE 0 END) AS created, 
            SUM(CASE WHEN state = 1 THEN 1 ELSE 0 END) AS confirmed, 
            SUM(CASE WHEN state = 2 THEN 1 ELSE 0 END) AS completed, 
            SUM(CASE WHEN state = 3 THEN 1 ELSE 0 END) AS canceled 
            FROM rdv " . $rdv_where_clause)[0] ?? [];

    $stats['patients'] = $GLOBALS['db']->select("SELECT COUNT(id) AS total FROM patient " . $patients_where_clause)[0]['total'] ?? 0;

    // بيانات الرسم البياني (آخر 7 أيام)
    $rdvPerDay = array_values(
        array_column(
            $GLOBALS['db']->select("SELECT COALESCE(COUNT(t.id), 0) AS total FROM (SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6) days LEFT JOIN rdv t ON DATE(t.date) = CURDATE() - INTERVAL days.n DAY " . str_replace("WHERE", "AND", $rdv_where_clause) . " GROUP BY days.n ORDER BY days.n ASC"),
            'total'
        )
    );
}

// حساب نسبة الإكمال
$completion_rate = ($rdv['total'] ?? 0) != 0 ? number_format((($rdv['completed'] ?? 0) / ($rdv['total']) * 100), 1) : 0;
?>

<!-- Link to Dashboard CSS -->
<link rel="stylesheet" type="text/css" href="<?= SITE_URL ?>/assets/css/pages/dashboard.css">

<div class="app-content content">
    <div class="content-wrapper p-0"> 
        <div class="content-body">
            <section id="dashboard-ecommerce">
                    <div class="row match-height">
                        
                        <?php if (!$is_super_admin): ?>
                                <!-- Standard Dashboard for Clinic -->
                                <div class="col-xl-3 col-md-4 col-12">
                                    <div class="card card-congratulation-medal">
                                        <div class="card-body">
                                            <h5> <?= $GLOBALS['language']['Welcome'] . ' ' . $_SESSION['user']['first_name']; ?>!</h5>
                                            <p class="card-text font-small-3">Total des Rendez-vous</p>
                                            <h3 class="mb-75 mt-2 pt-50">
                                                <a href="javascript:void(0);" class="text-primary"><?= ($rdv['total'] ?? 0); ?></a>
                                            </h3>
                                            <a href="<?= SITE_URL; ?>/rdv">
                                                <button type="button" class="btn btn-primary waves-effect waves-float waves-light">Voir la liste</button>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-9 col-md-8 col-12">
                                    <div class="card card-statistics">
                                        <div class="card-header mb-0">
                                            <h4 class="card-title">Statistiques</h4>
                                        </div>
                                        <div class="card-body statistics-body">
                                            <div class="row">
                                                <?php if ($user_role === 'admin'): ?>
                                                    <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                                        <div class="d-flex flex-row">
                                                            <!-- Green Icon -->
                                                            <div class="avatar bg-light-primary me-2">
                                                                <div class="avatar-content">
                                                                    <i data-feather="users" class="avatar-icon"></i>
                                                                </div>
                                                            </div>
                                                            <div class="my-auto">
                                                                <h4 class="fw-bolder mb-0"><?= $stats['doctors'] ?? 0; ?></h4>
                                                                <p class="card-text font-small-3 mb-0"><?= $GLOBALS['language']['doctors']; ?></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-sm-0">
                                                    <div class="d-flex flex-row">
                                                        <!-- Blue Icon (Was Red/Orange) -->
                                                        <div class="avatar bg-light-secondary me-2">
                                                            <div class="avatar-content">
                                                                <i data-feather="user-check" class="avatar-icon"></i>
                                                            </div>
                                                        </div>
                                                        <div class="my-auto">
                                                            <h4 class="fw-bolder mb-0"><?= $stats['patients'] ?? 0; ?></h4>
                                                            <p class="card-text font-small-3 mb-0"><?= $GLOBALS['language']['patients'] ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-sm-0">
                                                    <div class="d-flex flex-row">
                                                        <!-- Green Icon -->
                                                        <div class="avatar bg-light-success me-2">
                                                            <div class="avatar-content">
                                                                <i data-feather="check-circle" class="avatar-icon"></i>
                                                            </div>
                                                        </div>
                                                        <div class="my-auto">
                                                            <h4 class="fw-bolder mb-0"><?= $rdv['completed'] ?? 0; ?></h4>
                                                            <p class="card-text font-small-3 mb-0">Complétés</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Charts -->
                                 <div class="col-lg-8 col-12">
                                    <div class="card mb-0">
                                        <div class="card-body">
                                            <div class="row w-100 m-auto">
                                                <div class="col-sm-4 col-12">
                                                    <div class="row align-items-center justify-content-between w-100 m-auto h-100">
                                                        <div class="text-center mb-50">
                                                            <p class="card-text mb-0 text-secondary fw-bold"><?= $GLOBALS['language']['created']; ?></p>
                                                            <span class="font-large-1 fw-bold text-secondary"><?= $rdv['created'] ?? 0; ?></span>
                                                        </div>
                                                        <div class="text-center mb-50">
                                                            <p class="card-text mb-0 text-primary fw-bold"><?= $GLOBALS['language']['accepted']; ?></p>
                                                            <span class="font-large-1 fw-bold text-primary"><?= $rdv['confirmed'] ?? 0; ?></span>
                                                        </div>
                                                        <div class="text-center mb-50">
                                                            <p class="card-text mb-0 text-danger"><?= $GLOBALS['language']['Canceled']; ?></p>
                                                            <span class="font-large-1 fw-bold text-danger"><?= $rdv['canceled'] ?? 0; ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- Chart Container: Data passed via data attributes -->
                                                <div class="col-sm-8 col-12 row align-items-center justify-content-center">
                                                    <div id="support-trackers-chart" data-percentage="<?= $completion_rate ?>"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-6 col-12">
                                    <div class="card mb-0">
                                        <div class="card-header flex-column align-items-start pb-0 mb-0">
                                            <!-- Blue Icon (Was Orange) -->
                                            <div class="avatar bg-light-secondary p-50 m-0">
                                                <div class="avatar-content">
                                                    <i data-feather="clipboard" class="font-medium-5"></i>
                                                </div>
                                            </div>
                                            <h2 class="fw-bolder mt-1"><?= array_sum($rdvPerDay); ?></h2>
                                            <p class="card-text text-muted">Rendez-vous (7 derniers jours)</p>
                                        </div>
                                        <!-- Chart Container: Data passed via hidden input -->
                                        <input type="hidden" id="chart-data-series" value='<?= json_encode($rdvPerDay); ?>'>
                                        <div id="order-chart" style="min-height: 100px;"></div>
                                    </div>
                                </div>

                        <?php else: ?>
                                <!-- SUPER ADMIN DASHBOARD -->
                                <div class="col-12">
                                    <div class="card card-statistics">
                                        <div class="card-header">
                                            <h4 class="card-title">Vue d'ensemble de la plateforme</h4>
                                        </div>
                                        <div class="card-body statistics-body">
                                            <div class="row">
                                                <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                                    <div class="d-flex flex-row">
                                                        <div class="avatar bg-light-primary me-2">
                                                            <div class="avatar-content">
                                                                <i data-feather="briefcase" class="avatar-icon"></i>
                                                            </div>
                                                        </div>
                                                        <div class="my-auto">
                                                            <h4 class="fw-bolder mb-0"><?= $stats['cabinets']; ?></h4>
                                                            <p class="card-text font-small-3 mb-0">Cabinets Actifs</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-xl-0">
                                                    <div class="d-flex flex-row">
                                                        <div class="avatar bg-light-secondary me-2">
                                                            <div class="avatar-content">
                                                                <i data-feather="users" class="avatar-icon"></i>
                                                            </div>
                                                        </div>
                                                        <div class="my-auto">
                                                            <h4 class="fw-bolder mb-0"><?= $stats['admins']; ?></h4>
                                                            <p class="card-text font-small-3 mb-0">Administrateurs</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xl-3 col-sm-6 col-12 mb-2 mb-sm-0">
                                                    <div class="d-flex flex-row">
                                                        <div class="avatar bg-light-success me-2">
                                                            <div class="avatar-content">
                                                                <i data-feather="user-plus" class="avatar-icon"></i>
                                                            </div>
                                                        </div>
                                                        <div class="my-auto">
                                                            <h4 class="fw-bolder mb-0"><?= $stats['doctors']; ?></h4>
                                                            <p class="card-text font-small-3 mb-0">Médecins</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xl-3 col-sm-6 col-12">
                                                    <div class="d-flex flex-row">
                                                        <div class="avatar bg-light-info me-2">
                                                            <div class="avatar-content">
                                                                <i data-feather="star" class="avatar-icon"></i>
                                                            </div>
                                                        </div>
                                                        <div class="my-auto">
                                                            <h4 class="fw-bolder mb-0"><?= $stats['specialties']; ?></h4>
                                                            <p class="card-text font-small-3 mb-0">Spécialités</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        <?php endif; ?>

                    </div>

                </section>       
        </div>
    </div>
</div>

<?php include_once 'foot.php'; ?>
<script src="<?= SITE_URL; ?>/app-assets/vendors/js/charts/apexcharts.min.js"></script>
<script src="<?= SITE_URL; ?>/app-assets/vendors/js/extensions/toastr.min.js"></script>

<!-- Load Custom Dashboard JS -->
<script src="<?= SITE_URL; ?>/assets/js/dashboard-custom.js?v=2.0"></script>