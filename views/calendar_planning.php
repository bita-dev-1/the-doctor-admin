<?php
    // MODIFIED: Corrected security check to allow all logged-in users
    if(!isset($_SESSION['user']['id'])){
        header('location:'.SITE_URL.'/login');
        exit();
    }
    include_once 'header.php';

    $table = 'rdv';
    $result = []; 
    $calendarCSS = SITE_URL . '/app-assets/vendors/css/calendars/fullcalendar.min.css';
    $appCalendarCSS = SITE_URL . '/app-assets/css/pages/app-calendar.css';
    $btn_text = 'Ajouter';

    // Set the doctor ID for the calendar. 
    // If admin, it's initially empty, if doctor/nurse, it's their own ID.
    $doctor_id_for_calendar = ($_SESSION['user']['role'] !== 'admin') ? $_SESSION['user']['id'] : '';
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
                                    <!-- NEW: Add Doctor filter for Admins -->
                                    <?php if ($_SESSION['user']['role'] === 'admin') : ?>
                                        <div class="mb-1">
                                            <?php
                                                $doctor_where_clause = "role = 'doctor' AND deleted = 0";
                                                if (!empty($_SESSION['user']['cabinet_id'])) {
                                                    $doctor_where_clause .= " AND cabinet_id = " . intval($_SESSION['user']['cabinet_id']);
                                                }
                                                $input = array(
                                                    "label"         => $GLOBALS['language']['doctor'],
                                                    "name_id"       => "calendar_doctor_filter",
                                                    "placeholder"   => "Tous les médecins",
                                                    "class"         => "",
                                                    "serverSide"    => array(
                                                        "table"     => "users",
                                                        "value"     => "id",
                                                        "text"      => array("first_name", "last_name"),
                                                        "where"     => $doctor_where_clause
                                                    )
                                                );
                                                draw_select($input);
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                    
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
                                    <div id="calendar" data-doctor-id="<?= htmlspecialchars($doctor_id_for_calendar) ?>"></div>
                                </div>
                            </div>
                        </div>
                        <!-- /Calendar -->
                        <div class="body-content-overlay"></div>
                    </div>
                </div>

                <!-- Calendar Add/Edit Event Modal -->
                <div class="modal modal-slide-in event-sidebar fade" id="add-new-sidebar">
                    <div class="modal-dialog sidebar-lg">
                        <div class="modal-content p-0">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">×</button>
                            <div class="modal-header mb-1">
                                <h5 class="modal-title">Détails du Rendez-vous</h5>
                            </div>
                            <div class="modal-body flex-grow-1 pb-sm-0 pb-3">
                                <form class="event-form needs-validation" data-id="0" novalidate>
                                    <div class="mb-1">
                                        <label for="title" class="form-label">Patient</label>
                                        <input type="text" class="form-control" id="title" name="title" placeholder="Nom du Patient" readonly />
                                    </div>
                                    <div class="mb-1">
                                        <label for="event-phone" class="form-label">Téléphone</label>
                                        <input type="text" class="form-control" id="event-phone" readonly />
                                    </div>
                                    <div class="mb-1">
                                        <label for="event-num-rdv" class="form-label">Numéro RDV</label>
                                        <input type="text" class="form-control" id="event-num-rdv" readonly />
                                    </div>
                                    <div class="mb-1">
                                        <label for="select-label" class="form-label">État</label>
                                        <select class="select2 select-label form-select w-100" id="select-label" name="select-label">
                                            <option data-label="warning" value="0">Créé</option>
                                            <option data-label="info" value="1">Accepté</option>
                                            <option data-label="success" value="2">Complété</option>
                                            <option data-label="danger" value="3">Annulé</option>
                                        </select>
                                    </div>
                                    <div class="mb-1">
                                        <label for="start-date" class="form-label">Date</label>
                                        <input type="text" class="form-control" id="start-date" name="start-date" placeholder="Date du RDV" />
                                    </div>
                                    <div class="d-flex mb-1">
                                        <button type="submit" class="btn btn-primary me-1">Mettre à jour</button>
                                        <button type="reset" class="btn btn-outline-danger" data-bs-dismiss="modal">Annuler</button>
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
$handlerURL = SITE_URL . '/handlers';
?>

<script src="<?= $fullcalendarJS ?>"></script>
<script src="<?= $momentJS ?>"></script>

