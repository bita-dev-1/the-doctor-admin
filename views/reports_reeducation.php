<?php
// التحقق من الصلاحيات: مسموح للأدمن (سواء سوبر أدمن أو أدمن عيادة)
if (!isset($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'admin') {
    header('location:' . SITE_URL . '/');
    exit();
}
include_once 'header.php';

// --- 1. إعداد الفلاتر ---
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-t');
$technician_filter = isset($_GET['technician_id']) && !empty($_GET['technician_id']) ? "AND rd.technician_id = " . intval($_GET['technician_id']) : "";

// فلتر العيادة (Multi-tenant)
$cabinet_filter_sql = "";
$users_cabinet_sql = "";
$service_join_condition = "1=1"; 

if (!empty($_SESSION['user']['cabinet_id'])) {
    $cabinet_id = intval($_SESSION['user']['cabinet_id']);
    $cabinet_filter_sql = " AND u.cabinet_id = $cabinet_id";
    $users_cabinet_sql = " AND cabinet_id = $cabinet_id";
    $service_join_condition = "cs.cabinet_id = $cabinet_id";
}

// --- 2. جلب البيانات الإحصائية العامة (KPIs) ---
$kpi_sql = "SELECT 
        COUNT(CASE WHEN rs.status = 'completed' THEN 1 END) as total_completed,
        COUNT(CASE WHEN rs.status = 'absent' THEN 1 END) as total_absent,
        SUM(CASE WHEN rs.status = 'completed' THEN (rd.price / GREATEST(rd.sessions_prescribed, 1)) ELSE 0 END) as theoretical_revenue,
        SUM(
            CASE WHEN rs.status = 'completed' THEN 
                CASE 
                    WHEN rs.commission_amount > 0 THEN rs.commission_amount 
                    WHEN cs.commission_type = 'fixed' THEN (rd.technician_percentage / GREATEST(rd.sessions_prescribed, 1)) 
                    ELSE ((rd.price / GREATEST(rd.sessions_prescribed, 1)) * (rd.technician_percentage / 100)) 
                END
            ELSE 0 END
        ) as tech_commission,
        AVG(CASE WHEN rs.status = 'completed' THEN rs.duration ELSE NULL END) as avg_duration,
        AVG(CASE WHEN rs.status = 'completed' THEN rs.pain_scale ELSE NULL END) as avg_pain
    FROM reeducation_sessions rs
    JOIN reeducation_dossiers rd ON rs.dossier_id = rd.id
    JOIN users u ON rd.technician_id = u.id
    LEFT JOIN cabinet_services cs ON rd.reeducation_type_id = cs.reeducation_type_id AND $service_join_condition AND cs.deleted = 0
    WHERE rs.completed_at BETWEEN :date_from AND :date_to
    $technician_filter
    $cabinet_filter_sql";

$stmt_kpi = $db->prepare($kpi_sql);
$stmt_kpi->execute([':date_from' => $date_from . ' 00:00:00', ':date_to' => $date_to . ' 23:59:59']);
$kpi_data = $stmt_kpi->fetch(PDO::FETCH_ASSOC);

// ب. حساب المدفوعات الفعلية
$cash_sql = "SELECT SUM(amount_paid) as total_cash 
                 FROM caisse_transactions ct
                 JOIN reeducation_dossiers rd ON ct.dossier_id = rd.id
                 JOIN users u ON rd.technician_id = u.id
                 WHERE ct.payment_date BETWEEN :date_from AND :date_to
                 $technician_filter
                 $cabinet_filter_sql";
$stmt_cash = $db->prepare($cash_sql);
$stmt_cash->execute([':date_from' => $date_from . ' 00:00:00', ':date_to' => $date_to . ' 23:59:59']);
$total_cash_in = $stmt_cash->fetchColumn() ?: 0;

// --- 3. بيانات الرسم البياني ---
$chart_sql = "SELECT 
        DATE(rs.completed_at) as day_date,
        SUM(rd.price / GREATEST(rd.sessions_prescribed, 1)) as daily_revenue
    FROM reeducation_sessions rs
    JOIN reeducation_dossiers rd ON rs.dossier_id = rd.id
    JOIN users u ON rd.technician_id = u.id
    WHERE rs.status = 'completed' 
      AND rs.completed_at BETWEEN :date_from AND :date_to
      $technician_filter
      $cabinet_filter_sql
    GROUP BY DATE(rs.completed_at)
    ORDER BY day_date ASC";
$stmt_chart = $db->prepare($chart_sql);
$stmt_chart->execute([':date_from' => $date_from . ' 00:00:00', ':date_to' => $date_to . ' 23:59:59']);
$daily_stats = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);

