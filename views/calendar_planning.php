<?php

// Check if user data exists and user is logged in
if (empty($_SESSION['user']['data'])) {
    header('Location: ' . SITE_URL . '/login');
    exit();
}

include_once 'header.php';

$table = 'rdv';

// Initialize result array
$result = [
    'patient_id' => '',
    'phone'      => '',
    'date'       => '',
    'rdv_num'    => ''
];

$calendarCSS = SITE_URL . '/app-assets/vendors/css/calendars/fullcalendar.min.css';
$appCalendarCSS = SITE_URL . '/app-assets/css/pages/app-calendar.css';

// Define $btn_text.
$btn_text = 'Ajouter';

?>

<link rel="stylesheet" type="text/css" href="<?= $calendarCSS ?>">
<link rel="stylesheet" type="text/css" href="<?= $appCalendarCSS ?>">

<!-- BEGIN: Content-->
<div class="app-content content">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-wrapper p-0">
        <div class="content-header row"></div>
        <div class="content-body">
            <section>
                <div class="app-calendar overflow-hidden border">
                    <div class="row g-0">
                        <!-- Sidebar -->
                        <div class="col app-calendar-sidebar flex-grow-0 overflow-hidden d-flex flex-column" id="app-calendar-sidebar">
                            <div class="sidebar-wrapper">
                                <div class="card-body d-flex justify-content-center">
                                    <button class="btn btn-primary btn-toggle-sidebar w-100" data-bs-toggle="modal" data-bs-target="#add-new-sidebar">
                                        <span class="align-middle">Ajouter rendez-vous</span>
                                    </button>
                                </div>
                                <div class="card-body pb-0">
                                    <h5 class="section-label mb-1"><span class="align-middle">Filtre</span></h5>
                                    <div class="form-check form-check-secondary mb-1">
                                        <input type="checkbox" class="form-check-input select-all" id="select-all" checked />
                                        <label class="form-check-label" for="select-all">Voir Tout</label>
                                    </div>
                                    <div class="calendar-events-filter">
                                        <div class="form-check form-check-warning mb-1">
                                            <input type="checkbox" class="form-check-input input-filter" id="Waiting" data-value="0" checked />
                                            <label class="form-check-label" for="Waiting">Créé</label>
                                        </div>
                                        <div class="form-check form-check-info mb-1">
                                            <input type="checkbox" class="form-check-input input-filter" id="pending" data-value="1" checked />
                                            <label class="form-check-label" for="pending">Accepté</label>
                                        </div>
                                        <div class="form-check form-check-success mb-1">
                                            <input type="checkbox" class="form-check-input input-filter" id="completed" data-value="2" checked />
                                            <label class="form-check-label" for="completed">Complété</label>
                                        </div>
                                        <div class="form-check form-check-danger mb-1">
                                            <input type="checkbox" class="form-check-input input-filter" id="canceled" data-value="3" checked />
                                            <label class="form-check-label" for="canceled">Annulé</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- /Sidebar -->

                        <!-- Calendar -->
                        <div class="col position-relative">
                            <div class="card shadow-none border-0 mb-0 rounded-0">
                                <div class="card-body pb-0">
                                    <div id="calendar" data-id="<?= htmlspecialchars($_SESSION['user']['data'][0]['id'] ?? '') ?>"></div>
                                </div>
                            </div>
                        </div>
                        <!-- /Calendar -->
                        <div class="body-content-overlay"></div>
                    </div>
                </div>

                <!-- Calendar Add Event Modal -->
                <div class="modal modal-slide-in event-sidebar fade" id="add-new-sidebar">
                    <div class="modal-dialog sidebar-lg">
                        <div class="modal-content p-0">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                            <div class="modal-header mb-1">
                                <h5 class="modal-title">Ajouter rendez-vous</h5>
                            </div>
                            <div class="modal-body flex-grow-1 pb-sm-0 pb-3">
                                <form class="event-form needs-validation rdvForm" method="post" data-express="<?= customEncryption($table); ?>" novalidate>
                                    <?php set_csrf() ?>
                                    <div class="row">
                                        <div class="col-md-12 col-12">
                                            <div class="card">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <?php if ($_SESSION['user']['data'][0]['type'] == 1) { ?>
                                                            <div class="col-lg-6 col-md-6 col-12 mb-1">
                                                                <?php
                                                                $input = array(
                                                                    "label"         => $GLOBALS['language']['doctor'],
                                                                    "name_id"       => "doctor_id",
                                                                    "placeholder"   => $GLOBALS['language']['doctor'],
                                                                    "class"         => "",
                                                                    "his_parent"    => "",
                                                                    "serverSide"        => array(
                                                                        "table"         => "doctor",
                                                                        "value"         => "id",
                                                                        "value_parent"  => "",
                                                                        "text"          => array("first_name", "last_name"),
                                                                        "selected"      => $result['doctor_id'] ?? null,
                                                                        "where"         => "type = 0 AND deleted = 0"
                                                                    )
                                                                );
                                                                draw_select($input);
                                                                ?>
                                                            </div>
                                                        <?php
                                                        } else {
                                                            $input = array(
                                                                "label"         => "",
                                                                "type"          => "hidden",
                                                                "name_id"       => "doctor_id",
                                                                "placeholder"   => "",
                                                                "class"         => "",
                                                                "value"         => $_SESSION['user']['data'][0]['id']
                                                            );
                                                            draw_input($input);
                                                        }
                                                        ?>
                                                        <div class="col-lg-6 col-md-6 col-12 mb-1">
                                                            <?php
                                                            $input = array(
                                                                "label"         => $GLOBALS['language']['patient'],
                                                                "name_id"       => "patient_id",
                                                                "placeholder"   => $GLOBALS['language']['patient'],
                                                                "class"         => "",
                                                                "his_parent"    => "",
                                                                "serverSide"        => array(
                                                                    "table"         => "patient",
                                                                    "value"         => "id",
                                                                    "value_parent"  => "",
                                                                    "text"          => array("first_name", "last_name"),
                                                                    "selected"      => $result['patient_id'] ?? null,
                                                                    "where"         => "deleted = 0"
                                                                )
                                                            );
                                                            draw_select($input);
                                                            ?>
                                                        </div>
                                                        <div class="col-lg-4 col-md-6 col-12 mb-1">
                                                            <?php
                                                            $input = array(
                                                                "label"         => $GLOBALS['language']['firstname'],
                                                                "type"          => "text",
                                                                "name_id"       => "first_name",
                                                                "placeholder"   => $GLOBALS['language']['firstname'],
                                                                "class"         => "",
                                                                "value"         => $result['first_name'] ?? null
                                                            );
                                                            draw_input($input);
                                                            ?>
                                                        </div>
                                                        <div class="col-lg-4 col-md-6 col-12 mb-1">
                                                            <?php
                                                            $input = array(
                                                                "label"         => $GLOBALS['language']['lastname'],
                                                                "type"          => "text",
                                                                "name_id"       => "last_name",
                                                                "placeholder"   => $GLOBALS['language']['lastname'],
                                                                "class"         => "",
                                                                "value"         => $result['last_name'] ?? null
                                                            );
                                                            draw_input($input);
                                                            ?>
                                                        </div>
                                                        <div class="col-lg-4 col-md-6 col-12 mb-1">
                                                            <?php
                                                            $input = array(
                                                                "label"         => $GLOBALS['language']['phone'],
                                                                "type"          => "text",
                                                                "name_id"       => "phone",
                                                                "placeholder"   => $GLOBALS['language']['phone'],
                                                                "class"         => "",
                                                                "value"         => $result['phone'] ?? null
                                                            );
                                                            draw_input($input);
                                                            ?>
                                                        </div>
                                                        <div class="col-lg-6 col-md-6 col-12 mb-1">
                                                            <?php
                                                            $input = array(
                                                                "label"         => $GLOBALS['language']['date'],
                                                                "type"          => "text",
                                                                "name_id"       => "date",
                                                                "placeholder"   => "YYYY-MM-DD",
                                                                "class"         => "picker",
                                                                "value"         => $result['date'] ?? null
                                                            );
                                                            draw_input($input);
                                                            ?>
                                                        </div>
                                                        <div class="col-lg-6 col-md-6 col-12 mb-1">
                                                            <?php
                                                            $input = array(
                                                                "label"         => $GLOBALS['language']['rdv_num'],
                                                                "name_id"       => "rdv_num",
                                                                "placeholder"   => $GLOBALS['language']['rdv_num'],
                                                                "class"         => "rdv_num",
                                                                "attr"          => "data-search = '-1' ",
                                                                "his_parent"    => "",
                                                                "clientSideSelected"    => "",
                                                                "clientSide"   => array()
                                                            );

                                                            draw_select($input);
                                                            ?>
                                                        </div>
                                                    </div>
                                                    <?php
                                                    if (isset($id) && !empty($id)) {
                                                        $input = array(
                                                            "label"         => "",
                                                            "type"          => "hidden",
                                                            "name_id"       => "rdv_id",
                                                            "placeholder"   => "",
                                                            "class"         => "",
                                                            "value"         => $id
                                                        );
                                                        draw_input($input);
                                                    }

                                                    $button = array(
                                                        "text"          => $btn_text,
                                                        "type"          => "submit",
                                                        "name_id"       => "submit",
                                                        "class"         => "btn-primary mt-2"
                                                    );
                                                    draw_button($button);
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!--/ Calendar Add Event Modal -->
            </section>
        </div>
    </div>
