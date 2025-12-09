<?php
// ==========================================================================
// 1. إعداد البيئة والتحقق من الصلاحيات
// ==========================================================================
if (!isset($_SESSION['user']['id'])) {
    header('location:' . SITE_URL . '/login');
    exit();
}
include_once 'header.php';

$table = 'rdv';
// روابط ملفات CSS الخاصة بالتقويم
$calendarCSS = SITE_URL . '/app-assets/vendors/css/calendars/fullcalendar.min.css';
$appCalendarCSS = SITE_URL . '/app-assets/css/pages/app-calendar.css';
$btn_text = 'Ajouter';

// تحديد معرف الطبيب للتقويم (للمستخدم العادي) أو فارغ (للأدمن)
$doctor_id_for_calendar = ($_SESSION['user']['role'] !== 'admin') ? $_SESSION['user']['id'] : '';
?>

<link rel="stylesheet" type="text/css" href="<?= $calendarCSS ?>">
<link rel="stylesheet" type="text/css" href="<?= $appCalendarCSS ?>">

<style>
    /* ==========================================================================
       MODERN CALENDAR STYLING (2025 Design)
       ========================================================================== */

    :root {
        --fc-border-color: #ebe9f1;
        --fc-button-text-color: #6e6b7b;
        --fc-event-bg-opacity: 0.12;
    }

    /* 1. الحاوية الرئيسية والشبكة */
    .app-calendar {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 24px 0 rgba(34, 41, 47, 0.1);
        border: none !important;
        overflow: hidden;
    }

    .fc .fc-daygrid-day-frame {
        min-height: 140px !important;
        padding: 8px 8px 30px 8px;
        /* مساحة سفلية للشريط */
        position: relative;
        transition: background-color 0.2s;
    }

    .fc-day-today {
        background-color: rgba(115, 103, 240, 0.02) !important;
    }

    /* 2. تصميم الأحداث (Events) - Soft UI Style */
    .fc-daygrid-event {
        border-radius: 4px !important;
        margin-bottom: 4px !important;
        padding: 4px 8px !important;
        border: none !important;
        border-left-width: 3px !important;
        border-left-style: solid !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        line-height: 1.4 !important;
        box-shadow: none !important;
        transition: all 0.2s ease;
    }

    .fc-daygrid-event:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1) !important;
    }

    /* الألوان بأسلوب الباستيل مع حدود واضحة */
    .fc-event-warning {
        /* Créé */
        background-color: rgba(255, 159, 67, 0.12) !important;
        border-left-color: #ff9f43 !important;
        color: #ff9f43 !important;
    }

    .fc-event-info {
        /* Accepté */
        background-color: rgba(0, 207, 232, 0.12) !important;
        border-left-color: #00cfe8 !important;
        color: #00cfe8 !important;
    }

    .fc-event-success {
        /* Complété */
        background-color: rgba(40, 199, 111, 0.12) !important;
        border-left-color: #28c76f !important;
        color: #28c76f !important;
    }

    .fc-event-danger {
        /* Annulé */
        background-color: rgba(234, 84, 85, 0.12) !important;
        border-left-color: #ea5455 !important;
        color: #ea5455 !important;
    }

    /* تغميق النص داخل الحدث للقراءة */
    .fc-daygrid-event .fc-event-title {
        font-weight: 700;
    }

    /* 3. دائرة السعة (Capacity Ring) - تصميم عصري */
    .fc-daygrid-day-top {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 5px;
    }

    .day-capacity-ring {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        font-size: 10px;
        font-weight: bold;
        color: #5e5873;
        background: #f3f3f3;
        /* لون الخلفية للدائرة */
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
    }

    /* القناع الداخلي لعمل شكل الحلقة */
    .day-capacity-ring::before {
        content: "";
        position: absolute;
        inset: 3px;
        /* سمك الحلقة */
        background-color: #fff;
        border-radius: 50%;
        z-index: 1;
    }

    .day-capacity-ring span {
        position: relative;
        z-index: 2;
    }

    /* 4. شريط الإحصائيات السفلي (Bottom Stats Widget) */
    .unified-stats-bottom {
        position: absolute;
        bottom: 4px;
        left: 4px;
        right: 4px;
        height: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        background-color: #f8f8f8;
        border-radius: 12px;
        padding: 0 5px;
        z-index: 4;
        border: 1px solid #f0f0f0;
    }

    .stat-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }

    .stat-pill {
        font-size: 9px;
        padding: 1px 5px;
        border-radius: 6px;
        font-weight: bold;
        color: #fff;
        min-width: 15px;
        text-align: center;
    }

    .bg-waiting {
        background-color: #ff9f43;
    }

    .bg-accepted {
        background-color: #00cfe8;
    }

    .bg-completed {
        background-color: #28c76f;
    }

    /* 5. الشريط الجانبي (Sidebar) */
    .app-calendar-sidebar {
        border-right: 1px solid var(--fc-border-color);
        background-color: #fcfcfc;
    }

    .filter-section-title {
        font-size: 0.85rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #b9b9c3;
        margin-bottom: 1rem;
    }

    /* ==========================================================================
       DARK MODE ADJUSTMENTS
       ========================================================================== */
    html.dark-layout .app-calendar {
        background: #283046;
        box-shadow: none;
    }

    html.dark-layout .app-calendar-sidebar {
        background-color: #283046;
        border-right-color: #3b4253;
    }

    html.dark-layout .fc-daygrid-day-frame {
        border-color: #3b4253;
    }

    html.dark-layout .day-capacity-ring::before {
        background-color: #283046;
        /* لون خلفية الداكن */
    }

    html.dark-layout .day-capacity-ring span {
        color: #d0d2d6;
    }

    html.dark-layout .unified-stats-bottom {
        background-color: #343d55;
        border-color: #3b4253;
    }

    /* جعل النصوص أفتح في الأحداث */
    html.dark-layout .fc-event-warning {
        color: #ff9f43 !important;
    }

    html.dark-layout .fc-event-info {
        color: #00cfe8 !important;
    }

    html.dark-layout .fc-event-success {
        color: #28c76f !important;
    }
