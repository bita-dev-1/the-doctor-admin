<?php
// local_router.php

// 0. Define Root Constant
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', __DIR__);
}

// 1. Load Configuration
require_once __DIR__ . '/inc.php';

// Safe DB Include
if (!class_exists('DB')) {
    if (file_exists(__DIR__ . '/config/DB.php')) {
        require_once __DIR__ . '/config/DB.php';
    }
}

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// --- Host & Port Handling ---
$full_host = $_SERVER['HTTP_HOST'];
$host_without_port = preg_replace('/:\d+$/', '', $full_host);
$main_domain = 'localhost';

// 2. Serve Static Files
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// 3. Handle API Routes
if (stripos($uri, '/api') === 0) {
    $_SERVER['SCRIPT_NAME'] = '/api/index.php';
    chdir(__DIR__ . '/api');
    require 'index.php';
    return true;
}

if (stripos($uri, '/web-api') === 0) {
    $_SERVER['SCRIPT_NAME'] = '/web-api/index.php';
    chdir(__DIR__ . '/web-api');
    require 'index.php';
    return true;
}

// 4. REDIRECT OLD LINKS
if (preg_match('#^/dr/(.+)-([0-9]+)$#', $uri, $matches) || preg_match('#^/medecin/([0-9]+)(/.*)?$#', $uri, $matches)) {
    $target_id = intval(end($matches));
    try {
        $db = (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof DB) ? $GLOBALS['db'] : new DB();
        $user = $db->select("SELECT landing_slug FROM users WHERE id = $target_id");
        if (!empty($user) && !empty($user[0]['landing_slug'])) {
            $subdomain = $user[0]['landing_slug'];
            $port_suffix = ($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) ? ':' . $_SERVER['SERVER_PORT'] : '';
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
            $new_url = $protocol . $subdomain . '.' . $main_domain . $port_suffix;
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . $new_url);
            exit();
        }
    } catch (Exception $e) {
    }
}

// 5. HANDLE SUBDOMAIN REQUESTS
$clean_host = preg_replace('/^www\./', '', $host_without_port);
if ($clean_host !== $main_domain && str_ends_with($clean_host, $main_domain)) {
    $subdomain = substr($clean_host, 0, -strlen('.' . $main_domain));
    $reserved_subdomains = ['admin', 'panel', 'api', 'www', 'mail', 'webmail', 'cpanel'];
    if (!in_array($subdomain, $reserved_subdomains)) {
        $_GET['subdomain'] = $subdomain;
        $_SERVER['SCRIPT_NAME'] = '/landing_loader.php';
        chdir(__DIR__);
        require_once __DIR__ . '/landing_loader.php';
        return true;
    }
}

// 6. Default: Dashboard & System Routes
// --- FIX: Force SCRIPT_NAME to index.php so routing works correctly ---
$_SERVER['SCRIPT_NAME'] = '/index.php';
// ----------------------------------------------------------------------

chdir(__DIR__);
require_once __DIR__ . '/index.php';
?>