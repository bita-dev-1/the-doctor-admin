<?php
// التحقق من الصلاحيات
if (!isset($_SESSION['user']['id'])) {
    header('location:' . SITE_URL . '/login');
    exit();
}
include_once 'header.php';
?>

<style>
    /* --- Styles du Cockpit (Layout) --- */
    .cockpit-wrapper {
        height: calc(100vh - 170px);
        overflow: hidden;
        border: 1px solid #ebe9f1;
        border-radius: 0.428rem;
        background-color: #fff;
    }

    .patient-queue {
        height: 100%;
        background: #fff;
        border-right: 1px solid #ebe9f1;
        display: flex;
        flex-direction: column;
    }

    .queue-content {
        flex: 1;
        overflow-y: auto;
    }

    .patient-card {
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        border-left: 4px solid transparent;
        position: relative;
        padding: 15px;
        border-bottom: 1px solid #ebe9f1;
    }

    .patient-card:hover {
        background-color: #f8f9fa;
    }

    .patient-card.active {
        background-color: #e0f2fe;
        border-left: 4px solid #00cfe8;
    }

    .patient-card h6 {
        font-size: 0.95rem;
        margin-bottom: 0.2rem;
    }

    .workspace-area {
        height: 100%;
        overflow-y: auto;
        background: #f8f8f8;
        padding: 0;
        position: relative;
    }

    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #b9b9c3;
        text-align: center;
    }

    .queue-tabs .nav-link {
        padding: 12px 10px;
        font-weight: 600;
        border-radius: 0;
        border: none;
        border-bottom: 2px solid transparent;
        color: #6e6b7b;
    }

    .queue-tabs .nav-link.active {
        border-bottom: 2px solid #7367f0;
        color: #7367f0;
    }

    .time-badge {
        font-size: 0.75rem;
        padding: 0.3rem 0.5rem;
    }
</style>

<div class="app-content content">
    <div class="content-wrapper p-0">
        <div class="content-header row mb-1">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h2 class="content-header-title float-start mb-0">
                    <i data-feather="activity"></i> Espace Kiné (Cockpit)
                </h2>
                <div>
                    <a href="<?= SITE_URL ?>/reeducation/insert" class="btn btn-primary btn-sm shadow">
                        <i data-feather="plus"></i> Nouveau Dossier
                    </a>
                </div>
            </div>
        </div>

        <div class="content-body">
            <div class="card cockpit-wrapper mb-0">
                <div class="row g-0 h-100">

                    <!-- 1. Queue (Left Sidebar) -->
                    <div class="col-md-4 col-lg-3 col-12 patient-queue">
                        <ul class="nav nav-tabs nav-fill queue-tabs mb-0" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="tab-today" data-bs-toggle="tab" href="#queue-today"
                                    role="tab">Aujourd'hui</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="tab-active" data-bs-toggle="tab" href="#queue-active"
                                    role="tab">En cours</a>
                            </li>
                        </ul>

                        <div class="tab-content queue-content">
                            <div class="tab-pane active h-100" id="queue-today" role="tabpanel">
                                <div class="p-1 border-bottom bg-white sticky-top">
                                    <div class="input-group input-group-merge">
                                        <span class="input-group-text"><i data-feather="search"></i></span>
                                        <input type="text" class="form-control" id="search-today"
                                            placeholder="Rechercher RDV...">
                                    </div>
                                </div>
                                <div id="today-list-container">
                                    <div class="text-center p-3">
                                        <div class="spinner-border text-primary" role="status"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane h-100" id="queue-active" role="tabpanel">
                                <div class="p-1 border-bottom bg-white sticky-top">
                                    <div class="input-group input-group-merge">
                                        <span class="input-group-text"><i data-feather="search"></i></span>
                                        <input type="text" class="form-control" id="search-active"
                                            placeholder="Chercher patient...">
                                    </div>
                                </div>
                                <div id="active-list-container">
                                    <div class="text-center p-3">
                                        <div class="spinner-border text-primary" role="status"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Workspace (Right Area) -->
                    <div class="col-md-8 col-lg-9 col-12 workspace-area">
                        <div id="workspace-content" class="h-100 p-2">
                            <div class="empty-state">
                                <div class="mb-2">
                                    <div class="avatar bg-light-primary p-3">
                                        <span class="avatar-content"><i data-feather="monitor"
                                                class="font-large-2"></i></span>
                                    </div>
                                </div>
                                <h4>Prêt à travailler ?</h4>
                                <p class="text-muted">Sélectionnez un patient dans la liste à gauche pour afficher son
                                    dossier.</p>
                            </div>
                        </div>

                        <div id="workspace-loader"
                            class="position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-75 d-flex justify-content-center align-items-center d-none"
                            style="z-index: 10;">
                            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Rescheduling Session -->
