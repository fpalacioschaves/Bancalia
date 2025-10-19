<?php
// /public/admin/temas/delete.php
declare(strict_types=1);
require_once __DIR__ . '/../../../middleware/require_admin.php';

try {
  csrf_check($_POST['csrf'] ?? null);
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) throw new RuntimeException('ID inválido.');

  // (Opcional) Verificar dependencias si más adelante cuelgan recursos de un tema.

  $del = pdo()->prepare('DELETE FROM temas WHERE id=:id LIMIT 1');
  $del->execute([':id'=>$id]);

  flash('success','Tema eliminado.');
} catch (Throwable $e) {
  flash('error', $e->getMessage());
}
header('Location: ' . PUBLIC_URL . '/admin/temas/index.php');
exit;
