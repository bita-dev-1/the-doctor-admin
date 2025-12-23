<?php
if (!isset($_SESSION['user']['id']) || !isset($id)) {
    die("Accès non autorisé.");
}
include_once 'config/DB.php';
$db = new DB();

$sql = "SELECT 
            ct.*,
            rd.id as dossier_id, 
            p.first_name, p.last_name, p.phone, p.address,
            u.first_name as recorded_by_fname, u.last_name as recorded_by_lname,
            cab.name as cabinet_name, cab.address as cabinet_address, cab.phone as cabinet_phone
        FROM caisse_transactions ct
        JOIN reeducation_dossiers rd ON ct.dossier_id = rd.id
        JOIN patient p ON rd.patient_id = p.id
        JOIN users u ON ct.recorded_by = u.id
        LEFT JOIN cabinets cab ON u.cabinet_id = cab.id
        WHERE ct.id = :id";
$stmt = $db->prepare($sql);
$stmt->execute([':id' => $id]);
$receipt = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$receipt) {
    die("Reçu non trouvé.");
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Reçu de Paiement #<?= $receipt['id'] ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">

    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/pages/receipt.css">

</head>

<body>
    <div class="receipt-container">
        <div class="row mb-4">
            <div class="col-8">
                <h4><?= htmlspecialchars($receipt['cabinet_name'] ?? 'The Doctor App') ?></h4>
                <p class="mb-0"><?= htmlspecialchars($receipt['cabinet_address'] ?? '') ?></p>
                <p><?= htmlspecialchars($receipt['cabinet_phone'] ?? '') ?></p>
            </div>
            <div class="col-4 text-right">
                <h3>REÇU</h3>
                <p><strong>N°:</strong> <?= $receipt['id'] ?></p>
                <p><strong>Date:</strong> <?= date('d/m/Y H:i', strtotime($receipt['payment_date'])) ?></p>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Reçu de</div>
            <div class="card-body">
                <strong><?= htmlspecialchars($receipt['first_name'] . ' ' . $receipt['last_name']) ?></strong><br>
                Adresse: <?= htmlspecialchars($receipt['address'] ?? 'N/A') ?><br>
                Téléphone: <?= htmlspecialchars($receipt['phone'] ?? 'N/A') ?>
            </div>
        </div>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Montant Payé</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Paiement pour dossier de rééducation #<?= $receipt['dossier_id'] ?></td>
                    <td class="text-right"><?= number_format($receipt['amount_paid'], 2) ?> DA</td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="font-weight-bold">
                    <td class="text-right">Total Payé</td>
                    <td class="text-right"><?= number_format($receipt['amount_paid'], 2) ?> DA</td>
                </tr>
            </tfoot>
        </table>

        <div class="mt-4">
            <p><strong>Encaissé par:</strong>
                <?= htmlspecialchars($receipt['recorded_by_fname'] . ' ' . $receipt['recorded_by_lname']) ?></p>
        </div>

        <div class="text-center mt-5 no-print">
            <button onclick="window.print()" class="btn btn-primary">Imprimer</button>
        </div>
    </div>
</body>

</html>