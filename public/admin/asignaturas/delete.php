<?php
// /public/admin/asignaturas/delete.php
declare(strict_types=1);
require_once __DIR__ . '/../../../middleware/require_admin.php';

try {
  csrf_check($_POST['csrf'] ?? null);
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) throw new RuntimeException('ID inválido.');

  // (Opcional) Verifica dependencias (RA, actividades, etc.) antes de borrar.

  $del = pdo()->prepare('DELETE FROM asignaturas WHERE id=:id LIMIT 1');
  $del->execute([':id'=>$id]);

  flash('success', 'Asignatura eliminada.');
} catch (Throwable $e) {
  flash('error', $e->getMessage());
}
header('Location: ' . PUBLIC_URL . '/admin/asignaturas/index.php');
exit;
