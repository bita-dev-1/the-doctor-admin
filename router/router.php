<?php

include_once('config/encryption.core.php');
// تضمين ملف قاعدة البيانات للتحقق اللحظي
include_once('config/DB.php');

// ... (الدوال get, post, put, patch, delete, any تبقى كما هي) ...
function get($route, $path_to_include, $middlewares = [])
{
  if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    route($route, $path_to_include, $middlewares);
  }
}
function post($route, $path_to_include, $middlewares = [])
{
  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    route($route, $path_to_include, $middlewares);
  }
}
function put($route, $path_to_include, $middlewares = [])
{
  if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    route($route, $path_to_include, $middlewares);
  }
}
function patch($route, $path_to_include, $middlewares = [])
{
  if ($_SERVER['REQUEST_METHOD'] == 'PATCH') {
    route($route, $path_to_include, $middlewares);
  }
}
function delete($route, $path_to_include, $middlewares = [])
{
  if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    route($route, $path_to_include, $middlewares);
  }
}
function any($route, $path_to_include, $middlewares = [])
{
  route($route, $path_to_include, $middlewares);
}


// --- دالة Middleware المحسنة (Real-time Check) ---
function run_middleware($middlewares)
{
  // 1. التحقق الأساسي من وجود الجلسة
  if (empty($middlewares))
    return;

  // إذا كان المستخدم غير مسجل دخول، وطلب صفحة محمية بـ auth
  if (in_array('auth', $middlewares) && !isset($_SESSION['user']['id'])) {
    header('Location: ' . SITE_URL . '/login');
    exit();
  }

  // 2. جلب أحدث البيانات من قاعدة البيانات (التحقق الحي)
  if (isset($_SESSION['user']['id'])) {
    $db = new DB();
    $userId = intval($_SESSION['user']['id']);

    // استعلام خفيف لجلب الدور، حالة العيادة، وحالة الحساب
    $sql = "SELECT u.id, u.role, u.cabinet_id, u.status, u.deleted, c.kine_enabled 
                FROM users u 
                LEFT JOIN cabinets c ON u.cabinet_id = c.id 
                WHERE u.id = $userId";

    $freshData = $db->select($sql)[0] ?? null;

    // أ. التحقق من أن المستخدم لا يزال موجوداً ونشطاً
    if (!$freshData || $freshData['deleted'] == 1 || $freshData['status'] !== 'active') {
      session_destroy(); // طرد المستخدم فوراً
      header('Location: ' . SITE_URL . '/login');
      exit();
    }

    // ب. تحديث بيانات الجلسة الحالية لتكون متزامنة مع قاعدة البيانات
    $_SESSION['user']['role'] = $freshData['role'];
    $_SESSION['user']['cabinet_id'] = $freshData['cabinet_id'];
    $_SESSION['user']['kine_enabled'] = $freshData['kine_enabled']; // تحديث حالة Kiné
  }

  // 3. تنفيذ شروط الـ Middleware بناءً على البيانات الحديثة
  foreach ($middlewares as $mw) {
    switch ($mw) {
      case 'auth':
        // تم التحقق منه أعلاه
        break;

      case 'admin':
        if ($_SESSION['user']['role'] !== 'admin') {
          header('Location: ' . SITE_URL . '/');
          exit();
        }
        break;

      case 'super_admin':
        $is_super = ($_SESSION['user']['role'] === 'admin' && empty($_SESSION['user']['cabinet_id']));
        if (!$is_super) {
          header('Location: ' . SITE_URL . '/');
          exit();
        }
        break;

      case 'kine_enabled':
        // التحقق باستخدام البيانات المحدثة من الـ DB
        $is_super = ($_SESSION['user']['role'] === 'admin' && empty($_SESSION['user']['cabinet_id']));
        $kine_active = isset($_SESSION['user']['kine_enabled']) && $_SESSION['user']['kine_enabled'] == 1;

        if (!$is_super && !$kine_active) {
          include_once("views/404.php");
          exit();
        }
        break;
    }
  }
}

// ... (باقي الدوال request_path, route, out, set_csrf, is_csrf_valid تبقى كما هي) ...
function request_path()
{
  $request_uri = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
  $script_name = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'));
  $parts = array_diff_assoc($request_uri, $script_name);
  if (empty($parts)) {
    return '/';
  }
  $path = implode('/', $parts);
  if (($position = strpos($path, '?')) !== FALSE) {
    $path = substr($path, 0, $position);
  }
  return $path;
}

function route($route, $path_to_include, $middlewares = [])
{

  $route = rtrim($route, '/');

  if (in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1'))) {
    $ROOT = "http://localhost";
  } else {
    $ROOT = $_SERVER['DOCUMENT_ROOT'];
  }

  if ($route == "/404") {
    include_once("$path_to_include");
    exit();
  }

  $request_url = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
  $request_url = rtrim($request_url, '/');
  $request_url = strtok($request_url, '?');
  $route_parts = explode('/', $route);
  $request_url_parts = explode('/', $request_url);
  array_shift($route_parts);
  array_shift($request_url_parts);

  if (isset($route_parts[0]) && $route_parts[0] == '' && count($request_url_parts) == 0) {
    run_middleware($middlewares);
    include_once("$path_to_include");
    exit();
  }
  if (count($route_parts) != count($request_url_parts)) {
    return;
  }
  $parameters = [];
  for ($__i__ = 0; $__i__ < count($route_parts); $__i__++) {
    $route_part = $route_parts[$__i__];
    if (preg_match("/^[$]/", $route_part)) {
      $route_part = ltrim($route_part, '$');
      array_push($parameters, $request_url_parts[$__i__]);
      $$route_part = $request_url_parts[$__i__];
    } else if ($route_parts[$__i__] != $request_url_parts[$__i__]) {
      return;
    }
  }

  if (is_callable($path_to_include)) {
    run_middleware($middlewares);
    call_user_func($path_to_include);
    exit();
  }

  run_middleware($middlewares);
  include_once("$path_to_include");
  exit();
}
function out($text)
{
  echo htmlspecialchars($text);
}
function set_csrf()
{
  if (!isset($_SESSION["csrf"])) {
    $_SESSION["csrf"] = bin2hex(random_bytes(50));
  }
  echo '<input type="hidden" name="csrf" value="' . customEncryption($_SESSION["csrf"]) . '">';
}
function is_csrf_valid($csrf_token)
{
  if (!isset($_SESSION['csrf']) || !isset($csrf_token)) {
    return false;
  }
  if ($_SESSION['csrf'] != $csrf_token) {
    return false;
  }
  return true;
}