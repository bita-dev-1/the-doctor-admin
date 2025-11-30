<?php
// التحقق من الصلاحيات (Admin أو Nurse)
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
                            <h4 class="card-title" id="page-title">Historique des Paiements</h4>
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
                        <!-- حقل اختيار الملف (Select2) -->
                        <?php draw_select([
                            "label" => "Dossier de Rééducation",
                            "name_id" => "dossier_id",
                            "placeholder" => "Chercher patient ou N° dossier...",
                            "serverSide" => [
                                "table" => "reeducation_dossiers",
                                "join" => [(object) ["type" => "JOIN", "table" => "patient", "condition" => "reeducation_dossiers.patient_id = patient.id"]],
                                "value" => "reeducation_dossiers.id",
                                "text" => ["patient.first_name", "patient.last_name", "'(Dossier #'", "reeducation_dossiers.id", "')'"],
                                "where" => "reeducation_dossiers.deleted=0"
                            ]
                        ]); ?>
                    </div>

                    <!-- منطقة عرض تفاصيل الملف والحسابات -->
                    <div id="dossier-info" class="alert alert-secondary d-none"></div>

                    <div class="mb-1">
                        <label for="amount_paid" class="form-label">Montant à Payer</label>
                        <input type="number" class="form-control" name="amount_paid" id="amount_paid" step="0.01"
                            required>
                    </div>

                    <!-- أزرار الدفع السريع -->
                    <div class="mb-1 d-none" id="payment-options">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="pay-50-percent">Payer
                            50%</button>
                        <button type="button" class="btn btn-sm btn-outline-success" id="pay-full-balance">Tout
                            Payer</button>
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
    // 1. استخراج معرف الملف من الرابط (إذا تم توجيه المستخدم من صفحة أخرى)
    var dossierIdFromUrl = new URLSearchParams(window.location.search).get('dossier_id');

    // 2. إعداد طلب الجدول
    var request = {
        "query": "qr_caisse_transactions_table",
        "method": "data_table",
        "actions": [
            {
                "action": "view",
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

    // 3. تطبيق الفلتر إذا وجد المعرف
    if (dossierIdFromUrl) {
        request.condition = "ct.dossier_id = " + parseInt(dossierIdFromUrl);
        $(document).ready(function () {
            $('#page-title').append(' <span class="text-primary">(Dossier #' + dossierIdFromUrl + ')</span>');
        });
    }

    // 4. تهيئة الجدول
    call_data_table(request);

    // --- المنطق البرمجي (JavaScript) ---

    // زر طباعة الإيصال
    $('#codexTable').on('click', '.print-receipt-btn', function (e) {
        e.preventDefault();
        var transactionId = $(this).data('id');
        var receiptUrl = '<?= SITE_URL ?>/receipt/' + transactionId;
        window.open(receiptUrl, '_blank', 'width=800,height=600');
    });

    $(document).ready(function () {
        let currentDossierInfo = {};

        // عند اختيار ملف من القائمة
        $('#dossier_id').on('select2:select', function (e) {
            var dossierId = $(this).val();

            // تنظيف البيانات السابقة
            $('#dossier-info').addClass('d-none').html('');
            $('#payment-options').addClass('d-none');
            $('#amount_paid').val('');

            $.ajax({
                url: '<?= SITE_URL; ?>/handlers',
                type: 'POST',
                data: { method: 'get_dossier_payment_info', dossier_id: dossierId },
                dataType: 'json',
                success: function (response) {
                    if (response.state === "true") {
                        currentDossierInfo = response.data;
                        var info = currentDossierInfo;

                        // تحديد وضع العرض
                        var displayMode = (info.payment_mode === 'package') ? 'Forfait Global' : 'Par séance';

                        // بناء HTML لعرض التفاصيل
                        var tarifDetailsHtml = '';

                        if (info.payment_mode === 'package') {
                            // في حالة الفوفت، السعر هو الإجمالي
                            tarifDetailsHtml = `<strong>Tarif Forfaitaire:</strong> ${parseFloat(info.gross_total).toFixed(2)} DA`;
                        } else {
                            // في حالة الجلسة، نعرض الإجمالي وسعر الجلسة الواحدة
                            tarifDetailsHtml = `<strong>Tarif Global:</strong> ${parseFloat(info.gross_total).toFixed(2)} DA<br>
                                                <strong>Prix par séance:</strong> ${parseFloat(info.unit_price).toFixed(2)} DA`;
                        }

                        // عرض البيانات في الـ Alert
                        $('#dossier-info').html(
                            `<strong>Patient:</strong> ${info.patient_name}<br>` +
                            tarifDetailsHtml + `<br>` +
                            `<strong>Remise:</strong> ${parseFloat(info.discount_amount).toFixed(2)} DA<br>` +
                            `<strong>Total Net (Après remise):</strong> ${parseFloat(info.net_total).toFixed(2)} DA<br>` +
                            `<strong>Déjà Payé:</strong> ${parseFloat(info.total_paid).toFixed(2)} DA<br>` +
                            `<strong>Reste à Payer:</strong> <span class="fw-bolder text-danger">${parseFloat(info.remaining_balance).toFixed(2)} DA</span>`
                        ).removeClass('d-none');

                        // ملء حقل المبلغ بالمبلغ المقترح (سعر جلسة أو المتبقي)
                        $('#amount_paid').val(parseFloat(info.amount_to_pay).toFixed(2));

                        // إظهار أزرار الدفع السريع إذا كان هناك متبقي
                        if (parseFloat(info.remaining_balance) > 0) {
                            $('#payment-options').removeClass('d-none');
                        } else {
                            $('#payment-options').addClass('d-none');
                        }

                    } else {
                        // في حالة الفشل
                        $('#dossier-info').addClass('d-none');
                        $('#payment-options').addClass('d-none');
                        currentDossierInfo = {};
                        Swal.fire('Erreur', 'Impossible de récupérer les informations du dossier.', 'error');
                    }
                },
                error: function () {
                    Swal.fire('Erreur', 'Erreur de communication avec le serveur.', 'error');
                }
            });
        });

        // منطق أزرار الدفع السريع

        // دفع 50%
        $('#pay-50-percent').on('click', function () {
            if (currentDossierInfo.net_total) {
                // حساب 50% من الإجمالي الصافي
                var halfAmount = parseFloat(currentDossierInfo.net_total) / 2;
                var remaining = parseFloat(currentDossierInfo.remaining_balance);

                // التأكد من عدم تجاوز المبلغ المتبقي
                if (halfAmount > remaining) {
                    halfAmount = remaining;
                }

                $('#amount_paid').val(halfAmount.toFixed(2));
            }
        });

        // دفع كل المتبقي
        $('#pay-full-balance').on('click', function () {
            if (currentDossierInfo.remaining_balance) {
                $('#amount_paid').val(parseFloat(currentDossierInfo.remaining_balance).toFixed(2));
            }
        });

        // عند إرسال نموذج الدفع
        $('#recordPaymentForm').on('submit', function (e) {
            e.preventDefault();
            var form = $(this);
            var btn = form.find('button[type="submit"]');
            var amount = parseFloat($('#amount_paid').val());

            // التحقق من صحة المبلغ
            if (isNaN(amount) || amount <= 0) {
                Swal.fire('Attention', 'Veuillez entrer un montant valide supérieur à 0.', 'warning');
                return;
            }

            // التحقق من عدم تجاوز المبلغ المتبقي (اختياري، يمكن السماح به كبقشيش أو رصيد إضافي، لكن يفضل التنبيه)
            if (currentDossierInfo.remaining_balance && amount > parseFloat(currentDossierInfo.remaining_balance) + 100) {
                // +100 هامش خطأ بسيط أو سماحية
                if (!confirm("Attention : Le montant saisi est supérieur au reste à payer. Voulez-vous continuer ?")) {
                    return;
                }
            }

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
                        $('#codexTable').DataTable().ajax.reload(); // تحديث الجدول
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

        // إعادة تعيين النموذج عند إغلاق المودال
        $('#recordPaymentModal').on('hidden.bs.modal', function () {
            $('#recordPaymentForm')[0].reset();
            $('#dossier_id').val(null).trigger('change');
            $('#dossier-info').addClass('d-none');
            $('#payment-options').addClass('d-none');
            currentDossierInfo = {};
        });
    });
</script>