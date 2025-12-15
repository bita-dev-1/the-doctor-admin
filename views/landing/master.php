<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dr. <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?> -
        <?= htmlspecialchars($doctor['specialty_fr']) ?>
    </title>

    <meta name="description"
        content="Prenez rendez-vous avec Dr. <?= htmlspecialchars($doctor['last_name']) ?>, <?= htmlspecialchars($doctor['specialty_fr']) ?> à <?= htmlspecialchars($doctor['commune']) ?>.">
    <meta property="og:image" content="<?= $doctor['image1'] ?>">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
<link rel="shortcut icon" type="image/x-icon" href="<?= SITE_URI ?>assets/images/favicon.ico">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Base CSS (Optional if you want to keep external file) -->
    <!-- <link rel="stylesheet" href="<?= SITE_URI ?>assets/css/landing-page.css"> -->

    <!-- Combined Modern Styles -->
    <style>
        /* =========================================
           1. Variables & Reset
           ========================================= */
        :root {
            --primary: #0ea5e9;
            --primary-dark: #0284c7;
            --secondary: #0f172a;
            --text-main: #334155;
            --text-muted: #64748b;
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-color: 0 10px 25px -5px rgba(14, 165, 233, 0.25);
            --radius-lg: 20px;
            --radius-md: 12px;
            --container-width: 1200px;
            --font-main: "Plus Jakarta Sans", sans-serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: var(--font-main);
            background-color: var(--bg-body);
            color: var(--text-main);
            line-height: 1.6;
            overflow-x: hidden;
        }
        a { text-decoration: none; transition: 0.3s; }
        ul { list-style: none; }

        /* =========================================
           2. Utility Classes
           ========================================= */
        .container { max-width: var(--container-width); margin: 0 auto; padding: 0 20px; }

        .btn-primary-custom {
            background: var(--primary);
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary-custom:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-color);
        }

        .btn-outline-custom {
            background: transparent;
            color: var(--text-main);
            border: 1px solid #cbd5e1;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-outline-custom:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: #f0f9ff;
        }

        .social-btn {
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; transition: 0.3s;
            background: white; border: 1px solid #e2e8f0; color: var(--text-muted);
        }
        .social-btn:hover { transform: translateY(-3px); border-color: transparent; color: white; }
        .btn-fb:hover { background: #1877f2; box-shadow: 0 4px 10px rgba(24, 119, 242, 0.3); }
        .btn-insta:hover { background: #e1306c; box-shadow: 0 4px 10px rgba(225, 48, 108, 0.3); }

        /* =========================================
           3. Navbar
           ========================================= */
        .navbar {
            position: fixed; top: 0; left: 0; width: 100%;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            z-index: 1000; border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 15px 0; transition: 0.3s;
        }
        .nav-content { display: flex; justify-content: space-between; align-items: center; }
        .brand { font-size: 1.4rem; font-weight: 800; color: var(--secondary); display: flex; align-items: center; gap: 10px; }
        .brand i { color: var(--primary); }

        /* =========================================
           4. Hero Section
           ========================================= */
        .hero {
            padding-top: 120px; padding-bottom: 60px;
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
        }
        .hero-content { display: grid; grid-template-columns: 1.2fr 1fr; gap: 50px; align-items: center; }
        .specialty-badge {
            display: inline-flex; align-items: center;
            background: #e0f2fe; color: var(--primary-dark);
            padding: 8px 16px; border-radius: 30px;
            font-weight: 600; font-size: 0.9rem; margin-bottom: 20px;
        }
        .hero h1 {
            font-size: 3.5rem; line-height: 1.1; font-weight: 800;
            color: var(--secondary); margin-bottom: 20px; letter-spacing: -1px;
        }
        .location-text {
            font-size: 1.1rem; color: var(--text-muted);
            margin-bottom: 25px; display: flex; align-items: center; gap: 10px;
        }
        .hero-img-box {
            position: relative; border-radius: var(--radius-lg);
            padding: 10px; background: white;
            box-shadow: var(--shadow-lg); border: 1px solid #f1f5f9;
        }
        .hero-img { width: 100%; height: 450px; object-fit: cover; border-radius: 16px; }
        .status-badge {
            position: absolute; bottom: 30px; right: -20px;
            background: white; padding: 15px 25px;
            border-radius: 16px; box-shadow: var(--shadow-lg);
            display: flex; align-items: center; gap: 15px;
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }

        /* =========================================
           5. Stats
           ========================================= */
        .stats-section { margin-bottom: 60px; }
        .stats-grid {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;
            background: white; padding: 30px; border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md); border: 1px solid #f1f5f9;
        }
        .stat-item { text-align: center; padding: 10px; border-right: 1px solid #f1f5f9; }
        .stat-item:last-child { border-right: none; }
        .stat-item h3 { font-size: 1.8rem; font-weight: 800; color: var(--secondary); margin-bottom: 5px; }
        .stat-item p { color: var(--text-muted); font-size: 0.95rem; display: flex; justify-content: center; align-items: center; gap: 6px; }
        .stat-icon { color: var(--primary); }
        .recommend-btn:active { transform: scale(0.95); }
        .recommend-btn.liked .fa-heart { color: #ef4444; animation: heartBeat 0.5s; }
        @keyframes heartBeat { 0% { transform: scale(1); } 50% { transform: scale(1.3); } 100% { transform: scale(1); } }

        /* =========================================
           6. Main Content
           ========================================= */
        .content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        .card {
            background: var(--bg-card); border-radius: var(--radius-lg);
            padding: 30px; margin-bottom: 30px;
            box-shadow: var(--shadow-sm); border: 1px solid #f1f5f9; transition: 0.3s;
        }
        .card:hover { box-shadow: var(--shadow-md); }
        .card-title {
            font-size: 1.4rem; font-weight: 700; color: var(--secondary);
            margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9;
        }
        .booking-widget {
            background: linear-gradient(135deg, var(--secondary) 0%, #1e293b 100%);
            color: white; position: sticky; top: 100px; border: none; box-shadow: var(--shadow-lg);
        }
        .btn-book-white {
            width: 100%; background: white; color: var(--secondary);
            border: none; padding: 16px; border-radius: 12px;
            font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: 0.3s;
        }
        .btn-book-white:hover { background: var(--primary); color: white; box-shadow: 0 0 20px rgba(14, 165, 233, 0.4); }
        
        .contact-item { display: flex; align-items: center; gap: 20px; padding: 15px 0; border-bottom: 1px solid #f8fafc; }
        .contact-item:last-child { border-bottom: none; }
        .contact-icon {
            width: 50px; height: 50px; background: #f0f9ff; color: var(--primary);
            border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
        }
        .schedule-table tr { border-bottom: 1px solid #f1f5f9; }
        .schedule-table td { padding: 12px 0; font-size: 0.95rem; }
        .today-row td { color: var(--primary); }

        /* =========================================
           7. Footer
           ========================================= */
        .footer { background: var(--secondary); color: white; padding: 60px 0 30px; margin-top: auto; }
        .footer a { color: #cbd5e1; }
        .footer a:hover { color: white; }

        /* =========================================
           8. Mobile Sticky Bar
           ========================================= */
        .mobile-sticky-bar {
            display: none; position: fixed; bottom: 0; left: 0; width: 100%;
            background: white; padding: 15px 20px; box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.05);
            z-index: 999; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9;
        }

        /* =========================================
           9. Modals & Date Picker Styling
           ========================================= */
        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(5px);
            z-index: 2000; align-items: center; justify-content: center; padding: 20px;
        }
        .modal-content-box {
            background: white; width: 100%; max-width: 550px;
            border-radius: 24px; padding: 40px; position: relative;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: modalSlideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            max-height: 90vh; overflow-y: auto;
        }
        @keyframes modalSlideUp { from { transform: translateY(50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        /* Date Input Styling */
        .date-input-wrapper { position: relative; margin-bottom: 25px; }
        #booking-date {
            appearance: none; -webkit-appearance: none;
            background-color: #f8fafc; border: 2px solid #e2e8f0;
            border-radius: 16px; padding: 18px 20px;
            font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.1rem;
            font-weight: 600; color: var(--secondary); width: 100%;
            transition: all 0.3s ease; cursor: pointer;
        }
        #booking-date:hover { background-color: #f1f5f9; border-color: #cbd5e1; }
        #booking-date:focus { background-color: #ffffff; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.15); outline: none; }

        /* Slots Grid */
        #slots-container {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
            gap: 12px; margin-top: 25px; max-height: 300px; overflow-y: auto; padding: 5px;
        }
        #slots-container button, .slot-btn {
            background: white; border: 1px solid #e2e8f0; padding: 12px 8px;
            border-radius: 12px; color: var(--text-main); font-weight: 600;
            font-size: 0.95rem; cursor: pointer; transition: all 0.2s;
            display: flex; align-items: center; justify-content: center;
        }
        #slots-container button:hover, .slot-btn:hover {
            border-color: var(--primary); color: var(--primary);
            background: #f0f9ff; transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.15);
        }
        #slots-container button:disabled { opacity: 0.5; cursor: not-allowed; background: #f1f5f9; }

        /* Custom Scrollbar */
        #slots-container::-webkit-scrollbar { width: 6px; }
        #slots-container::-webkit-scrollbar-track { background: #f1f5f9; }
        #slots-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        /* =========================================
           10. Responsive
           ========================================= */
        @media (max-width: 992px) {
            .container { width: 100%; }
            .hero-content { grid-template-columns: 1fr; text-align: center; }
            .hero-img-box { display: none; }
            .content-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .stat-item { border: none; border-bottom: 1px solid #f1f5f9; padding: 20px; }
            .mobile-hidden { display: none !important; }
            .booking-widget { display: none; }
            .mobile-sticky-bar { display: flex; }
            .hero h1 { font-size: 2.5rem; }
            .location-text { justify-content: center; }
            .desktop-actions { justify-content: center; }
            body { padding-bottom: 80px; }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .hero h1 { font-size: 2rem; }
            .navbar { padding: 10px 0; }
            .brand span { font-size: 1.1rem; }
        }
    </style>
</head>

<body>

    <!-- Navbar (Fixed) -->
    <nav class="navbar">
        <div class="container nav-content">
            <div class="brand">
                <div style="width: 40px; height: 40px; background: #e0f2fe; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user-md" style="font-size: 1.2rem;"></i>
                </div>
                <span>Dr. <?= htmlspecialchars($doctor['last_name']) ?></span>
            </div>
            <div style="display:flex; gap:12px; align-items: center;">
                <button id="myAppointmentsBtn" class="btn-outline-custom" style="padding: 10px 20px; font-size: 0.9rem; border-color: transparent; background: transparent;">
                    <i class="fas fa-history"></i> <span style="display:none; @media(min-width:768px){display:inline;}">Mes RDV</span>
                </button>
                <a href="#contact" class="btn-outline-custom mobile-hidden" style="padding: 10px 24px; text-decoration: none;">Contact</a>
                <!-- Desktop Booking Button -->
                <button onclick="document.getElementById('openBookingModal').click()" class="btn-primary-custom mobile-hidden" style="padding: 10px 24px; font-size: 0.9rem; border-radius: 12px;">
                    Prendre RDV
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Content Wrapper -->
    <main>
        <header class="hero">
            <div class="container hero-content">
                <div class="hero-text">
                    <span class="specialty-badge">
                        <i class="fas fa-certificate" style="margin-right:8px;"></i>
                        <?= htmlspecialchars($doctor['specialty_fr']) ?>
                    </span>
                    <h1>Dr. <?= htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']) ?></h1>

                    <div class="location-text">
                        <i class="fas fa-map-marker-alt" style="color: var(--primary);"></i>
                        <span><?= htmlspecialchars($doctor['cabinet_name']) ?> - <?= htmlspecialchars($doctor['commune']) ?></span>
                    </div>

                    <div style="margin-bottom: 35px; display: flex; gap: 12px; justify-content: inherit;">
                        <?php if (!empty($doctor['facebook'])): ?>
                                        <a href="<?= htmlspecialchars($doctor['facebook']) ?>" target="_blank" class="social-btn btn-fb">
                                            <i class="fab fa-facebook-f"></i>
                                        </a>
                        <?php endif; ?>
                        <?php if (!empty($doctor['instagram'])): ?>
                                        <a href="<?= htmlspecialchars($doctor['instagram']) ?>" target="_blank" class="social-btn btn-insta">
                                            <i class="fab fa-instagram"></i>
                                        </a>
                        <?php endif; ?>
                    </div>

                    <div class="desktop-actions mobile-hidden" style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <button id="heroBookBtn" class="btn-primary-custom" style="padding: 14px 32px; font-size: 1rem; border-radius: 12px;">
                            <i class="far fa-calendar-check" style="margin-right: 8px;"></i> Prendre Rendez-vous
                        </button>
                        <a href="#contact" class="btn-outline-custom" style="padding: 14px 32px; font-size: 1rem; text-decoration: none;">
                            Voir Localisation
                        </a>
                    </div>
                </div>

                <div class="hero-img-box">
                    <img src="<?= $doctor['image1'] ?>" alt="Dr. <?= htmlspecialchars($doctor['last_name']) ?>" class="hero-img">
                    <?php if ($doctor['is_opened']): ?>
                                    <div class="status-badge">
                                        <div style="width: 12px; height: 12px; background: #10b981; border-radius: 50%; box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.2);"></div>
                                        <div>
                                            <strong style="color: var(--secondary); display: block; line-height: 1; font-size: 0.95rem;">Ouvert</strong>
                                            <small style="color: var(--text-muted); font-size: 0.8rem;">Aujourd'hui</small>
                                        </div>
                                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <div class="container">
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
        </div>

        <div class="container" style="margin-bottom: 80px;">
            <div class="content-grid">

                <div class="main-content">

                    <div class="card">
                        <h3 class="card-title">À propos du Docteur</h3>
                        <div class="about-text" style="color: var(--text-main); font-size: 1.05rem; white-space: pre-line; opacity: 0.9; line-height: 1.8;">
                            <?= htmlspecialchars($doctor['description'] ?? "Bienvenue au cabinet médical. Nous sommes dévoués à votre santé et votre bien-être.") ?>
                        </div>
                    </div>

                    <?php if (!empty($doctor['image2']) || !empty($doctor['image3'])): ?>
                                    <div class="card gallery-section">
                                        <h3 class="card-title">Galerie du Cabinet</h3>
                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
                                            <?php if (!empty($doctor['image2'])): ?>
                                                            <img src="<?= $doctor['image2'] ?>" style="width:100%; height:220px; object-fit:cover; border-radius:16px; cursor:pointer; box-shadow: var(--shadow-sm); transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform='scale(1)'" alt="Cabinet 1">
                                            <?php endif; ?>
                                            <?php if (!empty($doctor['image3'])): ?>
                                                            <img src="<?= $doctor['image3'] ?>" style="width:100%; height:220px; object-fit:cover; border-radius:16px; cursor:pointer; box-shadow: var(--shadow-sm); transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.03)'" onmouseout="this.style.transform='scale(1)'" alt="Cabinet 2">
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
                                    <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Téléphone</span><br>
                                    <a href="tel:<?= htmlspecialchars($doctor['phone']) ?>" style="font-size: 1.1rem; font-weight: 700; color: var(--secondary); text-decoration: none;"><?= htmlspecialchars($doctor['phone']) ?></a>
                                </div>
                            </div>

                            <?php if (!empty($doctor['cabinet_phone'])): ?>
                                            <div class="contact-item">
                                                <div class="contact-icon"><i class="fas fa-clinic-medical"></i></div>
                                                <div>
                                                    <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Fixe Cabinet</span><br>
                                                    <a href="tel:<?= htmlspecialchars($doctor['cabinet_phone']) ?>" style="color: var(--secondary); font-weight: 700; font-size: 1.1rem; text-decoration: none;"><?= htmlspecialchars($doctor['cabinet_phone']) ?></a>
                                                </div>
                                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="contact-item" style="margin-top: 25px; padding-top: 25px; border-top: 1px dashed #e2e8f0;">
                            <div class="contact-icon"><i class="fas fa-map-marked-alt"></i></div>
                            <div>
                                <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Adresse</span><br>
                                <strong style="color: var(--secondary); font-size: 1.05rem;"><?= htmlspecialchars($doctor['cabinet_address']) ?></strong>
                                <div style="font-size: 0.95rem; color: var(--text-muted); margin-top: 2px;">
                                    <?= htmlspecialchars($doctor['commune']) ?>, <?= htmlspecialchars($doctor['willaya']) ?>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($doctor['lat']) && !empty($doctor['lng'])): ?>
                                        <div style="margin-top: 30px;">
                                            <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $doctor['lat'] ?>,<?= $doctor['lng'] ?>" target="_blank" class="btn-outline-custom" style="width: 100%; justify-content: center; background: #f8fafc; display: flex; align-items: center; gap: 10px; padding: 14px;">
                                                <i class="fas fa-location-arrow"></i> Ouvrir dans Google Maps
                                            </a>
                                        </div>
                        <?php endif; ?>
                    </div>

                </div>

                <div class="sidebar">

                    <div class="card booking-widget">
                        <h3 style="color: white; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 20px; margin-bottom: 20px; font-size: 1.5rem;">Prendre RDV</h3>
                        <p style="color: rgba(255,255,255,0.8); margin-bottom: 25px; font-size: 1rem;">Réservez votre consultation en quelques clics.</p>
                        <button id="openBookingModal" class="btn-book-white">Réserver Maintenant</button>
                        <div style="margin-top: 20px; font-size: 0.85rem; color: rgba(255,255,255,0.5); display: flex; align-items: center; justify-content: center; gap: 6px;">
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
                                                    <?php if ($is_today): ?><i class="fas fa-clock" style="margin-left:5px; font-size:0.7rem; color: var(--primary);"></i><?php endif; ?>
                                                </td>
                                                <td class="hours" style="text-align: right;">
                                                    <?php if (!empty($hours['from']) && !empty($hours['to'])): ?>
                                                                    <span style="background: #f1f5f9; padding: 4px 10px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; color: var(--text-main);">
                                                                        <?= htmlspecialchars($hours['from']) ?> - <?= htmlspecialchars($hours['to']) ?>
                                                                    </span>
                                                    <?php else: ?>
                                                                    <span style="color: #ef4444; font-size: 0.85rem; font-weight: 600; background: #fef2f2; padding: 2px 8px; border-radius: 4px;">Fermé</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <div class="mobile-sticky-bar">
        <div>
            <span style="display:block; font-size:0.75rem; color:var(--text-muted);">Consultation avec Dr. <?= htmlspecialchars($doctor['last_name']) ?></span>
            <strong style="color:var(--primary); font-size: 0.9rem;">Disponible</strong>
        </div>
        <button onclick="document.getElementById('openBookingModal').click()" style="background:var(--secondary); color:white; border:none; padding:12px 28px; border-radius:50px; font-weight:700; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
            Prendre RDV
        </button>
    </div>

    <footer class="footer">
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; margin-bottom: 40px;">
                <div>
                    <div class="brand" style="color: white; margin-bottom: 20px;">
                        <i class="fas fa-user-md" style="color: var(--primary);"></i> Dr. <?= htmlspecialchars($doctor['last_name']) ?>
                    </div>
                    <p style="opacity: 0.7; line-height: 1.8;">Simplifiez votre parcours de santé. Prenez rendez-vous en ligne rapidement et facilement.</p>
                </div>
                <div>
                    <h4 style="color: white; margin-bottom: 20px;">Liens Utiles</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 10px;"><a href="#" onclick="document.getElementById('openBookingModal').click(); return false;" style="color: rgba(255,255,255,0.7); text-decoration: none; transition: 0.3s;">Prendre Rendez-vous</a></li>
                        <li><a href="#contact" style="color: rgba(255,255,255,0.7); text-decoration: none; transition: 0.3s;">Contact & Localisation</a></li>
                    </ul>
                </div>
            </div>

            <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 30px; text-align: center; color: rgba(255,255,255,0.5); font-size: 0.9rem;">
                <div style="display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 8px;">
                    <span>&copy; <?= date('Y') ?> Tous droits réservés.</span>
                    <span style="display: inline-block; width: 4px; height: 4px; background: rgba(255,255,255,0.3); border-radius: 50%; margin: 0 5px;"></span>
                    <span>
                        Une solution développée par
                        <a href="https://the-doctor.app/" target="_blank" rel="noopener noreferrer" style="font-weight: 700; color: #ffffff; text-decoration: none; transition: all 0.3s ease; border-bottom: 1px dotted rgba(255,255,255,0.3); padding-bottom: 1px;" onmouseover="this.style.color='var(--primary)'; this.style.borderBottomColor='var(--primary)'" onmouseout="this.style.color='#ffffff'; this.style.borderBottomColor='rgba(255,255,255,0.3)'">
                            Bita The-Doctor
                        </a>
                    </span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Booking Modal -->
    <div id="bookingModalOverlay" class="modal-overlay">
        <div class="modal-content-box">
            <button id="closeModalBtn" style="position:absolute; right:20px; top:20px; border:none; background:none; font-size:1.8rem; cursor:pointer; color: #cbd5e1; transition:0.3s;">&times;</button>
            <style>
                #closeModalBtn:hover { color: var(--secondary); }
                input:focus { outline: 2px solid var(--primary); border-color: transparent; }
            </style>

            <!-- Step 1: Date Selection (Improved) -->
            <div id="step-1">
                <div style="text-align: center; margin-bottom: 35px;">
                    <div style="width: 60px; height: 60px; background: #e0f2fe; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: var(--primary);">
                        <i class="far fa-calendar-alt" style="font-size: 1.8rem;"></i>
                    </div>
                    <h3 style="color:var(--secondary); margin-bottom:8px; font-weight: 800; font-size: 1.5rem;">
                        Choisir une date
                    </h3>
                    <p style="color: var(--text-muted); font-size: 0.95rem; max-width: 80%; margin: 0 auto;">
                        Sélectionnez une date pour voir les créneaux disponibles.
                    </p>
                </div>

                <div class="date-input-wrapper">
                    <label style="display: block; margin-bottom: 10px; font-weight: 700; color: var(--text-main); font-size: 0.9rem; margin-left: 5px;">
                        Date du rendez-vous
                    </label>
                    <div style="position: relative;">
                        <input type="date" id="booking-date" required min="<?= date('Y-m-d') ?>">
                        <i class="fas fa-chevron-down" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; font-size: 0.9rem;"></i>
                    </div>
                </div>

                <div id="loading-slots" style="display:none; text-align:center; margin:40px 0;">
                    <div style="display: inline-block; padding: 15px 25px; background: #f8fafc; border-radius: 50px; border: 1px solid #e2e8f0;">
                        <i class="fas fa-circle-notch fa-spin" style="color:var(--primary); margin-right: 10px;"></i>
                        <span style="color: var(--text-muted); font-weight: 600; font-size: 0.9rem;">Recherche des disponibilités...</span>
                    </div>
                </div>

                <div id="slots-container"></div>
                <div id="availability-msg" style="text-align:center; margin-top:25px; padding: 15px; border-radius: 12px; font-size: 0.95rem;"></div>
            </div>

            <!-- Step 2: Patient Info -->
            <div id="step-2" style="display:none;">
                <h3 style="text-align:center; color:var(--secondary); margin-bottom:20px; font-weight: 800;">Confirmez vos infos</h3>

                <div style="display: flex; align-items: center; background: #f0f9ff; padding: 18px; border-radius: 16px; border: 1px dashed var(--primary); margin-bottom: 30px;">
                    <div style="font-size: 2rem; font-weight: 800; color: var(--primary); margin-right: 20px; padding-right: 20px; border-right: 2px solid rgba(14, 165, 233, 0.2); line-height: 1;" id="selected-ticket-display"></div>
                    <div>
                        <small style="color: var(--text-muted); display: block; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; font-weight: 600;">Date du rendez-vous</small>
                        <strong id="selected-date-display" style="color: var(--secondary); font-size: 1.1rem;"></strong>
                    </div>
                </div>

                <form id="booking-form">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div style="margin-bottom:15px;">
                            <label style="display:block; margin-bottom:8px; font-weight:600; font-size: 0.9rem; color: var(--text-main);">Prénom *</label>
                            <input type="text" id="p_firstname" required style="width:100%; padding:14px; border-radius:12px; border: 1px solid #e2e8f0;">
                        </div>
                        <div style="margin-bottom:15px;">
                            <label style="display:block; margin-bottom:8px; font-weight:600; font-size: 0.9rem; color: var(--text-main);">Nom</label>
                            <input type="text" id="p_lastname" style="width:100%; padding:14px; border-radius:12px; border: 1px solid #e2e8f0;">
                        </div>
                    </div>
                    <div style="margin-bottom:25px;">
                        <label style="display:block; margin-bottom:8px; font-weight:600; font-size: 0.9rem; color: var(--text-main);">Téléphone *</label>
                        <input type="tel" id="p_phone" required placeholder="05/06/07..." style="width:100%; padding:14px; border-radius:12px; border: 1px solid #e2e8f0;">
                    </div>

                    <div id="booking-error" style="color: #ef4444; background: #fef2f2; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; display:none; font-size: 0.9rem; border: 1px solid #fee2e2;"></div>

                    <div style="display:flex; gap:15px;">
                        <button type="button" id="back-step-1" style="flex:1; padding:16px; border:none; border-radius:50px; background:#f1f5f9; color: var(--text-muted); font-weight: 700; cursor:pointer; font-size: 1rem; transition: 0.3s;">Retour</button>
                        <button type="submit" id="confirm-booking" style="flex:2; padding:16px; border:none; border-radius:50px; background:var(--primary); color:white; font-weight: 700; cursor:pointer; box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3); font-size: 1rem; transition: 0.3s;">Confirmer le RDV</button>
                    </div>
                </form>
            </div>

            <!-- Step 3: Success -->
            <div id="step-3" style="display:none; text-align:center; padding:10px;">
                <div style="width: 90px; height: 90px; background: #ecfdf5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px;">
                    <i class="fas fa-check" style="color:#10b981; font-size: 3rem;"></i>
                </div>
                <h2 style="margin-bottom:10px; color: var(--secondary); font-weight: 800;">Réservation Confirmée !</h2>
                <p style="color: var(--text-muted); font-size: 1.05rem;">Votre rendez-vous a été enregistré avec succès.</p>

                <div style="background:var(--bg-body); padding:25px; border-radius:20px; margin:35px 0; border: 2px dashed #cbd5e1; position: relative; overflow: hidden;">
                    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 6px; background: repeating-linear-gradient(45deg, var(--primary), var(--primary) 10px, white 10px, white 20px);"></div>
                    <small style="text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-muted); font-size: 0.8rem; font-weight: 700;">Numéro de Ticket</small>
                    <p style="margin:10px 0 0; font-size:4rem; font-weight: 800; color:var(--secondary); line-height: 1;">
                        <strong id="final-ticket"></strong>
                    </p>
                </div>
                <button onclick="location.reload()" style="background:var(--secondary); color:white; border:none; padding:16px 50px; border-radius:50px; cursor:pointer; font-weight:700; font-size: 1.1rem; box-shadow: 0 10px 20px rgba(15, 23, 42, 0.15);">Terminer</button>
            </div>
        </div>
    </div>

    <div id="myAppointmentsModal" class="modal-overlay">
        <div class="modal-content-box" style="max-width: 600px;">
            <button id="closeMyAppointments" style="position:absolute; right:20px; top:20px; border:none; background:none; font-size:1.5rem; cursor:pointer; color: #999;">&times;</button>
            <h3 style="text-align:center; color:var(--secondary); margin-bottom:25px; font-weight: 800;">Mes Rendez-vous</h3>
            <div id="appointments-list" style="max-height: 400px; overflow-y: auto; padding-right: 5px;"></div>
            <div style="text-align:center; margin-top:25px;">
                <button onclick="document.getElementById('myAppointmentsModal').style.display='none'" style="background:#f1f5f9; color:var(--secondary); border:none; padding:12px 30px; border-radius:50px; cursor:pointer; font-weight: 600;">Fermer</button>
            </div>
        </div>
    </div>

    <script>
        const DOCTOR_ID = <?= $doctor['id'] ?>;
        const API_BASE = "<?= SITE_URI ?>api/v1/public";
    </script>
    
    <!-- Main Logic Script -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // ============================================================
            // 1. VARIABLES & ELEMENTS
            // ============================================================

            // Booking Modal Elements
            const modal = document.getElementById("bookingModalOverlay");
            const openBtns = document.querySelectorAll("#navBookBtn, #heroBookBtn, #footerBookBtn, #openBookingModal");
            const closeBtn = document.getElementById("closeModalBtn");

            // My Appointments Elements
            const myAppModal = document.getElementById("myAppointmentsModal");
            const myAppBtn = document.getElementById("myAppointmentsBtn");
            const closeMyAppBtn = document.getElementById("closeMyAppointments");
            const appListContainer = document.getElementById("appointments-list");

            // Form & Steps Elements
            const dateInput = document.getElementById("booking-date");
            const slotsContainer = document.getElementById("slots-container");
            const loadingSlots = document.getElementById("loading-slots");
            const msgBox = document.getElementById("availability-msg");

            const step1 = document.getElementById("step-1");
            const step2 = document.getElementById("step-2");
            const step3 = document.getElementById("step-3");

            const backBtn = document.getElementById("back-step-1");
            const bookingForm = document.getElementById("booking-form");
            const errorMsg = document.getElementById("booking-error");
            const confirmBtn = document.getElementById("confirm-booking");

            // Recommendation Elements
            const recBtn = document.getElementById("recommendBtn");
            const recCount = document.getElementById("recommendCount");

            // State Variables
            let selectedTicket = null;
            let selectedDate = null;

            // ============================================================
            // 2. HELPER FUNCTIONS
            // ============================================================

            // Get Today's Date in YYYY-MM-DD format (Local Time)
            const getTodayDate = () => {
                const date = new Date();
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, "0");
                const day = String(date.getDate()).padStart(2, "0");
                return `${year}-${month}-${day}`;
            };

            // LocalStorage: Save Appointment
            const saveAppointment = (data) => {
                let appointments = JSON.parse(localStorage.getItem("my_appointments") || "[]");
                appointments.push(data);
                localStorage.setItem("my_appointments", JSON.stringify(appointments));
            };

            // LocalStorage: Get Appointments
            const getAppointments = () => {
                return JSON.parse(localStorage.getItem("my_appointments") || "[]");
            };

            // UI: Show Error Message
            function showError(msg) {
                if (errorMsg) {
                    errorMsg.textContent = msg;
                    errorMsg.style.display = "block";
                }
            }

            // UI: Reset Form State
            function resetForm() {
                if (step1) step1.style.display = "block";
                if (step2) step2.style.display = "none";
                if (step3) step3.style.display = "none";
                if (bookingForm) bookingForm.reset();
                if (errorMsg) errorMsg.style.display = "none";
                if (slotsContainer) slotsContainer.innerHTML = "";
                if (msgBox) msgBox.innerHTML = "";
            }

            // ============================================================
            // 3. BOOKING LOGIC
            // ============================================================

            // Open Booking Modal
            const openModalHandler = (e) => {
                if (e) e.preventDefault();
                if (modal) {
                    modal.style.display = "flex";
                    // Set default date if empty
                    if (!dateInput.value) {
                        dateInput.value = getTodayDate();
                    }
                    // Fetch slots immediately
                    fetchAvailability(dateInput.value);
                }
            };

            // Attach listeners to all booking buttons
            openBtns.forEach((btn) => {
                if (btn) btn.addEventListener("click", openModalHandler);
            });

            // Close Booking Modal
            if (closeBtn) {
                closeBtn.addEventListener("click", () => {
                    modal.style.display = "none";
                    resetForm();
                });
            }

            // Handle Date Change
            if (dateInput) {
                dateInput.addEventListener("change", (e) => {
                    fetchAvailability(e.target.value);
                });
            }

            // Fetch Availability from API
            function fetchAvailability(date) {
                if (!date) return;

                selectedDate = date;
                slotsContainer.innerHTML = "";
                msgBox.innerHTML = "";
                loadingSlots.style.display = "block";

                fetch(`${API_BASE}/availability?doctor_id=${DOCTOR_ID}&date=${date}`)
                    .then((response) => response.json())
                    .then((data) => {
                        loadingSlots.style.display = "none";

                        if (data.state === "true" && data.available === true) {
                            if (data.slots && data.slots.length > 0) {
                                renderSlots(data.slots);
                            } else {
                                msgBox.innerHTML = '<span style="color:#ea5455; font-weight:bold;">Complet (Aucun ticket disponible)</span>';
                            }
                        } else {
                            let reason = "Non disponible";
                            if (data.reason === "Day off") reason = "Jour de repos (Fermé)";
                            if (data.reason === "No tickets configured") reason = "Planning non configuré";

                            msgBox.innerHTML = `<span style="color:#ea5455; font-weight:bold;">${reason}</span>`;
                        }
                    })
                    .catch((err) => {
                        loadingSlots.style.display = "none";
                        msgBox.innerHTML = '<span style="color:red">Erreur de connexion.</span>';
                        console.error(err);
                    });
            }

            // Render Slot Buttons
            function renderSlots(slots) {
                slots.forEach((ticket) => {
                    const btn = document.createElement("button");
                    btn.className = "slot-btn";
                    btn.textContent = ticket;
                    btn.type = "button";
                    btn.onclick = () => goToStep2(ticket);
                    slotsContainer.appendChild(btn);
                });
            }

            // Go to Patient Info Step
            function goToStep2(ticket) {
                selectedTicket = ticket;
                document.getElementById("selected-ticket-display").textContent = ticket;
                document.getElementById("selected-date-display").textContent = selectedDate;

                step1.style.display = "none";
                step2.style.display = "block";
            }

            // Back Button
            if (backBtn) {
                backBtn.addEventListener("click", () => {
                    step2.style.display = "none";
                    step1.style.display = "block";
                    errorMsg.style.display = "none";
                });
            }

            // Submit Booking Form
            if (bookingForm) {
                bookingForm.addEventListener("submit", function (e) {
                    e.preventDefault();

                    const firstName = document.getElementById("p_firstname").value;
                    const lastName = document.getElementById("p_lastname").value;
                    const phone = document.getElementById("p_phone").value;

                    if (!firstName || !phone) {
                        showError("Veuillez remplir les champs obligatoires.");
                        return;
                    }

                    confirmBtn.disabled = true;
                    confirmBtn.textContent = "Traitement...";

                    const payload = {
                        doctor_id: DOCTOR_ID,
                        date: selectedDate,
                        ticket_number: selectedTicket,
                        first_name: firstName,
                        last_name: lastName,
                        phone: phone,
                    };

                    fetch(`${API_BASE}/book`, {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify(payload),
                    })
                        .then((response) => response.json())
                        .then((data) => {
                            confirmBtn.disabled = false;
                            confirmBtn.textContent = "Confirmer le RDV";

                            if (data.state === "true") {
                                // Save to LocalStorage
                                const docName = document.title.split("-")[0].trim();
                                saveAppointment({
                                    doctor_name: docName,
                                    date: selectedDate,
                                    ticket: data.ticket_number,
                                    rdv_id: data.rdv_id,
                                    created_at: new Date().toISOString(),
                                });

                                // Show Success Step
                                document.getElementById("final-ticket").textContent = data.ticket_number;
                                step2.style.display = "none";
                                step3.style.display = "block";
                            } else {
                                showError(data.message || "Erreur lors de la réservation.");
                            }
                        })
                        .catch((err) => {
                            confirmBtn.disabled = false;
                            confirmBtn.textContent = "Confirmer le RDV";
                            showError("Erreur de connexion au serveur.");
                        });
                });
            }

            // ============================================================
            // 4. MY APPOINTMENTS LOGIC
            // ============================================================

            if (myAppBtn) {
                myAppBtn.addEventListener("click", () => {
                    renderAppointmentsList();
                    if (myAppModal) myAppModal.style.display = "flex";
                });
            }

            if (closeMyAppBtn) {
                closeMyAppBtn.addEventListener("click", () => {
                    if (myAppModal) myAppModal.style.display = "none";
                });
            }

            function renderAppointmentsList() {
                const apps = getAppointments();
                appListContainer.innerHTML = "";

                if (apps.length === 0) {
                    appListContainer.innerHTML = '<p style="text-align:center; color:#777;">Aucun rendez-vous enregistré sur cet appareil.</p>';
                    return;
                }

                // Show loading state
                appListContainer.innerHTML = '<div style="text-align:center; padding:20px;"><i class="fas fa-spinner fa-spin fa-2x" style="color:var(--primary)"></i></div>';

                // Extract IDs to fetch fresh data
                const rdvIds = apps.map((a) => a.rdv_id);

                fetch(`${API_BASE}/my-appointments`, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ ids: rdvIds }),
                })
                    .then((response) => response.json())
                    .then((res) => {
                        appListContainer.innerHTML = ""; // Clear loader

                        if (res.state === "true" && res.data.length > 0) {
                            // Sort by date (newest first)
                            res.data.sort((a, b) => new Date(b.date) - new Date(a.date));

                            res.data.forEach((app) => {
                                // Determine Status Style
                                let statusBadge = "";
                                switch (parseInt(app.state)) {
                                    case 0: // Created
                                        statusBadge = '<span style="background:#fff3cd; color:#856404; padding:4px 10px; border-radius:12px; font-size:0.8rem; font-weight:bold;">En attente</span>';
                                        break;
                                    case 1: // Accepted
                                        statusBadge = '<span style="background:#d1e7dd; color:#0f5132; padding:4px 10px; border-radius:12px; font-size:0.8rem; font-weight:bold;">Confirmé</span>';
                                        break;
                                    case 2: // Completed
                                        statusBadge = '<span style="background:#cff4fc; color:#055160; padding:4px 10px; border-radius:12px; font-size:0.8rem; font-weight:bold;">Terminé</span>';
                                        break;
                                    case 3: // Canceled
                                        statusBadge = '<span style="background:#f8d7da; color:#842029; padding:4px 10px; border-radius:12px; font-size:0.8rem; font-weight:bold;">Annulé</span>';
                                        break;
                                }

                                const item = document.createElement("div");
                                item.style.cssText = "background:#fff; border:1px solid #eee; border-left:4px solid var(--primary); padding:15px; margin-bottom:10px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.05);";

                                item.innerHTML = `
                                    <div style="display:flex; justify-content:space-between; align-items:center;">
                                        <div>
                                            <h4 style="margin:0 0 5px 0; color:#333; font-size:1rem;">Dr. ${app.doctor_firstname} ${app.doctor_name}</h4>
                                            <p style="margin:0; color:#666; font-size:0.9rem;">
                                                <i class="far fa-calendar-alt"></i> ${app.date} 
                                                <span style="margin:0 8px; color:#ddd;">|</span> 
                                                <i class="fas fa-ticket-alt"></i> Ticket: <strong>${app.rdv_num}</strong>
                                            </p>
                                        </div>
                                        <div style="text-align:right;">
                                            ${statusBadge}
                                        </div>
                                    </div>
                                `;
                                appListContainer.appendChild(item);
                            });
                        } else {
                            appListContainer.innerHTML = '<p style="text-align:center; color:#ea5455;">Impossible de récupérer les statuts.</p>';
                        }
                    })
                    .catch((err) => {
                        console.error(err);
                        appListContainer.innerHTML = '<p style="text-align:center; color:#ea5455;">Erreur de connexion.</p>';
                    });
            }

            // ============================================================
            // 5. RECOMMENDATION LOGIC
            // ============================================================

            if (recBtn) {
                const storageKey = "rec_doctor_" + DOCTOR_ID;

                // Check if already recommended
                if (localStorage.getItem(storageKey)) {
                    recBtn.classList.add("liked");
                    recBtn.style.cursor = "default";
                } else {
                    // Attach click event only if not liked yet
                    recBtn.addEventListener("click", function () {
                        // Optimistic UI Update
                        let currentCount = parseInt(recCount.innerText.replace(/,/g, ""));
                        recCount.innerText = (currentCount + 1).toLocaleString();
                        recBtn.classList.add("liked");

                        // Send to API
                        fetch(`${API_BASE}/recommend`, {
                            method: "POST",
                            headers: { "Content-Type": "application/json" },
                            body: JSON.stringify({ doctor_id: DOCTOR_ID }),
                        })
                            .then((response) => response.json())
                            .then((data) => {
                                if (data.state === "true") {
                                    localStorage.setItem(storageKey, "true");
                                    recCount.innerText = parseInt(data.new_count).toLocaleString();
                                    recBtn.style.cursor = "default";
                                    // Remove listener to prevent multiple clicks
                                    recBtn.replaceWith(recBtn.cloneNode(true));
                                } else {
                                    // Revert on error
                                    recCount.innerText = currentCount.toLocaleString();
                                    recBtn.classList.remove("liked");
                                }
                            })
                            .catch((err) => {
                                recCount.innerText = currentCount.toLocaleString();
                                recBtn.classList.remove("liked");
                            });
                    });
                }
            }
        });
    </script>

</body>
</html>