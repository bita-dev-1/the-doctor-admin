<?php
// 1. Load Core Configuration
require_once 'inc.php';
require_once 'config/DB.php';

// 2. Load Landing Controller
require_once 'controllers/landing.controller.php';

// 3. Initialize DB
$db = new DB();

// 4. Get Doctor ID directly from URL parameters
$doctor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 5. Fetch Data
$doctor = getDoctorFullProfile($db, $doctor_id);

// 6. Render View
if ($doctor) {
    $viewPath = 'views/landing/master.php';

    if (file_exists($viewPath)) {
        include $viewPath;
    } else {
        echo "<h1>System Error: View file missing.</h1>";
    }
} else {
    // Doctor Not Found
    http_response_code(404);
    include 'views/404.php';
}
?>