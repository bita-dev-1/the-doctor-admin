<?php

    require_once("router/router.php");

    include_once('config/DB.php');
    include_once('controllers/api.controller.php');

    $db = new DB();
    
    post('/web-api/v1/doctors', 'middleware/doctors.php');
    post('/web-api/v1/rdv', 'middleware/rdv.php');
    post('/web-api/v1/rdv/me', 'middleware/rdv.php');
    any('/web-api/v1/notifications', 'middleware/notifications.php');

    post('/web-api/v1/upload', 'middleware/upload.php');
    post('/web-api/v1/endpoint', 'middleware/endpoint.php');
    any('/web-api/v2/endpoint', 'middleware/endpointBeta.php');

    any('/404','middleware/404.php');

    $db = null;