<!-- START: Custom Calendar JS to replace app-calendar.js -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  var calendarEl = document.getElementById('calendar');
  var addEventBtn = $('.btn-toggle-sidebar');
  var eventSidebar = $('.event-sidebar');
  var eventTitle = $('#title');
  var eventLabel = $('#select-label');
  var startDate = $('#start-date');
  var eventPhone = $('#event-phone');
  var eventNumRdv = $('#event-num-rdv');
  var updateEventBtn = $('.event-sidebar .btn-primary');
  var eventForm = $('.event-form');
  var selectAll = $('.select-all');
  var filterInput = $('.input-filter');
  var calendarEditor = $('#event-description-editor');

  var selectedEvent = {};

  // Init event sidebar
  if (eventSidebar.length) {
    eventSidebar.modal({
      show: false
    });
  }

  // Init select2
  if (eventLabel.length) {
    function renderBadges(option) {
      if (!option.id) {
        return option.text;
      }
      var $badge =
        "<span class='badge badge-light-" +
        $(option.element).data('label') +
        " me-1'>" +
        option.text +
        '</span>';

      return $badge;
    }

    eventLabel.wrap('<div class="position-relative"></div>').select2({
      placeholder: 'Select value',
      dropdownParent: eventLabel.parent(),
      templateResult: renderBadges,
      templateSelection: renderBadges,
      minimumResultsForSearch: -1,
      escapeMarkup: function (es) {
        return es;
      }
    });
  }

  // Init date picker
  if (startDate.length) {
    var start = startDate.flatpickr({
      enableTime: false,
      dateFormat: 'Y-m-d'
    });
  }
  
  // Calendar colors
  var calendarsColor = {
      warning: 'warning',
      info: 'info',
      success: 'success',
      danger: 'danger',
      secondary: 'secondary'
  };

  // Calendar
  var calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    events: function(fetchInfo, successCallback, failureCallback) {
        // MODIFIED: Logic to fetch events based on role and filters
        var doctorId = $('#calendar_doctor_filter').val() || $(calendarEl).data('doctor-id') || '';
        var filters = [];
        $('.calendar-events-filter .input-filter:checked').each(function () {
            filters.push($(this).data('value'));
        });
        
        $.ajax({
            url: '<?= $handlerURL ?>',
            type: 'POST',
            data: {
                method: 'get_RDV',
                doctor_id: doctorId,
                filters: filters
            },
            success: function(res) {
                var events = JSON.parse(res);
                if (Array.isArray(events) && events.length > 0 && events[0].id === '0') {
                    successCallback([]); // Return empty if only the placeholder event is returned
                } else {
                    successCallback(events);
                }
            },
            error: function() {
                failureCallback();
            }
        });
    },
    editable: true,
    dragScroll: true,
    dayMaxEvents: 2,
    eventResizableFromStart: true,
    customButtons: {
      sidebarToggle: {
        text: 'Sidebar'
      }
    },
    headerToolbar: {
      start: 'sidebarToggle, prev,next, title',
      end: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
    },
    direction: 'ltr', // Assuming ltr, change if needed
    initialDate: new Date(),
    navLinks: true, // can click day/week names to navigate views
    eventClassNames: function ({ event: calendarEvent }) {
      const colorName = calendarsColor[calendarEvent._def.extendedProps.calendar];
      return ['fc-event-' + colorName];
    },
    dateClick: function (info) {
        // This functionality is disabled as we use a dedicated 'add' button
    },
    eventClick: function (info) {
      selectedEvent = info.event;
      eventSidebar.modal('show');
      addEventBtn.addClass('d-none');
      
      eventTitle.val(info.event.title);
      start.setDate(info.event.start, true, 'Y-m-d');
      eventPhone.val(info.event.extendedProps.phone || '');
      eventNumRdv.val(info.event.extendedProps.num_rdv || '');
      eventLabel.val(info.event.extendedProps.calendar).trigger('change');
      eventForm.attr('data-id', info.event.id);
    },
    eventDrop: function (info) {
        $.ajax({
            url: '<?= $handlerURL ?>',
            type: 'POST',
            data: {
                method: 'moveEvent',
                id: info.event.id,
                date: moment(info.event.start).format('YYYY-MM-DD')
            },
            success: function(res) {
                // You can add a success notification here if you want
            }
        });
    },
    eventResize: function (info) {
        // This is not used for RDV but kept from original file
    },
    refetchEventsOnNavigate: true
  });
  
  calendar.render();

  // Form submit
  $(eventForm).on('submit', function (e) {
    e.preventDefault();
    if (eventForm.valid()) {
      var eventData = {
        id: eventForm.attr('data-id'),
        date: startDate.val(),
        rdv__state: eventLabel.val(),
        method: 'updateEvent'
      };
      $.ajax({
        url: '<?= $handlerURL ?>',
        type: 'POST',
        data: eventData,
        success: function(res) {
          calendar.refetchEvents();
          eventSidebar.modal('hide');
        }
      });
    }
  });

  // Modal close
  eventSidebar.on('hidden.bs.modal', function () {
    eventForm[0].reset();
    eventForm.attr('data-id', 0);
    eventTitle.val('');
    eventLabel.val('').trigger('change');
    startDate.val('');
    eventPhone.val('');
    eventNumRdv.val('');
  });

  // Sidebar filter
  if (selectAll.length) {
    selectAll.on('change', function () {
      var $this = $(this);

      if ($this.prop('checked')) {
        filterInput.prop('checked', true);
      } else {
        filterInput.prop('checked', false);
      }
      calendar.refetchEvents();
    });
  }
  if (filterInput.length) {
    filterInput.on('change', function () {
      if ($('.input-filter:checked').length < $('.input-filter').length) {
        selectAll.prop('checked', false);
      } else {
        selectAll.prop('checked', true);
      }
      calendar.refetchEvents();
    });
  }

  // MODIFIED: Add event listener for the new doctor filter
  $('#calendar_doctor_filter').on('change', function() {
      calendar.refetchEvents();
  });
});

