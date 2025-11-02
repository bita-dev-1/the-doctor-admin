<?php
    require_once("inc.php");
    require_once("router/router.php");

    get(SITE_URL.'/', 'views/dashboard.php');
    get(SITE_URL.'/login', 'views/login.php');

    get(SITE_URL.'/profile', 'views/form_users.php');
    get(SITE_URL.'/profile/password', 'views/changePass.php');
    
/**************** [Begin]: Tables *******************/
    get(SITE_URL.'/admins', 'views/table_admins.php');
    get(SITE_URL.'/doctors', 'views/table_doctors.php');
    get(SITE_URL.'/patients', 'views/table_patients.php');
    get(SITE_URL.'/rdv', 'views/table_rdv.php');
    get(SITE_URL.'/waitingList', 'views/table_waitingList.php');
    get(SITE_URL.'/specialities', 'views/table_specialities.php');
	get(SITE_URL.'/calendar', 'views/calendar_planning.php');
    get(SITE_URL.'/messages/$conversationId', 'views/messages.php');
    get(SITE_URL.'/messages', 'views/messages.php');
	get(SITE_URL.'/forget_password', 'views/forget_password.php');
    
/**************** [End]: Tables *******************/

/**************** [Begin]: Forms *******************/

    get(SITE_URL.'/doctors/insert', 'views/form_users.php');
    get(SITE_URL.'/doctors/update/$id', 'views/form_users.php');

    get(SITE_URL.'/admins/insert', 'views/form_users.php');
    get(SITE_URL.'/admins/update/$id', 'views/form_users.php');

    get(SITE_URL.'/patients/insert', 'views/form_patients.php');
    get(SITE_URL.'/patients/update/$id', 'views/form_patients.php');

    get(SITE_URL.'/specialities/insert', 'views/form_specialities.php');
    get(SITE_URL.'/specialities/update/$id', 'views/form_specialities.php');

    get(SITE_URL.'/rdv/insert', 'views/form_rdv.php');
    get(SITE_URL.'/rdv/update/$id', 'views/form_rdv.php');

/**************** [End]: Forms *******************/

/******* [Begin]: Route Core *********/
    post(SITE_URL.'/handlers', 'controllers/custom/handlers.php');
    post(SITE_URL.'/data', 'controllers/data.core.php');
    any('/404','views/404.php');

/******* [End]: Route Core *********/