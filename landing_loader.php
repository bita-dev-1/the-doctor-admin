<?php
// 1. Load Core Configuration
require_once __DIR__ . '/inc.php';

// 2. Load Database Class
if (file_exists(__DIR__ . '/config/DB.php')) {
    require_once __DIR__ . '/config/DB.php';
} else {
    die("System Error: Database configuration missing.");
}

// 3. Load Landing Controller
if (file_exists(__DIR__ . '/controllers/landing.controller.php')) {
    require_once __DIR__ . '/controllers/landing.controller.php';
} else {
    die("System Error: Controller missing.");
}

// 4. Initialize DB Connection
try {
    $db = new DB();
} catch (Exception $e) {
    http_response_code(500);
    die("Database Error: " . $e->getMessage());
}

// 5. Get Doctor ID from URL
$doctor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 6. Fetch Data & Render View
if ($doctor_id > 0) {
    $doctor = getDoctorFullProfile($db, $doctor_id);

    if ($doctor) {
        // Render View
        $viewPath = __DIR__ . '/views/landing/master.php';

        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            die("System Error: View file not found at " . $viewPath);
        }

    } else {
        http_response_code(404);
        // رسالة واضحة لمعرفة السبب
        echo "<h1>404 - Médecin introuvable</h1>";
        echo "<p>ID: $doctor_id exists but user is not an active doctor.</p>";
    }
} else {
    http_response_code(404);
    echo "<h1>404 - ID Manquant</h1>";
}

$db = null;
?>