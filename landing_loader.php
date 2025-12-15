<?php
// 1. Load Core Configuration
require_once __DIR__ . '/inc.php';

// 2. Load Database Class
if (file_exists(__DIR__ . '/config/DB.php')) {
    require_once __DIR__ . '/config/DB.php';
} elseif (defined('PROJECT_ROOT') && file_exists(PROJECT_ROOT . '/config/DB.php')) {
    require_once PROJECT_ROOT . '/config/DB.php';
} else {
    die("System Error: Database configuration missing.");
}

// 3. Load Landing Controller
if (file_exists(__DIR__ . '/controllers/landing.controller.php')) {
    require_once __DIR__ . '/controllers/landing.controller.php';
} elseif (defined('PROJECT_ROOT') && file_exists(PROJECT_ROOT . '/controllers/landing.controller.php')) {
    require_once PROJECT_ROOT . '/controllers/landing.controller.php';
} else {
    die("System Error: Controller missing.");
}

// 4. Initialize DB Connection
try {
    $db = new DB();
} catch (Exception $e) {
    error_log("Landing Page DB Error: " . $e->getMessage());
    http_response_code(500);
    echo "<h1>500 Internal Server Error</h1><p>Database connection failed.</p>";
    exit();
}

// 5. Get Doctor ID from URL
$doctor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 6. Fetch Data & Render View
if ($doctor_id > 0) {
    $doctor = getDoctorFullProfile($db, $doctor_id);

    if ($doctor) {
        // --- START: SEO AUTO REDIRECT LOGIC ---

        // 1. Build the correct SEO Slug
        $slug = strtolower(trim($doctor['first_name'] . '-' . $doctor['last_name']));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        // 2. Construct the Expected Path
        // Example: /medecin/509/dr-mohamed-salah
        $expected_path = SITE_URL . "/medecin/" . $doctor['id'] . "/dr-" . $slug;

        // 3. Get Current Request URI (ignoring query params for comparison if needed, but here we want exact match)
        $current_uri = $_SERVER['REQUEST_URI'];

        // Remove query string from current URI for comparison (e.g. ?id=509)
        $current_path = parse_url($current_uri, PHP_URL_PATH);

        // 4. Compare and Redirect if mismatch
        // We check if the current path is NOT the expected SEO path
        if ($current_path !== $expected_path && $_SERVER['REQUEST_METHOD'] === 'GET') {
            // 301 Moved Permanently is best for SEO
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . $expected_path);
            exit();
        }
        // --- END: SEO AUTO REDIRECT LOGIC ---

        $viewPath = 'views/landing/master.php';

        if (file_exists(__DIR__ . '/' . $viewPath)) {
            include __DIR__ . '/' . $viewPath;
        } elseif (defined('PROJECT_ROOT') && file_exists(PROJECT_ROOT . '/' . $viewPath)) {
            include PROJECT_ROOT . '/' . $viewPath;
        } else {
            error_log("Critical: View file not found at $viewPath");
            die("System Error: View file missing.");
        }

    } else {
        http_response_code(404);
        if (file_exists('views/404.php'))
            include 'views/404.php';
        else
            echo "404 Not Found";
    }
} else {
    http_response_code(404);
    if (file_exists('views/404.php'))
        include 'views/404.php';
    else
        echo "404 Not Found";
}

$db = null;
?>