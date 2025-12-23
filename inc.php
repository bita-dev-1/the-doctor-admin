<?php
// --- Define Project Root (Safe Check) ---
// نتحقق أولاً إذا كان الثابت معرفاً لتجنب التحذير
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', __DIR__);
}

// --- Fix Session Path (لحل مشكلة CSRF) ---
// نحدد مسار حفظ الجلسات داخل مجلد tmp في المشروع
$session_save_path = PROJECT_ROOT . '/tmp';
if (!file_exists($session_save_path)) {
    mkdir($session_save_path, 0777, true);
}
session_save_path($session_save_path);

// --- Start Session ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Autoload Composer dependencies and load environment variables ---
require_once PROJECT_ROOT . '/vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(PROJECT_ROOT);
    $dotenv->load();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    // die("Could not find .env file. Please create one in the root directory.");
} catch (\Dotenv\Exception\InvalidFileException $e) {
    error_log("Dotenv Syntax Error: " . $e->getMessage());
}

// --- Auto-detect Environment and Paths ---

// 1. Determine BASE_PATH
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base_path === '/' || $base_path === '\\') {
    $base_path = '';
}

// This constant will be used for routing and form actions
if (!defined('SITE_URL')) {
    define('SITE_URL', $base_path);
}

// 2. Determine BASE_URI
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];

// This constant will be used for linking assets
if (!defined('SITE_URI')) {
    define('SITE_URI', $protocol . $host . $base_path . '/');
}

// These can be moved to a .env file later for better security
if (!defined('API_URL'))
    define('API_URL', '');
if (!defined('API_KEY'))
    define('API_KEY', '');

// --- NEW: Define Mail Constants from .env ---
if (!defined('MAIL_HOST'))
    define('MAIL_HOST', $_ENV['MAIL_HOST'] ?? 'smtp.mailtrap.io');
if (!defined('MAIL_PORT'))
    define('MAIL_PORT', $_ENV['MAIL_PORT'] ?? 587);
if (!defined('MAIL_USERNAME'))
    define('MAIL_USERNAME', $_ENV['MAIL_USERNAME'] ?? '');
if (!defined('MAIL_PASSWORD'))
    define('MAIL_PASSWORD', $_ENV['MAIL_PASSWORD'] ?? '');
if (!defined('MAIL_ENCRYPTION'))
    define('MAIL_ENCRYPTION', $_ENV['MAIL_ENCRYPTION'] ?? 'tls');
if (!defined('MAIL_FROM_ADDRESS'))
    define('MAIL_FROM_ADDRESS', $_ENV['MAIL_FROM_ADDRESS'] ?? 'from@example.com');
if (!defined('MAIL_FROM_NAME'))
    define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? 'Example');

// --- Google Auth Configuration ---
if (!defined('GOOGLE_CLIENT_ID'))
    define('GOOGLE_CLIENT_ID', $_ENV['GOOGLE_CLIENT_ID'] ?? 'ضع_Client_ID_الخاص_بك_هنا');
if (!defined('GOOGLE_CLIENT_SECRET'))
    define('GOOGLE_CLIENT_SECRET', $_ENV['GOOGLE_CLIENT_SECRET'] ?? 'ضع_Client_Secret_الخاص_بك_هنا');
if (!defined('GOOGLE_REDIRECT_URI'))
    define('GOOGLE_REDIRECT_URI', SITE_URI . 'login/google/callback');
?>