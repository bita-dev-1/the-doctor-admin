<!-- Booking Modal -->
<div id="bookingModalOverlay" class="modal-overlay">
    <div class="modal-content-box">
        <button id="closeModalBtn"
            style="position:absolute; right:20px; top:20px; border:none; background:none; font-size:1.8rem; cursor:pointer; color: #cbd5e1; transition:0.3s;">&times;</button>

        <!-- Step 1: Date Selection -->
        <div id="step-1">
            <div style="text-align: center; margin-bottom: 35px;">
                <div
                    style="width: 60px; height: 60px; background: #e0f2fe; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: var(--primary);">
                    <i class="far fa-calendar-alt" style="font-size: 1.8rem;"></i>
                </div>
                <h3 style="color:var(--secondary); margin-bottom:8px; font-weight: 800; font-size: 1.5rem;">
                    Choisir une date
                </h3>
                <p style="color: var(--text-muted); font-size: 0.95rem; max-width: 80%; margin: 0 auto;">
                    Sélectionnez une date pour réserver.
                </p>
            </div>

            <div class="date-input-wrapper">
                <label
                    style="display: block; margin-bottom: 10px; font-weight: 700; color: var(--text-main); font-size: 0.9rem; margin-left: 5px;">
                    Date du rendez-vous
                </label>
                <div style="position: relative;">
                    <input type="date" id="booking-date" required min="<?= date('Y-m-d') ?>">
                    <i class="fas fa-chevron-down"
                        style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; font-size: 0.9rem;"></i>
                </div>
            </div>

            <div id="loading-slots" style="display:none; text-align:center; margin:40px 0;">
                <div
                    style="display: inline-block; padding: 15px 25px; background: #f8fafc; border-radius: 50px; border: 1px solid #e2e8f0;">
                    <i class="fas fa-circle-notch fa-spin" style="color:var(--primary); margin-right: 10px;"></i>
                    <span style="color: var(--text-muted); font-weight: 600; font-size: 0.9rem;">Vérification...</span>
                </div>
            </div>

            <div id="slots-container"></div>
            <div id="availability-msg"
                style="text-align:center; margin-top:25px; padding: 15px; border-radius: 12px; font-size: 0.95rem;">
            </div>
        </div>

        <!-- Step 2: Patient Info (Cleaned - No Ticket Number) -->
        <div id="step-2" style="display:none;">
            <h3 style="text-align:center; color:var(--secondary); margin-bottom:20px; font-weight: 800;">Confirmez vos
                infos</h3>

            <div
                style="display: flex; align-items: center; background: #f0f9ff; padding: 18px; border-radius: 16px; border: 1px dashed var(--primary); margin-bottom: 30px;">
                <!-- Hidden Ticket Display -->
                <div style="display:none;" id="selected-ticket-display"></div>

                <div style="width: 100%; text-align: center;">
                    <small
                        style="color: var(--text-muted); display: block; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; font-weight: 600;">Date
                        du rendez-vous</small>
                    <strong id="selected-date-display" style="color: var(--secondary); font-size: 1.3rem;"></strong>
                </div>
            </div>

            <form id="booking-form">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div style="margin-bottom:15px;">
                        <label
                            style="display:block; margin-bottom:8px; font-weight:600; font-size: 0.9rem; color: var(--text-main);">Prénom
                            *</label>
                        <input type="text" id="p_firstname" required
                            style="width:100%; padding:14px; border-radius:12px; border: 1px solid #e2e8f0;">
                    </div>
                    <div style="margin-bottom:15px;">
                        <label
                            style="display:block; margin-bottom:8px; font-weight:600; font-size: 0.9rem; color: var(--text-main);">Nom</label>
                        <input type="text" id="p_lastname"
                            style="width:100%; padding:14px; border-radius:12px; border: 1px solid #e2e8f0;">
                    </div>
                </div>
                <div style="margin-bottom:25px;">
                    <label
                        style="display:block; margin-bottom:8px; font-weight:600; font-size: 0.9rem; color: var(--text-main);">Téléphone
                        *</label>
                    <input type="tel" id="p_phone" required placeholder="05/06/07..."
                        style="width:100%; padding:14px; border-radius:12px; border: 1px solid #e2e8f0;">
                </div>

                <div id="booking-error"
                    style="color: #ef4444; background: #fef2f2; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; display:none; font-size: 0.9rem; border: 1px solid #fee2e2;">
                </div>

                <div style="display:flex; gap:15px;">
                    <button type="button" id="back-step-1"
                        style="flex:1; padding:16px; border:none; border-radius:50px; background:#f1f5f9; color: var(--text-muted); font-weight: 700; cursor:pointer; font-size: 1rem; transition: 0.3s;">Retour</button>
                    <button type="submit" id="confirm-booking"
                        style="flex:2; padding:16px; border:none; border-radius:50px; background:var(--primary); color:white; font-weight: 700; cursor:pointer; box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3); font-size: 1rem; transition: 0.3s;">Confirmer</button>
                </div>
            </form>
        </div>

        <!-- Step 3: Success -->
        <div id="step-3" style="display:none; text-align:center; padding:10px;">
            <div
                style="width: 90px; height: 90px; background: #ecfdf5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px;">
                <i class="fas fa-check" style="color:#10b981; font-size: 3rem;"></i>
            </div>
            <h2 style="margin-bottom:10px; color: var(--secondary); font-weight: 800;">Réservation Confirmée !</h2>
            <p style="color: var(--text-muted); font-size: 1.05rem;">Votre rendez-vous a été enregistré avec succès.</p>

            <div
                style="background:var(--bg-body); padding:25px; border-radius:20px; margin:35px 0; border: 2px dashed #cbd5e1; position: relative; overflow: hidden;">
                <div
                    style="position: absolute; top: 0; left: 0; width: 100%; height: 6px; background: repeating-linear-gradient(45deg, var(--primary), var(--primary) 10px, white 10px, white 20px);">
                </div>
                <small
                    style="text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-muted); font-size: 0.8rem; font-weight: 700;">Votre
                    Numéro</small>
                <p style="margin:10px 0 0; font-size:4rem; font-weight: 800; color:var(--secondary); line-height: 1;">
                    <strong id="final-ticket"></strong>
                </p>
            </div>
            <button onclick="location.reload()"
                style="background:var(--secondary); color:white; border:none; padding:16px 50px; border-radius:50px; cursor:pointer; font-weight:700; font-size: 1.1rem; box-shadow: 0 10px 20px rgba(15, 23, 42, 0.15);">Terminer</button>
        </div>
    </div>
</div>

<!-- My Appointments Modal -->
<div id="myAppointmentsModal" class="modal-overlay">
    <div class="modal-content-box" style="max-width: 600px;">
        <button id="closeMyAppointments"
            style="position:absolute; right:20px; top:20px; border:none; background:none; font-size:1.5rem; cursor:pointer; color: #999;">&times;</button>
        <h3 style="text-align:center; color:var(--secondary); margin-bottom:25px; font-weight: 800;">Mes Rendez-vous
        </h3>
        <div id="appointments-list" style="max-height: 400px; overflow-y: auto; padding-right: 5px;"></div>
        <div style="text-align:center; margin-top:25px;">
            <button onclick="document.getElementById('myAppointmentsModal').style.display='none'"
                style="background:#f1f5f9; color:var(--secondary); border:none; padding:12px 30px; border-radius:50px; cursor:pointer; font-weight: 600;">Fermer</button>
        </div>
    </div>
</div>