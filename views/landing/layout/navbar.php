<nav class="navbar">
    <div class="container nav-content">
        <!-- Brand / Logo -->
        <div class="brand" style="display: flex; align-items: center; gap: 10px;">
            <div
                style="width: 40px; height: 40px; background: #e0f2fe; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-user-md" style="font-size: 1.2rem; color: var(--primary);"></i>
            </div>
            <span style="font-weight: 700; font-size: 1.1rem;">Dr. <?= htmlspecialchars($doctor['last_name']) ?></span>
        </div>

        <!-- Actions -->
        <div style="display:flex; gap:12px; align-items: center;">
            <!-- Language Switcher -->
            <a href="?lang=<?= $lang_code == 'ar' ? 'fr' : 'ar' ?>" class="btn-outline-custom"
                style="padding: 8px 15px; font-size: 0.9rem; text-decoration: none; border-radius: 8px; display: flex; align-items: center; gap: 5px;">
                <i class="fas fa-globe"></i>
                <?= $lang_code == 'ar' ? 'Français' : 'العربية' ?>
            </a>

            <button id="myAppointmentsBtn" class="btn-outline-custom"
                style="padding: 10px 20px; font-size: 0.9rem; border-color: transparent; background: transparent;">
                <i class="fas fa-history"></i>
                <span style="display:none; @media(min-width:768px){display:inline;}"><?= __t('my_rdv') ?></span>
            </button>

            <a href="#contact" class="btn-outline-custom mobile-hidden"
                style="padding: 10px 24px; text-decoration: none;"><?= __t('contact') ?></a>

            <button onclick="document.getElementById('openBookingModal').click()"
                class="btn-primary-custom mobile-hidden"
                style="padding: 10px 24px; font-size: 0.9rem; border-radius: 12px;">
                <?= __t('book_btn') ?>
            </button>
        </div>
    </div>
</nav>