// --- 4. بيانات الرسم الدائري ---
$types_sql = "SELECT 
        rt.name as type_name,
        COUNT(rs.id) as session_count
    FROM reeducation_sessions rs
    JOIN reeducation_dossiers rd ON rs.dossier_id = rd.id
    LEFT JOIN reeducation_types rt ON rd.reeducation_type_id = rt.id
    JOIN users u ON rd.technician_id = u.id
    WHERE rs.status = 'completed'
      AND rs.completed_at BETWEEN :date_from AND :date_to
      $technician_filter
      $cabinet_filter_sql
    GROUP BY rt.id, rt.name";
$stmt_types = $db->prepare($types_sql);
$stmt_types->execute([':date_from' => $date_from . ' 00:00:00', ':date_to' => $date_to . ' 23:59:59']);
$types_data = $stmt_types->fetchAll(PDO::FETCH_ASSOC);

// --- 5. الجدول التفصيلي حسب التقني ---
$tech_table_sql = "SELECT 
        u.id as technician_id,
        CONCAT(u.first_name, ' ', u.last_name) as technician_name,
        COUNT(CASE WHEN rs.status = 'completed' THEN 1 END) as completed_sessions,
        COUNT(CASE WHEN rs.status = 'absent' THEN 1 END) as absent_sessions,
        SUM(CASE WHEN rs.status = 'completed' THEN (rd.price / GREATEST(rd.sessions_prescribed, 1)) ELSE 0 END) as generated_revenue,
        SUM(
            CASE WHEN rs.status = 'completed' THEN 
                CASE 
                    WHEN rs.commission_amount > 0 THEN rs.commission_amount
                    WHEN cs.commission_type = 'fixed' THEN (rd.technician_percentage / GREATEST(rd.sessions_prescribed, 1))
                    ELSE ((rd.price / GREATEST(rd.sessions_prescribed, 1)) * (rd.technician_percentage / 100))
                END
            ELSE 0 END
        ) as tech_share,
        AVG(CASE WHEN rs.status = 'completed' THEN rs.pain_scale ELSE NULL END) as avg_pain
    FROM reeducation_sessions rs
    JOIN reeducation_dossiers rd ON rs.dossier_id = rd.id
    JOIN users u ON rd.technician_id = u.id
    LEFT JOIN cabinet_services cs ON rd.reeducation_type_id = cs.reeducation_type_id AND $service_join_condition AND cs.deleted = 0
    WHERE rs.completed_at BETWEEN :date_from AND :date_to
    $technician_filter
    $cabinet_filter_sql
    GROUP BY u.id, technician_name";

$stmt_tech = $db->prepare($tech_table_sql);
$stmt_tech->execute([':date_from' => $date_from . ' 00:00:00', ':date_to' => $date_to . ' 23:59:59']);
$tech_stats = $stmt_tech->fetchAll(PDO::FETCH_ASSOC);

?>

<!-- START: CSS FOR PRINTING -->
<style>
    @media print {
        /* إخفاء جميع العناصر غير الضرورية */
        body * {
            visibility: hidden;
        }
        
        /* إخفاء العناصر الهيكلية للقالب */
        .app-content, .header-navbar, .main-menu, footer, .btn, .modal-footer, .btn-close, .modal-header .btn-close {
            display: none !important;
        }
        
        /* إظهار المودال ومحتوياته فقط */
        #techDetailsModal, #techDetailsModal * {
            visibility: visible;
        }

        /* تنسيق المودال ليملأ الصفحة */
        #techDetailsModal {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: auto;
            margin: 0;
            padding: 0;
            background: white;
            overflow: visible !important;
        }

        .modal-dialog {
            margin: 0;
            padding: 0;
            max-width: 100%;
            width: 100%;
            transform: none !important;
        }

        .modal-content {
            border: none;
            box-shadow: none;
        }

        .modal-body {
            padding: 20px;
        }

        /* إظهار الترويسة الخاصة بالطباعة */
        .d-print-block {
            display: block !important;
        }
        
        /* تحسين مظهر الجدول */
        .table {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 12px;
        }
        .table th, .table td {
            border: 1px solid #ddd !important;
            padding: 8px !important;
            color: #000 !important;
        }
        .badge {
            border: 1px solid #000;
            color: #000 !important;
            background: transparent !important;
            padding: 2px 5px;
        }
        
        /* إخفاء التنبيهات اللونية */
        .alert {
            border: 1px solid #000;
            background: none !important;
            color: #000 !important;
        }
    }
