<?php
// /public/admin/centros/delete.php
declare(strict_types=1);

require_once __DIR__ . '/../../../middleware/require_auth.php';
require_once __DIR__ . '/../../../lib/auth.php';

$u = current_user();
if (!$u || !in_array(($u['role'] ?? ''), ['admin','profesor'], true)) {
  $_SESSION['flash'] = 'Acceso restringido.';
  header('Location: ' . PUBLIC_URL . '/auth/login.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  $_SESSION['flash'] = 'Método inválido.';
  header('Location: ' . PUBLIC_URL . '/admin/centros/index.php'); exit;
}

try {
  csrf_check($_POST['csrf'] ?? null);

  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) throw new RuntimeException('ID inválido.');

  // Si tu esquema tiene FK a centros (profesores, etc.), puede fallar por FK. Capturamos el error.
  $del = pdo()->prepare('DELETE FROM centros WHERE id=:id');
  $del->execute([':id'=>$id]);

  $_SESSION['flash'] = 'Centro eliminado.';
} catch (Throwable $e) {
  $_SESSION['flash'] = 'No se pudo eliminar: ' . $e->getMessage();
}
header('Location: ' . PUBLIC_URL . '/admin/centros/index.php'); exit;
