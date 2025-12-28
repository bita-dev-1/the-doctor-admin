<!-- Booking Modal -->
<div id="bookingModalOverlay" class="modal-overlay">
    <div class="modal-content-box">
        <button id="closeModalBtn" class="modal-close-btn">&times;</button>

        <!-- Step 1: Date Selection -->
        <div id="step-1">
            <div style="text-align: center; margin-bottom: 35px;">
                <div
                    style="width: 60px; height: 60px; background: #e0f2fe; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: var(--primary);">
                    <i class="far fa-calendar-alt" style="font-size: 1.8rem;"></i>
                </div>
                <h3 style="color:var(--secondary); margin-bottom:8px; font-weight: 800; font-size: 1.5rem;">
                    <?= __t('choose_date') ?>
                </h3>
                <p style="color: var(--text-muted); font-size: 0.95rem; max-width: 80%; margin: 0 auto;">
                    <?= __t('select_date_msg') ?>
                </p>
            </div>

            <div class="date-input-wrapper">
                <label
                    style="display: block; margin-bottom: 10px; font-weight: 700; color: var(--text-main); font-size: 0.9rem;">
                    <?= __t('date_label') ?>
                </label>
                <div class="input-icon-wrapper">
                    <input type="date" id="booking-date" required min="<?= date('Y-m-d') ?>">
                    <!-- Date picker icon is handled by browser, but wrapper keeps styling consistent -->
                </div>
            </div>

            <div id="loading-slots" style="display:none; text-align:center; margin:40px 0;">
                <div
                    style="display: inline-block; padding: 15px 25px; background: #f8fafc; border-radius: 50px; border: 1px solid #e2e8f0;">
                    <i class="fas fa-circle-notch fa-spin" style="color:var(--primary);"></i>
                    <span
                        style="color: var(--text-muted); font-weight: 600; font-size: 0.9rem; margin: 0 10px;"><?= __t('checking') ?></span>
                </div>
            </div>

            <div id="slots-container"></div>
            <div id="availability-msg"
                style="text-align:center; margin-top:25px; padding: 15px; border-radius: 12px; font-size: 0.95rem;">
            </div>
        </div>

        <!-- Step 2: Patient Info -->
        <div id="step-2" style="display:none;">
            <h3 style="text-align:center; color:var(--secondary); margin-bottom:20px; font-weight: 800;">
                <?= __t('confirm_info') ?>
            </h3>

            <div
                style="display: flex; align-items: center; background: #f0f9ff; padding: 18px; border-radius: 16px; border: 1px dashed var(--primary); margin-bottom: 30px;">
                <div style="width: 100%; text-align: center;">
                    <small
                        style="color: var(--text-muted); display: block; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; font-weight: 600;"><?= __t('date_label') ?></small>
                    <strong id="selected-date-display" style="color: var(--secondary); font-size: 1.3rem;"></strong>
                </div>
            </div>

            <form id="booking-form">
                <!-- Name (Lastname then Firstname) -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label
                            style="display:block; margin-bottom:8px; font-weight:600; font-size: 0.9rem; color: var(--text-main);"><?= __t('lastname') ?></label>
                        <div class="input-icon-wrapper">
                            <input type="text" id="p_lastname">
                            <i class="far fa-user"></i>
                        </div>
                    </div>
                    <div>
                        <label
                            style="display:block; margin-bottom:8px; font-weight:600; font-size: 0.9rem; color: var(--text-main);"><?= __t('firstname') ?>
                            *</label>
                        <div class="input-icon-wrapper">
                            <input type="text" id="p_firstname" required>
                            <i class="far fa-user"></i>
                        </div>
                    </div>
                </div>

                <!-- Phone & Email -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom:15px;">
                    <div>
                        <label
                            style="display:block; margin-bottom:8px; font-weight:600; font-size: 0.9rem; color: var(--text-main);"><?= __t('phone') ?>
                            *</label>
                        <div class="input-icon-wrapper">
                            <input type="tel" id="p_phone" required placeholder="05/06/07..." style="direction: ltr;">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                    </div>
                    <div>
                        <label
                            style="display:block; margin-bottom:8px; font-weight:600; font-size: 0.9rem; color: var(--text-main);"><?= __t('email') ?></label>
                        <div class="input-icon-wrapper">
                            <input type="email" id="p_email" placeholder="exemple@mail.com" style="direction: ltr;">
                            <i class="far fa-envelope"></i>
                        </div>
                    </div>
                </div>

                <!-- Location (Wilaya & Commune) -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom:15px;">
                    <div>
                        <label
                            style="display:block; margin-bottom:8px; font-weight:600; font-size: 0.9rem; color: var(--text-main);"><?= __t('wilaya') ?></label>
                        <div class="input-icon-wrapper">
                            <select id="p_wilaya">
                                <option value=""><?= __t('choose') ?></option>
                            </select>
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                    </div>
                    <div>
                        <label
                            style="display:block; margin-bottom:8px; font-weight:600; font-size: 0.9rem; color: var(--text-main);"><?= __t('commune') ?></label>
                        <div class="input-icon-wrapper">
                            <select id="p_commune" disabled>
                                <option value="">--</option>
                            </select>
                            <i class="fas fa-map-pin"></i>
                        </div>
                    </div>
                </div>

                <!-- Motif -->
                <div style="margin-bottom:15px;">
                    <label
                        style="display:block; margin-bottom:8px; font-weight:600; font-size: 0.9rem; color: var(--text-main);"><?= __t('motif') ?></label>
                    <div class="input-icon-wrapper">
                        <select id="p_motif">
                            <option value=""><?= __t('motif_default') ?></option>
                        </select>
                        <i class="fas fa-stethoscope"></i>
                    </div>
                </div>

                <!-- Description -->
                <div style="margin-bottom:25px;">
                    <label
                        style="display:block; margin-bottom:8px; font-weight:600; font-size: 0.9rem; color: var(--text-main);"><?= __t('desc') ?></label>
                    <textarea id="p_description" rows="2"
                        style="width:100%; padding:14px; border-radius:12px; border: 1px solid #e2e8f0; resize:none; font-family: inherit;"></textarea>
                </div>

                <div id="booking-error"
                    style="color: #ef4444; background: #fef2f2; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; display:none; font-size: 0.9rem; border: 1px solid #fee2e2;">
                </div>

                <div style="display:flex; gap:15px;">
                    <button type="button" id="back-step-1"
                        style="flex:1; padding:16px; border:none; border-radius:50px; background:#f1f5f9; color: var(--text-muted); font-weight: 700; cursor:pointer; font-size: 1rem; transition: 0.3s;"><?= __t('back') ?></button>
                    <button type="submit" id="confirm-booking"
                        style="flex:2; padding:16px; border:none; border-radius:50px; background:var(--primary); color:white; font-weight: 700; cursor:pointer; box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3); font-size: 1rem; transition: 0.3s;"><?= __t('confirm') ?></button>
                </div>
            </form>
        </div>

        <!-- Step 3: Success -->
        <div id="step-3" style="display:none; text-align:center; padding:10px;">
            <div
                style="width: 90px; height: 90px; background: #ecfdf5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px;">
                <i class="fas fa-check" style="color:#10b981; font-size: 3rem;"></i>
            </div>
            <h2 style="margin-bottom:10px; color: var(--secondary); font-weight: 800;"><?= __t('success_title') ?></h2>
            <p style="color: var(--text-muted); font-size: 1.05rem;"><?= __t('success_msg') ?></p>

            <div
                style="background:var(--bg-body); padding:25px; border-radius:20px; margin:35px 0; border: 2px dashed #cbd5e1; position: relative; overflow: hidden;">
                <div
                    style="position: absolute; top: 0; left: 0; width: 100%; height: 6px; background: repeating-linear-gradient(45deg, var(--primary), var(--primary) 10px, white 10px, white 20px);">
                </div>
                <small
                    style="text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-muted); font-size: 0.8rem; font-weight: 700;"><?= __t('your_number') ?></small>
                <p style="margin:10px 0 0; font-size:4rem; font-weight: 800; color:var(--secondary); line-height: 1;">
                    <strong id="final-ticket"></strong>
                </p>
            </div>
            <button onclick="location.reload()"
                style="background:var(--secondary); color:white; border:none; padding:16px 50px; border-radius:50px; cursor:pointer; font-weight:700; font-size: 1.1rem; box-shadow: 0 10px 20px rgba(15, 23, 42, 0.15);"><?= __t('finish') ?></button>
        </div>
    </div>
</div>


<!-- My Appointments Modal -->
<div id="myAppointmentsModal" class="modal-overlay">
    <div class="modal-content-box" style="max-width: 600px;">
        <button id="closeMyAppointments" class="modal-close-btn">&times;</button>
        <h3 style="text-align:center; color:var(--secondary); margin-bottom:25px; font-weight: 800;">
            <?= __t('my_rdv_title') ?>
        </h3>
        <div id="appointments-list" style="max-height: 400px; overflow-y: auto; padding-right: 5px;"></div>
        <div style="text-align:center; margin-top:25px;">
            <button onclick="document.getElementById('myAppointmentsModal').style.display='none'"
                style="background:#f1f5f9; color:var(--secondary); border:none; padding:12px 30px; border-radius:50px; cursor:pointer; font-weight: 600;"><?= __t('close') ?></button>
        </div>
    </div>
</div>

<!-- Image Lightbox Modal -->
<div id="imageLightbox" class="lightbox-overlay" onclick="closeLightbox(event)">
    <span class="lightbox-close">&times;</span>
    <img id="lightboxImg" class="lightbox-content" src="" alt="Full View">
</div>