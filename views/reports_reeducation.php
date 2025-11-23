<?php 
    // MODIFIED: Allow any admin (Super or Cabinet) to access, as per requirements
    if(!isset($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'admin'){
        header('location:'.SITE_URL.'/');
        exit();
    }
    include_once 'header.php'; 

    // --- FILTERS ---
    $date_from = $_GET['date_from'] ?? date('Y-m-01');
    $date_to = $_GET['date_to'] ?? date('Y-m-t');
    $technician_filter = isset($_GET['technician_id']) && !empty($_GET['technician_id']) ? "AND rd.technician_id = " . intval($_GET['technician_id']) : "";

    // --- CABINET FILTER FOR MULTI-TENANCY ---
    // If it's a Cabinet Admin, restrict data to their cabinet
    $cabinet_data_filter = "";
    if (!empty($_SESSION['user']['cabinet_id'])) {
        $cabinet_data_filter = " AND u.cabinet_id = " . intval($_SESSION['user']['cabinet_id']);
    }

    // --- REPORT DATA ---
    $report_sql = "SELECT 
        u.id as technician_id,
        CONCAT(u.first_name, ' ', u.last_name) as technician_name,
        COUNT(DISTINCT rs.id) as completed_sessions,
        SUM(rd.price / rd.sessions_prescribed) as total_revenue,
        SUM((rd.price / rd.sessions_prescribed) * (rd.technician_percentage / 100)) as technician_earnings
    FROM reeducation_sessions rs
    JOIN reeducation_dossiers rd ON rs.dossier_id = rd.id
    JOIN users u ON rd.technician_id = u.id
    WHERE rs.status = 'completed' 
      AND rs.completed_at BETWEEN :date_from AND :date_to
      $technician_filter
      $cabinet_data_filter
    GROUP BY u.id, technician_name
    ORDER BY technician_name";

    $stmt = $db->prepare($report_sql);
    $stmt->execute([':date_from' => $date_from . ' 00:00:00', ':date_to' => $date_to . ' 23:59:59']);
    $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- SUMMARY STATS ---
    $total_revenue = array_sum(array_column($report_data, 'total_revenue'));
    $total_technician_share = array_sum(array_column($report_data, 'technician_earnings'));
    $cabinet_share = $total_revenue - $total_technician_share;
?>
<div class="app-content content">
    <div class="content-wrapper p-0">
        <div class="content-header row">
            <div class="col-12 mb-2">
                <h2 class="content-header-title float-start mb-0">Rapports de Rééducation</h2>
            </div>
        </div>
        <div class="content-body">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Filtres du Rapport</h4>
                </div>
                <div class="card-body">
                    <form method="get">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">De</label>
                                <input type="text" class="form-control picker" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">À</label>
                                <input type="text" class="form-control picker" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                            </div>
                            <div class="col-md-4">
                                <?php 
                                    // Filter technicians dropdown by cabinet if needed
                                    $tech_where = "role='doctor' AND deleted=0";
                                    if (!empty($_SESSION['user']['cabinet_id'])) {
                                        $tech_where .= " AND cabinet_id=" . intval($_SESSION['user']['cabinet_id']);
                                    }
                                    draw_select(["label"=>"Technicien", "name_id"=>"technician_id", "placeholder"=>"Tous les techniciens", "serverSide"=>["table"=>"users", "value"=>"id", "text"=>["first_name", "last_name"], "selected"=>$_GET['technician_id'] ?? null, "where"=>$tech_where]]); 
                                ?>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-1">Filtrer</button>
                    </form>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-4 col-12">
                    <div class="card"><div class="card-body"><h3><?= number_format($total_revenue, 2) ?> DA</h3><span>Revenu Total Généré</span></div></div>
                </div>
                <div class="col-lg-4 col-12">
                    <div class="card"><div class="card-body"><h3><?= number_format($total_technician_share, 2) ?> DA</h3><span>Part Totale Techniciens</span></div></div>
                </div>
                <div class="col-lg-4 col-12">
                    <div class="card"><div class="card-body"><h3><?= number_format($cabinet_share, 2) ?> DA</h3><span>Part Cabinet</span></div></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Rapport des Parts par Technicien</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Technicien</th>
                                    <th>Séances Complétées</th>
                                    <th>Revenu Total Généré</th>
                                    <th>Part Technicien</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($report_data)): ?>
                                    <tr><td colspan="4" class="text-center">Aucune donnée à afficher pour la période sélectionnée.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($report_data as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['technician_name']) ?></td>
                                        <td><?= htmlspecialchars($row['completed_sessions']) ?></td>
                                        <td><?= number_format($row['total_revenue'], 2) ?> DA</td>
                                        <td class="fw-bolder text-success"><?= number_format($row['technician_earnings'], 2) ?> DA</td>
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
<?php include_once 'foot.php'; ?>