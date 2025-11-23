<?php
if (!isset($_SESSION['user']['id'])) {
    header('location:' . SITE_URL . '/login');
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
                            <h4 class="card-title">Séances du Jour</h4>
                            <p class="card-text">Liste des séances de rééducation planifiées pour aujourd'hui.</p>
                        </div>
                        <div class="card-datatable">
                            <?php
                            draw_table(array(
                                'query' => "qr_technician_today_sessions",
                                'table' => "reeducation_sessions",
                                'columns' => [
                                    "id",                // 1. REF
                                    "hours",             // 2. Heures
                                    "patient",           // 3. Patient
                                    "type_reeducation",  // 4. Type
                                    "session_num",       // 5. Numéro (Subquery result)
                                    "sessions_restantes",// 6. Restantes
                                    "statut_paiement",   // 7. Paiement
                                    "action"             // 8. Action Button (was __action)
                                ]
                            ));
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Modal for Session Validation -->
<div class="modal fade" id="validateSessionModal" tabindex="-1" aria-labelledby="validateSessionModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="validateSessionModalLabel">Valider la Séance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="validateSessionForm">
                <div class="modal-body">
                    <?php set_csrf(); ?>
                    <input type="hidden" name="session_id" id="modal_session_id">
                    <div class="mb-1">
                        <label class="form-label">Présence</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="session_status" id="status_completed"
                                value="completed" checked>
                            <label class="form-check-label" for="status_completed">Présent</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="session_status" id="status_absent"
                                value="absent">
                            <label class="form-check-label" for="status_absent">Absent</label>
                        </div>
                    </div>
                    <div id="session-details-fields">
                        <div class="mb-1">
                            <label for="duration" class="form-label">Durée (minutes)</label>
                            <input type="number" class="form-control" name="duration" id="duration"
                                placeholder="Ex: 45">
                        </div>
                        <div class="mb-1">
                            <label for="exercises_performed" class="form-label">Description des exercices</label>
                            <textarea class="form-control" name="exercises_performed" id="exercises_performed"
                                rows="3"></textarea>
                        </div>
                        <div class="mb-1">
                            <label for="pain_scale" class="form-label">Douleur (échelle 0-10)</label>
                            <input type="number" class="form-control" name="pain_scale" id="pain_scale" min="0"
                                max="10">
                        </div>
                        <div class="mb-1">
                            <label for="observations" class="form-label">Commentaire rapide / Observations</label>
                            <textarea class="form-control" name="observations" id="observations" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Valider Séance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal for Rescheduling Session -->
<div class="modal fade" id="rescheduleSessionModal" tabindex="-1" aria-labelledby="rescheduleSessionModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rescheduleSessionModalLabel">Modifier Date/Heure</h5>
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
    var request = {
        "query": "qr_technician_today_sessions",
        "method": "data_table",
        "actions": [],
        "button": []
    };

    call_data_table(request);

    $(document).ready(function () {

        $('body').on('click', '.validate-session-btn', function (e) {
            e.preventDefault();
            var sessionId = $(this).data('id');
            $('#modal_session_id').val(sessionId);
            $('#validateSessionForm')[0].reset();
            $('#validateSessionModal').modal('show');
        });

        $('input[name="session_status"]').on('change', function () {
            if ($(this).val() === 'absent') {
                $('#session-details-fields').hide();
            } else {
                $('#session-details-fields').show();
            }
        });

        $('#validateSessionForm').on('submit', function (e) {
            e.preventDefault();
            var form = $(this);
            var btn = form.find('button[type="submit"]');

            $.ajax({
                url: '<?= SITE_URL; ?>/handlers',
                type: 'POST',
                data: form.serialize() + '&method=validate_session',
                dataType: 'json',
                beforeSend: function () {
                    btn.attr('disabled', 'disabled').text('Validation...');
                },
                success: function (response) {
                    if (response.state === "true") {
                        $('#validateSessionModal').modal('hide');
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
                    btn.removeAttr('disabled').text('Valider Séance');
                }
            });
        });

        // --- Reschedule Logic ---
        $('body').on('click', '.reschedule-session-btn', function (e) {
            e.preventDefault();
            var rdvId = $(this).data('rdv');
            var currentDate = $(this).data('date');
            var currentTime = $(this).data('time');

            $('#reschedule_rdv_id').val(rdvId);

            // Initialize or update flatpickr
            if ($('#new_date').hasClass('flatpickr-input')) {
                var fp = document.querySelector("#new_date")._flatpickr;
                fp.setDate(currentDate);
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
                beforeSend: function () {
                    btn.attr('disabled', 'disabled').text('En cours...');
                },
                success: function (response) {
                    if (response.state === "true") {
                        $('#rescheduleSessionModal').modal('hide');
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
                    btn.removeAttr('disabled').text('Sauvegarder');
                }
            });
        });
    });
</script>