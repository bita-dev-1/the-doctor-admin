<section class="stats-section">
    <div class="stats-grid">
        <div class="stat-item">
            <h3><?= number_format($doctor['views']) ?></h3>
            <p><i class="far fa-eye stat-icon"></i> Vues</p>
        </div>
        <div class="stat-item recommend-btn" id="recommendBtn" style="cursor: pointer;">
            <h3 id="recommendCount"><?= number_format($doctor['recomondation']) ?></h3>
            <p><i class="fas fa-heart stat-icon" id="recommendIcon"></i> J'aime</p>
        </div>
        <div class="stat-item">
            <h3>100%</h3>
            <p><i class="fas fa-check-circle stat-icon"></i> Vérifié</p>
        </div>
        <div class="stat-item">
            <h3>24/7</h3>
            <p><i class="fas fa-laptop-medical stat-icon"></i> En Ligne</p>
        </div>
    </div>
</section>