<?php

function postReeducationDossier($DB)
{
    try {
        if (!isset($_SESSION['user']['id'])) {
            throw new Exception("Auth required");
        }

        $DB->pdo->beginTransaction();

        $array_data = array();
        $table = 'reeducation_dossiers';

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

        if (isset($csrf)) {
            $csrf = customDecrypt($csrf);
            if (!is_csrf_valid($csrf)) {
                throw new Exception($GLOBALS['language']['The form is forged']);
            }
        }

        $array_data['created_by'] = $_SESSION['user']['id'];
        $array_data['status'] = 'active';

        $DB->table = $table;
        $DB->data = $array_data;
        $dossier_id = $DB->insert();

        if (!$dossier_id) {
            throw new Exception("Erreur lors de la création du dossier.");
        }

        $dates_json = $_POST['initial_sessions_dates'] ?? '[]';
        $dates = json_decode($dates_json, true);

        if (!empty($dates) && is_array($dates)) {
            $tech_id = $array_data['technician_id'];
            $tech_data = $DB->select("SELECT cabinet_id FROM users WHERE id = $tech_id")[0] ?? null;
            $cabinet_id = $tech_data['cabinet_id'] ?? ($_SESSION['user']['cabinet_id'] ?? null);

            foreach ($dates as $date_str) {
                if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date_str))
                    continue;

                $rdv_data = [
                    'patient_id' => $array_data['patient_id'],
                    'doctor_id' => $tech_id,
                    'cabinet_id' => $cabinet_id,
                    'date' => $date_str,
                    'state' => 0,
                    'created_by' => $_SESSION['user']['id'],
                ];
                $DB->table = 'rdv';
                $DB->data = $rdv_data;
                $rdv_id = $DB->insert();

                if (!$rdv_id)
                    throw new Exception("Erreur création RDV pour le $date_str");

                $session_data = [
                    'dossier_id' => $dossier_id,
                    'rdv_id' => $rdv_id,
                    'status' => 'planned',
                ];
                $DB->table = 'reeducation_sessions';
                $DB->data = $session_data;
                $session_id = $DB->insert();

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

function generate_sessions_manual($DB)
{
    if (!isset($_POST['dossier_id']) || !isset($_POST['dates']) || !is_array($_POST['dates'])) {
        echo json_encode(["state" => "false", "message" => "Données manquantes."]);
        return;
    }

    $dossier_id = filter_var($_POST['dossier_id'], FILTER_SANITIZE_NUMBER_INT);
    $dates = $_POST['dates'];

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

    $sql_check_locked = "SELECT COUNT(*) FROM reeducation_sessions 
                         WHERE dossier_id = :dossier_id 
                         AND (status = 'completed' OR payment_status = 'paid')";
    $stmt_locked = $DB->prepare($sql_check_locked);
    $stmt_locked->execute([':dossier_id' => $dossier_id]);

    if ($stmt_locked->fetchColumn() == 0) {
        $sql_delete_rdv = "DELETE FROM rdv WHERE reeducation_session_id IN (SELECT id FROM reeducation_sessions WHERE dossier_id = :dossier_id AND status = 'planned')";
        $stmt_del_rdv = $DB->prepare($sql_delete_rdv);
        $stmt_del_rdv->execute([':dossier_id' => $dossier_id]);

        $sql_delete_sessions = "DELETE FROM reeducation_sessions WHERE dossier_id = :dossier_id AND status = 'planned'";
        $stmt_del_sess = $DB->prepare($sql_delete_sessions);
        $stmt_del_sess->execute([':dossier_id' => $dossier_id]);
    }

    try {
        if (!$DB->pdo->inTransaction()) {
            $DB->pdo->beginTransaction();
        }

        $sessions_created_count = 0;
        $cabinet_to_assign = $dossier['technician_cabinet_id'] ?? ($dossier['cabinet_id'] ?? null);

        foreach ($dates as $date_str) {
            if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date_str))
                continue;

            $check_sql = "SELECT COUNT(*) FROM rdv r 
                          JOIN reeducation_sessions rs ON r.reeducation_session_id = rs.id 
                          WHERE rs.dossier_id = :dossier_id AND r.date = :date";
            $stmt_check = $DB->prepare($check_sql);
            $stmt_check->execute([':dossier_id' => $dossier_id, ':date' => $date_str]);

            if ($stmt_check->fetchColumn() > 0) {
                continue;
            }

            $rdv_data = [
                'patient_id' => $dossier['patient_id'],
                'doctor_id' => $dossier['technician_id'],
                'cabinet_id' => $cabinet_to_assign,
                'date' => $date_str,
                'state' => 0,
                'created_by' => $_SESSION['user']['id'],
            ];
            $DB->table = 'rdv';
            $DB->data = $rdv_data;
            $rdv_id = $DB->insert();

            if (!$rdv_id) {
                throw new Exception("Erreur lors de la création du RDV pour la date $date_str");
            }

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

            $DB->table = 'rdv';
            $DB->data = ['reeducation_session_id' => $session_id];
            $DB->where = 'id = ' . $rdv_id;
            $DB->update();

            $sessions_created_count++;
        }

        $DB->pdo->commit();
        echo json_encode(["state" => "true", "message" => "$sessions_created_count séances ont été planifiées avec succès."]);

    } catch (\Throwable $e) {
        if ($DB->pdo->inTransaction()) {
            $DB->pdo->rollBack();
        }
        echo json_encode(["state" => "false", "message" => "Erreur système: " . $e->getMessage()]);
    }
}

