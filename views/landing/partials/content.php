<div class="content-grid">
    <div class="main-content">
        <!-- About Section -->
        <div class="card">
            <h3 class="card-title"><?= __t('about') ?></h3>
            <div class="about-text"
                style="color: var(--text-main); font-size: 0.95rem; white-space: pre-line; opacity: 0.9; line-height: 1.7;">
                <?= htmlspecialchars($doctor['description'] ?? "") ?>
            </div>
        </div>

        <!-- Gallery Section (Full Album Loop) -->
        <?php
        // Collect all available gallery images (image2 to image6)
        $gallery_images = [];
        for ($i = 2; $i <= 6; $i++) {
            if (!empty($doctor["image$i"])) {
                $gallery_images[] = $doctor["image$i"];
            }
        }
        ?>

        <?php if (!empty($gallery_images)): ?>
            <div class="card gallery-section">
                <h3 class="card-title"><?= __t('gallery') ?></h3>
                <div class="gallery-grid-container">
                    <?php foreach ($gallery_images as $img_src): ?>
                        <div style="overflow: hidden; border-radius: 12px;">
                            <img src="<?= $img_src ?>" class="gallery-img" onclick="openLightbox(this.src)" alt="Cabinet Photo">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Contact Section -->
        <div class="card" id="contact">
            <h3 class="card-title"><?= __t('coords') ?></h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div class="contact-item">
                    <div class="contact-icon"><i class="fas fa-phone-alt"></i></div>
                    <div>
                        <span
                            style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;"><?= __t('phone') ?></span><br>
                        <a href="tel:<?= htmlspecialchars($doctor['phone']) ?>"
                            style="font-size: 1rem; font-weight: 700; color: var(--secondary); text-decoration: none;"><?= htmlspecialchars($doctor['phone']) ?></a>
                    </div>
                </div>
                <?php if (!empty($doctor['cabinet_phone'])): ?>
                    <div class="contact-item">
                        <div class="contact-icon"><i class="fas fa-clinic-medical"></i></div>
                        <div>
                            <span
                                style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;"><?= __t('fix_phone') ?></span><br>
                            <a href="tel:<?= htmlspecialchars($doctor['cabinet_phone']) ?>"
                                style="color: var(--secondary); font-weight: 700; font-size: 1rem; text-decoration: none;"><?= htmlspecialchars($doctor['cabinet_phone']) ?></a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="contact-item" style="margin-top: 20px; padding-top: 20px; border-top: 1px dashed #e2e8f0;">
                <div class="contact-icon"><i class="fas fa-map-marked-alt"></i></div>
                <div>
                    <span
                        style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;"><?= __t('address') ?></span><br>
                    <strong
                        style="color: var(--secondary); font-size: 0.95rem;"><?= htmlspecialchars($doctor['cabinet_address']) ?></strong>
                    <div style="font-size: 0.9rem; color: var(--text-muted); margin-top: 2px;">
                        <?= htmlspecialchars($doctor['commune']) ?>, <?= htmlspecialchars($doctor['willaya']) ?>
                    </div>
                </div>
            </div>
            <?php if (!empty($doctor['lat']) && !empty($doctor['lng'])): ?>
                <div style="margin-top: 20px;">
                    <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $doctor['lat'] ?>,<?= $doctor['lng'] ?>"
                        target="_blank" class="btn-outline-custom"
                        style="width: 100%; justify-content: center; background: #f8fafc; display: flex; align-items: center; gap: 10px; padding: 12px; font-size: 0.9rem;">
                        <i class="fas fa-location-arrow"></i> <?= __t('open_maps') ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar (Hours & Widget) -->
    <div class="sidebar">
        <div class="card booking-widget">
            <h3
                style="color: white; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px; margin-bottom: 15px; font-size: 1.3rem;">
                <?= __t('book_btn') ?>
            </h3>
            <p style="color: rgba(255,255,255,0.8); margin-bottom: 20px; font-size: 0.9rem;"><?= __t('footer_desc') ?>
            </p>
            <button id="openBookingModal" class="btn-book-white"><?= __t('book_btn') ?></button>
        </div>

        <div class="card">
            <h3 class="card-title"><?= __t('hours') ?></h3>
            <table class="schedule-table" style="width: 100%;">
                <?php
                $days_fr = ['Samedi', 'Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
                $days_ar = ['السبت', 'الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'];
                $db_keys_map = ['Samedi' => 'Saturday', 'Dimanche' => 'Sunday', 'Lundi' => 'Monday', 'Mardi' => 'Tuesday', 'Mercredi' => 'Wednesday', 'Jeudi' => 'Thursday', 'Vendredi' => 'Friday'];
                $today_day = date('l');

                foreach ($days_fr as $index => $day_fr):
                    $day_display = ($lang_code == 'ar') ? $days_ar[$index] : $day_fr;
                    $is_today = ($db_keys_map[$day_fr] == $today_day);
                    $hours = isset($doctor['schedule'][$day_fr]) ? $doctor['schedule'][$day_fr] : (isset($doctor['schedule'][$db_keys_map[$day_fr]]) ? $doctor['schedule'][$db_keys_map[$day_fr]] : null);
                    ?>
                    <tr class="<?= $is_today ? 'today-row' : '' ?>">
                        <td class="day" style="font-weight: 600; color: var(--secondary); font-size: 0.9rem;">
                            <?= $day_display ?>
                            <?php if ($is_today): ?><i class="fas fa-clock"
                                    style="margin-left:5px; font-size:0.7rem; color: var(--primary);"></i><?php endif; ?>
                        </td>
                        <td class="hours" style="text-align: <?= $align == 'right' ? 'left' : 'right' ?>;">
                            <?php if (!empty($hours['from']) && !empty($hours['to'])): ?>
                                <span
                                    style="background: #f1f5f9; padding: 3px 8px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; color: var(--text-main);">
                                    <?= htmlspecialchars($hours['from']) ?> - <?= htmlspecialchars($hours['to']) ?>
                                </span>
                            <?php else: ?>
                                <span
                                    style="color: #ef4444; font-size: 0.8rem; font-weight: 600; background: #fef2f2; padding: 2px 6px; border-radius: 4px;"><?= __t('closed') ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>