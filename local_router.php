<?php
// local_router.php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// 1. Serve Static Files Directly (Images, CSS, JS)
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// 2. Handle API Routes
if (strpos($uri, '/api/') === 0) {
    $_SERVER['SCRIPT_NAME'] = '/api/index.php';

    // --- FIX: Change directory to 'api' so relative includes work correctly ---
    chdir(__DIR__ . '/api');

    require_once 'index.php';
    return true;
}

// 3. Handle Web-API Routes (If used)
if (strpos($uri, '/web-api/') === 0) {
    $_SERVER['SCRIPT_NAME'] = '/web-api/index.php';

    // --- FIX: Change directory to 'web-api' ---
    chdir(__DIR__ . '/web-api');

    require_once 'index.php';
    return true;
}

// 4. Handle Doctor Landing Page (SEO URL)
// Pattern: /medecin/{id}/{slug}
if (preg_match('#^/medecin/([0-9]+)(/.*)?$#', $uri, $matches)) {
    $_GET['id'] = $matches[1];
    require_once __DIR__ . '/landing_loader.php';
    return true;
}

// 5. Default: Dashboard & Admin Panel
require_once __DIR__ . '/index.php';
?>