</style>

<div class="app-content content">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-wrapper p-0">
        <div class="content-body">

            <section>
                <div class="app-calendar overflow-hidden border">
                    <div class="row g-0">
                        <div class="col app-calendar-sidebar flex-grow-0 overflow-hidden d-flex flex-column"
                            id="app-calendar-sidebar">
                            <div class="sidebar-wrapper">
                                <div class="card-body d-flex justify-content-center my-1">
                                    <button class="btn btn-primary w-100 shadow-sm" data-bs-toggle="modal"
                                        data-bs-target="#addRdvModal">
                                        <i data-feather="plus" class="me-50"></i>
                                        <span class="align-middle">Ajouter Rendez-vous</span>
                                    </button>
                                </div>
                                <div class="card-body pb-0">
                                    <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                                        <div class="mb-2">
                                            <span class="filter-section-title">Médecin</span>
                                            <div class="mt-1">
                                                <?php
                                                $doctor_where_clause = "role = 'doctor' AND deleted = 0";
                                                if (!empty($_SESSION['user']['cabinet_id'])) {
                                                    $doctor_where_clause .= " AND cabinet_id = " . intval($_SESSION['user']['cabinet_id']);
                                                }
                                                $input = array(
                                                    "label" => "",
                                                    "name_id" => "calendar_doctor_filter",
                                                    "placeholder" => "Tous les médecins",
                                                    "class" => "form-select",
                                                    "serverSide" => array(
                                                        "table" => "users",
                                                        "value" => "id",
                                                        "text" => array("first_name", "last_name"),
                                                        "where" => $doctor_where_clause
                                                    )
                                                );
                                                draw_select($input);
                                                ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <span class="filter-section-title">Filtres</span>
                                    <div class="mt-1">
                                        <div class="form-check form-check-secondary mb-1">
                                            <input type="checkbox" class="form-check-input select-all" id="select-all"
                                                checked />
                                            <label class="form-check-label fw-bold" for="select-all">Voir Tout</label>
                                        </div>
                                        <div class="calendar-events-filter">
                                            <div class="form-check form-check-warning mb-1">
                                                <input type="checkbox" class="form-check-input input-filter"
                                                    id="Waiting" data-value="0" checked />
                                                <label class="form-check-label" for="Waiting">Créé</label>
                                            </div>
                                            <div class="form-check form-check-info mb-1">
                                                <input type="checkbox" class="form-check-input input-filter"
                                                    id="pending" data-value="1" checked />
                                                <label class="form-check-label" for="pending">Accepté</label>
                                            </div>
                                            <div class="form-check form-check-success mb-1">
                                                <input type="checkbox" class="form-check-input input-filter"
                                                    id="completed" data-value="2" checked />
                                                <label class="form-check-label" for="completed">Complété</label>
                                            </div>
                                            <div class="form-check form-check-danger mb-1">
                                                <input type="checkbox" class="form-check-input input-filter"
                                                    id="canceled" data-value="3" checked />
                                                <label class="form-check-label" for="canceled">Annulé</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col position-relative">
                            <div class="card shadow-none border-0 mb-0 rounded-0">
                                <div class="card-body pb-0">
                                    <div id="calendar"
                                        data-doctor-id="<?= htmlspecialchars($doctor_id_for_calendar) ?>"></div>
                                </div>
                            </div>
                        </div>
                        <div class="body-content-overlay"></div>
                    </div>
                </div>

                <div class="modal fade" id="addRdvModal" tabindex="-1" aria-labelledby="addRdvModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-transparent">
                                <h5 class="modal-title text-primary" id="addRdvModalLabel">Nouveau Rendez-vous</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <form class="rdvForm" method="post" role="form">
                                <div class="modal-body px-sm-5 pb-50">
                                    <?php set_csrf() ?>
                                    <div class="row gy-1">
                                        <?php if ($_SESSION['user']['role'] == 'admin') { ?>
                                            <div class="col-md-6 col-12">
                                                <label class="form-label">Médecin</label>
                                                <?php
                                                $doctor_where_clause = "role = 'doctor' AND deleted = 0";
                                                if (!empty($_SESSION['user']['cabinet_id'])) {
                                                    $doctor_where_clause .= " AND cabinet_id = " . intval($_SESSION['user']['cabinet_id']);
                                                }
                                                draw_select([
                                                    "label" => "",
                                                    "name_id" => "doctor_id",
                                                    "placeholder" => "Sélectionner Médecin",
                                                    "serverSide" => [
                                                        "table" => "users",
                                                        "value" => "id",
                                                        "text" => ["first_name", "last_name"],
                                                        "where" => $doctor_where_clause
                                                    ]
                                                ]);
                                                ?>
                                            </div>
                                        <?php } else {
                                            draw_input(["type" => "hidden", "name_id" => "doctor_id", "value" => $_SESSION['user']['id']]);
                                        } ?>

                                        <div class="col-md-6 col-12">
                                            <label class="form-label">Patient (Recherche)</label>
                                            <?php
                                            draw_select([
                                                "label" => "",
                                                "name_id" => "patient_id",
                                                "placeholder" => 'Rechercher par nom, tél...',
                                                "serverSide" => [
                                                    "table" => "patient",
                                                    "value" => "id",
                                                    "text" => ["first_name", "last_name", "phone", "id"],
                                                    "where" => "deleted = 0"
                                                ]
                                            ]);
                                            ?>
                                        </div>

                                        <div class="col-12">
                                            <hr />
                                        </div>

                                        <div class="col-md-4 col-12">
                                            <?php draw_input(["label" => $GLOBALS['language']['firstname'], "type" => "text", "name_id" => "first_name", "placeholder" => "Prénom"]); ?>
                                        </div>
                                        <div class="col-md-4 col-12">
                                            <?php draw_input(["label" => $GLOBALS['language']['lastname'], "type" => "text", "name_id" => "last_name", "placeholder" => "Nom"]); ?>
                                        </div>
                                        <div class="col-md-4 col-12">
                                            <?php draw_input(["label" => $GLOBALS['language']['phone'], "type" => "text", "name_id" => "phone", "placeholder" => "0X XX XX XX XX"]); ?>
                                        </div>

                                        <div class="col-md-6 col-12">
                                            <?php draw_input(["label" => $GLOBALS['language']['date'], "type" => "text", "name_id" => "date", "placeholder" => "YYYY-MM-DD", "class" => "picker form-control"]); ?>
                                        </div>
                                        <div class="col-md-6 col-12">
                                            <label class="form-label"><?= $GLOBALS['language']['rdv_num'] ?></label>
                                            <?php draw_select(["label" => "", "name_id" => "rdv_num", "placeholder" => "Sélectionner Ticket", "class" => "rdv_num", "attr" => "data-search = '-1'"]); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer justify-content-center">
                                    <button type="button" class="btn btn-outline-secondary"
                                        data-bs-dismiss="modal">Annuler</button>
                                    <?php draw_button(["text" => $btn_text, "type" => "submit", "name_id" => "submit", "class" => "btn-primary px-3"]); ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="modal modal-slide-in event-sidebar fade" id="add-new-sidebar">
                    <div class="modal-dialog sidebar-lg">
                        <div class="modal-content p-0">
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close">×</button>
                            <div class="modal-header mb-1">
                                <h5 class="modal-title">Détails du Rendez-vous</h5>
                            </div>
                            <div class="modal-body flex-grow-1 pb-sm-0 pb-3">
                                <form class="event-form needs-validation" data-id="0" novalidate>
                                    <div class="mb-1">
                                        <label for="title" class="form-label">Patient</label>
                                        <input type="text" class="form-control" id="title" name="title" readonly />
                                    </div>
                                    <div class="row">
                                        <div class="col-6 mb-1">
                                            <label for="event-phone" class="form-label">Téléphone</label>
                                            <input type="text" class="form-control" id="event-phone" readonly />
                                        </div>
                                        <div class="col-6 mb-1">
                                            <label for="event-num-rdv" class="form-label">N° Ticket</label>
                                            <input type="text" class="form-control fw-bold text-center"
                                                id="event-num-rdv" readonly />
                                        </div>
                                    </div>
                                    <div class="mb-1">
                                        <label for="select-label" class="form-label">État du RDV</label>
                                        <select class="select2 select-label form-select w-100" id="select-label"
                                            name="select-label">
                                            <option data-label="warning" value="0">Créé (En attente)</option>
                                            <option data-label="info" value="1">Accepté</option>
                                            <option data-label="success" value="2">Complété</option>
                                            <option data-label="danger" value="3">Annulé</option>
                                        </select>
                                    </div>
                                    <div class="mb-1">
                                        <label for="start-date" class="form-label">Date</label>
                                        <input type="text" class="form-control" id="start-date" name="start-date" />
                                    </div>
                                    <div class="d-flex justify-content-between mt-2">
                                        <button type="submit" class="btn btn-primary">Mettre à jour</button>
                                        <button type="button" class="btn btn-outline-danger"
                                            data-bs-dismiss="modal">Fermer</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var calendarEl = document.getElementById('calendar');
        var eventSidebar = $('.event-sidebar');
        var eventTitle = $('#title');
        var eventLabel = $('#select-label');
        var startDate = $('#start-date');
        var eventPhone = $('#event-phone');
        var eventNumRdv = $('#event-num-rdv');
        var eventForm = $('.event-form');
        var selectAll = $('.select-all');
        var filterInput = $('.input-filter');

        var dayNamesMap = ["Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi"];

        // =================================================================================
        //  Visual Logic: Update Statistics (Ring Chart + Bottom Pills)
        // =================================================================================
        function updateDailyStats(startStr, endStr) {
            var doctorId = $('#calendar_doctor_filter').val() || $(calendarEl).data('doctor-id') || '';
            var isDarkMode = $('html').hasClass('dark-layout');
            var trackColor = isDarkMode ? '#3b4253' : '#e6e6e6';

            $.ajax({
                url: '<?= $handlerURL ?>',
                type: 'POST',
                data: {
                    method: 'get_daily_calendar_stats',
                    start: startStr,
                    end: endStr,
                    doctor_id: doctorId
                },
                dataType: 'json',
                success: function (res) {
                    // Clear previous elements
                    $('.day-capacity-ring').remove();
                    $('.unified-stats-bottom').remove();

                    var bookings = res.bookings || {};
                    var tickets = res.settings.tickets || {};
                    var hours = res.settings.hours || {};

                    $('.fc-day').each(function () {
                        var dateStr = $(this).data('date');
                        if (!dateStr) return;

                        var dateObj = new Date(dateStr);
                        var dayName = dayNamesMap[dateObj.getDay()];
                        var isWorkDay = hours[dayName] && hours[dayName].from && hours[dayName].to;

                        if (isWorkDay) {
                            var dayData = bookings[dateStr] || { total: 0, details: {} };
                            var count = parseInt(dayData.total) || 0;
                            var limit = parseInt(tickets[dayName]) || 0;

                            // 1. Render Capacity Ring (Donut Chart)
                            if (limit > 0) {
                                var available = limit - count;
                                if (available < 0) available = 0;

                                var percentage = (count / limit) * 100;

                                // Determine color based on occupancy
                                var color = '#28c76f'; // Green (Safe)
                                if (percentage >= 100) color = '#ea5455'; // Red (Full)
                                else if (percentage >= 75) color = '#ff9f43'; // Orange (Almost Full)

                                // Conic Gradient for the Donut Chart effect
                                var gradient = `conic-gradient(${color} ${percentage}%, ${trackColor} 0)`;

                                var ringHtml = `
                            <div class="day-capacity-ring" style="background: ${gradient}" title="Places restantes: ${available}">
                                <span>${available}</span>
                            </div>
                          `;
                                $(this).find('.fc-daygrid-day-top').prepend(ringHtml);
                            }

                            // 2. Render Bottom Stats (Pills)
                            var pillsHtml = '';
                            if (count > 0 || (dayData.details && dayData.details[3] > 0)) {
                                var pending = dayData.details[0] || 0;
                                var accepted = dayData.details[1] || 0;
                                var completed = dayData.details[2] || 0;

                                if (pending > 0) pillsHtml += `<span class="stat-pill bg-waiting" title="En attente">${pending}</span>`;
                                if (accepted > 0) pillsHtml += `<span class="stat-pill bg-accepted" title="Accepté">${accepted}</span>`;
                                if (completed > 0) pillsHtml += `<span class="stat-pill bg-completed" title="Complété">${completed}</span>`;
                            }

                            if (pillsHtml) {
                                var unifiedHtml = `<div class="unified-stats-bottom">${pillsHtml}</div>`;
                                $(this).find('.fc-daygrid-day-frame').append(unifiedHtml);
                            }
                        }
                    });
                }
            });
        }

        // --- Calendar Initialization ---
        if (eventSidebar.length) { eventSidebar.modal({ show: false }); }

        if (eventLabel.length) {
            function renderBadges(option) {
                if (!option.id) { return option.text; }
                var $badge = "<span class='badge badge-light-" + $(option.element).data('label') + " me-1'>" + option.text + '</span>';
                return $badge;
            }
            eventLabel.wrap('<div class="position-relative"></div>').select2({
                placeholder: 'Select value',
                dropdownParent: eventLabel.parent(),
                templateResult: renderBadges,
                templateSelection: renderBadges,
                minimumResultsForSearch: -1,
                escapeMarkup: function (es) { return es; }
            });
        }

        if (startDate.length) {
            var start = startDate.flatpickr({ enableTime: false, dateFormat: 'Y-m-d' });
        }

        // Mapping statuses to CSS classes
        var calendarsColor = { warning: 'warning', info: 'info', success: 'success', danger: 'danger', secondary: 'secondary' };

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: { start: 'sidebarToggle, prev,next, title', end: 'dayGridMonth,listMonth' },
            editable: true,
            dayMaxEvents: 2,
            eventResizableFromStart: true,
            dragScroll: true,
            navLinks: true,
            initialDate: new Date(),
            direction: 'ltr',

            // Custom Class Names for Events
            eventClassNames: function ({ event: calendarEvent }) {
                const colorName = calendarsColor[calendarEvent._def.extendedProps.calendar];
                return ['fc-event-' + colorName];
            },

            // Fetch Events via AJAX
            events: function (fetchInfo, successCallback, failureCallback) {
                var doctorId = $('#calendar_doctor_filter').val() || $(calendarEl).data('doctor-id') || '';
                var filters = [];
                $('.calendar-events-filter .input-filter:checked').each(function () { filters.push($(this).data('value')); });

                $.ajax({
                    url: '<?= $handlerURL ?>', type: 'POST',
                    data: { method: 'get_RDV', doctor_id: doctorId, filters: filters },
                    success: function (res) {
                        var events = JSON.parse(res);
                        if (Array.isArray(events) && events.length > 0 && events[0].id === '0') {
                            successCallback([]);
                        } else {
                            successCallback(events);
                        }
                    },
                    error: function () { failureCallback(); }
                });
            },

            // Update Stats on View Change
            datesSet: function (info) {
                updateDailyStats(info.startStr, info.endStr);
            },

            // Add New RDV (Click on Day)
            dateClick: function (info) {
                var addRdvModal = new bootstrap.Modal(document.getElementById('addRdvModal'));
                $('#addRdvModal form')[0].reset();
                $('#addRdvModal .select2').val(null).trigger('change');

                // 1. تعيين التاريخ في الحقل
                $('#addRdvModal #date').val(info.dateStr);

                // 2. تفعيل قائمة التذاكر يدوياً لأننا قمنا بتعيين التاريخ
                $('#addRdvModal #rdv_num').prop('disabled', false);

                // 3. إذا كان الطبيب محدداً مسبقاً (في حالة الأدمن)، نقوم بتحديث القائمة
                if ($('#addRdvModal #doctor_id').val()) {
                    // لا نحتاج لعمل trigger change هنا لأن Select2 سيجلب البيانات عند الفتح
                    // ولكن يمكننا تصفير القيمة للتأكد
                    $('#addRdvModal #rdv_num').val(null).trigger('change');
                }

                addRdvModal.show();
            },

            // Edit RDV (Click on Event)
            eventClick: function (info) {
                selectedEvent = info.event;
                eventSidebar.modal('show');
                eventTitle.val(info.event.title);
                start.setDate(info.event.start, true, 'Y-m-d');
                eventPhone.val(info.event.extendedProps.phone || '');
                eventNumRdv.val(info.event.extendedProps.num_rdv || '');

                if (info.event.extendedProps.state_id !== undefined) {
                    eventLabel.val(info.event.extendedProps.state_id).trigger('change');
                } else {
                    var mapState = { 'warning': 0, 'info': 1, 'success': 2, 'danger': 3 };
                    var stateVal = mapState[info.event.extendedProps.calendar] || 0;
                    eventLabel.val(stateVal).trigger('change');
                }

                eventForm.attr('data-id', info.event.id);
            },

            // Drag & Drop
            eventDrop: function (info) {
                $.ajax({
                    url: '<?= $handlerURL ?>', type: 'POST',
                    data: { method: 'moveEvent', id: info.event.id, date: moment(info.event.start).format('YYYY-MM-DD') },
                    success: function (res) {
                        calendar.refetchEvents();
                        updateDailyStats(calendar.view.activeStart.toISOString(), calendar.view.activeEnd.toISOString());
                    }
                });
            },

            customButtons: {
                sidebarToggle: {
                    text: ' Filtres',
                    click: function () {
                        // Toggle sidebar logic if needed or just visual
                    }
                }
            }
        });

        calendar.render();

        // Update Event Form Submit
        $(eventForm).on('submit', function (e) {
            e.preventDefault();
            if (eventForm.valid()) {
                // جلب التوكن
                var csrfToken = $('input[name="csrf"]').val();

                // تجهيز البيانات مع البادئة الصحيحة rdv__
                var formDataArray = [
                    { name: 'rdv__state', value: eventLabel.val() },
                    { name: 'rdv__date', value: startDate.val() },
                    { name: 'csrf', value: csrfToken }
                ];

                var eventData = {
                    id: eventForm.attr('data-id'),
                    method: 'updateEvent',
                    data: formDataArray
                };

                $.ajax({
                    url: '<?= $handlerURL ?>', type: 'POST', data: eventData,
                    dataType: 'json',
                    success: function (res) {
                        if (res.state === "true") {
                            calendar.refetchEvents();
                            updateDailyStats(calendar.view.activeStart.toISOString(), calendar.view.activeEnd.toISOString());
                            eventSidebar.modal('hide');
                            Swal.fire({ icon: 'success', title: 'Succès', text: res.message, timer: 1500, showConfirmButton: false });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Erreur', text: res.message });
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error(xhr.responseText);
                        Swal.fire({ icon: 'error', title: 'Erreur', text: 'Erreur serveur (Check Logs)' });
                    }
                });
            }
        });

        // Reset Sidebar Form
        eventSidebar.on('hidden.bs.modal', function () {
            eventForm[0].reset();
            eventForm.attr('data-id', 0);
            eventTitle.val('');
            eventLabel.val('').trigger('change');
            startDate.val('');
            eventPhone.val('');
            eventNumRdv.val('');
        });

        // Filters Logic
        if (selectAll.length) {
            selectAll.on('change', function () {
                var $this = $(this);
                if ($this.prop('checked')) { filterInput.prop('checked', true); }
                else { filterInput.prop('checked', false); }
                calendar.refetchEvents();
            });
        }
        if (filterInput.length) {
            filterInput.on('change', function () {
                if ($('.input-filter:checked').length < $('.input-filter').length) { selectAll.prop('checked', false); }
                else { selectAll.prop('checked', true); }
                calendar.refetchEvents();
            });
        }

        $('#calendar_doctor_filter').on('change', function () {
            calendar.refetchEvents();
            updateDailyStats(calendar.view.activeStart.toISOString(), calendar.view.activeEnd.toISOString());
        });

        // Observe Theme Change (Light/Dark)
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.attributeName === "class") {
                    updateDailyStats(calendar.view.activeStart.toISOString(), calendar.view.activeEnd.toISOString());
                }
            });
        });
        observer.observe(document.documentElement, { attributes: true });

    });

    // =================================================================================
    //  Modal & Ticket Fetching Logic (Updated)
    // =================================================================================
    $(document).ready(function () {
        $('.rdvForm').validate({
            rules: { 'doctor_id': { required: true }, 'date': { required: true }, 'rdv_num': { required: true }, 'first_name': { required: true }, 'last_name': { required: true }, 'phone': { required: true } }
        });

        // 1. تعريف متغير لحقل التذاكر
        var $rdvSelect = $('.rdv_num.select2');

        // 2. تهيئة Select2 (مرة واحدة فقط)
        $rdvSelect.select2({
            dropdownParent: $rdvSelect.parent(),
            placeholder: "Sélectionner Ticket",
            language: {
                noResults: function () { return "Aucun ticket disponible"; },
                searching: function () { return "Recherche..."; }
            },
            ajax: {
                type: "post",
                dataType: "json",
                url: "<?= $handlerURL ?>",
                delay: 250,
                data: function (params) {
                    // قراءة القيم الحالية من المودال
                    return {
                        method: 'handleRdv_nbr',
                        date: $('#addRdvModal #date').val(),
                        doctor: $('#addRdvModal #doctor_id').val()
                    };
                },
                processResults: function (data) {
                    return { results: data };
                },
                cache: false
            }
        });

        // 3. تعطيل القائمة افتراضياً
        $rdvSelect.prop('disabled', true);

        // 4. دالة لتحديث حالة القائمة
        function updateRdvFieldState() {
            var selectedDate = $('#addRdvModal #date').val();
            if (selectedDate && selectedDate !== "") {
                $rdvSelect.prop('disabled', false);
            } else {
                $rdvSelect.prop('disabled', true);
            }
            $rdvSelect.val(null).trigger('change');
        }

        // 5. ربط حدث التغيير بمكتبة Flatpickr داخل المودال
        var dateElement = document.querySelector("#addRdvModal #date");
        if (dateElement && dateElement._flatpickr) {
            dateElement._flatpickr.config.onChange.push(function (selectedDates, dateStr, instance) {
                $('#addRdvModal #date').val(dateStr);
                updateRdvFieldState();
            });
        }

        // 6. ربط حدث التغيير المباشر (للحالات الأخرى)
        $(document).on('change', '#addRdvModal #date', function () {
            updateRdvFieldState();
        });

        // 7. عند تغيير الطبيب
        $(document).on('change', '#addRdvModal #doctor_id', function () {
            $rdvSelect.val(null).trigger('change');
        });

        // Fetch Patient Details
        $(document).on('change', '#patient_id', function (e) {
            e.preventDefault();
            var self = $(this);
            $.ajax({
                type: "POST", url: "<?= $handlerURL ?>", data: { id: self.val(), method: "getPatients" }, dataType: "json",
                success: function (data) {
                    if (data[0] && data[0].hasOwnProperty('id')) {
                        $('#addRdvModal #first_name').val(data[0].first_name);
                        $('#addRdvModal #last_name').val(data[0].last_name);
                        $('#addRdvModal #phone').val(data[0].phone);
                    }
                }
            });
        });

        // Submit New RDV
        $(document).on('submit', '.rdvForm', function (e) {
            e.preventDefault();
            var self = $(this);
            if (!self.valid()) return;

            var data = {
                patient: $('#patient_id').val(),
                doctor: self.find('#doctor_id').val(),
                rdv_num: $('#rdv_num').val(),
                date: self.find('#date').val(),
                first_name: $('#first_name').val(),
                last_name: $('#last_name').val(),
                phone: $('#phone').val(),
                method: "postRdv"
            };

            $.ajax({
                type: "POST", url: "<?= $handlerURL ?>", data: data, dataType: "json",
                beforeSend: function () {
                    let svg = '<span class="spinner-border spinner-border-sm ms-1" role="status" aria-hidden="true"></span>';
                    self.find(':submit').attr("disabled", "disabled").append(svg);
                },
                success: function (data) {
                    if (data.state != "false") {
                        Swal.fire({ title: 'Succès', text: data.message, icon: 'success', confirmButtonText: 'OK', customClass: { confirmButton: 'btn btn-primary' }, buttonsStyling: false })
                            .then((result) => { if (result.isConfirmed) { location.reload(); } });
                    } else {
                        Swal.fire({ title: 'Erreur', text: data.message, icon: 'error', confirmButtonText: 'OK', customClass: { confirmButton: 'btn btn-primary' }, buttonsStyling: false });
                    }
                },
                complete: function () {
                    self.find(':submit').removeAttr("disabled");
                    self.find(':submit').find('span').remove();
                }
            });
        });

        $('#addRdvModal').on('hidden.bs.modal', function () {
            $(this).find('form.rdvForm')[0].reset();
            $(this).find('.select2').val(null).trigger('change');
            // إعادة تعطيل القائمة عند الإغلاق
            $rdvSelect.prop('disabled', true);
        });
    });
</script>
<?php include_once 'foot.php'; ?>