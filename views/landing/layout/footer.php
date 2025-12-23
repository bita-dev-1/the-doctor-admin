<footer class="footer">
    <div class="container">
        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; margin-bottom: 40px;">
            <div>
                <div class="brand" style="color: white; margin-bottom: 20px;">
                    <i class="fas fa-user-md" style="color: var(--primary);"></i> Dr.
                    <?= htmlspecialchars($doctor['last_name']) ?>
                </div>
                <p style="opacity: 0.7; line-height: 1.8;">Simplifiez votre parcours de santé. Prenez rendez-vous en
                    ligne rapidement et facilement.</p>
            </div>
            <div>
                <h4 style="color: white; margin-bottom: 20px;">Liens Utiles</h4>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 10px;"><a href="#"
                            onclick="document.getElementById('openBookingModal').click(); return false;"
                            style="color: rgba(255,255,255,0.7); text-decoration: none; transition: 0.3s;">Prendre
                            Rendez-vous</a></li>
                    <li><a href="#contact"
                            style="color: rgba(255,255,255,0.7); text-decoration: none; transition: 0.3s;">Contact &
                            Localisation</a></li>
                </ul>
            </div>
        </div>

        <div
            style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 30px; text-align: center; color: rgba(255,255,255,0.5); font-size: 0.9rem;">
            <div style="display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 8px;">
                <span>&copy; <?= date('Y') ?> Tous droits réservés.</span>
                <span
                    style="display: inline-block; width: 4px; height: 4px; background: rgba(255,255,255,0.3); border-radius: 50%; margin: 0 5px;"></span>
                <span>Une solution développée par <a href="https://the-doctor.app/" target="_blank"
                        style="font-weight: 700; color: #ffffff; text-decoration: none;">Bita The-Doctor</a></span>
            </div>
        </div>
    </div>
</footer>

<script>
    const DOCTOR_ID = <?= $doctor['id'] ?>;
    const API_BASE = "<?= SITE_URI ?>api/v1/public";
</script>
<script src="<?= SITE_URI ?>assets/js/landing-page.js?v=<?= time() ?>"></script>