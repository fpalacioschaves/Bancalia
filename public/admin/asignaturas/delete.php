<?php
// /public/admin/asignaturas/delete.php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();
$u = current_user();

/*if (($u['role'] ?? '') !== 'admin') {
  flash('error', 'Acceso restringido a administradores.');
  header('Location: ' . PUBLIC_URL . '/dashboard.php');
  exit;
}*/

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash('error', 'Método no permitido.');
    header('Location: ' . PUBLIC_URL . '/admin/asignaturas/index.php');
    exit;
  }

  csrf_check($_POST['csrf'] ?? null);

  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) {
    throw new RuntimeException('ID inválido.');
  }

  $st = pdo()->prepare('DELETE FROM asignaturas WHERE id = :id LIMIT 1');
  $st->execute([':id' => $id]);

  if ($st->rowCount() > 0) {
    flash('success', 'Asignatura eliminado correctamente.');
  } else {
    flash('error', 'No se encontró la asignatura.');
  }

} catch (PDOException $e) {
  if ($e->getCode() === '23000') {
    flash('error', 'No se puede eliminar: la asignatura tiene elementos relacionados (temas, actividades...).');
  } else {
    flash('error', 'Error de base de datos: ' . $e->getMessage());
  }
} catch (Throwable $e) {
  flash('error', $e->getMessage());
}

header('Location: ' . PUBLIC_URL . '/admin/asignaturas/index.php');
exit;
