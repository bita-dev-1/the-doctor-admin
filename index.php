<?php
require_once("inc.php");
require_once("router/router.php");

get(SITE_URL . '/', 'views/dashboard.php');
get(SITE_URL . '/login', 'views/login.php');

// --- NEW ROUTE ---
get(SITE_URL . '/force_change_password', 'views/force_change_password.php');

get(SITE_URL . '/profile', 'views/form_users.php');
get(SITE_URL . '/profile/password', 'views/changePass.php');

/**************** [Begin]: Tables *******************/
// Admin routes for user management
get(SITE_URL . '/doctors', 'views/table_doctors.php'); // Now serves as the main user list for doctors/nurses
get(SITE_URL . '/users', 'views/table_doctors.php');   // Alias for clarity
get(SITE_URL . '/admins', 'views/table_doctors.php');  // Redirect old admins link to the new user management page

// --- NEW ROUTES FOR CABINETS ---
get(SITE_URL . '/cabinets', 'views/table_cabinets.php');

// Other tables
get(SITE_URL . '/patients', 'views/table_patients.php');
get(SITE_URL . '/rdv', 'views/table_rdv.php');
get(SITE_URL . '/waitingList', 'views/table_waitingList.php');
get(SITE_URL . '/specialities', 'views/table_specialities.php');
get(SITE_URL . '/calendar', 'views/calendar_planning.php');
get(SITE_URL . '/messages/$conversationId', 'views/messages.php');
get(SITE_URL . '/messages', 'views/messages.php');
get(SITE_URL . '/forget_password', 'views/forget_password.php');

// --- NEW REEDUCATION ROUTES ---
get(SITE_URL . '/reeducation', 'views/table_reeducation.php');
get(SITE_URL . '/sessions/today', 'views/table_sessions_today.php');
get(SITE_URL . '/caisse', 'views/table_caisse.php');
get(SITE_URL . '/reeducation/reports', 'views/reports_reeducation.php');
get(SITE_URL . '/receipt/$id', 'views/receipt.php');

// --- NEW ROUTES FOR CABINET SERVICES (CONFIGURATION) ---
get(SITE_URL . '/cabinet-services', 'views/table_cabinet_services.php');
get(SITE_URL . '/cabinet-services/insert', 'views/form_cabinet_services.php');
get(SITE_URL . '/cabinet-services/update/$id', 'views/form_cabinet_services.php');

/**************** [End]: Tables *******************/

/**************** [Begin]: Forms *******************/
// Unified form for adding/editing users (doctors/nurses)
get(SITE_URL . '/users/insert', 'views/form_users.php');
get(SITE_URL . '/users/update/$id', 'views/form_users.php');

// Redirect old routes to new ones for backward compatibility
get(SITE_URL . '/doctors/insert', 'views/form_users.php');
get(SITE_URL . '/doctors/update/$id', 'views/form_users.php');
get(SITE_URL . '/admins/insert', 'views/form_users.php');
get(SITE_URL . '/admins/update/$id', 'views/form_users.php');

// --- NEW ROUTES FOR CABINETS ---
get(SITE_URL . '/cabinets/insert', 'views/form_cabinets.php');
get(SITE_URL . '/cabinets/update/$id', 'views/form_cabinets.php');

// Other forms
get(SITE_URL . '/patients/insert', 'views/form_patients.php');
get(SITE_URL . '/patients/update/$id', 'views/form_patients.php');

get(SITE_URL . '/specialities/insert', 'views/form_specialities.php');
get(SITE_URL . '/specialities/update/$id', 'views/form_specialities.php');

// Routes for Reeducation Types
get(SITE_URL . '/reeducation-types', 'views/table_reeducation_types.php');
get(SITE_URL . '/reeducation-types/insert', 'views/form_reeducation_types.php');
get(SITE_URL . '/reeducation-types/update/$id', 'views/form_reeducation_types.php');

get(SITE_URL . '/rdv/insert', 'views/form_rdv.php');
get(SITE_URL . '/rdv/update/$id', 'views/form_rdv.php');

// --- NEW REEDUCATION ROUTES ---
get(SITE_URL . '/reeducation/insert', 'views/form_reeducation.php');
get(SITE_URL . '/reeducation/update/$id', 'views/form_reeducation.php');

/**************** [End]: Forms *******************/

/******* [Begin]: Route Core *********/
post(SITE_URL . '/handlers', 'controllers/custom/handlers.php');
post(SITE_URL . '/data', 'controllers/data.core.php');
any('/404', 'views/404.php');

/******* [End]: Route Core *********/