</div>

<?php include_once 'foot.php';
$fullcalendarJS = SITE_URL . '/app-assets/vendors/js/calendar/fullcalendar.min.js';
$momentJS = SITE_URL . '/app-assets/vendors/js/extensions/moment.min.js';
$appCalendarJS = SITE_URL . '/app-assets/js/scripts/pages/app-calendar.js';
$handlerURL = SITE_URL . '/handlers';
?>

<script src="<?= $fullcalendarJS ?>"></script>
<script src="<?= $momentJS ?>"></script>
<script src="<?= $appCalendarJS ?>"></script>

<script>
    $(document).ready(function() {
        $('.rdvForm').validate({
            rules: {
                'doctor_id': {
                    required: true
                },
                'date': {
                    required: true
                },
                'rdv_num': {
                    required: true
                },
                'first_name': {
                    required: true
                },
                'last_name': {
                    required: true
                },
                'phone': {
                    required: true
                }
            }
        });

        $('.rdv_num.select2').select2({
            dropdownParent: $('.rdv_num.select2').parent(),
            placeholder: $('.rdv_num.select2').attr('placeholder'),
            ajax: {
                type: "post",
                dataType: "json",
                url: "<?= $handlerURL ?>",
                delay: 250,
                data: function(params) {
                    var query = {
                        method: 'handleRdv_nbr'
                    }

                    if ($('.picker').val() != "")
                        query.date = $('.picker').val();

                    if ($('#doctor_id').val() != null)
                        query.doctor = $('#doctor_id').val();

                    return query;
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                cache: true
            },
        }).change(function() {
            $('.rdv_num.select2').valid();
        });

        $(document).on('change', '#patient_id', function(e) {
            e.preventDefault();

            let self = $(this);
            let data = {
                id: self.val(),
                method: "getPatients"
            };

            $.ajax({
                type: "POST",
                url: "<?= $handlerURL ?>",
                data: data,
                dataType: "json",
                success: function(data) {
                    if (data[0] && data[0].hasOwnProperty('id')) {
                        $('#first_name').val(data[0].first_name);
                        $('#last_name').val(data[0].last_name);
                        $('#phone').val(data[0].phone);
                    }
                }
            });
        });

        $(document).on('submit', '.rdvForm', function(e) {
            e.preventDefault();

            let self = $(this);
            let data = {
                patient: $('#patient_id').val(),
                doctor: $('#doctor_id').val(),
                rdv_num: $('#rdv_num').val(),
                date: $('#date').val(),
                first_name: $('#first_name').val(),
                last_name: $('#last_name').val(),
                phone: $('#phone').val(),
                method: "postRdv"
            };

            $.ajax({
                type: "POST",
                url: "<?= $handlerURL ?>",
                data: data,
                dataType: "json",
                beforeSend: function() {
                    let svg = '<svg class="preloader" width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" stroke="currentColor"><g fill="none" fill-rule="evenodd"><g transform="translate(1 1)" stroke-width="2"><circle stroke-opacity=".5" cx="12" cy="12" r="12"/><path d="M24 12c0-6.627-5.373-12-12-12"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/></path></g></g></svg>';
                    self.find(':submit').attr("disabled", "disabled");
                    self.find(':submit').append(svg);
                },
                success: function(data) {
                    if (data.state != "false") {
                        Swal.fire({
                            title: data.message,
                            icon: 'success',
                            confirmButtonText: 'OK',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            },
                            buttonsStyling: false
                        }).then((result) => {
                            // Reload the page after the user clicks "OK"
                            if (result.isConfirmed) {
                                location.reload();
                            }
                        });


                    } else {
                        Swal.fire({
                            title: data.message,
                            icon: 'error',
                            confirmButtonText: 'OK',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            },
                            buttonsStyling: false
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    Swal.fire({
                        title: 'An error occurred!',
                        text: 'Please try again later.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false
                    });
                },
                complete: function() {
                    self.find(':submit').removeAttr("disabled");
                    $('.preloader').remove();
                }
            });
        });

        $('#add-new-sidebar').on('hidden.bs.modal', function () {
            $(this).find('form')[0].reset();
            $(this).find('.select2').val(null).trigger('change');
        });
    });
</script>