<?php
// /public/admin/profesores/delete.php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();
$u = current_user();

if (($u['role'] ?? '') !== 'admin') {
  flash('error', 'Acceso restringido a administradores.');
  header('Location: ' . PUBLIC_URL . '/dashboard.php');
  exit;
}

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash('error', 'Método no permitido.');
    header('Location: ' . PUBLIC_URL . '/admin/profesores/index.php');
    exit;
  }

  csrf_check($_POST['csrf'] ?? null);

  $id = (int) ($_POST['id'] ?? 0);
  if ($id <= 0)
    throw new RuntimeException('ID inválido.');

  $service = new ProfesorService(pdo());
  $service->delete($id);
  flash('success', 'Profesor eliminado correctamente.');

} catch (PDOException $e) {
  if ($e->getCode() === '23000') {
    flash('error', 'No se puede eliminar: el profesor tiene asignaciones/actividades relacionadas.');
  } else {
    flash('error', 'Error de base de datos: ' . $e->getMessage());
  }
} catch (Throwable $e) {
  flash('error', $e->getMessage());
}

header('Location: ' . PUBLIC_URL . '/admin/profesores/index.php');
exit;