// --- Your existing script for the modal form ---
$(document).ready(function() {
    $('.rdvForm').validate({
        rules: {
            'doctor_id': { required: true },
            'date': { required: true },
            'rdv_num': { required: true },
            'first_name': { required: true },
            'last_name': { required: true },
            'phone': { required: true }
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
            data: function (params) {
                var query = { method: 'handleRdv_nbr' };
                if($('.picker').val() != "") query.date = $('.picker').val();
                if($('#doctor_id').val() != null) query.doctor = $('#doctor_id').val();
                return query;
            },
            processResults: function (data) { return { results: data }; },
            cache: true
        },
    }).change(function () { $('.rdv_num.select2').valid(); });

    $(document).on('change', '#patient_id', function(e) {
        e.preventDefault();
        var self = $(this);
        $.ajax({
            type: "POST", url: "<?= $handlerURL ?>", data: { id: self.val(), method: "getPatients" }, dataType: "json",
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
        var self = $(this);
        var data = {
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
            type: "POST", url: "<?= $handlerURL ?>", data: data, dataType: "json",
            beforeSend: function(){
                let svg = '<svg class="preloader" width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" stroke="currentColor"><g fill="none" fill-rule="evenodd"><g transform="translate(1 1)" stroke-width="2"><circle stroke-opacity=".5" cx="12" cy="12" r="12"/><path d="M24 12c0-6.627-5.373-12-12-12"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/></path></g></g></svg>';
                self.find(':submit').attr("disabled", "disabled").append(svg);
            },
            success: function(data) {
                if (data.state != "false") {
                    Swal.fire({
                        title: data.message, icon: 'success', confirmButtonText: 'OK',
                        customClass: { confirmButton: 'btn btn-primary' }, buttonsStyling: false
                    }).then((result) => { if (result.isConfirmed) { location.reload(); } });
                } else {
                    Swal.fire({
                        title: data.message, icon: 'error', confirmButtonText: 'OK',
                        customClass: { confirmButton: 'btn btn-primary' }, buttonsStyling: false
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'An error occurred!', text: 'Please try again later.', icon: 'error', confirmButtonText: 'OK',
                    customClass: { confirmButton: 'btn btn-primary' }, buttonsStyling: false
                });
            },
            complete: function() {
                self.find(':submit').removeAttr("disabled");
                $('.preloader').remove();
            }
        });
    });

    $('#add-new-sidebar').on('hidden.bs.modal', function () {
        $(this).find('form.rdvForm')[0].reset();
        $(this).find('.select2').val(null).trigger('change');
    });
});
</script>
<!-- END: Custom Calendar JS -->