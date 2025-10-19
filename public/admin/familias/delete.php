<?php
// /public/admin/familias/delete.php
declare(strict_types=1);
require_once __DIR__ . '/../../../middleware/require_admin.php';

try {
  csrf_check($_POST['csrf'] ?? null);
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) throw new RuntimeException('ID inválido.');

  // (Opcional) Aquí podrías comprobar que no existan ciclos/grados vinculados.

  $del = pdo()->prepare('DELETE FROM familias_profesionales WHERE id=:id LIMIT 1');
  $del->execute([':id'=>$id]);

  flash('success','Familia eliminada.');
} catch (Throwable $e) {
  flash('error',$e->getMessage());
}
header('Location: '.PUBLIC_URL.'/admin/familias/index.php');
exit;
