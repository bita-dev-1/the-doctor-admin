<nav class="navbar">
    <div class="container nav-content">
        <div class="brand">
            <div
                style="width: 40px; height: 40px; background: #e0f2fe; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-user-md" style="font-size: 1.2rem;"></i>
            </div>
            <span>Dr. <?= htmlspecialchars($doctor['last_name']) ?></span>
        </div>
        <div style="display:flex; gap:12px; align-items: center;">
            <button id="myAppointmentsBtn" class="btn-outline-custom"
                style="padding: 10px 20px; font-size: 0.9rem; border-color: transparent; background: transparent;">
                <i class="fas fa-history"></i> <span style="display:none; @media(min-width:768px){display:inline;}">Mes
                    RDV</span>
            </button>
            <a href="#contact" class="btn-outline-custom mobile-hidden"
                style="padding: 10px 24px; text-decoration: none;">Contact</a>
            <button onclick="document.getElementById('openBookingModal').click()"
                class="btn-primary-custom mobile-hidden"
                style="padding: 10px 24px; font-size: 0.9rem; border-radius: 12px;">
                Prendre RDV
            </button>
        </div>
    </div>
</nav>