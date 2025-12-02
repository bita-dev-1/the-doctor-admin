<?php
// --- Define Project Root ---
define('PROJECT_ROOT', __DIR__);

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
    // تم التعليق لتجنب توقف الموقع في حال عدم وجود الملف، الاعتماد على القيم الافتراضية
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

// --- NEW: Define Mail Constants from .env ---
define('MAIL_HOST', $_ENV['MAIL_HOST'] ?? 'smtp.mailtrap.io');
define('MAIL_PORT', $_ENV['MAIL_PORT'] ?? 587);
define('MAIL_USERNAME', $_ENV['MAIL_USERNAME'] ?? '');
define('MAIL_PASSWORD', $_ENV['MAIL_PASSWORD'] ?? '');
define('MAIL_ENCRYPTION', $_ENV['MAIL_ENCRYPTION'] ?? 'tls');
define('MAIL_FROM_ADDRESS', $_ENV['MAIL_FROM_ADDRESS'] ?? 'from@example.com');
define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? 'Example');
?>