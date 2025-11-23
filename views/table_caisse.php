<?php
if (!isset($_SESSION['user']['id']) || !in_array($_SESSION['user']['role'], ['admin', 'nurse'])) {
    header('location:' . SITE_URL . '/');
    exit();
}
include_once 'header.php';
?>

<div class="app-content content">
    <div class="content-wrapper p-0">
        <section id="ajax-datatable">
            <div class="row">
                <div class="col-12">
                    <div class="card p-1">
                        <div class="card-header border-bottom">
                            <h4 class="card-title">Historique des Paiements</h4>
                        </div>
                        <div class="card-datatable">
                            <?php draw_table(array('query' => "qr_caisse_transactions_table", "table" => "caisse_transactions")); ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Modal for Recording Payment -->
<div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-labelledby="recordPaymentModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="recordPaymentModalLabel">Encaisser un Paiement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="recordPaymentForm">
                <div class="modal-body">
                    <?php set_csrf(); ?>
                    <div class="mb-1">
                        <?php draw_select(["label" => "Dossier de Rééducation", "name_id" => "dossier_id", "placeholder" => "Chercher patient ou N° dossier...", "serverSide" => ["table" => "reeducation_dossiers", "join" => [(object) ["type" => "JOIN", "table" => "patient", "condition" => "reeducation_dossiers.patient_id = patient.id"]], "value" => "reeducation_dossiers.id", "text" => ["patient.first_name", "patient.last_name", "'(Dossier #'", "reeducation_dossiers.id", "')'"], "where" => "reeducation_dossiers.deleted=0"]]); ?>
                    </div>
                    <div id="dossier-info" class="alert alert-secondary d-none"></div>
                    <div class="mb-1">
                        <label for="amount_paid" class="form-label">Montant à Payer</label>
                        <input type="number" class="form-control" name="amount_paid" id="amount_paid" step="0.01"
                            required>
                    </div>
                    <div class="mb-1 d-none" id="payment-options">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="pay-50-percent">Payer
                            50%</button>
                        <button type="button" class="btn btn-sm btn-outline-success" id="pay-full-balance">Payer le
                            solde</button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer Paiement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once 'foot.php'; ?>

