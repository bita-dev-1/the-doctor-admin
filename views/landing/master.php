<?php
// views/landing/master.php

// 1. Language Logic (Arabic Default)
$lang_code = isset($_GET['lang']) && $_GET['lang'] === 'fr' ? 'fr' : 'ar';
$dir = $lang_code === 'ar' ? 'rtl' : 'ltr';
$align = $lang_code === 'ar' ? 'right' : 'left';

// Translations Array
$t = [
    'ar' => [
        'book_btn' => 'احجز موعد',
        'my_rdv' => 'مواعيدي',
        'contact' => 'اتصل بنا',
        'about' => 'حول الطبيب',
        'gallery' => 'معرض الصور',
        'coords' => 'العنوان والاتصال',
        'phone' => 'الهاتف',
        'fix_phone' => 'هاتف العيادة',
        'address' => 'العنوان',
        'open_maps' => 'فتح في خرائط جوجل',
        'hours' => 'ساعات العمل',
        'closed' => 'مغلق',
        'available' => 'متاح',
        'views' => 'مشاهدات',
        'likes' => 'إعجاب',
        'verified' => 'موثق',
        'online' => 'أونلاين',
        'footer_desc' => 'سهّل رحلتك العلاجية. احجز موعدك عبر الإنترنت بسرعة وسهولة.',
        'useful_links' => 'روابط مفيدة',
        'rights' => 'جميع الحقوق محفوظة.',
        'dev_by' => 'تم التطوير بواسطة',
        // Modal
        'choose_date' => 'اختر تاريخاً',
        'select_date_msg' => 'حدد تاريخاً لحجز موعدك.',
        'date_label' => 'تاريخ الموعد',
        'checking' => 'جاري التحقق...',
        'confirm_booking' => 'تأكيد الحجز',
        'full' => 'مكتمل',
        'no_slots' => 'لا توجد مواعيد متاحة في هذا التاريخ.',
        'confirm_info' => 'تأكيد المعلومات',
        'firstname' => 'الاسم',
        'lastname' => 'اللقب',
        'email' => 'البريد الإلكتروني',
        'wilaya' => 'الولاية',
        'commune' => 'البلدية',
        'motif' => 'سبب الزيارة',
        'motif_default' => 'آخر / غير محدد',
        'desc' => 'وصف الحالة (أعراض، ملاحظات...)',
        'back' => 'رجوع',
        'confirm' => 'تأكيد',
        'success_title' => 'تم الحجز بنجاح!',
        'success_msg' => 'تم تسجيل موعدك بنجاح.',
        'your_number' => 'رقمك هو',
        'finish' => 'إنهاء',
        'my_rdv_title' => 'مواعيدي',
        'close' => 'إغلاق',
        'loading' => 'جاري التحميل...',
        'error' => 'خطأ',
        'choose' => 'اختر...',
        'processing' => 'جاري المعالجة...',
        'fill_required' => 'يرجى ملء الحقول الإجبارية.',
        'connection_error' => 'خطأ في الاتصال.',
        'day_off' => 'يوم عطلة',
        'no_config' => 'الجدول غير مهيأ',
        'pending' => 'قيد الانتظار',
        'confirmed' => 'مؤكد',
        'completed' => 'مكتمل',
        'canceled' => 'ملغى',
        'no_rdv_device' => 'لا توجد مواعيد مسجلة على هذا الجهاز.',
        'status_fetch_error' => 'تعذر جلب الحالة.',
        'already_liked' => 'تم الإعجاب مسبقاً'
    ],
    'fr' => [
        'book_btn' => 'Prendre RDV',
        'my_rdv' => 'Mes RDV',
        'contact' => 'Contact',
        'about' => 'À propos du Docteur',
        'gallery' => 'Galerie du Cabinet',
        'coords' => 'Coordonnées & Accès',
        'phone' => 'Téléphone',
        'fix_phone' => 'Fixe Cabinet',
        'address' => 'Adresse',
        'open_maps' => 'Ouvrir dans Google Maps',
        'hours' => 'Horaires',
        'closed' => 'Fermé',
        'available' => 'Disponible',
        'views' => 'Vues',
        'likes' => 'J\'aime',
        'verified' => 'Vérifié',
        'online' => 'En Ligne',
        'footer_desc' => 'Simplifiez votre parcours de santé. Prenez rendez-vous en ligne rapidement et facilement.',
        'useful_links' => 'Liens Utiles',
        'rights' => 'Tous droits réservés.',
        'dev_by' => 'Une solution développée par',
        // Modal
        'choose_date' => 'Choisir une date',
        'select_date_msg' => 'Sélectionnez une date pour réserver.',
        'date_label' => 'Date du rendez-vous',
        'checking' => 'Vérification...',
        'confirm_booking' => 'Confirmer la réservation',
        'full' => 'Complet',
        'no_slots' => 'Aucun rendez-vous disponible pour cette date.',
        'confirm_info' => 'Confirmez vos infos',
        'firstname' => 'Prénom',
        'lastname' => 'Nom',
        'email' => 'Email',
        'wilaya' => 'Wilaya',
        'commune' => 'Commune',
        'motif' => 'Motif de consultation',
        'motif_default' => 'Autre / Non spécifié',
        'desc' => 'Description (Symptômes, notes...)',
        'back' => 'Retour',
        'confirm' => 'Confirmer',
        'success_title' => 'Réservation Confirmée !',
        'success_msg' => 'Votre rendez-vous a été enregistré avec succès.',
        'your_number' => 'Votre Numéro',
        'finish' => 'Terminer',
        'my_rdv_title' => 'Mes Rendez-vous',
        'close' => 'Fermer',
        'loading' => 'Chargement...',
        'error' => 'Erreur',
        'choose' => 'Choisir...',
        'processing' => 'Traitement...',
        'fill_required' => 'Veuillez remplir les champs obligatoires.',
        'connection_error' => 'Erreur de connexion.',
        'day_off' => 'Jour de repos (Fermé)',
        'no_config' => 'Planning non configuré',
        'pending' => 'En attente',
        'confirmed' => 'Confirmé',
        'completed' => 'Terminé',
        'canceled' => 'Annulé',
        'no_rdv_device' => 'Aucun rendez-vous enregistré sur cet appareil.',
        'status_fetch_error' => 'Impossible de récupérer les statuts.',
        'already_liked' => 'Déjà recommandé'
    ]
];