</style>
<!-- END: CSS FOR PRINTING -->

<div class="app-content content">
    <div class="content-wrapper p-0">
        <div class="content-header row">
            <div class="col-12 mb-2">
                <h2 class="content-header-title float-start mb-0">Rapports & Paie (Kiné)</h2>
            </div>
        </div>
        <div class="content-body">

            <!-- Filters -->
            <div class="card mb-2">
                <div class="card-body">
                    <form method="get" class="row align-items-end">
                        <div class="col-md-3 mb-1">
                            <label class="form-label">Date Début</label>
                            <input type="text" class="form-control picker" name="date_from"
                                value="<?= htmlspecialchars($date_from) ?>">
                        </div>
                        <div class="col-md-3 mb-1">
                            <label class="form-label">Date Fin</label>
                            <input type="text" class="form-control picker" name="date_to"
                                value="<?= htmlspecialchars($date_to) ?>">
                        </div>
                        <div class="col-md-3 mb-1">
                            <?php
                            $tech_where_select = "role='doctor' AND deleted=0 $users_cabinet_sql";
                            draw_select([
                                "label" => "Technicien (Optionnel)",
                                "name_id" => "technician_id",
                                "placeholder" => "Tous",
                                "serverSide" => ["table" => "users", "value" => "id", "text" => ["first_name", "last_name"], "selected" => $_GET['technician_id'] ?? null, "where" => $tech_where_select]
                            ]);
                            ?>
                        </div>
                        <div class="col-md-3 mb-1">
                            <button type="submit" class="btn btn-primary w-100"><i data-feather="filter"></i>
                                Appliquer</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="row match-height">
                <!-- Revenue (CA) -->
                <div class="col-lg-3 col-sm-6 col-12">
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h2 class="fw-bolder mb-0"><?= number_format($kpi_data['theoretical_revenue'], 0) ?> DA
                                </h2>
                                <p class="card-text">Chiffre d'Affaires (Généré)</p>
                            </div>
                            <div class="avatar bg-light-primary p-50 m-0">
                                <div class="avatar-content"><i data-feather="trending-up" class="font-medium-5"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <small class="text-muted">Valeur des séances terminées</small>
                        </div>
                    </div>
                </div>

                <!-- Cash In -->
                <div class="col-lg-3 col-sm-6 col-12">
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h2 class="fw-bolder mb-0 text-success"><?= number_format($total_cash_in, 0) ?> DA</h2>
                                <p class="card-text">Encaissé (Caisse Réelle)</p>
                            </div>
                            <div class="avatar bg-light-success p-50 m-0">
                                <div class="avatar-content"><i data-feather="dollar-sign" class="font-medium-5"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <small class="text-muted">Total paiements reçus</small>
                        </div>
                    </div>
                </div>

                <!-- Technician Share -->
                <div class="col-lg-3 col-sm-6 col-12">
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h2 class="fw-bolder mb-0 text-warning">
                                    <?= number_format($kpi_data['tech_commission'], 0) ?> DA</h2>
                                <p class="card-text">Salaires / Commissions</p>
                            </div>
                            <div class="avatar bg-light-warning p-50 m-0">
                                <div class="avatar-content"><i data-feather="users" class="font-medium-5"></i></div>
                            </div>
                        </div>
                        <div class="card-body">
                            <small class="text-muted">Part globale des techniciens</small>
                        </div>
                    </div>
                </div>

                <!-- Operations -->
                <div class="col-lg-3 col-sm-6 col-12">
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h2 class="fw-bolder mb-0"><?= $kpi_data['total_completed'] ?> <small
                                        class="fs-6 text-muted">Séances</small></h2>
                                <p class="card-text">Volume d'Activité</p>
                            </div>
                            <div class="avatar bg-light-info p-50 m-0">
                                <div class="avatar-content"><i data-feather="activity" class="font-medium-5"></i></div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <small class="text-danger">Absences: <?= $kpi_data['total_absent'] ?></small>
                                <small class="text-primary">Qualité Moy:
                                    <?= round($kpi_data['avg_pain'], 1) ?>/10</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row match-height">
                <!-- Revenue Evolution -->
                <div class="col-lg-8 col-12">
                    <div class="card">
                        <div
                            class="card-header d-flex justify-content-between align-items-sm-center align-items-start flex-sm-row flex-column">
                            <div class="header-left">
                                <h4 class="card-title">Évolution des Revenus Journaliers</h4>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="revenue-chart"></div>
                        </div>
                    </div>
                </div>

                <!-- Therapy Types Distribution -->
                <div class="col-lg-4 col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Répartition par Acte</h4>
                        </div>
                        <div class="card-body">
                            <div id="types-chart"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Table by Technician -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Performance & Paie par Technicien</h4>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Technicien</th>
                                        <th class="text-center">Séances (Fait/Absent)</th>
                                        <th class="text-center">CA Généré</th>
                                        <th class="text-center">Salaire (Com.)</th>
                                        <th class="text-center">Indice Qualité</th>
                                        <th class="text-center">Détails Paie</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($tech_stats)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center p-2">Aucune donnée trouvée pour cette
                                                période.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($tech_stats as $row):
                                            $total_sess = $row['completed_sessions'] + $row['absent_sessions'];
                                            $absent_rate = $total_sess > 0 ? round(($row['absent_sessions'] / $total_sess) * 100, 1) : 0;
                                            ?>
                                            <tr>
                                                <td class="fw-bold"><?= $row['technician_name'] ?></td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge badge-light-success"><?= $row['completed_sessions'] ?></span>
                                                    /
                                                    <span class="badge badge-light-danger"><?= $row['absent_sessions'] ?></span>
                                                    <small class="d-block text-muted mt-50">Taux absence:
                                                        <?= $absent_rate ?>%</small>
                                                </td>
                                                <td class="text-center fw-bold">
                                                    <?= number_format($row['generated_revenue'], 2) ?> DA</td>
                                                <td class="text-center text-success fw-bolder fs-5">
                                                    <?= number_format($row['tech_share'], 2) ?> DA</td>
                                                <td class="text-center">
                                                    <?php if ($row['avg_pain'] !== null): ?>
                                                        <span class="fw-bold"><?= round($row['avg_pain'], 1) ?>/10</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-outline-primary btn-details"
                                                        data-id="<?= $row['technician_id'] ?>"
                                                        data-name="<?= $row['technician_name'] ?>"
                                                        data-total="<?= $row['tech_share'] ?>">
                                                        <i data-feather="list"></i> Détails
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal Détails Paie (Salary Slip Details) -->
<div class="modal fade" id="techDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-transparent">
                <h5 class="modal-title">Fiche Détail : <span id="modal-tech-name" class="fw-bold text-primary"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- ترويسة الطباعة (تظهر فقط عند الطباعة) -->
                <div class="d-none d-print-block text-center mb-4">
                    <h3>Fiche de Paie / Rapport d'Activité</h3>
                    <h4 id="print-tech-name"></h4>
                    <p>Période du <?= date('d/m/Y', strtotime($date_from)) ?> au <?= date('d/m/Y', strtotime($date_to)) ?></p>
                    <hr>
                </div>

                <div class="alert alert-primary p-1 mb-2 d-print-none">
                    <i data-feather="calendar" class="me-50"></i> Période:
                    <strong><?= date('d/m/Y', strtotime($date_from)) ?></strong> au
                    <strong><?= date('d/m/Y', strtotime($date_to)) ?></strong>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Date & Heure</th>
                                <th>Patient</th>
                                <th>Acte / Type</th>
                                <th class="text-end">Prix Séance</th>
                                <th class="text-end">Règle Com.</th>
                                <th class="text-end">Montant Net (DA)</th>
                            </tr>
                        </thead>
                        <tbody id="modal-table-body">
                            <!-- JS will populate this -->
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <td colspan="5" class="text-end fw-bold">TOTAL À PAYER:</td>
                                <td class="text-end fw-bolder text-success fs-5" id="modal-total-amount"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                    <i data-feather="printer"></i> Imprimer
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<?php include_once 'foot.php'; ?>
<!-- Include ApexCharts -->
<script src="<?= SITE_URL; ?>/app-assets/vendors/js/charts/apexcharts.min.js"></script>

