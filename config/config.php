<?php
// /config/config.php
declare(strict_types=1);

ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
session_name('bancalia_sess');
session_start([
  'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
  'cookie_samesite' => 'Lax',
]);

define('APP_ENV', 'dev');

// --- URLs base (sin duplicar /public) ---
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script = rtrim($_SERVER['SCRIPT_NAME'] ?? '/', '/');           // p.ej. /Bancalia/public/auth/login.php
// quita /public y lo que venga detrás → queda /Bancalia
$basePath = preg_replace('#/public(?:/.*)?$#', '', $script);     // robusto tanto si estás en /public como /public/loquesea
define('BASE_URL', rtrim("$scheme://$host$basePath", '/'));      // p.ej. http://localhost/Bancalia
define('PUBLIC_URL', BASE_URL.'/public');                        // p.ej. http://localhost/Bancalia/public

// --- DB ---
define('DB_DSN', 'mysql:host=127.0.0.1;port=3306;dbname=bancalia;charset=utf8mb4');
define('DB_USER', 'root');
define('DB_PASS', '');

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/str.php';
