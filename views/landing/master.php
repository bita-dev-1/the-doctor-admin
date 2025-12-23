<?php
// views/landing/master.php

// 1. Head & Styles
include __DIR__ . '/layout/head.php';
?>

<body>

    <!-- 2. Navbar -->
    <?php include __DIR__ . '/layout/navbar.php'; ?>

    <main>
        <!-- 3. Hero Section -->
        <?php include __DIR__ . '/partials/hero.php'; ?>

        <div class="container">
            <!-- 4. Stats Section -->
            <?php include __DIR__ . '/partials/stats.php'; ?>
        </div>

        <div class="container" style="margin-bottom: 80px;">
            <!-- 5. Main Content & Sidebar -->
            <?php include __DIR__ . '/partials/content.php'; ?>
        </div>
    </main>

    <!-- Mobile Sticky Bar -->
    <div class="mobile-sticky-bar">
        <div>
            <span style="display:block; font-size:0.75rem; color:var(--text-muted);">Consultation avec Dr. <?= htmlspecialchars($doctor['last_name']) ?></span>
            <strong style="color:var(--primary); font-size: 0.9rem;">Disponible</strong>
        </div>
        <button onclick="document.getElementById('openBookingModal').click()" style="background:var(--secondary); color:white; border:none; padding:12px 28px; border-radius:50px; font-weight:700; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
            Prendre RDV
        </button>
    </div>

    <!-- 6. Footer & Scripts -->
    <?php include __DIR__ . '/layout/footer.php'; ?>

    <!-- 7. Modals -->
    <?php include __DIR__ . '/partials/modals.php'; ?>

</body>
</html>