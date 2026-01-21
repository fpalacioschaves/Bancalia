<?php
// /public/admin/actividades/delete.php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();

$u = current_user();
$role = (string) ($u['role'] ?? '');
$profesorId = (int) ($u['profesor_id'] ?? 0);

// Admin: solo lectura → sin borrar
if ($role === 'admin') {
  flash('error', 'El administrador no puede borrar actividades (solo visualización).');
  header('Location: ' . PUBLIC_URL . '/admin/actividades/index.php');
  exit;
}

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash('error', 'Método no permitido.');
    header('Location: ' . PUBLIC_URL . '/admin/actividades/index.php');
    exit;
  }

  csrf_check($_POST['csrf'] ?? null);

  $id = (int) ($_POST['id'] ?? 0);
  if ($id <= 0)
    throw new RuntimeException('ID inválido.');

  $actividadService = new ActividadService(pdo());
  $actividad = $actividadService->find($id);

  if (!$actividad) {
    throw new RuntimeException('Actividad no encontrada.');
  }
  if ((int) $actividad['profesor_id'] !== $profesorId || $profesorId <= 0) {
    throw new RuntimeException('No tienes permisos para borrar esta actividad.');
  }

  $actividadService->delete($id);
  flash('success', 'Actividad eliminada correctamente.');

} catch (PDOException $e) {
  if ($e->getCode() === '23000') {
    flash('error', 'No se puede eliminar: la actividad tiene referencias relacionadas.');
  } else {
    flash('error', 'Error de base de datos: ' . $e->getMessage());
  }
} catch (Throwable $e) {
  flash('error', $e->getMessage());
}

header('Location: ' . PUBLIC_URL . '/admin/actividades/index.php');
exit;
