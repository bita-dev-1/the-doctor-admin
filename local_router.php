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
    chdir(__DIR__ . '/api');
    require_once 'index.php';
    return true;
}

// 3. Handle Web-API Routes
if (strpos($uri, '/web-api/') === 0) {
    $_SERVER['SCRIPT_NAME'] = '/web-api/index.php';
    chdir(__DIR__ . '/web-api');
    require_once 'index.php';
    return true;
}

// 4. Handle Doctor Landing Page (New SEO Pattern)
// Matches: /dr/name-slug-123
if (preg_match('#^/dr/(.+)-([0-9]+)$#', $uri, $matches)) {
    $_GET['id'] = $matches[2]; // The ID is the last group of digits
    $_SERVER['SCRIPT_NAME'] = '/landing_loader.php';
    require_once __DIR__ . '/landing_loader.php';
    return true;
}

// 5. Handle Old Pattern (Redirect or Load)
if (preg_match('#^/medecin/([0-9]+)(/.*)?$#', $uri, $matches)) {
    $_GET['id'] = $matches[1];
    $_SERVER['SCRIPT_NAME'] = '/landing_loader.php';
    require_once __DIR__ . '/landing_loader.php';
    return true;
}

// 6. Default: Dashboard & Admin Panel
require_once __DIR__ . '/index.php';
?>