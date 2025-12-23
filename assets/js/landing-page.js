/**
 * Landing Page Logic - Full Refactor
 * Handles: Booking (Auto-assign), Availability, My Appointments, and Recommendations
 */

document.addEventListener("DOMContentLoaded", function () {
  // ============================================================
  // 1. VARIABLES & ELEMENTS
  // ============================================================

  // Booking Modal Elements
  const modal = document.getElementById("bookingModalOverlay");
  const openBtns = document.querySelectorAll(
    "#navBookBtn, #heroBookBtn, #footerBookBtn, #openBookingModal"
  );
  const closeBtn = document.getElementById("closeModalBtn");

  // My Appointments Elements
  const myAppModal = document.getElementById("myAppointmentsModal");
  const myAppBtn = document.getElementById("myAppointmentsBtn");
  const closeMyAppBtn = document.getElementById("closeMyAppointments");
  const appListContainer = document.getElementById("appointments-list");

  // Form & Steps Elements
  const dateInput = document.getElementById("booking-date");
  const slotsContainer = document.getElementById("slots-container");
  const loadingSlots = document.getElementById("loading-slots");
  const msgBox = document.getElementById("availability-msg");

  const step1 = document.getElementById("step-1");
  const step2 = document.getElementById("step-2");
  const step3 = document.getElementById("step-3");

  const backBtn = document.getElementById("back-step-1");
  const bookingForm = document.getElementById("booking-form");
  const errorMsg = document.getElementById("booking-error");
  const confirmBtn = document.getElementById("confirm-booking");

  // Recommendation Elements
  const recBtn = document.getElementById("recommendBtn");
  const recCount = document.getElementById("recommendCount");

  // State Variables
  let selectedTicket = 0; // Default to 0 for auto-assignment
  let selectedDate = null;

  // ============================================================
  // 2. HELPER FUNCTIONS
  // ============================================================

  // Get Today's Date in YYYY-MM-DD format
  const getTodayDate = () => {
    const date = new Date();
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
  };

  // LocalStorage: Save Appointment
  const saveAppointment = (data) => {
    let appointments = JSON.parse(
      localStorage.getItem("my_appointments") || "[]"
    );
    appointments.push(data);
    localStorage.setItem("my_appointments", JSON.stringify(appointments));
  };

  // LocalStorage: Get Appointments
  const getAppointments = () => {
    return JSON.parse(localStorage.getItem("my_appointments") || "[]");
  };

  // UI: Show Error Message
  function showError(msg) {
    if (errorMsg) {
      errorMsg.textContent = msg;
      errorMsg.style.display = "block";
    }
  }

  // UI: Reset Form State
  function resetForm() {
    if (step1) step1.style.display = "block";
    if (step2) step2.style.display = "none";
    if (step3) step3.style.display = "none";
    if (bookingForm) bookingForm.reset();
    if (errorMsg) errorMsg.style.display = "none";
    if (slotsContainer) slotsContainer.innerHTML = "";
    if (msgBox) msgBox.innerHTML = "";
    selectedTicket = 0;
  }

  // ============================================================
  // 3. CORE FUNCTIONS (Render & Navigation)
  // ============================================================

  // الانتقال للخطوة الثانية (تأكيد المعلومات)
  function goToStep2(ticket) {
    selectedTicket = ticket; // 0 means auto-assign

    // تحديث عرض التاريخ
    const dateDisplay = document.getElementById("selected-date-display");
    if (dateDisplay) {
      // تنسيق التاريخ ليكون مقروءاً
      const dateObj = new Date(selectedDate);
      const options = {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
      };
      dateDisplay.textContent = dateObj.toLocaleDateString("fr-FR", options);
    }

    // إخفاء الخطوة 1 وإظهار الخطوة 2
    if (step1) step1.style.display = "none";
    if (step2) step2.style.display = "block";
  }

  // عرض زر الحجز (الصندوق الأسود - بدون أرقام)
  function renderSlots(slots) {
    slotsContainer.innerHTML = "";

    // إذا كانت هناك أماكن شاغرة (المصفوفة ليست فارغة)
    if (slots && slots.length > 0) {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "btn-primary-custom"; // استخدام كلاس CSS

      // تنسيق إضافي لضمان الظهور
      btn.style.width = "100%";
      btn.style.padding = "15px";
      btn.style.fontSize = "1.1rem";
      btn.style.justifyContent = "center";
      btn.style.borderRadius = "12px";
      btn.style.marginTop = "15px";
      btn.style.cursor = "pointer";

      btn.innerHTML =
        '<i class="far fa-calendar-check" style="margin-right:10px"></i> Confirmer la réservation';

      // عند النقر، ننتقل للخطوة التالية مع التذكرة رقم 0
      btn.onclick = function () {
        goToStep2(0);
      };

      slotsContainer.appendChild(btn);
    } else {
      slotsContainer.innerHTML = `
              <div style="text-align:center; padding: 20px; color: #ea5455; background: #fef2f2; border-radius: 12px;">
                  <i class="fas fa-times-circle" style="font-size: 2rem; margin-bottom: 10px;"></i>
                  <p style="font-weight:bold; margin:0;">Complet</p>
                  <small>Aucun rendez-vous disponible pour cette date.</small>
              </div>
            `;
    }
  }

  // جلب التوفر من الـ API
  function fetchAvailability(date) {
    if (!date) return;

    selectedDate = date;
    slotsContainer.innerHTML = "";
    if (msgBox) msgBox.innerHTML = "";
    if (loadingSlots) loadingSlots.style.display = "block";

    // استخدام المتغيرات العامة المعرفة في footer.php
    fetch(`${API_BASE}/availability?doctor_id=${DOCTOR_ID}&date=${date}`)
      .then((response) => response.json())
      .then((data) => {
        if (loadingSlots) loadingSlots.style.display = "none";

        if (data.state === "true" && data.available === true) {
          if (data.slots && data.slots.length > 0) {
            renderSlots(data.slots);
          } else {
            if (msgBox)
              msgBox.innerHTML =
                '<span style="color:#ea5455; font-weight:bold;">Complet (Aucun ticket disponible)</span>';
          }
        } else {
          let reason = "Non disponible";
          if (data.reason === "Day off") reason = "Jour de repos (Fermé)";
          if (data.reason === "No tickets configured")
            reason = "Planning non configuré";

          if (msgBox)
            msgBox.innerHTML = `<span style="color:#ea5455; font-weight:bold;">${reason}</span>`;
        }
      })
      .catch((err) => {
        if (loadingSlots) loadingSlots.style.display = "none";
        if (msgBox)
          msgBox.innerHTML =
            '<span style="color:red">Erreur de connexion.</span>';
        console.error(err);
      });
  }

  // ============================================================
  // 4. EVENT LISTENERS
  // ============================================================

  // Open Booking Modal
  const openModalHandler = (e) => {
    if (e) e.preventDefault();
    if (modal) {
      modal.style.display = "flex";
      if (!dateInput.value) {
        dateInput.value = getTodayDate();
      }
      fetchAvailability(dateInput.value);
    }
  };

  openBtns.forEach((btn) => {
    if (btn) btn.addEventListener("click", openModalHandler);
  });

  // Close Booking Modal
  if (closeBtn) {
    closeBtn.addEventListener("click", () => {
      modal.style.display = "none";
      resetForm();
    });
  }

  // Handle Date Change
  if (dateInput) {
    dateInput.addEventListener("change", (e) => {
      fetchAvailability(e.target.value);
    });
  }

  // Back Button
  if (backBtn) {
    backBtn.addEventListener("click", () => {
      step2.style.display = "none";
      step1.style.display = "block";
      if (errorMsg) errorMsg.style.display = "none";
    });
  }

  // Submit Booking Form
  if (bookingForm) {
    bookingForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const firstName = document.getElementById("p_firstname").value;
      const lastName = document.getElementById("p_lastname").value;
      const phone = document.getElementById("p_phone").value;

      if (!firstName || !phone) {
        showError("Veuillez remplir les champs obligatoires.");
        return;
      }

      confirmBtn.disabled = true;
      confirmBtn.textContent = "Traitement...";

      const payload = {
        doctor_id: DOCTOR_ID,
        date: selectedDate,
        ticket_number: selectedTicket, // Will be 0
        first_name: firstName,
        last_name: lastName,
        phone: phone,
      };

      fetch(`${API_BASE}/book`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      })
        .then((response) => response.json())
        .then((data) => {
          confirmBtn.disabled = false;
          confirmBtn.textContent = "Confirmer";

          if (data.state === "true") {
            // Save to LocalStorage
            const docName = document.title.split("-")[0].trim();
            saveAppointment({
              doctor_name: docName,
              date: selectedDate,
              ticket: data.ticket_number, // The assigned ticket from server
              rdv_id: data.rdv_id,
              created_at: new Date().toISOString(),
            });

            // Show Success Step
            const finalTicketEl = document.getElementById("final-ticket");
            if (finalTicketEl) finalTicketEl.textContent = data.ticket_number;

            step2.style.display = "none";
            step3.style.display = "block";
          } else {
            showError(data.message || "Erreur lors de la réservation.");
          }
        })
        .catch((err) => {
          confirmBtn.disabled = false;
          confirmBtn.textContent = "Confirmer";
          showError("Erreur de connexion au serveur.");
        });
    });
  }

  // ============================================================
  // 5. MY APPOINTMENTS LOGIC
  // ============================================================

  if (myAppBtn) {
    myAppBtn.addEventListener("click", () => {
      renderAppointmentsList();
      if (myAppModal) myAppModal.style.display = "flex";
    });
  }

  if (closeMyAppBtn) {
    closeMyAppBtn.addEventListener("click", () => {
      if (myAppModal) myAppModal.style.display = "none";
    });
  }

  function renderAppointmentsList() {
    const apps = getAppointments();
    appListContainer.innerHTML = "";

    if (apps.length === 0) {
      appListContainer.innerHTML =
        '<p style="text-align:center; color:#777;">Aucun rendez-vous enregistré sur cet appareil.</p>';
      return;
    }

    appListContainer.innerHTML =
      '<div style="text-align:center; padding:20px;"><i class="fas fa-spinner fa-spin fa-2x" style="color:var(--primary)"></i></div>';

    const rdvIds = apps.map((a) => a.rdv_id);

    fetch(`${API_BASE}/my-appointments`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ ids: rdvIds }),
    })
      .then((response) => response.json())
      .then((res) => {
        appListContainer.innerHTML = "";

        if (res.state === "true" && res.data.length > 0) {
          res.data.sort((a, b) => new Date(b.date) - new Date(a.date));

          res.data.forEach((app) => {
            let statusBadge = "";
            switch (parseInt(app.state)) {
              case 0:
                statusBadge =
                  '<span style="background:#fff3cd; color:#856404; padding:4px 10px; border-radius:12px; font-size:0.8rem; font-weight:bold;">En attente</span>';
                break;
              case 1:
                statusBadge =
                  '<span style="background:#d1e7dd; color:#0f5132; padding:4px 10px; border-radius:12px; font-size:0.8rem; font-weight:bold;">Confirmé</span>';
                break;
              case 2:
                statusBadge =
                  '<span style="background:#cff4fc; color:#055160; padding:4px 10px; border-radius:12px; font-size:0.8rem; font-weight:bold;">Terminé</span>';
                break;
              case 3:
                statusBadge =
                  '<span style="background:#f8d7da; color:#842029; padding:4px 10px; border-radius:12px; font-size:0.8rem; font-weight:bold;">Annulé</span>';
                break;
            }

            const item = document.createElement("div");
            item.style.cssText =
              "background:#fff; border:1px solid #eee; border-left:4px solid var(--primary); padding:15px; margin-bottom:10px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.05);";

            item.innerHTML = `
                          <div style="display:flex; justify-content:space-between; align-items:center;">
                              <div>
                                  <h4 style="margin:0 0 5px 0; color:#333; font-size:1rem;">Dr. ${app.doctor_firstname} ${app.doctor_name}</h4>
                                  <p style="margin:0; color:#666; font-size:0.9rem;">
                                      <i class="far fa-calendar-alt"></i> ${app.date} 
                                      <span style="margin:0 8px; color:#ddd;">|</span> 
                                      <i class="fas fa-ticket-alt"></i> Ticket: <strong>${app.rdv_num}</strong>
                                  </p>
                              </div>
                              <div style="text-align:right;">
                                  ${statusBadge}
                              </div>
                          </div>
                      `;
            appListContainer.appendChild(item);
          });
        } else {
          appListContainer.innerHTML =
            '<p style="text-align:center; color:#ea5455;">Impossible de récupérer les statuts.</p>';
        }
      })
      .catch((err) => {
        console.error(err);
        appListContainer.innerHTML =
          '<p style="text-align:center; color:#ea5455;">Erreur de connexion.</p>';
      });
  }

  // ============================================================
  // 6. RECOMMENDATION LOGIC
  // ============================================================

  if (recBtn) {
    const storageKey = "rec_doctor_" + DOCTOR_ID;
    if (localStorage.getItem(storageKey)) {
      recBtn.classList.add("liked");
      recBtn.style.cursor = "default";
    } else {
      recBtn.addEventListener("click", function () {
        let currentCount = parseInt(recCount.innerText.replace(/,/g, ""));
        recCount.innerText = (currentCount + 1).toLocaleString();
        recBtn.classList.add("liked");

        fetch(`${API_BASE}/recommend`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ doctor_id: DOCTOR_ID }),
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.state === "true") {
              localStorage.setItem(storageKey, "true");
              recCount.innerText = parseInt(data.new_count).toLocaleString();
              recBtn.style.cursor = "default";
              recBtn.replaceWith(recBtn.cloneNode(true));
            } else {
              recCount.innerText = currentCount.toLocaleString();
              recBtn.classList.remove("liked");
            }
          })
          .catch((err) => {
            recCount.innerText = currentCount.toLocaleString();
            recBtn.classList.remove("liked");
          });
      });
    }
  }
});