<div class="modal fade" id="rescheduleSessionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifier Date/Heure</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rescheduleSessionForm">
                <div class="modal-body">
                    <?php set_csrf(); ?>
                    <input type="hidden" name="rdv_id" id="reschedule_rdv_id">

                    <div class="mb-1">
                        <label for="new_date" class="form-label">Nouvelle Date</label>
                        <input type="text" class="form-control picker" name="new_date" id="new_date" required>
                    </div>
                    <div class="mb-1">
                        <label for="new_time" class="form-label">Nouvelle Heure (Optionnel)</label>
                        <input type="time" class="form-control" name="new_time" id="new_time">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Sauvegarder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once 'foot.php'; ?>

<script>
    $(document).ready(function () {
        loadQueueData();

        // Search Filters
        $('#search-today').on('keyup', function () {
            var value = $(this).val().toLowerCase();
            $("#today-list-container .patient-card").filter(function () { $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1) });
        });
        $('#search-active').on('keyup', function () {
            var value = $(this).val().toLowerCase();
            $("#active-list-container .patient-card").filter(function () { $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1) });
        });

        // Validate Session Logic with Future Date Warning
        $(document).on('click', '.validate-session-btn', function (e) {
            e.preventDefault();
            var btn = $(this);
            var sessionId = btn.data('id');

            // Get data from workspace inputs
            var painScale = $('#ws-pain').val();
            var observations = $('#ws-observations').val();
            var duration = $('#ws-duration').val();
            var exercises = $('#ws-exercises').val();

            // --- التحقق من التاريخ (Future Date Check) ---
            var rdvDate = $('#workspace_rdv_date').val(); // القيمة من الحقل المخفي YYYY-MM-DD

            // الحصول على تاريخ اليوم المحلي للمستخدم
            var today = new Date();
            var dd = String(today.getDate()).padStart(2, '0');
            var mm = String(today.getMonth() + 1).padStart(2, '0');
            var yyyy = today.getFullYear();
            var todayStr = yyyy + '-' + mm + '-' + dd;

            // المقارنة النصية (ISO Format)
            var isFuture = (rdvDate > todayStr);
            // ---------------------------------------------

            var isEditMode = btn.hasClass('d-none');

            // إعداد رسالة التحذير
            var swalTitle = isEditMode ? 'Enregistrer les modifications ?' : 'Terminer la séance ?';
            var swalText = "";
            var swalIcon = 'warning';
            var confirmColor = '#7367f0'; // Primary color

            if (isFuture && !isEditMode) {
                swalTitle = 'Attention : Séance Future !';
                swalText = "Cette séance est planifiée pour le <b>" + rdvDate.split('-').reverse().join('/') + "</b>.<br>La valider aujourd'hui est <b>sous votre entière responsabilité</b>.";
                swalIcon = 'error'; // أيقونة حمراء للتحذير الشديد
                confirmColor = '#ea5455'; // لون أحمر لزر التأكيد
            }

            Swal.fire({
                title: swalTitle,
                html: swalText,
                icon: swalIcon,
                showCancelButton: true,
                confirmButtonText: 'Oui, confirmer',
                cancelButtonText: 'Annuler',
                confirmButtonColor: confirmColor,
                customClass: {
                    confirmButton: 'btn ' + (isFuture ? 'btn-danger' : 'btn-primary'),
                    cancelButton: 'btn btn-outline-secondary ms-1'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    if (!isEditMode) {
                        var originalHtml = btn.html();
                        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                    }

                    $.ajax({
                        url: '<?= SITE_URL ?>/handlers',
                        type: 'POST',
                        data: {
                            method: 'validate_session',
                            session_id: sessionId,
                            session_status: 'completed',
                            pain_scale: painScale,
                            observations: observations,
                            duration: duration,
                            exercises_performed: exercises
                        },
                        dataType: 'json',
                        success: function (res) {
                            if (res.state === 'true') {
                                Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 1500, showConfirmButton: false });
                                loadQueueData();
                                var activeCard = $('.patient-card.active')[0];
                                if (activeCard) loadPatientWorkspace(sessionId, activeCard);
                            } else {
                                Swal.fire('Erreur', res.message, 'error');
                                if (!isEditMode) btn.prop('disabled', false).html(originalHtml);
                            }
                        }
                    });
                }
            });
        });

        // --- Reschedule Logic ---
        $(document).on('click', '.reschedule-session-btn', function (e) {
            e.preventDefault();
            var rdvId = $(this).data('rdv');
            var currentDate = $(this).data('date');
            var currentTime = $(this).data('time');

            $('#reschedule_rdv_id').val(rdvId);

            // Handle Flatpickr
            if ($('#new_date').hasClass('flatpickr-input')) {
                var fp = document.querySelector("#new_date")._flatpickr;
                if (fp) fp.setDate(currentDate);
            } else {
                $('#new_date').val(currentDate);
            }

            $('#new_time').val(currentTime);
            $('#rescheduleSessionModal').modal('show');
        });

        $('#rescheduleSessionForm').on('submit', function (e) {
            e.preventDefault();
            var form = $(this);
            var btn = form.find('button[type="submit"]');

            $.ajax({
                url: '<?= SITE_URL; ?>/handlers',
                type: 'POST',
                data: form.serialize() + '&method=reschedule_session',
                dataType: 'json',
                beforeSend: function () { btn.attr('disabled', 'disabled').text('En cours...'); },
                success: function (response) {
                    if (response.state === "true") {
                        $('#rescheduleSessionModal').modal('hide');
                        Swal.fire('Succès!', response.message, 'success');
                        loadQueueData(); // Refresh queue
                        // Refresh workspace if the active patient was rescheduled
                        var activeCard = $('.patient-card.active')[0];
                        if (activeCard) {
                            var sessionId = $('#workspace_session_id').val();
                            loadPatientWorkspace(sessionId, activeCard);
                        }
                    } else {
                        Swal.fire('Erreur!', response.message, 'error');
                    }
                },
                complete: function () { btn.removeAttr('disabled').text('Sauvegarder'); }
            });
        });
    });

    function loadQueueData() {
        $.ajax({
            url: '<?= SITE_URL ?>/handlers', type: 'POST', data: { method: 'get_kine_queue' }, dataType: 'json',
            success: function (res) {
                if (res.state === 'true') {
                    renderQueue('#today-list-container', res.data.today, 'Aucun rendez-vous pour aujourd\'hui.');
                    renderQueue('#active-list-container', res.data.active, 'Aucun dossier actif trouvé.');
                }
            }
        });
    }

    function renderQueue(containerId, data, emptyMsg) {
        var html = '';
        if (data.length === 0) {
            $(containerId).html('<div class="text-center p-4 text-muted"><i data-feather="inbox" class="mb-1"></i><br>' + emptyMsg + '</div>');
            if (feather) feather.replace();
            return;
        }
        data.forEach(function (p) {
            var badgeClass = p.status === 'completed' ? 'badge-light-success' : 'badge-light-warning';
            var timeDisplay = (p.status === 'active') ? 'Ouvert' : (p.status === 'completed' ? 'Terminé' : (p.time ? p.time.substring(0, 5) : 'Prévu'));
            var card = `
            <div class="patient-card" onclick="loadPatientWorkspace(${p.session_id}, this)">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div class="d-flex align-items-center">
                        <div class="avatar bg-light-primary me-1"><span class="avatar-content fw-bold">${p.initials}</span></div>
                        <div style="line-height: 1.2;">
                            <h6 class="mb-0 fw-bold text-truncate" style="max-width: 140px;">${p.patient_name}</h6>
                            <small class="text-muted">${p.act_name}</small>
                        </div>
                    </div>
                    <span class="badge ${badgeClass} time-badge">${timeDisplay}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="font-small-2 fw-bold text-primary"><i data-feather="layers" style="width:12px"></i> Séance ${p.session_num}/${p.total_sessions}</span>
                    <i data-feather="chevron-right" class="text-muted" style="width: 16px;"></i>
                </div>
            </div>`;
            html += card;
        });
        $(containerId).html(html);
        if (feather) feather.replace();
    }

    function loadPatientWorkspace(sessionId, element) {
        $('.patient-card').removeClass('active');
        if (element) $(element).addClass('active');
        $('#workspace-loader').removeClass('d-none');

        $.ajax({
            url: '<?= SITE_URL ?>/handlers', type: 'POST', data: { method: 'get_kine_workspace_data', session_id: sessionId },
            success: function (html) {
                $('#workspace-content').html(html);
                $('#workspace-loader').addClass('d-none');
                if (feather) feather.replace();
            },
            error: function () {
                $('#workspace-content').html('<div class="text-center text-danger mt-5">Erreur de chargement.</div>');
                $('#workspace-loader').addClass('d-none');
            }
        });
    }

    function enableEditMode() {
        $('#ws-pain, #ws-observations, #ws-duration, #ws-exercises').prop('disabled', false);
        $('#btn-save-edit').removeClass('d-none');
        $('#ws-observations').focus();
    }

    function processQuickPay(dossierId, amount) {
        Swal.fire({
            title: 'Encaissement', text: "Confirmer le paiement de " + amount + " DA ?", icon: 'info',
            showCancelButton: true, confirmButtonText: 'Oui', cancelButtonText: 'Non',
            customClass: { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-outline-secondary ms-1' }, buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?= SITE_URL ?>/handlers', type: 'POST',
                    data: { method: 'record_payment', dossier_id: dossierId, amount_paid: amount },
                    dataType: 'json',
                    success: function (res) {
                        if (res.state === 'true') {
                            Swal.fire({ position: 'top-end', icon: 'success', title: 'Paiement enregistré', showConfirmButton: false, timer: 1500 });
                            var currentSessionId = $('#workspace_session_id').val();
                            var activeCard = $('.patient-card.active')[0];
                            loadPatientWorkspace(currentSessionId, activeCard);
                        } else { Swal.fire('Erreur', res.message, 'error'); }
                    }
                });
            }
        });
    }
</script>