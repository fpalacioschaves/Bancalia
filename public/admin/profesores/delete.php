<?php
// /public/admin/profesores/delete.php
declare(strict_types=1);
require_once __DIR__ . '/../../../middleware/require_admin.php';

try {
  csrf_check($_POST['csrf'] ?? null);
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) throw new RuntimeException('ID inválido.');

  // (Si luego creamos profesor_asignacion, aquí podemos impedir borrar si tiene asignaciones activas)

  $del = pdo()->prepare('DELETE FROM profesores WHERE id=:id LIMIT 1');
  $del->execute([':id'=>$id]);

  flash('success', 'Profesor eliminado.');
} catch (Throwable $e) {
  flash('error', $e->getMessage());
}
header('Location: ' . PUBLIC_URL . '/admin/profesores/index.php');
exit;
