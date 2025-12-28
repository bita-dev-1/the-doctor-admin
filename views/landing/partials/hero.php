<header class="hero">
    <div class="container hero-content">
        <div class="hero-text">
            <span class="specialty-badge">
                <i class="fas fa-certificate" style="margin-<?= $align == 'right' ? 'left' : 'right' ?>:8px;"></i>
                <?= htmlspecialchars($lang_code == 'ar' && !empty($doctor['specialty_ar']) ? $doctor['specialty_ar'] : $doctor['specialty_fr']) ?>
            </span>
            <h1>Dr. <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?></h1>

            <div class="location-text">
                <i class="fas fa-map-marker-alt"
                    style="color: var(--primary); margin-<?= $align == 'right' ? 'left' : 'right' ?>: 10px;"></i>
                <span><?= htmlspecialchars($doctor['cabinet_name']) ?> -
                    <?= htmlspecialchars($doctor['commune']) ?></span>
            </div>

            <div style="margin-bottom: 35px; display: flex; gap: 12px; justify-content: inherit;">
                <?php if (!empty($doctor['facebook'])): ?>
                    <a href="<?= htmlspecialchars($doctor['facebook']) ?>" target="_blank" class="social-btn btn-fb"><i
                            class="fab fa-facebook-f"></i></a>
                <?php endif; ?>
                <?php if (!empty($doctor['instagram'])): ?>
                    <a href="<?= htmlspecialchars($doctor['instagram']) ?>" target="_blank" class="social-btn btn-insta"><i
                            class="fab fa-instagram"></i></a>
                <?php endif; ?>
            </div>

            <div class="desktop-actions mobile-hidden" style="display: flex; gap: 15px; flex-wrap: wrap;">
                <button id="heroBookBtn" class="btn-primary-custom"
                    style="padding: 14px 32px; font-size: 1rem; border-radius: 12px;">
                    <i class="far fa-calendar-check"
                        style="margin-<?= $align == 'right' ? 'left' : 'right' ?>: 8px;"></i> <?= __t('book_btn') ?>
                </button>
                <a href="#contact" class="btn-outline-custom"
                    style="padding: 14px 32px; font-size: 1rem; text-decoration: none;">
                    <i class="fas fa-map-pin" style="margin-<?= $align == 'right' ? 'left' : 'right' ?>: 8px;"></i>
                    <?= __t('coords') ?>
                </a>
            </div>
        </div>

        <div class="hero-img-box">
            <img src="<?= $doctor['image1'] ?>" alt="Dr. <?= htmlspecialchars($doctor['last_name']) ?>"
                class="hero-img">
            <?php if ($doctor['is_opened']): ?>
                <div class="status-badge" style="<?= $align == 'right' ? 'right: auto; left: -30px;' : 'right: -30px;' ?>">
                    <div
                        style="width: 12px; height: 12px; background: #10b981; border-radius: 50%; box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.2);">
                    </div>
                    <div>
                        <strong
                            style="color: var(--secondary); display: block; line-height: 1; font-size: 0.95rem;">Ouvert</strong>
                        <small style="color: var(--text-muted); font-size: 0.8rem;">Aujourd'hui</small>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>