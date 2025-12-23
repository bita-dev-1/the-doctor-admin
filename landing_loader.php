<?php
// 1. Load Core Configuration
require_once __DIR__ . '/inc.php';

if (file_exists(__DIR__ . '/config/DB.php')) {
    require_once __DIR__ . '/config/DB.php';
} else {
    die("System Error: Database configuration missing.");
}

if (file_exists(__DIR__ . '/controllers/landing.controller.php')) {
    require_once __DIR__ . '/controllers/landing.controller.php';
} else {
    die("System Error: Controller missing.");
}

try {
    $db = new DB();
} catch (Exception $e) {
    http_response_code(500);
    die("Database Error: " . $e->getMessage());
}

// 5. Resolve Doctor ID from Subdomain
$doctor_id = 0;

if (isset($_GET['subdomain'])) {
    // Sanitize
    $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['subdomain']));

    // Search in DB (We reuse landing_slug column)
    $sql = "SELECT id FROM users WHERE landing_slug = '$slug' LIMIT 1";
    $res = $db->select($sql);

    if (!empty($res)) {
        $doctor_id = intval($res[0]['id']);
    }
} elseif (isset($_GET['id'])) {
    // Fallback for old links
    $doctor_id = intval($_GET['id']);
}

// 6. Fetch Data & Render View
if ($doctor_id > 0) {
    $doctor = getDoctorFullProfile($db, $doctor_id);

    if ($doctor) {
        $viewPath = __DIR__ . '/views/landing/master.php';
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            die("System Error: View file not found.");
        }
    } else {
        http_response_code(404);
        include __DIR__ . '/views/404.php'; // Better 404 page
    }
} else {
    // Subdomain not found -> Redirect to main site or show 404
    header("Location: " . SITE_URL);
    exit();
}

$db = null;
?>