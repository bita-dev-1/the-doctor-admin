$(document).ready(function () {
  console.log("🚀 [Init] Reeducation Form Script Started");

  // 1. Initialize Feather Icons
  if (feather) {
    feather.replace({ width: 14, height: 14 });
  }

  // 2. Access PHP variables via the config object
  const config = window.ReeducationConfig || {};
  const tablePrefix = "reeducation_dossiers";

  // --- Validation ---
  if (!config.isReadOnly) {
    $(".codexForm").validate({
      rules: {
        [`${tablePrefix}__patient_id`]: { required: true },
        [`${tablePrefix}__sessions_prescribed`]: { required: true, min: 1 },
        [`${tablePrefix}__price`]: { required: true, number: true },
        [`${tablePrefix}__technician_id`]: { required: true },
      },
    });
  }

  // --- Orientation Fields Toggle ---
  function toggleOrientationFields() {
    var type = $(`#${tablePrefix}__dossier_type`).val();
    if (type === "interne") {
      $(".external-only-field").slideUp();
    } else {
      $(".external-only-field").slideDown();
    }
  }
  $(`#${tablePrefix}__dossier_type`).on("change", toggleOrientationFields);
  toggleOrientationFields();

  // --- Price Calculation Logic (UPDATED) ---

  // متغير لتخزين السعر الأساسي القادم من السيرفر (بدون تخفيض)
  var baseServerPrice = 0;

  // تهيئة السعر الأساسي عند تحميل الصفحة (في حالة التعديل)
  var initialPrice = parseFloat($(`#${tablePrefix}__price`).val()) || 0;
  var initialDiscount =
    parseFloat($(`#${tablePrefix}__discount_amount`).val()) || 0;
  baseServerPrice = initialPrice + initialDiscount;

  // دالة لحساب السعر الصافي (السعر الأساسي - التخفيض)
  function updateNetPrice() {
    var discount = parseFloat($(`#${tablePrefix}__discount_amount`).val()) || 0;
    var finalPrice = baseServerPrice - discount;

    if (finalPrice < 0) finalPrice = 0;

    $(`#${tablePrefix}__price`).val(finalPrice.toFixed(2));
  }

  // مراقبة حقل التخفيض لتحديث السعر فوراً
  $(`#${tablePrefix}__discount_amount`).on("input change", function () {
    updateNetPrice();
  });

  var isAutoUpdating = false;

  function calculatePrice(forceUpdateCount = false) {
    var typeId = $(`#${tablePrefix}__reeducation_type_id`).val();
    var countInput = $(`#${tablePrefix}__sessions_prescribed`);
    var count = countInput.val();

    if (typeId && !isAutoUpdating) {
      var safeCount = count && count > 0 ? count : 1;
      $.ajax({
        url: config.siteUrl + "/handlers",
        type: "POST",
        data: {
          method: "get_service_pricing_details",
          reeducation_type_id: typeId,
          sessions_count: safeCount,
        },
        dataType: "json",
        success: function (res) {
          if (res.state === "true") {
            var data = res.data;
            var paymentModel = data.payment_model;
            var isPackage = paymentModel === "package";

            // تحديث السعر الأساسي من السيرفر
            baseServerPrice = parseFloat(data.total_price);

            // تطبيق التخفيض الموجود حالياً (إن وجد) على السعر الجديد
            updateNetPrice();

            $(`#${tablePrefix}__technician_percentage`).val(
              data.commission_total
            );
            $(`#${tablePrefix}__payment_mode`).val(paymentModel);
            $("#display_payment_mode").val(
              isPackage ? "Forfait Global" : "Par Séance"
            );

            if (forceUpdateCount && isPackage && data.package_capacity > 0) {
              var currentVal = parseInt(countInput.val());
              var newVal = parseInt(data.package_capacity);
              if (currentVal !== newVal) {
                isAutoUpdating = true;
                countInput.val(newVal);
                setTimeout(function () {
                  isAutoUpdating = false;
                  calculatePrice(false);
                }, 100);
              }
            }
          }
        },
      });
    }
  }

  $(`#${tablePrefix}__reeducation_type_id`).on("select2:select", function () {
    calculatePrice(true);
  });
  $(`#${tablePrefix}__sessions_prescribed`).on("input change", function () {
    calculatePrice(false);
    $("#max-sessions-display").text($(this).val());
  });

  // --- Quick Add Patient ---
  $("#btn-add-quick-patient").on("click", function () {
    $("#quickAddPatientModal").modal("show");
  });
  $("#quickAddPatientForm").on("submit", function (e) {
    e.preventDefault();
    var btn = $(this).find('button[type="submit"]');
    btn.attr("disabled", true).text("...");
    $.ajax({
      url: config.siteUrl + "/handlers",
      type: "POST",
      data: $(this).serialize() + "&method=quick_add_patient",
      dataType: "json",
      success: function (res) {
        if (res.state === "true") {
          var newOption = new Option(res.data.text, res.data.id, true, true);
          $(`#${tablePrefix}__patient_id`).append(newOption).trigger("change");
          $("#quickAddPatientModal").modal("hide");
          $("#quickAddPatientForm")[0].reset();
          Swal.fire({
            icon: "success",
            title: "Succès",
            text: "Patient ajouté",
            timer: 1500,
            showConfirmButton: false,
          });
        } else {
          Swal.fire("Erreur", res.message, "error");
        }
      },
      complete: function () {
        btn.attr("disabled", false).text("Enregistrer");
      },
    });
  });

  // --- CALENDAR LOGIC ---
  if (!config.isReadOnly) {
    // Initialize variables from PHP config
    var selectedDates = config.selectedDates || [];
    var workingDays = config.workingDays || [];
    var ticketsPerDay = config.ticketsPerDay || {};
    var globalBookings = config.globalBookings || {};
    var dayNamesMap = [
      "Dimanche",
      "Lundi",
      "Mardi",
      "Mercredi",
      "Jeudi",
      "Vendredi",
      "Samedi",
    ];

    console.log("📅 [Calendar] Initial Data:", {
      workingDays,
      ticketsPerDay,
      globalBookings,
    });

    var calendarTopEl = document.getElementById("calendar-top");
    var calendarBottomEl = document.getElementById("calendar-bottom");
    var calendarTop, calendarBottom;

    function createCalendarConfig(initialDate) {
      return {
        initialView: "dayGridMonth",
        initialDate: initialDate,
        headerToolbar: { left: "", center: "title", right: "" },
        contentHeight: "auto",
        locale: "fr",
        firstDay: 6, // Samedi
        selectable: false,
        businessHours: {
          daysOfWeek: workingDays,
          startTime: "08:00",
          endTime: "18:00",
        },

        dayCellDidMount: function (info) {
          var dateStr = info.el.getAttribute("data-date");
          var dateObj = info.date;
          var dayIndex = dateObj.getDay();
          var dayName = dayNamesMap[dayIndex];

          // 1. Check working days
          if (!workingDays.includes(dayIndex)) {
            info.el.classList.add("fc-non-working-day");
            return;
          }

          // 2. Calculate Stats
          var limit = parseInt(ticketsPerDay[dayName]) || 0;

          if (limit > 0) {
            var booked = parseInt(globalBookings[dateStr]) || 0;
            var available = limit - booked;
            if (available < 0) available = 0;

            // 3. Render HTML (Compact Split Capsule)
            // تصميم الكبسولة: [ محجوز | متاح ]
            var counterHtml = `
                            <div class="slots-stats-container">
                                <div class="stats-capsule" title="Réservés: ${booked} | Libres: ${available}">
                                    <div class="stat-segment booked">
                                        ${booked}
                                    </div>
                                    <div class="stat-segment free">
                                        ${available}
                                    </div>
                                </div>
                            </div>`;

            // Find the correct element to append to
            var frame = info.el.querySelector(".fc-daygrid-day-frame");
            if (frame) {
              // Remove old stats if exists
              var old = frame.querySelector(".slots-stats-container");
              if (old) old.remove();

              frame.insertAdjacentHTML("beforeend", counterHtml);
            }

            // 4. Visual Feedback
            var isSelectedHere = selectedDates.includes(dateStr);
            if (available <= 0 && !isSelectedHere) {
              info.el.classList.add("fc-day-full");
            }
            if (isSelectedHere) {
              info.el.classList.add("fc-day-selected");
            }
          }
        },
        dateClick: function (info) {
          handleDateClick(info);
        },
        events: function (fetchInfo, successCallback) {
          successCallback([]);
        },
      };
    }

    function handleDateClick(info) {
      var dateStr = info.dateStr;
      var dateObj = new Date(dateStr + "T00:00:00");
      var dayIndex = dateObj.getDay();
      var dayName = dayNamesMap[dayIndex];

      console.log(`👆 [Calendar] Clicked: ${dateStr} (${dayName})`);

      var limit = parseInt(ticketsPerDay[dayName]) || 0;
      var booked = parseInt(globalBookings[dateStr]) || 0;
      var available = limit - booked;

      var today = new Date();
      today.setHours(0, 0, 0, 0);
      if (dateObj < today) {
        Swal.fire({
          toast: true,
          position: "top-end",
          showConfirmButton: false,
          timer: 2000,
          icon: "warning",
          title: "Date passée.",
        });
        return;
      }
      if (!workingDays.includes(dayIndex)) {
        Swal.fire({
          toast: true,
          position: "top-end",
          showConfirmButton: false,
          timer: 2000,
          icon: "warning",
          title: "Jour non travaillé.",
        });
        return;
      }

      var isSelected = selectedDates.includes(dateStr);
      if (!isSelected && limit > 0 && booked >= limit) {
        Swal.fire({
          toast: true,
          position: "top-end",
          showConfirmButton: false,
          timer: 2000,
          icon: "error",
          title: "Journée complète.",
        });
        return;
      }

      var maxSessions =
        parseInt($(`#${tablePrefix}__sessions_prescribed`).val()) || 0;

      if (isSelected) {
        selectedDates = selectedDates.filter((d) => d !== dateStr);
      } else {
        if (selectedDates.length >= maxSessions) {
          Swal.fire({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 2000,
            icon: "error",
            title: "Limite atteinte.",
          });
          return;
        }
        selectedDates.push(dateStr);
      }

      // Update UI classes immediately
      var cells = document.querySelectorAll('[data-date="' + dateStr + '"]');
      cells.forEach(function (cell) {
        if (selectedDates.includes(dateStr))
          cell.classList.add("fc-day-selected");
        else cell.classList.remove("fc-day-selected");
      });

      updateSelectedCount();
      $("#initial_sessions_dates").val(JSON.stringify(selectedDates));
    }

    // Function to Initialize or Re-Initialize Calendars
    function initCalendars() {
      if (calendarTopEl && calendarBottomEl) {
        var today = new Date();
        var nextMonth = new Date(today.getFullYear(), today.getMonth() + 1, 1);

        // Destroy existing instances if they exist
        if (calendarTop) calendarTop.destroy();
        if (calendarBottom) calendarBottom.destroy();

        calendarTop = new FullCalendar.Calendar(
          calendarTopEl,
          createCalendarConfig(today)
        );
        calendarTop.render();

        calendarBottom = new FullCalendar.Calendar(
          calendarBottomEl,
          createCalendarConfig(nextMonth)
        );
        calendarBottom.render();

        updateSelectedCount();
      }
    }

    // Initial Load
    initCalendars();

    $("#cal-next").on("click", function () {
      calendarTop.next();
      calendarBottom.next();
    });
    $("#cal-prev").on("click", function () {
      calendarTop.prev();
      calendarBottom.prev();
    });
    function updateSelectedCount() {
      $("#selected-count").text(selectedDates.length);
    }
    $("#clear-selection-btn").on("click", function () {
      selectedDates = [];
      $(".fc-day-selected").removeClass("fc-day-selected");
      updateSelectedCount();
      $("#initial_sessions_dates").val("[]");
    });

    // --- NEW: Update Calendar when Technician Changes ---
    $(`#${tablePrefix}__technician_id`).on("select2:select", function (e) {
      var techId = $(this).val();
      console.log("👨‍⚕️ [Technician] Changed to ID:", techId);

      if (!techId) return;

      $(".planning-calendar-wrapper").css("opacity", "0.5");

      $.ajax({
        url: config.siteUrl + "/handlers",
        type: "POST",
        data: { method: "get_technician_planning_data", technician_id: techId },
        dataType: "json",
        success: function (res) {
          console.log("📥 [Technician] Data Received:", res);
          if (res.state === "true") {
            // Update global variables
            workingDays = res.data.workingDays;
            ticketsPerDay = res.data.ticketsPerDay;
            globalBookings = res.data.globalBookings;

            console.log("🔄 [Calendar] Re-initializing with new data...");

            // Destroy and Re-create to force dayCellDidMount to run again
            initCalendars();
          }
        },
        error: function (err) {
          console.error("❌ [Technician] AJAX Error:", err);
        },
        complete: function () {
          $(".planning-calendar-wrapper").css("opacity", "1");
        },
      });
    });

    // Observe Theme Change
    var observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        if (mutation.attributeName === "class") {
          if (calendarTop) calendarTop.render();
          if (calendarBottom) calendarBottom.render();
        }
      });
    });
    observer.observe(document.documentElement, { attributes: true });
  }

  // --- Form Submission (Create) ---
  if (!config.isEditMode) {
    $(".codexForm").on("submit", function (e) {
      e.preventDefault();
      var form = $(this);
      if (!form.valid()) return;

      var btn = form.find('button[type="submit"]');
      var originalText = btn.text();
      btn
        .attr("disabled", "disabled")
        .html('<span class="spinner-border spinner-border-sm"></span>');

      var formData = form.serializeArray();
      var dates = $("#initial_sessions_dates").val();

      $.ajax({
        url: config.siteUrl + "/handlers",
        type: "POST",
        data: {
          method: "postReeducationDossier",
          data: formData,
          initial_sessions_dates: dates,
        },
        dataType: "json",
        success: function (response) {
          if (response.state === "true") {
            Swal.fire({
              title: "Succès",
              text: response.message,
              icon: "success",
              confirmButtonText: "OK",
              customClass: { confirmButton: "btn btn-success" },
            }).then(() => {
              window.location.href = config.siteUrl + "/reeducation";
            });
          } else {
            Swal.fire("Erreur", response.message, "error");
            btn.removeAttr("disabled").text(originalText);
          }
        },
        error: function () {
          Swal.fire("Erreur", "Erreur serveur.", "error");
          btn.removeAttr("disabled").text(originalText);
        },
      });
    });
  }

  // --- Edit Mode Actions ---
  if (config.isEditMode) {
    $("#generate-sessions-btn").on("click", function (e) {
      e.preventDefault();
      var btn = $(this);
      var dossierId = btn.data("dossier-id");
      if (selectedDates.length === 0) {
        Swal.fire({
          title: "Attention",
          text: "Sélectionnez au moins une date.",
          icon: "warning",
          customClass: { confirmButton: "btn btn-primary" },
        });
        return;
      }
      Swal.fire({
        title: "Confirmer ?",
        text: "Générer " + selectedDates.length + " séances ?",
        icon: "info",
        showCancelButton: true,
        confirmButtonText: "Oui",
        cancelButtonText: "Non",
        customClass: {
          confirmButton: "btn btn-primary",
          cancelButton: "btn btn-outline-danger ms-1",
        },
      }).then(function (result) {
        if (result.isConfirmed) {
          $.ajax({
            url: config.siteUrl + "/handlers",
            type: "POST",
            data: {
              method: "generate_sessions_manual",
              dossier_id: dossierId,
              dates: selectedDates,
            },
            dataType: "json",
            beforeSend: function () {
              btn.attr("disabled", "disabled").html("...");
            },
            success: function (response) {
              if (response.state === "true") {
                Swal.fire({
                  title: "Succès",
                  text: response.message,
                  icon: "success",
                  customClass: { confirmButton: "btn btn-success" },
                }).then(() => location.reload());
              } else {
                Swal.fire({
                  title: "Erreur",
                  text: response.message,
                  icon: "error",
                  customClass: { confirmButton: "btn btn-danger" },
                });
              }
            },
            complete: function () {
              btn
                .removeAttr("disabled")
                .html('<i data-feather="refresh-cw"></i> Générer');
            },
          });
        }
      });
    });

    $("#complete-dossier-btn").on("click", function (e) {
      e.preventDefault();
      Swal.fire({
        title: "Clôturer le dossier ?",
        text: "Statut -> Terminé.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Oui",
        cancelButtonText: "Non",
      }).then(function (result) {
        if (result.value) {
          $.ajax({
            url: config.siteUrl + "/data",
            type: "POST",
            data: {
              method: "updatForm",
              class: config.encryptedTable,
              object: config.encryptedWhere,
              csrf: $('input[name="csrf"]').val(),
              data: [{ name: `${tablePrefix}__status`, value: "completed" }],
            },
            dataType: "json",
            success: function (response) {
              if (response.state === "true") {
                Swal.fire("Succès!", "Dossier clôturé.", "success").then(() =>
                  location.reload()
                );
              }
            },
          });
        }
      });
    });
  }
});
