<?php
// /middleware/require_admin.php
declare(strict_types=1);
require_once __DIR__ . '/require_auth.php';
$u = current_user();
if (!$u || $u['role'] !== 'admin') {
  flash('error', 'Acceso restringido a administradores.');
  header('Location: '.PUBLIC_URL.'/dashboard.php');
  exit;
}