<script>
    $(document).ready(function () {
        // --- 1. Line Chart (Revenue Evolution) ---
        var dates = <?= json_encode(array_column($daily_stats, 'day_date')) ?>;
        var revenues = <?= json_encode(array_column($daily_stats, 'daily_revenue')) ?>;

        var revenueChartOptions = {
            chart: { height: 300, type: 'area', toolbar: { show: false }, fontFamily: 'Helvetica, Arial, sans-serif' },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            series: [{ name: 'Revenu (DA)', data: revenues }],
            xaxis: { categories: dates, type: 'datetime', tooltip: { enabled: false } },
            colors: ['#0093c9'],
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 0.2, stops: [0, 90, 100] } },
            tooltip: { y: { formatter: function (val) { return val + " DA" } } },
            grid: { padding: { bottom: 0 } }
        };

        if (dates.length > 0) {
            new ApexCharts(document.querySelector("#revenue-chart"), revenueChartOptions).render();
        } else {
            document.querySelector("#revenue-chart").innerHTML = "<div class='text-center p-3 text-muted'>Pas assez de données pour afficher le graphique.</div>";
        }

        // --- 2. Donut Chart (Therapy Types) ---
        var typeLabels = <?= json_encode(array_column($types_data, 'type_name')) ?>;
        var typeCounts = <?= json_encode(array_map('intval', array_column($types_data, 'session_count'))) ?>;

        var typeChartOptions = {
            chart: { type: 'donut', height: 320, fontFamily: 'Helvetica, Arial, sans-serif' },
            labels: typeLabels,
            series: typeCounts,
            colors: ['#00cfe8', '#28c76f', '#ea5455', '#ff9f43', '#0093c9', '#7367f0'],
            plotOptions: {
                pie: {
                    donut: {
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                formatter: function (w) { return w.globals.seriesTotals.reduce((a, b) => a + b, 0); }
                            }
                        }
                    }
                }
            },
            dataLabels: { enabled: false },
            legend: { position: 'bottom' }
        };

        if (typeCounts.length > 0) {
            new ApexCharts(document.querySelector("#types-chart"), typeChartOptions).render();
        } else {
            document.querySelector("#types-chart").innerHTML = "<div class='text-center p-3 text-muted'>Aucune donnée.</div>";
        }

        // --- 3. Logic for Detail Modal ---
        $('.btn-details').on('click', function () {
            var btn = $(this);
            var techId = btn.data('id');
            var techName = btn.data('name');
            var totalAmount = btn.data('total');

            // Setup Modal Headers
            $('#modal-tech-name').text(techName);
            $('#print-tech-name').text(techName); // For print view
            $('#modal-total-amount').text(new Intl.NumberFormat('fr-FR').format(totalAmount) + ' DA');
            $('#modal-table-body').html('<tr><td colspan="6" class="text-center p-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');

            $('#techDetailsModal').modal('show');

            // AJAX to fetch session details
            $.ajax({
                url: '<?= SITE_URL; ?>/handlers',
                type: 'POST',
                data: {
                    method: 'get_technician_report_details',
                    tech_id: techId,
                    date_from: '<?= $date_from ?>',
                    date_to: '<?= $date_to ?>'
                },
                dataType: 'json',
                success: function (res) {
                    if (res.state === 'true') {
                        var html = '';
                        if (res.data.length > 0) {
                            res.data.forEach(function (item) {
                                var dateObj = new Date(item.completed_at);
                                var dateStr = dateObj.toLocaleDateString('fr-FR');
                                var timeStr = dateObj.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });

                                var sessionPrice = parseFloat(item.session_price).toFixed(2);
                                var commission = parseFloat(item.commission_amount).toFixed(2);

                                // العرض الصحيح لنوع العمولة (Fixe أو نسبة)
                                var comDisplay = '';
                                if (item.commission_type === 'fixed') {
                                    comDisplay = '<span class="badge badge-light-info">Fixe (' + parseFloat(item.raw_commission_value) + ')</span>';
                                } else {
                                    comDisplay = parseFloat(item.raw_commission_value) + '%';
                                }

                                html += `<tr>
                                <td>${dateStr} <small class="text-muted">${timeStr}</small></td>
                                <td>${item.patient_name}</td>
                                <td><span class="badge badge-light-info">${item.act_name || '-'}</span></td>
                                <td class="text-end">${sessionPrice}</td>
                                <td class="text-end">${comDisplay}</td>
                                <td class="text-end fw-bold">${commission}</td>
                            </tr>`;
                            });
                        } else {
                            html = '<tr><td colspan="6" class="text-center p-3 text-muted">Aucune séance trouvée pour cette période.</td></tr>';
                        }
                        $('#modal-table-body').html(html);
                    } else {
                        $('#modal-table-body').html('<tr><td colspan="6" class="text-center text-danger p-3">Erreur lors du chargement des données.</td></tr>');
                    }
                },
                error: function () {
                    $('#modal-table-body').html('<tr><td colspan="6" class="text-center text-danger p-3">Erreur de communication serveur.</td></tr>');
                }
            });
        });
    });
</script>