<script>
    var dossierIdFromUrl = new URLSearchParams(window.location.search).get('dossier_id');

    var request = {
        "query": "qr_caisse_transactions_table",
        "method": "data_table",
        "actions": [
            {
                "action": "view", // Changed action to 'view' for the icon
                "class": "print-receipt-btn",
                "icon": '<i data-feather="printer" class="text-primary"></i>'
            }
        ],
        "button": [
            {
                "text": "Encaisser un Paiement",
                "class": "btn btn-primary",
                "action": "popup",
                "attr": 'data-bs-toggle="modal" data-bs-target="#recordPaymentModal"'
            }
        ]
    };

    if (dossierIdFromUrl) {
        request.condition = "ct.dossier_id = " + parseInt(dossierIdFromUrl);
        $('.card-title').append(' pour le Dossier #' + dossierIdFromUrl);
    }

    // --- LOGGING STEP 1 ---
    console.log('[LOG 1] Request object being sent to call_data_table:', JSON.parse(JSON.stringify(request)));
    call_data_table(request);

    // Print Receipt handler
    $('#codexTable').on('click', '.print-receipt-btn', function (e) {
        e.preventDefault();
        var transactionId = $(this).data('id');
        // We will create a new simple page for the receipt
        var receiptUrl = '<?= SITE_URL ?>/receipt/' + transactionId;
        window.open(receiptUrl, '_blank', 'width=800,height=600');
    });

    $(document).ready(function () {
        let currentDossierInfo = {};

        // Fetch and display dossier info when a dossier is selected
        $('#dossier_id').on('select2:select', function (e) {
            var dossierId = $(this).val();
            $.ajax({
                url: '<?= SITE_URL; ?>/handlers',
                type: 'POST',
                data: { method: 'get_dossier_payment_info', dossier_id: dossierId },
                dataType: 'json',
                success: function (response) {
                    if (response.state === "true") {
                        currentDossierInfo = response.data;
                        var info = currentDossierInfo;
                        var totalDueWithDiscount = info.total_due - info.discount_amount;
                        var resteAPayer = totalDueWithDiscount - info.total_paid;

                        $('#dossier-info').html(
                            `<strong>Patient:</strong> ${info.patient_name}<br>` +
                            `<strong>Tarif:</strong> ${info.price} DA (${info.payment_mode})<br>` +
                            `<strong>Remise:</strong> ${parseFloat(info.discount_amount).toFixed(2)} DA<br>` +
                            `<strong>Total à Payer:</strong> ${totalDueWithDiscount.toFixed(2)} DA<br>` +
                            `<strong>Total Payé:</strong> ${parseFloat(info.total_paid).toFixed(2)} DA<br>` +
                            `<strong>Reste à Payer:</strong> <span class="fw-bolder text-danger">${resteAPayer.toFixed(2)} DA</span>`
                        ).removeClass('d-none');

                        // Pre-fill amount
                        var amountToPay = (info.payment_mode === 'package') ? resteAPayer : parseFloat(info.price);
                        $('#amount_paid').val(amountToPay > 0 ? amountToPay.toFixed(2) : '0.00');

                        // Show payment buttons for 'package' mode
                        if (info.payment_mode === 'package') {
                            $('#payment-options').removeClass('d-none');
                        } else {
                            $('#payment-options').addClass('d-none');
                        }

                    } else {
                        $('#dossier-info').addClass('d-none');
                        $('#payment-options').addClass('d-none');
                        currentDossierInfo = {};
                    }
                }
            });
        });

        // Payment option buttons logic
        $('#pay-50-percent').on('click', function () {
            if (currentDossierInfo.total_due) {
                var totalAfterDiscount = currentDossierInfo.total_due - currentDossierInfo.discount_amount;
                $('#amount_paid').val((totalAfterDiscount / 2).toFixed(2));
            }
        });

        $('#pay-full-balance').on('click', function () {
            if (currentDossierInfo.total_due) {
                var totalAfterDiscount = currentDossierInfo.total_due - currentDossierInfo.discount_amount;
                var resteAPayer = totalAfterDiscount - currentDossierInfo.total_paid;
                $('#amount_paid').val(resteAPayer > 0 ? resteAPayer.toFixed(2) : '0.00');
            }
        });

        // Handle payment form submission
        $('#recordPaymentForm').on('submit', function (e) {
            e.preventDefault();
            var form = $(this);
            var btn = form.find('button[type="submit"]');

            $.ajax({
                url: '<?= SITE_URL; ?>/handlers',
                type: 'POST',
                data: form.serialize() + '&method=record_payment',
                dataType: 'json',
                beforeSend: function () {
                    btn.attr('disabled', 'disabled').text('Enregistrement...');
                },
                success: function (response) {
                    if (response.state === "true") {
                        $('#recordPaymentModal').modal('hide');
                        Swal.fire('Succès!', response.message, 'success');
                        $('#codexTable').DataTable().ajax.reload();
                    } else {
                        Swal.fire('Erreur!', response.message, 'error');
                    }
                },
                error: function () {
                    Swal.fire('Erreur!', 'Une erreur de communication est survenue.', 'error');
                },
                complete: function () {
                    btn.removeAttr('disabled').text('Enregistrer Paiement');
                }
            });
        });

        // Reset form on modal close
        $('#recordPaymentModal').on('hidden.bs.modal', function () {
            $('#recordPaymentForm')[0].reset();
            $('#dossier_id').val(null).trigger('change');
            $('#dossier-info').addClass('d-none');
            $('#payment-options').addClass('d-none');
            currentDossierInfo = {};
        });
    });
</script>