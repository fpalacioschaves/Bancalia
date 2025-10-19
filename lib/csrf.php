<?php
// /lib/csrf.php
declare(strict_types=1);

function csrf_token(): string {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf'];
}

function csrf_field(): string {
  return '<input type="hidden" name="csrf" value="'.htmlspecialchars(csrf_token(), ENT_QUOTES).'">';
}

function csrf_check(?string $token): void {
  if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
    http_response_code(419);
    throw new RuntimeException('CSRF token inválido.');
  }
}
