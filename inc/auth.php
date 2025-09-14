<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

require_once __DIR__ . '/db.php';

/** Devuelve array con el usuario logueado o null */
function current_user(): ?array {
  return $_SESSION['user'] ?? null;
}

/** true si hay sesión */
function is_logged_in(): bool {
  return isset($_SESSION['user']);
}

/** id de rol actual o null */
function current_role_id(): ?int {
  return $_SESSION['user']['rol_id'] ?? null;
}

/** ¿admin? (en tu BD: 1=admin, 2=profesor, 3=alumno) */
function is_admin(): bool {
  return current_role_id() === 1; // según dump roles. :contentReference[oaicite:1]{index=1}
}

/** Lanza 401/403 si no está logueado / no tiene rol requerido */
function require_login(): void {
  if (!is_logged_in()) {
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
      header('Location: /Bancalia/index.php');
    } else {
      http_response_code(401);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['ok'=>false,'error'=>'No autenticado']);
    }
    exit;
  }
}

/** Inicia sesión si credenciales válidas; lanza excepción si no lo son */
function login_with_credentials(PDO $pdo, string $email, string $password): array {
  $st = $pdo->prepare('SELECT id, nombre, email, password_hash, rol_id, estado FROM usuarios WHERE email = ? LIMIT 1');
  $st->execute([$email]);
  $u = $st->fetch();
  if (!$u || !password_verify($password, $u['password_hash'])) {
    throw new RuntimeException('Credenciales inválidas.');
  }
  if ($u['estado'] !== 'activo') {
    throw new RuntimeException('Cuenta no activa.');
  }
  // Persistir sólo lo necesario
  $_SESSION['user'] = [
    'id'     => (int)$u['id'],
    'nombre' => $u['nombre'],
    'email'  => $u['email'],
    'rol_id' => (int)$u['rol_id'],
  ];
  return $_SESSION['user'];
}

/** Cerrar sesión */
function logout(): void {
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
  }
  session_destroy();
}
