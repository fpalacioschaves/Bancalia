<?php
// /config.php
declare(strict_types=1);

/* ===== URLs base ===== */
if (!defined('BASE_URL'))   define('BASE_URL',   '/Bancalia');
if (!defined('PUBLIC_URL')) define('PUBLIC_URL', BASE_URL . '/public');

/* ===== Sesión ÚNICA y estable =====
   - Nombre fijo
   - Cookie válida en toda la ruta "/"
   - Limpieza de cookies “fantasma” que puedan pisar la sesión (PHPSESSID, etc.)
*/
if (session_status() !== PHP_SESSION_ACTIVE) {
  if (session_name() !== 'BANCALIASESSID') session_name('BANCALIASESSID');

  // fuerza path "/" desde el arranque
  ini_set('session.cookie_path', '/');
  ini_set('session.use_strict_mode', '1');

  session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',      // ¡crítico!
    'secure'   => false,    // true si usas HTTPS
    'httponly' => true,
    'samesite' => 'Lax',
  ]);

  session_start();

  // Si existen cookies “competidoras”, las anulamos
  if (isset($_COOKIE['PHPSESSID']) && session_name() !== 'PHPSESSID') {
    setcookie('PHPSESSID','', time()-42000, '/');
  }
  if (isset($_COOKIE['bancalia_sess'])) {
    setcookie('bancalia_sess','', time()-42000, '/');
  }
}

/* ===== DB ===== */
function pdo(): PDO {
  static $pdo;
  if ($pdo instanceof PDO) return $pdo;
  $pdo = new PDO(
    'mysql:host=127.0.0.1;dbname=bancalia;charset=utf8mb4',
    'root', '',
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ]
  );
  return $pdo;
}

/* ===== Helpers ===== */
function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function flash(string $key, ?string $value=null): ?string {
  if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) $_SESSION['flash'] = [];
  if ($value === null) {
    $msg = $_SESSION['flash'][$key] ?? null;
    if (array_key_exists($key, $_SESSION['flash'])) {
      unset($_SESSION['flash'][$key]);
      if (!$_SESSION['flash']) unset($_SESSION['flash']);
    }
    return $msg;
  }
  $_SESSION['flash'][$key] = $value;
  return null;
}

function current_user(): ?array { return $_SESSION['user'] ?? null; }

function require_login_or_redirect(): void {
  if (!current_user()) {
    flash('error','Debes iniciar sesión.');
    // Usa URL ABSOLUTA para evitar líos de host/alias
    $target = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://')
            . ($_SERVER['HTTP_HOST'] ?? 'localhost')
            . PUBLIC_URL . '/auth/login.php';
    header('Location: ' . $target);
    exit;
  }
}

function login_user(string $email, string $pass): void {
  $st = pdo()->prepare('SELECT * FROM users WHERE email=:e LIMIT 1');
  $st->execute([':e'=>mb_strtolower(trim($email))]);
  $u = $st->fetch();
  if (!$u || !password_verify($pass, $u['password_hash'])) {
    throw new RuntimeException('Credenciales inválidas.');
  }
  if ((int)($u['is_active'] ?? 1) !== 1) {
    throw new RuntimeException('Usuario inactivo. Contacta con el administrador.');
  }
  session_regenerate_id(true);
  $_SESSION['user'] = [
    'id'          => (int)$u['id'],
    'nombre'      => $u['nombre'] ?? null,
    'email'       => $u['email'],
    'role'        => $u['role'] ?? 'profesor',
    'profesor_id' => isset($u['profesor_id']) ? (int)$u['profesor_id'] : null,
  ];
}

function logout_user(): void {
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
  }
  setcookie('PHPSESSID','', time()-42000, '/');
  setcookie('bancalia_sess','', time()-42000, '/');
  if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');
}

function register_user(string $nombre, string $email, string $pass): int {
  $email = mb_strtolower(trim($email));
  if ($email === '' || $pass === '' || trim($nombre) === '') {
    throw new RuntimeException('Nombre, email y contraseña son obligatorios.');
  }
  $st = pdo()->prepare('SELECT 1 FROM users WHERE email=:e LIMIT 1');
  $st->execute([':e'=>$email]);
  if ($st->fetch()) throw new RuntimeException('El email ya está registrado.');

  $hash = password_hash($pass, PASSWORD_DEFAULT);
  $st = pdo()->prepare('INSERT INTO users (nombre,email,password_hash,role,is_active,created_at,updated_at)
                        VALUES (:n,:e,:h,"profesor",1,NOW(),NOW())');
  $st->execute([':n'=>$nombre, ':e'=>$email, ':h'=>$hash]);
  return (int)pdo()->lastInsertId();
}

// -----------------------------------------------------------------------------
// CSRF HELPERS
// -----------------------------------------------------------------------------
if (!function_exists('csrf_token')) {
  function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
  }
}

if (!function_exists('csrf_field')) {
  function csrf_field(): string {
    $token = csrf_token();
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
  }
}

if (!function_exists('csrf_check')) {
  function csrf_check(?string $token): void {
    $valid = isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
    if (!$valid) {
      throw new RuntimeException('CSRF token inválido o ausente.');
    }
  }
}

