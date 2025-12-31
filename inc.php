<?php
// inc.php

// 1. Define Project Root
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', __DIR__);
}

// 2. Load Composer Autoloader
require_once PROJECT_ROOT . '/vendor/autoload.php';

// 3. Load Environment Variables (.env)
try {
    $dotenv = Dotenv\Dotenv::createImmutable(PROJECT_ROOT);
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    // Suppress error if .env is missing
}

// 4. Secure Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

$session_save_path = PROJECT_ROOT . '/tmp';
if (!file_exists($session_save_path)) {
    mkdir($session_save_path, 0777, true);
}
session_save_path($session_save_path);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 5. Define Global Constants

// Detect Protocol & Domain
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";

// --- FIX: Use Current Host for Assets to avoid CORS Block ---
// نستخدم النطاق الحالي كما هو (سواء كان فرعياً أو رئيسياً)
// هذا يضمن أن المتصفح يرى الملفات قادمة من نفس المصدر (Same Origin)
$domain_for_assets = $_SERVER['HTTP_HOST'];
// ----------------------------------------------------------

// --- SITE_URL (Relative Path for Routing) ---
if (!defined('SITE_URL')) {
    if (!empty($_ENV['APP_URL'])) {
        $parsed = parse_url($_ENV['APP_URL']);
        $path = isset($parsed['path']) ? rtrim($parsed['path'], '/') : '';
        define('SITE_URL', $path);
    } else {
        $script_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        if ($script_path === '/' || $script_path === '\\')
            $script_path = '';
        define('SITE_URL', $script_path);
    }
}

// --- SITE_URI (Full URL for Assets) ---
if (!defined('SITE_URI')) {
    if (!empty($_ENV['APP_URL'])) {
        // إذا كان هناك متغير بيئة، نستخدمه ولكن نستبدل الهوست بالهوست الحالي
        // لضمان عمل النطاقات الفرعية
        $env_url = parse_url($_ENV['APP_URL']);
        $path = isset($env_url['path']) ? rtrim($env_url['path'], '/') : '';
        define('SITE_URI', $protocol . $domain_for_assets . $path . '/');
    } else {
        $script_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        if ($script_path === '/' || $script_path === '\\')
            $script_path = '';
        define('SITE_URI', $protocol . $domain_for_assets . $script_path . '/');
    }
}

// --- API Constants ---
if (!defined('API_URL')) {
    define('API_URL', SITE_URI . 'web-api/v1');
}
if (!defined('API_KEY')) {
    define('API_KEY', '');
}

// Mail Constants
define('MAIL_HOST', $_ENV['MAIL_HOST'] ?? '');
define('MAIL_PORT', $_ENV['MAIL_PORT'] ?? 587);
define('MAIL_USERNAME', $_ENV['MAIL_USERNAME'] ?? '');
define('MAIL_PASSWORD', $_ENV['MAIL_PASSWORD'] ?? '');
define('MAIL_ENCRYPTION', $_ENV['MAIL_ENCRYPTION'] ?? 'tls');
define('MAIL_FROM_ADDRESS', $_ENV['MAIL_FROM_ADDRESS'] ?? '');
define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? 'The Doctor');

// API Secret
define('API_SECRET_KEY', $_ENV['API_SECRET_KEY'] ?? 'default_insecure_key');

// Google Auth
define('GOOGLE_CLIENT_ID', $_ENV['GOOGLE_CLIENT_ID'] ?? '');
define('GOOGLE_CLIENT_SECRET', $_ENV['GOOGLE_CLIENT_SECRET'] ?? '');
// ملاحظة: يجب أن يكون رابط إعادة التوجيه ثابتاً في إعدادات جوجل
// لذا نستخدم النطاق الرئيسي هنا حصراً إذا كنت قد سجلت النطاق الرئيسي فقط في جوجل
// لكن لغرض العرض، سنتركه ديناميكياً، وقد تحتاج لتعديله في Google Console
define('GOOGLE_REDIRECT_URI', SITE_URI . 'login/google/callback');

?>