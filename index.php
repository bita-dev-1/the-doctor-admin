<?php
require_once("inc.php");

// ============================================================
// منطق النطاقات الفرعية (تم نقله من local_router.php)
// ============================================================
$full_host = $_SERVER['HTTP_HOST'];
$host_without_port = preg_replace('/:\d+$/', '', $full_host);

// حدد الدومين الرئيسي (غيره عند الرفع للسيرفر الحقيقي)
$main_domain = 'localhost';
// $main_domain = 'the-doctor.app'; // Production

$clean_host = preg_replace('/^www\./', '', $host_without_port);

// التحقق مما إذا كان الرابط نطاقاً فرعياً
if ($clean_host !== $main_domain && str_ends_with($clean_host, $main_domain)) {

    $subdomain = substr($clean_host, 0, -strlen('.' . $main_domain));
    $reserved_subdomains = ['admin', 'panel', 'api', 'web-api', 'www', 'mail', 'webmail', 'cpanel'];

    if (!in_array($subdomain, $reserved_subdomains)) {
        // توجيه الطلب إلى صفحة الطبيب
        $_GET['subdomain'] = $subdomain;
        require_once 'landing_loader.php';
        exit(); // توقف هنا، لا تحمل لوحة التحكم
    }
}
// ============================================================

require_once("router/router.php");
require_once 'controllers/custom/core/GoogleAuth.php';

// Public Routes
get(SITE_URL . '/login', 'views/login.php');
get(SITE_URL . '/login/google', 'google_login_redirect');
get(SITE_URL . '/login/google/callback', 'google_login_callback');
get(SITE_URL . '/complete-profile', 'views/complete_profile.php', ['auth']);

// Protected Routes
get(SITE_URL . '/', 'views/dashboard.php', ['auth']);
get(SITE_URL . '/force_change_password', 'views/force_change_password.php', ['auth']);
get(SITE_URL . '/profile', 'views/form_users.php', ['auth']);
get(SITE_URL . '/profile/password', 'views/changePass.php', ['auth']);

// ... (باقي المسارات كما هي دون تغيير) ...

/**************** [Begin]: Tables *******************/
get(SITE_URL . '/doctors', 'views/table_doctors.php', ['auth', 'admin']);
get(SITE_URL . '/users', 'views/table_doctors.php', ['auth', 'admin']);
get(SITE_URL . '/admins', 'views/table_doctors.php', ['auth', 'admin']);
get(SITE_URL . '/cabinets', 'views/table_cabinets.php', ['auth', 'super_admin']);
get(SITE_URL . '/patients', 'views/table_patients.php', ['auth', 'admin']);
get(SITE_URL . '/rdv', 'views/table_rdv.php', ['auth']);
get(SITE_URL . '/waitingList', 'views/table_waitingList.php', ['auth']);
get(SITE_URL . '/specialities', 'views/table_specialities.php', ['auth', 'super_admin']);
get(SITE_URL . '/calendar', 'views/calendar_planning.php', ['auth']);
get(SITE_URL . '/messages/$conversationId', 'views/messages.php', ['auth']);
get(SITE_URL . '/messages', 'views/messages.php', ['auth']);
get(SITE_URL . '/forget_password', 'views/forget_password.php');

// Kine Routes
get(SITE_URL . '/reeducation', 'views/table_reeducation.php', ['auth', 'kine_enabled']);
get(SITE_URL . '/sessions/today', 'views/table_sessions_today.php', ['auth', 'kine_enabled']);
get(SITE_URL . '/caisse', 'views/table_caisse.php', ['auth', 'kine_enabled']);
get(SITE_URL . '/reeducation/reports', 'views/reports_reeducation.php', ['auth', 'admin', 'kine_enabled']);
get(SITE_URL . '/receipt/$id', 'views/receipt.php', ['auth', 'kine_enabled']);
get(SITE_URL . '/salle_kine', 'views/salle_kine.php', ['auth', 'kine_enabled']);
get(SITE_URL . '/cabinet-services', 'views/table_cabinet_services.php', ['auth', 'admin', 'kine_enabled']);
get(SITE_URL . '/cabinet-services/insert', 'views/form_cabinet_services.php', ['auth', 'admin', 'kine_enabled']);
get(SITE_URL . '/cabinet-services/update/$id', 'views/form_cabinet_services.php', ['auth', 'admin', 'kine_enabled']);

// Forms
get(SITE_URL . '/users/insert', 'views/form_users.php', ['auth', 'admin']);
get(SITE_URL . '/users/update/$id', 'views/form_users.php', ['auth', 'admin']);
get(SITE_URL . '/doctors/insert', 'views/form_users.php', ['auth', 'admin']);
get(SITE_URL . '/doctors/update/$id', 'views/form_users.php', ['auth', 'admin']);
get(SITE_URL . '/admins/insert', 'views/form_users.php', ['auth', 'admin']);
get(SITE_URL . '/admins/update/$id', 'views/form_users.php', ['auth', 'admin']);
get(SITE_URL . '/cabinets/insert', 'views/form_cabinets.php', ['auth', 'super_admin']);
get(SITE_URL . '/cabinets/update/$id', 'views/form_cabinets.php', ['auth', 'super_admin']);
get(SITE_URL . '/patients/insert', 'views/form_patients.php', ['auth', 'admin']);
get(SITE_URL . '/patients/update/$id', 'views/form_patients.php', ['auth', 'admin']);
get(SITE_URL . '/specialities/insert', 'views/form_specialities.php', ['auth', 'super_admin']);
get(SITE_URL . '/specialities/update/$id', 'views/form_specialities.php', ['auth', 'super_admin']);
get(SITE_URL . '/reeducation-types', 'views/table_reeducation_types.php', ['auth', 'super_admin']);
get(SITE_URL . '/reeducation-types/insert', 'views/form_reeducation_types.php', ['auth', 'super_admin']);
get(SITE_URL . '/reeducation-types/update/$id', 'views/form_reeducation_types.php', ['auth', 'super_admin']);
get(SITE_URL . '/rdv/insert', 'views/form_rdv.php', ['auth']);
get(SITE_URL . '/rdv/update/$id', 'views/form_rdv.php', ['auth']);
get(SITE_URL . '/reeducation/insert', 'views/form_reeducation.php', ['auth', 'kine_enabled']);
get(SITE_URL . '/reeducation/update/$id', 'views/form_reeducation.php', ['auth', 'kine_enabled']);

// Core Handlers
post(SITE_URL . '/handlers', 'controllers/custom/handlers.php');
post(SITE_URL . '/data', 'controllers/data.core.php');

any('/404', path_to_include: 'views/404.php');
?>