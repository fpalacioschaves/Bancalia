<?php
// /public/admin/actividades/duplicate.php
declare(strict_types=1);

// RUTA CORRECTA → solo subir 3 niveles
require_once __DIR__ . '/../../../config.php';

require_login_or_redirect();

$u = current_user();
$role = $u['role'] ?? '';
$profesorId = (int) ($u['profesor_id'] ?? 0);
$centroId = (int) ($u['centro_id'] ?? 0);

if (!in_array($role, ['profesor', 'admin'], true)) {
  http_response_code(403);
  echo 'Acceso denegado';
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . PUBLIC_URL . '/dashboard.php');
  exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
  header('Location: ' . PUBLIC_URL . '/dashboard.php');
  exit;
}

try {
  $actividadService = new ActividadService(pdo());
  $act = $actividadService->find($id);

  if (!$act) {
    throw new RuntimeException('Actividad no encontrada');
  }

  // Comprobar permisos: propia o visible (si no es admin)
  if ($role !== 'admin') {
    $esMia = ((int) $act['profesor_id'] === $profesorId);
    $visible = in_array($act['visibilidad'], ['publica', 'centro'], true);
    if (!$esMia && !$visible) {
      throw new RuntimeException('No tienes permisos para duplicar esta actividad');
    }
  }

  $newId = $actividadService->duplicate($id, $profesorId, $centroId);

  header('Location: ' . PUBLIC_URL . '/admin/actividades/edit.php?id=' . $newId);
  exit;

} catch (Throwable $e) {
  echo 'Error al duplicar la actividad: ' . h($e->getMessage());
  exit;
}