function validate_session($DB)
{
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

    $old_session_sql = "SELECT status, dossier_id FROM reeducation_sessions WHERE id = $session_id";
    $old_session = $DB->select($old_session_sql)[0] ?? null;

    if (!$old_session) {
        echo json_encode(["state" => "false", "message" => "Session introuvable."]);
        return;
    }

    $sql_info = "SELECT 
                    rs.dossier_id, 
                    rd.price, 
                    rd.sessions_prescribed, 
                    rd.technician_percentage,
                    rd.technician_id,
                    cs.commission_type
                 FROM reeducation_sessions rs 
                 JOIN reeducation_dossiers rd ON rs.dossier_id = rd.id
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

        if ($session_status === 'completed') {
            $sessions_count = (int) $info['sessions_prescribed'];
            if ($sessions_count <= 0)
                $sessions_count = 1;

            if (isset($info['commission_type']) && $info['commission_type'] === 'fixed') {
                $commission_amount = (float) $info['technician_percentage'] / $sessions_count;
            } else {
                $session_price = (float) $info['price'] / $sessions_count;
                $commission_amount = $session_price * ((float) $info['technician_percentage'] / 100);
            }
        }

        $session_data = [
            'status' => $session_status,
            'completed_at' => $completed_at,
            'exercises_performed' => $_POST['exercises_performed'] ?? null,
            'pain_scale' => $_POST['pain_scale'] ?? null,
            'observations' => $_POST['observations'] ?? null,
            'duration' => $_POST['duration'] ?? null,
            'commission_amount' => number_format($commission_amount, 2, '.', '')
        ];

        if ($old_session['status'] !== 'completed') {
            $session_data['completed_by'] = $_SESSION['user']['id'];
        }

        $DB->table = 'reeducation_sessions';
        $DB->data = $session_data;
        $DB->where = 'id = ' . $session_id;

        if (!$DB->update()) {
            throw new Exception("Erreur lors de la mise à jour de la session.");
        }

        if ($old_session['status'] !== 'completed' && $session_status === 'completed') {
            $DB->update('reeducation_dossiers', [], "id=" . $info['dossier_id'], "sessions_completed = sessions_completed + 1");

            $dossier_id = $info['dossier_id'];
            $total_paid_query = $DB->select("SELECT SUM(amount_paid) as total FROM caisse_transactions WHERE dossier_id = $dossier_id")[0];
            $total_paid = $total_paid_query['total'] ?? 0;

            $dossier_data = $DB->select("SELECT price, discount_amount, sessions_prescribed, payment_mode FROM reeducation_dossiers WHERE id = $dossier_id")[0];

            $net_price = (float) $dossier_data['price'] - (float) $dossier_data['discount_amount'];
            $count = (int) $dossier_data['sessions_prescribed'] > 0 ? (int) $dossier_data['sessions_prescribed'] : 1;
            $price_per_session = $net_price / $count;

            $sessions_covered = ($price_per_session > 0) ? floor(($total_paid + 0.1) / $price_per_session) : 999;

            $DB->update('reeducation_sessions', ['payment_status' => 'unpaid'], "dossier_id = $dossier_id");

            if ($sessions_covered > 0) {
                $limit = intval($sessions_covered);
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


function get_kine_queue($DB)
{
    if (!isset($_SESSION['user']['id'])) {
        echo json_encode([]);
        return;
    }

    $tech_id = $_SESSION['user']['id'];
    $today = date('Y-m-d');

    // 1. قائمة اليوم (Aujourd'hui) - لم تتغير
    $sql_today = "SELECT 
                rs.id as session_id,
                rs.status,
                r.hours as time,
                CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                LEFT(p.first_name, 1) as f_init, LEFT(p.last_name, 1) as l_init,
                rt.name as act_name,
                (
                    SELECT COUNT(*) 
                    FROM reeducation_sessions s2 
                    INNER JOIN rdv r2 ON s2.rdv_id = r2.id 
                    WHERE s2.dossier_id = rs.dossier_id 
                    AND r2.deleted = 0 
                    AND (r2.date < r.date OR (r2.date = r.date AND r2.hours <= r.hours))
                ) as session_num,
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

    // 2. القائمة النشطة (En cours) - تم التعديل
    $sql_active = "SELECT 
                    derived.*,
                    (
                        SELECT COUNT(*) 
                        FROM reeducation_sessions s2 
                        INNER JOIN rdv r2 ON s2.rdv_id = r2.id
                        WHERE s2.dossier_id = derived.dossier_id 
                        AND r2.deleted = 0
                        AND (r2.date < derived.ref_date OR (r2.date = derived.ref_date AND r2.hours <= derived.ref_time))
                    ) as session_num
                FROM (
                    SELECT 
                        rd.id as dossier_id,
                        target_session.id as session_id,
                        target_rdv.date as ref_date,
                        target_rdv.hours as ref_time,
                        'active' as status,
                        '' as time,
                        CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                        LEFT(p.first_name, 1) as f_init, LEFT(p.last_name, 1) as l_init,
                        rt.name as act_name,
                        rd.sessions_prescribed as total_sessions
                    FROM reeducation_dossiers rd
                    JOIN patient p ON rd.patient_id = p.id
                    LEFT JOIN reeducation_types rt ON rd.reeducation_type_id = rt.id
                    
                    -- JOIN لاختيار الجلسة القادمة (اليوم أو المستقبل فقط)
                    JOIN reeducation_sessions target_session ON target_session.id = (
                        SELECT rs.id 
                        FROM reeducation_sessions rs 
                        JOIN rdv r ON rs.rdv_id = r.id 
                        WHERE rs.dossier_id = rd.id 
                        AND rs.status = 'planned' 
                        AND r.deleted = 0 
                        -- التعديل هنا: شرط التاريخ أكبر من أو يساوي اليوم
                        AND r.date >= '$today'
                        ORDER BY r.date ASC, r.hours ASC 
                        LIMIT 1
                    )
                    
                    JOIN rdv target_rdv ON target_session.rdv_id = target_rdv.id
                    
                    WHERE rd.technician_id = $tech_id
                    AND rd.status = 'active'
                    AND rd.deleted = 0
                ) as derived
                ORDER BY derived.ref_date ASC, derived.ref_time ASC
                LIMIT 50";

    $data_active = $DB->select($sql_active);

    foreach ($data_active as &$row) {
        $row['initials'] = strtoupper(($row['f_init'] ?? '') . ($row['l_init'] ?? ''));
    }

    echo json_encode([
        "state" => "true",
        "data" => [
            "today" => $data_today,
            "active" => $data_active
        ]
    ]);
}

function get_kine_workspace_data($DB)
{
    $session_id = filter_var($_POST['session_id'], FILTER_SANITIZE_NUMBER_INT);

    if (empty($session_id)) {
        echo '<div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted">
                <div class="mb-2"><i data-feather="alert-circle" style="width: 50px; height: 50px;"></i></div>
                <h4>Aucune séance trouvée</h4>
              </div><script>if(feather) feather.replace();</script>';
        return;
    }

    // المنطق: نفس منطق العد الزمني المستخدم في القوائم أعلاه
    $sql = "SELECT 
                rs.id as session_id, rs.status, rs.payment_status, rs.observations, rs.pain_scale, 
                rs.duration, rs.exercises_performed, rs.rdv_id,
                r.date as rdv_date, r.hours as rdv_time,
                rd.id as dossier_id, rd.price, rd.payment_mode, rd.sessions_prescribed, rd.sessions_completed,
                CONCAT(p.first_name, ' ', p.last_name) as patient_name, p.phone, p.id as patient_id,
                rt.name as act_name,
                (SELECT SUM(amount_paid) FROM caisse_transactions WHERE dossier_id = rd.id) as total_paid,
                (
                    SELECT COUNT(*) 
                    FROM reeducation_sessions s2 
                    INNER JOIN rdv r2 ON s2.rdv_id = r2.id 
                    WHERE s2.dossier_id = rs.dossier_id 
                    AND r2.deleted = 0 
                    AND (r2.date < r.date OR (r2.date = r.date AND r2.hours <= r.hours))
                ) as current_rank
            FROM reeducation_sessions rs
            JOIN reeducation_dossiers rd ON rs.dossier_id = rd.id
            JOIN patient p ON rd.patient_id = p.id
            LEFT JOIN rdv r ON rs.rdv_id = r.id
            LEFT JOIN reeducation_types rt ON rd.reeducation_type_id = rt.id
            WHERE rs.id = $session_id";

    $data = $DB->select($sql)[0] ?? null;

    if (!$data) {
        echo '<div class="alert alert-danger m-2">Erreur : Session introuvable.</div>';
        return;
    }

    // ... (باقي كود العرض HTML يبقى كما هو تماماً) ...
    // ... (نسخ نفس كود HTML من الردود السابقة) ...

    $total_price = (float) $data['price'];
    $total_paid = (float) ($data['total_paid'] ?? 0);
    $remaining = $total_price - $total_paid;
    if ($remaining < 0)
        $remaining = 0;
    $session_price = $data['sessions_prescribed'] > 0 ? ($total_price / $data['sessions_prescribed']) : 0;
    $current_rank = $data['current_rank'];
    $total_sessions = $data['sessions_prescribed'];
    $progress_percent = $total_sessions > 0 ? ($current_rank / $total_sessions) * 100 : 0;
    $is_completed = ($data['status'] === 'completed');
    $readonly_attr = $is_completed ? 'disabled' : '';
    $is_session_paid = ($data['payment_status'] === 'paid');
    $rdv_date_display = date('d/m/Y', strtotime($data['rdv_date']));
    $rdv_date_iso = date('Y-m-d', strtotime($data['rdv_date']));
    ?>
    <input type="hidden" id="workspace_session_id" value="<?= $data['session_id'] ?>">
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
                            <span class="badge bg-light-info ms-1"><i data-feather="calendar"
                                    style="width: 12px; height: 12px;"></i> <?= $rdv_date_display ?></span>
                            <small class="text-muted ms-1"><i data-feather="phone"></i> <?= $data['phone'] ?></small>
                        </div>
                    </div>
                </div>
                <div class="text-end" id="action-buttons-container">
                    <?php if (!$is_completed): ?>
                        <button class="btn btn-outline-info me-1 reschedule-session-btn" data-rdv="<?= $data['rdv_id'] ?>"
                            data-date="<?= $data['rdv_date'] ?>" data-time="<?= $data['rdv_time'] ?>"><i
                                data-feather="calendar"></i> Reporter</button>
                        <button class="btn btn-success shadow validate-session-btn" data-id="<?= $data['session_id'] ?>"><i
                                data-feather="check-circle"></i> Terminer</button>
                    <?php else: ?>
                        <div class="d-flex align-items-center gap-1">
                            <span class="badge bg-success fs-6 p-2"><i data-feather="check"></i> Terminée</span>
                            <button class="btn btn-outline-secondary btn-sm" onclick="enableEditMode()"><i
                                    data-feather="edit-2"></i> Modifier</button>
                            <button class="btn btn-primary btn-sm d-none" id="btn-save-edit"
                                onclick="$('.validate-session-btn').click()">Enregistrer</button>
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
                                onclick="processQuickPay(<?= $data['dossier_id'] ?>, <?= number_format($session_price, 2, '.', '') ?>)"><i
                                    data-feather="dollar-sign"></i> Payer la séance (<?= number_format($session_price, 0) ?>
                                DA)</button>
                            <?php if ($remaining > $session_price): ?>
                                <button class="btn btn-outline-secondary"
                                    onclick="processQuickPay(<?= $data['dossier_id'] ?>, <?= number_format($remaining, 2, '.', '') ?>)">Tout
                                    solder (<?= number_format($remaining, 0) ?> DA)</button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="mt-2 text-start bg-white border p-1 rounded">
                        <div class="d-flex justify-content-between"><small>Total Dossier:</small><small
                                class="fw-bold"><?= number_format($total_price, 0) ?> DA</small></div>
                        <div class="d-flex justify-content-between"><small>Déjà payé:</small><small
                                class="fw-bold text-success"><?= number_format($total_paid, 0) ?> DA</small></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function get_technician_report_details($DB)
{
    if (!isset($_SESSION['user']['id']) || $_SESSION['user']['role'] !== 'admin') {
        echo json_encode(["state" => "false", "message" => "Auth required"]);
        return;
    }

    $tech_id = filter_var($_POST['tech_id'], FILTER_SANITIZE_NUMBER_INT);
    $date_from = $_POST['date_from'];
    $date_to = $_POST['date_to'];

    $cabinet_id = $_SESSION['user']['cabinet_id'] ?? 0;

    $service_join_condition = $cabinet_id
        ? "cs.cabinet_id = $cabinet_id"
        : "cs.cabinet_id = (SELECT cabinet_id FROM users WHERE id = rd.technician_id)";

    $sql = "SELECT 
                rs.completed_at,
                CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                rt.name as act_name,
                (rd.price / GREATEST(rd.sessions_prescribed, 1)) as session_price,
                rd.technician_percentage as raw_commission_value,
                cs.commission_type,
                CASE 
                    WHEN rs.commission_amount > 0 THEN rs.commission_amount
                    WHEN cs.commission_type = 'fixed' THEN (rd.technician_percentage / GREATEST(rd.sessions_prescribed, 1))
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

    $stmt = $DB->prepare($sql);
    $stmt->execute([
        ':tech_id' => $tech_id,
        ':date_from' => $date_from . ' 00:00:00',
        ':date_to' => $date_to . ' 23:59:59'
    ]);

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["state" => "true", "data" => $data]);
}

function get_technician_planning_data($DB)
{
    if (!isset($_POST['technician_id'])) {
        echo json_encode(["state" => "false"]);
        return;
    }

    $tech_id = intval($_POST['technician_id']);

    // 1. جلب إعدادات الطبيب
    $user = $DB->select("SELECT travel_hours, tickets_day FROM users WHERE id = $tech_id")[0] ?? null;

    $working_days = [];
    $tickets_day = [];

    if ($user) {
        // تحليل أيام العمل
        $schedule = json_decode($user['travel_hours'] ?? '[]', true);
        $day_map = ["Dimanche" => 0, "Lundi" => 1, "Mardi" => 2, "Mercredi" => 3, "Jeudi" => 4, "Vendredi" => 5, "Samedi" => 6];

        if (is_array($schedule)) {
            foreach ($schedule as $day_name => $hours) {
                if (!empty($hours['from']) && !empty($hours['to'])) {
                    if (isset($day_map[$day_name])) {
                        $working_days[] = $day_map[$day_name];
                    }
                }
            }
        }

        // تحليل عدد التذاكر
        $tickets_day = json_decode($user['tickets_day'] ?? '{}', true);
    }

    // 2. جلب الحجوزات الحالية
    $sql_bookings = "SELECT DATE(date) as rdv_date, COUNT(*) as total 
                     FROM rdv 
                     WHERE doctor_id = $tech_id 
                     AND deleted = 0 
                     AND state != 3 
                     AND date >= CURDATE() 
                     GROUP BY DATE(date)";

    $res_bookings = $DB->select($sql_bookings);
    $bookings_map = [];
    foreach ($res_bookings as $row) {
        $bookings_map[$row['rdv_date']] = $row['total'];
    }

    echo json_encode([
        "state" => "true",
        "data" => [
            "workingDays" => $working_days,
            "ticketsPerDay" => $tickets_day,
            "globalBookings" => $bookings_map
        ]
    ]);
}
?>