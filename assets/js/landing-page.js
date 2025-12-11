/**
 * Landing Page Logic
 * Handles: Booking, Availability, My Appointments, and Recommendations
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
  let selectedTicket = null;
  let selectedDate = null;

  // ============================================================
  // 2. HELPER FUNCTIONS
  // ============================================================

  // Get Today's Date in YYYY-MM-DD format (Local Time)
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
  }

  // ============================================================
  // 3. BOOKING LOGIC
  // ============================================================

  // Open Booking Modal
  const openModalHandler = (e) => {
    if (e) e.preventDefault();
    if (modal) {
      modal.style.display = "flex";
      // Set default date if empty
      if (!dateInput.value) {
        dateInput.value = getTodayDate();
      }
      // Fetch slots immediately
      fetchAvailability(dateInput.value);
    }
  };

  // Attach listeners to all booking buttons
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

  // Fetch Availability from API
  function fetchAvailability(date) {
    if (!date) return;

    selectedDate = date;
    slotsContainer.innerHTML = "";
    msgBox.innerHTML = "";
    loadingSlots.style.display = "block";

    fetch(`${API_BASE}/availability?doctor_id=${DOCTOR_ID}&date=${date}`)
      .then((response) => response.json())
      .then((data) => {
        loadingSlots.style.display = "none";

        if (data.state === "true" && data.available === true) {
          if (data.slots && data.slots.length > 0) {
            renderSlots(data.slots);
          } else {
            msgBox.innerHTML =
              '<span style="color:#ea5455; font-weight:bold;">Complet (Aucun ticket disponible)</span>';
          }
        } else {
          let reason = "Non disponible";
          if (data.reason === "Day off") reason = "Jour de repos (Fermé)";
          if (data.reason === "No tickets configured")
            reason = "Planning non configuré";

          msgBox.innerHTML = `<span style="color:#ea5455; font-weight:bold;">${reason}</span>`;
        }
      })
      .catch((err) => {
        loadingSlots.style.display = "none";
        msgBox.innerHTML =
          '<span style="color:red">Erreur de connexion.</span>';
        console.error(err);
      });
  }

  // Render Slot Buttons
  function renderSlots(slots) {
    slots.forEach((ticket) => {
      const btn = document.createElement("button");
      btn.className = "slot-btn";
      btn.textContent = ticket;
      btn.type = "button";
      btn.onclick = () => goToStep2(ticket);
      slotsContainer.appendChild(btn);
    });
  }

  // Go to Patient Info Step
  function goToStep2(ticket) {
    selectedTicket = ticket;
    document.getElementById("selected-ticket-display").textContent = ticket;
    document.getElementById("selected-date-display").textContent = selectedDate;

    step1.style.display = "none";
    step2.style.display = "block";
  }

  // Back Button
  if (backBtn) {
    backBtn.addEventListener("click", () => {
      step2.style.display = "none";
      step1.style.display = "block";
      errorMsg.style.display = "none";
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
        ticket_number: selectedTicket,
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
          confirmBtn.textContent = "Confirmer le RDV";

          if (data.state === "true") {
            // Save to LocalStorage
            const docName = document.title.split("-")[0].trim();
            saveAppointment({
              doctor_name: docName,
              date: selectedDate,
              ticket: data.ticket_number,
              rdv_id: data.rdv_id,
              created_at: new Date().toISOString(),
            });

            // Show Success Step
            document.getElementById("final-ticket").textContent =
              data.ticket_number;
            step2.style.display = "none";
            step3.style.display = "block";
          } else {
            showError(data.message || "Erreur lors de la réservation.");
          }
        })
        .catch((err) => {
          confirmBtn.disabled = false;
          confirmBtn.textContent = "Confirmer le RDV";
          showError("Erreur de connexion au serveur.");
        });
    });
  }

  // ============================================================
  // 4. MY APPOINTMENTS LOGIC
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

    // Show loading state
    appListContainer.innerHTML =
      '<div style="text-align:center; padding:20px;"><i class="fas fa-spinner fa-spin fa-2x" style="color:var(--primary)"></i></div>';

    // Extract IDs to fetch fresh data
    const rdvIds = apps.map((a) => a.rdv_id);

    fetch(`${API_BASE}/my-appointments`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ ids: rdvIds }),
    })
      .then((response) => response.json())
      .then((res) => {
        appListContainer.innerHTML = ""; // Clear loader

        if (res.state === "true" && res.data.length > 0) {
          // Sort by date (newest first)
          res.data.sort((a, b) => new Date(b.date) - new Date(a.date));

          res.data.forEach((app) => {
            // Determine Status Style
            let statusBadge = "";
            switch (parseInt(app.state)) {
              case 0: // Created
                statusBadge =
                  '<span style="background:#fff3cd; color:#856404; padding:4px 10px; border-radius:12px; font-size:0.8rem; font-weight:bold;">En attente</span>';
                break;
              case 1: // Accepted
                statusBadge =
                  '<span style="background:#d1e7dd; color:#0f5132; padding:4px 10px; border-radius:12px; font-size:0.8rem; font-weight:bold;">Confirmé</span>';
                break;
              case 2: // Completed
                statusBadge =
                  '<span style="background:#cff4fc; color:#055160; padding:4px 10px; border-radius:12px; font-size:0.8rem; font-weight:bold;">Terminé</span>';
                break;
              case 3: // Canceled
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
  // 5. RECOMMENDATION LOGIC
  // ============================================================

  if (recBtn) {
    const storageKey = "rec_doctor_" + DOCTOR_ID;

    // Check if already recommended
    if (localStorage.getItem(storageKey)) {
      recBtn.classList.add("liked");
      recBtn.style.cursor = "default";
    } else {
      // Attach click event only if not liked yet
      recBtn.addEventListener("click", function () {
        // Optimistic UI Update
        let currentCount = parseInt(recCount.innerText.replace(/,/g, ""));
        recCount.innerText = (currentCount + 1).toLocaleString();
        recBtn.classList.add("liked");

        // Send to API
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
              // Remove listener to prevent multiple clicks
              recBtn.replaceWith(recBtn.cloneNode(true));
            } else {
              // Revert on error
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
