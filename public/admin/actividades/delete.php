<?php
// /public/admin/actividades/delete.php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();

$u = current_user();
$role = (string)($u['role'] ?? '');
$profesorId = (int)($u['profesor_id'] ?? 0);

// Admin: solo lectura → sin borrar
if ($role === 'admin') {
  flash('error', 'El administrador no puede borrar actividades (solo visualización).');
  header('Location: ' . PUBLIC_URL . '/admin/actividades/index.php');
  exit;
}

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash('error', 'Método no permitido.');
    header('Location: ' . PUBLIC_URL . '/admin/actividades/index.php'); exit;
  }

  csrf_check($_POST['csrf'] ?? null);

  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) throw new RuntimeException('ID inválido.');

  // Confirmar propiedad (la actividad debe ser del profesor logado)
  $st = pdo()->prepare('SELECT id, profesor_id FROM actividades WHERE id = :id LIMIT 1');
  $st->execute([':id' => $id]);
  $act = $st->fetch();

  if (!$act) {
    throw new RuntimeException('Actividad no encontrada.');
  }
  if ((int)$act['profesor_id'] !== $profesorId || $profesorId <= 0) {
    throw new RuntimeException('No tienes permisos para borrar esta actividad.');
  }

  // Borrar SIEMPRE tablas hijas conocidas (idempotente y seguro)
  // Aunque existan FKs ON DELETE CASCADE, lo hacemos explícito para evitar residuos si algún FK no está.
  $delVF = pdo()->prepare('DELETE FROM actividades_vf   WHERE actividad_id = :aid');
  $delRC = pdo()->prepare('DELETE FROM actividades_rc   WHERE actividad_id = :aid');
  $delRH = pdo()->prepare('DELETE FROM actividades_rh   WHERE actividad_id = :aid');
  $delTA = pdo()->prepare('DELETE FROM actividades_tarea WHERE actividad_id = :aid');

  $params = [':aid' => $id];
  try { $delVF->execute($params); } catch (Throwable $e) { /* tabla puede no existir */ }
  try { $delRC->execute($params); } catch (Throwable $e) { /* tabla puede no existir */ }
  try { $delRH->execute($params); } catch (Throwable $e) { /* tabla puede no existir */ }
  try { $delTA->execute($params); } catch (Throwable $e) { /* tabla puede no existir */ }

  // Si añades más subtipos en el futuro, elimínalos aquí:
  // try { pdo()->prepare('DELETE FROM actividades_om WHERE actividad_id=:aid')->execute($params); } catch (Throwable $e) {}
  // try { pdo()->prepare('DELETE FROM actividades_emparejar WHERE actividad_id=:aid')->execute($params); } catch (Throwable $e) {}

  // Borrar actividad
  $del = pdo()->prepare('DELETE FROM actividades WHERE id = :id LIMIT 1');
  $del->execute([':id' => $id]);

  if ($del->rowCount() > 0) {
    flash('success', 'Actividad eliminada correctamente.');
  } else {
    flash('error', 'No se pudo eliminar la actividad.');
  }

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
