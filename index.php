<?php
require_once("inc.php");
require_once("router/router.php");

// Public Routes
get(SITE_URL . '/login', 'views/login.php');

// Protected Routes (Require Auth)
get(SITE_URL . '/', 'views/dashboard.php', ['auth']);
get(SITE_URL . '/force_change_password', 'views/force_change_password.php', ['auth']);
get(SITE_URL . '/profile', 'views/form_users.php', ['auth']);
get(SITE_URL . '/profile/password', 'views/changePass.php', ['auth']);

/**************** [Begin]: Tables *******************/
// Admin Routes
get(SITE_URL . '/doctors', 'views/table_doctors.php', ['auth', 'admin']);
get(SITE_URL . '/users', 'views/table_doctors.php', ['auth', 'admin']);
get(SITE_URL . '/admins', 'views/table_doctors.php', ['auth', 'admin']);

// Super Admin Routes
get(SITE_URL . '/cabinets', 'views/table_cabinets.php', ['auth', 'super_admin']);

// General Protected Routes
get(SITE_URL . '/patients', 'views/table_patients.php', ['auth', 'admin']); // Assuming only admins manage patients list directly
get(SITE_URL . '/rdv', 'views/table_rdv.php', ['auth']);
get(SITE_URL . '/waitingList', 'views/table_waitingList.php', ['auth']);
get(SITE_URL . '/specialities', 'views/table_specialities.php', ['auth', 'super_admin']);
get(SITE_URL . '/calendar', 'views/calendar_planning.php', ['auth']);
get(SITE_URL . '/messages/$conversationId', 'views/messages.php', ['auth']);
get(SITE_URL . '/messages', 'views/messages.php', ['auth']);
get(SITE_URL . '/forget_password', 'views/forget_password.php'); // Public

// --- KINE ROUTES (Protected by 'kine_enabled' middleware) ---
get(SITE_URL . '/reeducation', 'views/table_reeducation.php', ['auth', 'kine_enabled']);
get(SITE_URL . '/sessions/today', 'views/table_sessions_today.php', ['auth', 'kine_enabled']);
get(SITE_URL . '/caisse', 'views/table_caisse.php', ['auth', 'kine_enabled']); // Assuming Caisse is linked to Kine
get(SITE_URL . '/reeducation/reports', 'views/reports_reeducation.php', ['auth', 'admin', 'kine_enabled']); // Admin only + Kine
get(SITE_URL . '/receipt/$id', 'views/receipt.php', ['auth', 'kine_enabled']);
get(SITE_URL . '/salle_kine', 'views/salle_kine.php', ['auth', 'kine_enabled']);

// --- CABINET SERVICES (CONFIGURATION) ---
get(SITE_URL . '/cabinet-services', 'views/table_cabinet_services.php', ['auth', 'admin', 'kine_enabled']);
get(SITE_URL . '/cabinet-services/insert', 'views/form_cabinet_services.php', ['auth', 'admin', 'kine_enabled']);
get(SITE_URL . '/cabinet-services/update/$id', 'views/form_cabinet_services.php', ['auth', 'admin', 'kine_enabled']);

/**************** [End]: Tables *******************/

/**************** [Begin]: Forms *******************/
// Users Forms
get(SITE_URL . '/users/insert', 'views/form_users.php', ['auth', 'admin']);
get(SITE_URL . '/users/update/$id', 'views/form_users.php', ['auth', 'admin']);

// Legacy Redirects
get(SITE_URL . '/doctors/insert', 'views/form_users.php', ['auth', 'admin']);
get(SITE_URL . '/doctors/update/$id', 'views/form_users.php', ['auth', 'admin']);
get(SITE_URL . '/admins/insert', 'views/form_users.php', ['auth', 'admin']);
get(SITE_URL . '/admins/update/$id', 'views/form_users.php', ['auth', 'admin']);

// Cabinets Forms
get(SITE_URL . '/cabinets/insert', 'views/form_cabinets.php', ['auth', 'super_admin']);
get(SITE_URL . '/cabinets/update/$id', 'views/form_cabinets.php', ['auth', 'super_admin']);

// Patients Forms
get(SITE_URL . '/patients/insert', 'views/form_patients.php', ['auth', 'admin']);
get(SITE_URL . '/patients/update/$id', 'views/form_patients.php', ['auth', 'admin']);

// Specialities Forms
get(SITE_URL . '/specialities/insert', 'views/form_specialities.php', ['auth', 'super_admin']);
get(SITE_URL . '/specialities/update/$id', 'views/form_specialities.php', ['auth', 'super_admin']);

// Reeducation Types Forms
get(SITE_URL . '/reeducation-types', 'views/table_reeducation_types.php', ['auth', 'super_admin']);
get(SITE_URL . '/reeducation-types/insert', 'views/form_reeducation_types.php', ['auth', 'super_admin']);
get(SITE_URL . '/reeducation-types/update/$id', 'views/form_reeducation_types.php', ['auth', 'super_admin']);

// RDV Forms
get(SITE_URL . '/rdv/insert', 'views/form_rdv.php', ['auth']);
get(SITE_URL . '/rdv/update/$id', 'views/form_rdv.php', ['auth']);

// Reeducation Forms
get(SITE_URL . '/reeducation/insert', 'views/form_reeducation.php', ['auth', 'kine_enabled']);
get(SITE_URL . '/reeducation/update/$id', 'views/form_reeducation.php', ['auth', 'kine_enabled']);

/**************** [End]: Forms *******************/

/******* [Begin]: Route Core *********/
post(SITE_URL . '/handlers', 'controllers/custom/handlers.php'); // Handlers usually have internal auth checks
post(SITE_URL . '/data', 'controllers/data.core.php'); // Data core usually has internal auth checks

/******* [End]: Route Core *********/

any('/404', path_to_include: 'views/404.php');