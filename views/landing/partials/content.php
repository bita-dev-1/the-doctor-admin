<div class="content-grid">
    <div class="main-content">
        <div class="card">
            <h3 class="card-title">À propos du Docteur</h3>
            <div class="about-text"
                style="color: var(--text-main); font-size: 1.05rem; white-space: pre-line; opacity: 0.9; line-height: 1.8;">
                <?= htmlspecialchars($doctor['description'] ?? "Bienvenue au cabinet médical. Nous sommes dévoués à votre santé et votre bien-être.") ?>
            </div>
        </div>

        <?php if (!empty($doctor['image2']) || !empty($doctor['image3'])): ?>
            <div class="card gallery-section">
                <h3 class="card-title">Galerie du Cabinet</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
                    <?php if (!empty($doctor['image2'])): ?>
                        <img src="<?= $doctor['image2'] ?>"
                            style="width:100%; height:220px; object-fit:cover; border-radius:16px; cursor:pointer; box-shadow: var(--shadow-sm); transition: transform 0.3s;"
                            onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform='scale(1)'"
                            alt="Cabinet 1">
                    <?php endif; ?>
                    <?php if (!empty($doctor['image3'])): ?>
                        <img src="<?= $doctor['image3'] ?>"
                            style="width:100%; height:220px; object-fit:cover; border-radius:16px; cursor:pointer; box-shadow: var(--shadow-sm); transition: transform 0.3s;"
                            onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform='scale(1)'"
                            alt="Cabinet 2">
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="card" id="contact">
            <h3 class="card-title">Coordonnées & Accès</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 25px;">
                <div class="contact-item">
                    <div class="contact-icon"><i class="fas fa-phone-alt"></i></div>
                    <div>
                        <span
                            style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Téléphone</span><br>
                        <a href="tel:<?= htmlspecialchars($doctor['phone']) ?>"
                            style="font-size: 1.1rem; font-weight: 700; color: var(--secondary); text-decoration: none;"><?= htmlspecialchars($doctor['phone']) ?></a>
                    </div>
                </div>
                <?php if (!empty($doctor['cabinet_phone'])): ?>
                    <div class="contact-item">
                        <div class="contact-icon"><i class="fas fa-clinic-medical"></i></div>
                        <div>
                            <span
                                style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Fixe
                                Cabinet</span><br>
                            <a href="tel:<?= htmlspecialchars($doctor['cabinet_phone']) ?>"
                                style="color: var(--secondary); font-weight: 700; font-size: 1.1rem; text-decoration: none;"><?= htmlspecialchars($doctor['cabinet_phone']) ?></a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="contact-item" style="margin-top: 25px; padding-top: 25px; border-top: 1px dashed #e2e8f0;">
                <div class="contact-icon"><i class="fas fa-map-marked-alt"></i></div>
                <div>
                    <span
                        style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Adresse</span><br>
                    <strong
                        style="color: var(--secondary); font-size: 1.05rem;"><?= htmlspecialchars($doctor['cabinet_address']) ?></strong>
                    <div style="font-size: 0.95rem; color: var(--text-muted); margin-top: 2px;">
                        <?= htmlspecialchars($doctor['commune']) ?>, <?= htmlspecialchars($doctor['willaya']) ?>
                    </div>
                </div>
            </div>
            <?php if (!empty($doctor['lat']) && !empty($doctor['lng'])): ?>
                <div style="margin-top: 30px;">
                    <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $doctor['lat'] ?>,<?= $doctor['lng'] ?>"
                        target="_blank" class="btn-outline-custom"
                        style="width: 100%; justify-content: center; background: #f8fafc; display: flex; align-items: center; gap: 10px; padding: 14px;">
                        <i class="fas fa-location-arrow"></i> Ouvrir dans Google Maps
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar (Hours & Widget) -->
    <div class="sidebar">
        <div class="card booking-widget">
            <h3
                style="color: white; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; margin-bottom: 20px; font-size: 1.5rem;">
                Prendre RDV</h3>
            <p style="color: rgba(255,255,255,0.8); margin-bottom: 25px; font-size: 1rem;">Réservez votre consultation
                en quelques clics.</p>
            <button id="openBookingModal" class="btn-book-white">Réserver Maintenant</button>
            <div
                style="margin-top: 20px; font-size: 0.85rem; color: rgba(255,255,255,0.5); display: flex; align-items: center; justify-content: center; gap: 6px;">
                <i class="fas fa-shield-alt"></i> Réservation 100% Gratuite
            </div>
        </div>

        <div class="card">
            <h3 class="card-title">Horaires</h3>
            <table class="schedule-table" style="width: 100%;">
                <?php
                $days_fr = ['Samedi', 'Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
                $db_keys_map = ['Samedi' => 'Saturday', 'Dimanche' => 'Sunday', 'Lundi' => 'Monday', 'Mardi' => 'Tuesday', 'Mercredi' => 'Wednesday', 'Jeudi' => 'Thursday', 'Vendredi' => 'Friday'];
                $today_day = date('l');

                foreach ($days_fr as $day_fr):
                    $is_today = ($db_keys_map[$day_fr] == $today_day);
                    $hours = isset($doctor['schedule'][$day_fr]) ? $doctor['schedule'][$day_fr] : (isset($doctor['schedule'][$db_keys_map[$day_fr]]) ? $doctor['schedule'][$db_keys_map[$day_fr]] : null);
                    ?>
                    <tr class="<?= $is_today ? 'today-row' : '' ?>">
                        <td class="day" style="font-weight: 600; color: var(--secondary);">
                            <?= $day_fr ?>
                            <?php if ($is_today): ?><i class="fas fa-clock"
                                    style="margin-left:5px; font-size:0.7rem; color: var(--primary);"></i><?php endif; ?>
                        </td>
                        <td class="hours" style="text-align: right;">
                            <?php if (!empty($hours['from']) && !empty($hours['to'])): ?>
                                <span
                                    style="background: #f1f5f9; padding: 4px 10px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; color: var(--text-main);">
                                    <?= htmlspecialchars($hours['from']) ?> - <?= htmlspecialchars($hours['to']) ?>
                                </span>
                            <?php else: ?>
                                <span
                                    style="color: #ef4444; font-size: 0.85rem; font-weight: 600; background: #fef2f2; padding: 2px 8px; border-radius: 4px;">Fermé</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>