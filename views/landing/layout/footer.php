<footer class="footer">
    <div class="container">
        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; margin-bottom: 40px;">
            <div>
                <div class="brand" style="color: white; margin-bottom: 20px;">
                    <i class="fas fa-user-md" style="color: var(--primary);"></i> Dr.
                    <?= htmlspecialchars($doctor['last_name']) ?>
                </div>
                <p style="opacity: 0.7; line-height: 1.8;"><?= __t('footer_desc') ?></p>
            </div>
            <div>
                <h4 style="color: white; margin-bottom: 20px;"><?= __t('useful_links') ?></h4>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 10px;"><a href="#"
                            onclick="document.getElementById('openBookingModal').click(); return false;"
                            style="color: rgba(255,255,255,0.7); text-decoration: none; transition: 0.3s;"><?= __t('book_btn') ?></a>
                    </li>
                    <li><a href="#contact"
                            style="color: rgba(255,255,255,0.7); text-decoration: none; transition: 0.3s;"><?= __t('coords') ?></a>
                    </li>
                </ul>
            </div>
        </div>

        <div
            style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 30px; text-align: center; color: rgba(255,255,255,0.5); font-size: 0.9rem;">
            <div style="display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 8px;">
                <span>&copy; <?= date('Y') ?> <?= __t('rights') ?></span>
                <span
                    style="display: inline-block; width: 4px; height: 4px; background: rgba(255,255,255,0.3); border-radius: 50%; margin: 0 5px;"></span>
                <span><?= __t('dev_by') ?> <a href="https://the-doctor.app/" target="_blank"
                        style="font-weight: 700; color: #ffffff; text-decoration: none;">Bita The-Doctor</a></span>
            </div>
        </div>
    </div>
</footer>

<script>
    const DOCTOR_ID = <?= $doctor['id'] ?>;
    const API_BASE = "<?= SITE_URI ?>api/v1/public";
    // Pass translations to JS
    const LANG_TEXT = {
        confirm_booking: "<?= __t('confirm_booking') ?>",
        processing: "<?= __t('processing') ?>",
        fill_required: "<?= __t('fill_required') ?>",
        connection_error: "<?= __t('connection_error') ?>",
        full: "<?= __t('full') ?>",
        no_slots: "<?= __t('no_slots') ?>",
        day_off: "<?= __t('day_off') ?>",
        no_config: "<?= __t('no_config') ?>",
        choose: "<?= __t('choose') ?>",
        loading: "<?= __t('loading') ?>",
        no_rdv_device: "<?= __t('no_rdv_device') ?>",
        status_fetch_error: "<?= __t('status_fetch_error') ?>",
        already_liked: "<?= __t('already_liked') ?>",
        pending: "<?= __t('pending') ?>",
        confirmed: "<?= __t('confirmed') ?>",
        completed: "<?= __t('completed') ?>",
        canceled: "<?= __t('canceled') ?>"
    };
</script>
<script src="<?= SITE_URI ?>assets/js/landing-page.js?v=<?= time() ?>"></script>