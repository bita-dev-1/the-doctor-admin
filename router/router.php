<?php

// 1. Calculate Project Root based on this file's location
// router.php is inside /router folder, so dirname(__DIR__) gives the project root
$ROUTER_ROOT = dirname(__DIR__);

// 2. Include Dependencies using Absolute Paths
if (file_exists($ROUTER_ROOT . '/config/encryption.core.php')) {
  include_once($ROUTER_ROOT . '/config/encryption.core.php');
}

if (!class_exists('DB')) {
  if (file_exists($ROUTER_ROOT . '/config/DB.php')) {
    include_once($ROUTER_ROOT . '/config/DB.php');
  }
}

// --- Helper Functions ---
function get($route, $path_to_include, $middlewares = [])
{
  if ($_SERVER['REQUEST_METHOD'] == 'GET')
    route($route, $path_to_include, $middlewares);
}
function post($route, $path_to_include, $middlewares = [])
{
  if ($_SERVER['REQUEST_METHOD'] == 'POST')
    route($route, $path_to_include, $middlewares);
}
function put($route, $path_to_include, $middlewares = [])
{
  if ($_SERVER['REQUEST_METHOD'] == 'PUT')
    route($route, $path_to_include, $middlewares);
}
function patch($route, $path_to_include, $middlewares = [])
{
  if ($_SERVER['REQUEST_METHOD'] == 'PATCH')
    route($route, $path_to_include, $middlewares);
}
function delete($route, $path_to_include, $middlewares = [])
{
  if ($_SERVER['REQUEST_METHOD'] == 'DELETE')
    route($route, $path_to_include, $middlewares);
}
function any($route, $path_to_include, $middlewares = [])
{
  route($route, $path_to_include, $middlewares);
}

// --- Middleware Handler ---
function run_middleware($middlewares)
{
  global $ROUTER_ROOT; // Access the root path

  if (empty($middlewares))
    return;

  // Auth Check
  if (in_array('auth', $middlewares) && !isset($_SESSION['user']['id'])) {
    header('Location: ' . SITE_URL . '/login');
    exit();
  }

  // Real-time DB Check & Session Refresh
  if (isset($_SESSION['user']['id'])) {
    $db = (isset($GLOBALS['db']) && $GLOBALS['db'] instanceof DB) ? $GLOBALS['db'] : new DB();
    $userId = intval($_SESSION['user']['id']);
    $sql = "SELECT u.id, u.role, u.cabinet_id, u.status, u.deleted, c.kine_enabled 
            FROM users u 
            LEFT JOIN cabinets c ON u.cabinet_id = c.id 
            WHERE u.id = $userId";

    $freshData = $db->select($sql)[0] ?? null;

    if (!$freshData || $freshData['deleted'] == 1 || $freshData['status'] !== 'active') {
      session_destroy();
      header('Location: ' . SITE_URL . '/login');
      exit();
    }
    $_SESSION['user']['role'] = $freshData['role'];
    $_SESSION['user']['cabinet_id'] = $freshData['cabinet_id'];
    $_SESSION['user']['kine_enabled'] = $freshData['kine_enabled'];
  }

  // Permission Checks
  foreach ($middlewares as $mw) {
    switch ($mw) {
      case 'auth':
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
        $is_super = ($_SESSION['user']['role'] === 'admin' && empty($_SESSION['user']['cabinet_id']));
        $kine_active = isset($_SESSION['user']['kine_enabled']) && $_SESSION['user']['kine_enabled'] == 1;

        if (!$is_super && !$kine_active) {
          // --- FIX: Use Absolute Path for 404 ---
          $view404 = $ROUTER_ROOT . '/views/404.php';
          if (file_exists($view404)) {
            include_once($view404);
          } else {
            echo "404 Not Found (Access Denied)";
          }
          exit();
        }
        break;
    }
  }
}

function request_path()
{
  $request_uri = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
  $script_name = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'));
  $parts = array_diff_assoc($request_uri, $script_name);
  if (empty($parts))
    return '/';
  $path = implode('/', $parts);
  if (($position = strpos($path, '?')) !== FALSE)
    $path = substr($path, 0, $position);
  return $path;
}

function route($route, $path_to_include, $middlewares = [])
{
  global $ROUTER_ROOT; // Access the root path

  $route = rtrim($route, '/');

  // --- FIX: Build Absolute Path for Include ---
  $full_path_to_include = $path_to_include;

  if (!is_callable($path_to_include)) {
    // If path is relative (doesn't start with / or X:\), prepend root
    // Check for Windows drive letter (X:) or Unix slash (/)
    $is_absolute = (strpos($path_to_include, ':') !== false) || (strpos($path_to_include, '/') === 0);

    if (!$is_absolute) {
      $full_path_to_include = $ROUTER_ROOT . '/' . ltrim($path_to_include, '/');
    }
  }
  // --------------------------------------------

  // Handle 404 Route Definition
  if ($route == "/404") {
    if (!is_callable($path_to_include) && file_exists($full_path_to_include)) {
      include_once($full_path_to_include);
    } else {
      echo "404 Not Found";
    }
    exit();
  }

  // URL Matching
  $request_url = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
  $request_url = rtrim($request_url, '/');
  $request_url = strtok($request_url, '?');
  $route_parts = explode('/', $route);
  $request_url_parts = explode('/', $request_url);
  array_shift($route_parts);
  array_shift($request_url_parts);

  if (isset($route_parts[0]) && $route_parts[0] == '' && count($request_url_parts) == 0) {
    run_middleware($middlewares);
    if (file_exists($full_path_to_include)) {
      include_once($full_path_to_include);
    } else {
      die("Error: File not found: " . $full_path_to_include);
    }
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

  // --- FIX: Include using Absolute Path ---
  if (file_exists($full_path_to_include)) {
    include_once($full_path_to_include);
  } else {
    die("Error: View file not found: " . $full_path_to_include);
  }
  exit();
}

function out($text)
{
  echo htmlspecialchars($text);
}
function set_csrf()
{
  if (!isset($_SESSION["csrf"]))
    $_SESSION["csrf"] = bin2hex(random_bytes(50));
  echo '<input type="hidden" name="csrf" value="' . customEncryption($_SESSION["csrf"]) . '">';
}
function is_csrf_valid($csrf_token)
{
  if (!isset($_SESSION['csrf']) || !isset($csrf_token))
    return false;
  return $_SESSION['csrf'] == $csrf_token;
}
?>