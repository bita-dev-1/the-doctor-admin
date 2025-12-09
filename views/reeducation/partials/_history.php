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
                    <a href="<?= SITE_URL ?>/caisse?dossier_id=<?= $id ?>" class="btn btn-sm btn-primary"><i
                            data-feather="dollar-sign"></i> Gérer</a>
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