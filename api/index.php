<?php

// 1. Error Handling (Crucial for API JSON responses)
// Disable HTML error output to prevent breaking JSON syntax
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// 2. Handle CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 3. Bootstrap (Load .env & Settings)
$rootPath = dirname(__DIR__);

if (file_exists($rootPath . '/inc.php')) {
    require_once($rootPath . '/inc.php');
} else {
    require_once('../inc.php');
}

// 4. Include Router
require_once("router/router.php");

// 5. Include DB Config (Safe Include)
// Check if class exists to avoid "Cannot declare class DB" fatal error
if (!class_exists('DB')) {
    if (file_exists('config/DB.php')) {
        include_once('config/DB.php');
    } elseif (file_exists($rootPath . '/config/DB.php')) {
        include_once($rootPath . '/config/DB.php');
    } else {
        http_response_code(500);
        echo json_encode(["state" => "false", "message" => "DB Config missing"]);
        exit();
    }
}

// 6. Include Controller
include_once('controllers/api.controller.php');

// 7. Initialize DB
try {
    if (!class_exists('DB')) {
        throw new Exception("Class DB not found.");
    }

    // Use the global connection if available (from local_router), otherwise create new
    if (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof DB) {
        $db = $GLOBALS['db'];
    } else {
        $db = new DB();
    }
} catch (Exception $e) {
    error_log("API DB Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["state" => "false", "message" => "Database Connection Error"]);
    exit();
}

// 8. Define Routes

// --- Protected Routes ---
post('/api/v1/doctors', 'middleware/doctors.php');
post('/api/v1/rdv', 'middleware/rdv.php');
post('/api/v1/rdv/me', 'middleware/rdv.php');
any('/api/v1/notifications', 'middleware/notifications.php');

post('/api/v1/upload', 'middleware/upload.php');
post('/api/v1/endpoint', 'middleware/endpoint.php');

// --- Public Routes (Landing Page) ---
any('/api/v1/doctor/landing', 'middleware/doctor_landing.php');
any('/api/v1/public/availability', 'middleware/public_availability.php');
post('/api/v1/public/book', 'middleware/public_booking.php');
post('/api/v1/public/my-appointments', 'middleware/public_appointments.php');
post('/api/v1/public/recommend', 'middleware/public_recommend.php');

// --- Beta Routes ---
any('/api/v2/endpoint', 'middleware/endpointBeta.php');

// --- 404 Handler ---
any('/404', 'middleware/404.php');

$db = null;
?>