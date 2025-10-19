<?php
// /lib/auth.php
declare(strict_types=1);

function flash(string $key, ?string $value=null): ?string {
  if ($value === null) {
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
  }
  $_SESSION['flash'][$key] = $value;
  return null;
}

function current_user(): ?array {
  return $_SESSION['user'] ?? null;
}

function require_role(string ...$roles): void {
  $u = current_user();
  if (!$u || !in_array($u['role'], $roles, true)) {
    header('Location: '.BASE_URL.'/public/auth/login.php');
    exit;
  }
}

function register_user(string $nombre, string $email, string $pass, string $role='profesor'): int {
  $email = mb_strtolower(trim($email));
  $st = pdo()->prepare('SELECT id FROM users WHERE email=:e LIMIT 1');
  $st->execute([':e'=>$email]);
  if ($st->fetch()) {
    throw new RuntimeException('El email ya está registrado.');
  }
  $hash = password_hash($pass, PASSWORD_DEFAULT);
  $verify = bin2hex(random_bytes(32)); // si quieres verificación por email
  $st = pdo()->prepare('INSERT INTO users (nombre,email,password_hash,role,verify_token) VALUES (:n,:e,:h,:r,:v)');
  $st->execute([':n'=>$nombre, ':e'=>$email, ':h'=>$hash, ':r'=>$role, ':v'=>$verify]);
  return (int)pdo()->lastInsertId();
}

function login_user(string $email, string $pass): void {
  $st = pdo()->prepare('SELECT * FROM users WHERE email=:e LIMIT 1');
  $st->execute([':e'=>mb_strtolower(trim($email))]);
  $u = $st->fetch();
  if (!$u || !password_verify($pass, $u['password_hash'])) {
    throw new RuntimeException('Credenciales inválidas.');
  }
  if ((int)$u['is_active'] !== 1) {
    throw new RuntimeException('Usuario inactivo. Contacta con el administrador.');
  }
  $_SESSION['user'] = [
    'id' => (int)$u['id'],
    'nombre' => $u['nombre'],
    'email' => $u['email'],
    'role' => $u['role'],
  ];
}

function logout_user(): void {
  $_SESSION = [];
  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time()-42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
  }
  session_destroy();
}
