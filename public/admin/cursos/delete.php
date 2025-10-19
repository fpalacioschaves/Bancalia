<?php
// /public/admin/cursos/delete.php
declare(strict_types=1);
require_once __DIR__ . '/../../../middleware/require_admin.php';

try {
  csrf_check($_POST['csrf'] ?? null);
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) throw new RuntimeException('ID inválido.');

  // (Opcional) Comprobar dependencias antes de borrar (asignaturas, grupos, etc.)

  $del = pdo()->prepare('DELETE FROM cursos WHERE id=:id LIMIT 1');
  $del->execute([':id'=>$id]);

  flash('success','Curso eliminado.');
} catch (Throwable $e) {
  flash('error', $e->getMessage());
}
header('Location: ' . PUBLIC_URL . '/admin/cursos/index.php');
exit;
