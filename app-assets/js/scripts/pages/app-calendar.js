/**
 * App Calendar
 */

/**
 * ! If both start and end dates are same Full calendar will nullify the end date value.
 * ! Full calendar will end the event on a day before at 12:00:00AM thus, event won't extend to the end date.
 * ! We are getting events from a separate file named app-calendar-events.js. You can add or remove events from there.
 **/

'use-strict';

// RTL Support
var direction = 'ltr',
  assetPath = '../../../app-assets/';
if ($('html').data('textdirection') == 'rtl') {
  direction = 'rtl';
}

if ($('body').attr('data-framework') === 'laravel') {
  assetPath = $('body').attr('data-asset-path');
}

$(document).on('click', '.fc-sidebarToggle-button', function (e) {
  $('.app-calendar-sidebar, .body-content-overlay').addClass('show');
});

$(document).on('click', '.body-content-overlay', function (e) {
  $('.app-calendar-sidebar, .body-content-overlay').removeClass('show');
});

document.addEventListener('DOMContentLoaded', function () {
  var calendarEl = document.getElementById('calendar'),
    eventToUpdate,
    sidebar = $('.event-sidebar'),
    calendarsColor = {
        '0':  'warning',
        '1':  'info',
        '2': 'success',
        '3':  'danger'
    },
    addEventBtn = $('.add-event-btn'),
    cancelBtn = $('.btn-cancel'),
    updateEventBtn = $('.update-event-btn'),
    toggleSidebarBtn = $('.btn-toggle-sidebar'),
    planning__Client_id = $('#rdv__patient_id'),
    planning__phone = $('#rdv__phone'),
    planning__State = $('#rdv__state'),
    planning__Date_RDV = $('#rdv__date'),
    planning__rdv_num = $('#rdv__rdv_num'),
    selectAll = $('.select-all'),
    calEventFilter = $('.calendar-events-filter'),
    filterInput = $('.input-filter'),
    btnDeleteEvent = $('.btn-delete-event');

    // --------------------------------------------
    // On add new item, clear sidebar-right field fields
    // --------------------------------------------
    $('.add-event button').on('click', function (e) {
      $('.event-sidebar').addClass('show');
      $('.sidebar-left').removeClass('show');
      $('.app-calendar .body-content-overlay').addClass('show');
    });

    // Event click function
    function eventClick(info) {
        eventToUpdate = info.event;
        sidebar.modal('show');
        addEventBtn.addClass('d-none');
        cancelBtn.addClass('d-none');
        updateEventBtn.removeClass('d-none');
        btnDeleteEvent.removeClass('d-none');

        $('.event-form').attr('data-update', eventToUpdate.id);
		planning__Client_id.val(eventToUpdate.extendedProps.Client.name);
        planning__phone.val(eventToUpdate.extendedProps.phone);
        planning__Date_RDV.val( moment(eventToUpdate.start, 'YYYY-MM-DD').format('YYYY-MM-DD'));
        planning__rdv_num.val(eventToUpdate.extendedProps.num_rdv);
        sidebar.find(planning__State).val(eventToUpdate.extendedProps.calendar).trigger('change');

        //  Delete Event
        btnDeleteEvent.on('click', function () {
            eventToUpdate.remove();
            removeEvent(eventToUpdate.id);
            sidebar.modal('hide');
            $('.event-sidebar').removeClass('show');
            $('.app-calendar .body-content-overlay').removeClass('show');
        });
    }
    
    function eventDrop(info) {
        eventToUpdate = info.event;
        option_swal =  {
            title: 'Are you sure you want to move this item?',
            html: "",  
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'YES',
            cancelButtonText: 'NO',
            customClass: {
                confirmButton: 'btn btn-success',
                cancelButton: 'btn btn-outline-danger me-1 ms-1'
            },
            buttonsStyling: false
        }
        
        let data = { date: moment(eventToUpdate.start).format('YYYY-MM-DD'), id: eventToUpdate.id, method: "moveEvent" };
        
        Swal.fire(option_swal).then(function (result) {
            if (result.value) {
                $.ajax({
                    type: "POST",
                    url: SITE_URL + "/handlers",
                    data: data,
                    dataType: "json",
                    success: function(data) {
                        location.reload();
                    }
                });
            } else {
                info.revert(); 
            }
        });
    }


             /*   function eventDrop(info) {
                    eventToUpdate = info.event;
                    option_swal =  {
                        title: 'Are you sure you want to delete this item?',
                        html: "",  
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'YES',
                        cancelButtonText: 'NO',
                        customClass: {
                          confirmButton: 'btn btn-success',
                          cancelButton: 'btn btn-outline-danger me-1 ms-1'
                        },
                        buttonsStyling: false
                    }
                    let data = { date: moment(eventToUpdate.start).format('YYYY-MM-DD'), id: eventToUpdate.id, method: "moveEvent" };
                    Swal.fire(option_swal).then(function (result) {
                        if (result.value) {
                            $.ajax({
                              type: "POST",
                              url: SITE_URL+"/custom",
                              data: data,
                              dataType: "json",
                              success: function(data) { location.reload();}
                            });
                        }
                    });
                }*/

    // Modify sidebar toggler
    function modifyToggler() {
      $('.fc-sidebarToggle-button')
        .empty()
        .append(feather.icons['menu'].toSvg({ class: 'ficon' }));
    }

    // Selected Checkboxes
    function selectedCalendars() {
      var selected = [];
      $('.calendar-events-filter input:checked').each(function () {
        selected.push($(this).attr('data-value'));
      });
      return selected;
    }

    function fetchEvents(info, successCallback) {
    
      let filters = [];
      $('.input-filter:checked').each(function(index){
        filters.push($(this).attr('data-value'));
      });
      
      $.ajax({
        url: SITE_URL+"/handlers",
        type: 'POST',
        data: { method:'get_RDV', doctor_id: $('#calendar').attr('data-id'), filters: filters},
        success: function (result) {         
          result = JSON.parse(result);
          successCallback(result);
          
          calendar.render();
        }
      });
    }

    // Calendar plugins
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        // locale: 'fr',
        events: fetchEvents,
        editable: true,
        dragScroll: true,
        dayMaxEvents: 200,
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
        direction: direction,
        initialDate: new Date(),
        navLinks: true, // can click day/week names to navigate views
        eventClassNames: function ({ event: calendarEvent }) {
        const colorName = calendarsColor[calendarEvent._def.extendedProps.calendar];

        return [
            // Background Color
            'bg-light-' + colorName
        ];
        },
        dateClick: function (info) {
        var date = moment(info.date).format('YYYY-MM-DD');
        resetValues();
        sidebar.modal('show');
        addEventBtn.removeClass('d-none');
        updateEventBtn.addClass('d-none');
        btnDeleteEvent.addClass('d-none');
        planning__Date_RDV.val(date);
        },
        eventClick: function (info) {
        eventClick(info);
        },
        datesSet: function () {
        modifyToggler();
        },
        viewDidMount: function () {
        modifyToggler();
        },
        eventDrop: function(info) {
          eventDrop(info);
        }
    });

    // Render calendar
    calendar.render();
    // Modify sidebar toggler
    modifyToggler();
    // updateEventClass();

    // Validate add new and update form


    // Sidebar Toggle Btn
    if (toggleSidebarBtn.length) {
        toggleSidebarBtn.on('click', function () {
            cancelBtn.removeClass('d-none');
        });
    }

    // ------------------------------------------------
    // removeEvent
    // ------------------------------------------------
    function removeEvent(eventId) {
        let data = { id: eventId, method: "removeEvent" };
                
        $.ajax({
            type: "POST",
            url: SITE_URL+"/handlers",
            data: data,
            dataType: "json",
            success: function(data) {
              removeEventInCalendar(eventId);
            }
        });
    }

  // ------------------------------------------------
  // (UI) removeEventInCalendar
  // ------------------------------------------------
    function removeEventInCalendar(eventId) {
        calendar.getEventById(eventId).remove();
    }

    $(document).on('submit', '.event-form', function(e){
        e.preventDefault();
        e.stopPropagation();

        let self = $(this),
            data = { data: $(this).find(':not(.excluded, .dataTables_wrapper input, .dataTables_wrapper select)').serializeArray() };

        if(typeof self.attr("data-update") === 'undefined' || self.attr("data-update") === false || self.attr('data-update') == "" ) data.method= "postEvent";
        else{ data.method= "updateEvent";  data.id= self.attr("data-update"); }
                
        $.ajax({
            type: "POST",
            url: SITE_URL+"/handlers",
            data: data,
            dataType: "json",
            beforeSend: function(){
                var svg = '<svg class="seloader" width="16" viewBox="0 0 38 38" xmlns="http://www.w3.org/2000/svg" stroke="#fff"><g fill="none" fill-rule="evenodd"><g transform="translate(1 1)" stroke-width="2"><circle stroke-opacity=".5" cx="18" cy="18" r="18"/><path d="M36 18c0-9.94-8.06-18-18-18"><animateTransform attributeName="transform" type="rotate" from="0 18 18" to="360 18 18" dur="1s" repeatCount="indefinite"/></path></g></g></svg>';
                    self.find('button[type="submit"]').attr("disabled","disabled");
                    self.find('button[type="submit"]').append(svg);
            },
            success: function(data) {
             /*   if (data.state != "false") {
                    Swal.fire({
                        title: data.message,
                        icon: 'success',
                        showConfirmButton: false,
                        buttonsStyling: false,
                        timer: 1500,
                        timerProgressBar: true,
                    }).then((result) => {                                
                        
                        self.parents('.modal').modal('toggle');
                        calendar.refetchEvents();
                        location.reload();
                        
                    })
                }else{
                    Swal.fire({
                        title: 'something is wrong!',
                        icon: 'error',
                        confirmButtonText: 'back',
                        customClass: {
                        confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false
                    })
                }*/
            },
            complete: function(){
                self.find('button[type="submit"]').removeAttr("disabled");
                $('.seloader').remove();
            }
        });
    });

    // Reset sidebar input values
    function resetValues() {
        planning__Client_id.val(null).trigger('change');
        planning__Date_RDV.val('');
        planning__State.val(0).trigger('change');
        $(".event-form").attr("data-update", '');
    }

    // When modal hides reset input values
    sidebar.on('hidden.bs.modal', function () {
      resetValues();
    });

    // Hide left sidebar if the right sidebar is open
    $('.btn-toggle-sidebar').on('click', function () {
      btnDeleteEvent.addClass('d-none');
      updateEventBtn.addClass('d-none');
      addEventBtn.removeClass('d-none');
      $('.app-calendar-sidebar, .body-content-overlay').removeClass('show');
    });

    // Select all & filter functionality
    if (selectAll.length) {
      selectAll.on('change', function () {
        var $this = $(this);

        
        if ($this.prop('checked')) {
          calEventFilter.find('input').prop('checked', true);
        } else {
          calEventFilter.find('input').prop('checked', false);
        }
        calendar.refetchEvents();
      });
    }

    if (filterInput.length) {
      filterInput.on('change', function () {
        $('.input-filter:checked').length < calEventFilter.find('input').length
          ? selectAll.prop('checked', false)
          : selectAll.prop('checked', true);
          
        calendar.refetchEvents();
      });
    }


});