// Helper function for translation
function __t($key)
{
    global $t, $lang_code;
    return $t[$lang_code][$key] ?? $key;
}

// 2. Head & Styles
include __DIR__ . '/layout/head.php';
?>

<body dir="<?= $dir ?>" style="text-align: <?= $align ?>;">

    <!-- 3. Navbar -->
    <?php include __DIR__ . '/layout/navbar.php'; ?>

    <main>
        <!-- 4. Hero Section -->
        <?php include __DIR__ . '/partials/hero.php'; ?>

        <div class="container">
            <!-- 5. Stats Section -->
            <?php include __DIR__ . '/partials/stats.php'; ?>
        </div>

        <div class="container" style="margin-bottom: 80px;">
            <!-- 6. Main Content & Sidebar -->
            <?php include __DIR__ . '/partials/content.php'; ?>
        </div>
    </main>

    <!-- Mobile Sticky Bar -->
    <div class="mobile-sticky-bar" style="direction: <?= $dir ?>;">
        <div>
            <span style="display:block; font-size:0.75rem; color:var(--text-muted);">Dr.
                <?= htmlspecialchars($doctor['last_name']) ?></span>
            <strong style="color:var(--primary); font-size: 0.9rem;"><?= __t('available') ?></strong>
        </div>
        <button onclick="document.getElementById('openBookingModal').click()"
            style="background:var(--secondary); color:white; border:none; padding:12px 28px; border-radius:50px; font-weight:700; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
            <?= __t('book_btn') ?>
        </button>
    </div>

    <!-- 7. Footer & Scripts -->
    <?php include __DIR__ . '/layout/footer.php'; ?>

    <!-- 8. Modals -->
    <?php include __DIR__ . '/partials/modals.php'; ?>

</body>

</html>