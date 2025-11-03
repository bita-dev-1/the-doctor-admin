<?php
    // --- Autoload Composer dependencies and load environment variables ---
    require_once __DIR__ . '/vendor/autoload.php';
    
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();
    } catch (\Dotenv\Exception\InvalidPathException $e) {
        die("Could not find .env file. Please create one in the root directory.");
    }

    // --- Auto-detect Environment and Paths ---

    // 1. Determine BASE_PATH (e.g., /the-doctor-admin or empty if at root)
    $base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    // If script is at root, dirname might return '/' or '\', so we make it empty
    if ($base_path === '/' || $base_path === '\\') {
        $base_path = '';
    }

    // This constant will be used for routing and form actions (e.g., /the-doctor-admin/data)
    define('SITE_URL', $base_path);

    // 2. Determine BASE_URI (the full URL for assets like CSS, JS, images)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    
    // This constant will be used for linking assets (e.g., http://localhost/the-doctor-admin/assets/css/style.css)
    define('SITE_URI', $protocol . $host . $base_path . '/');

    // These can be moved to a .env file later for better security
    define('API_URL', ''); 
    define('API_KEY', '');
?>