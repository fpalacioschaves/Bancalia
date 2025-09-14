<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../inc/auth.php';
require_login();
if (!is_admin()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Solo admin']); exit; }

try {
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Método no permitido']); exit;
  }

  $userId = (int)($_POST['user_id'] ?? 0);
  if ($userId <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'user_id requerido']); exit; }

  $st = $pdo->prepare('SELECT id, rol_id FROM usuarios WHERE id=?');
  $st->execute([$userId]);
  $target = $st->fetch();
  if (!$target) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Usuario no encontrado']); exit; }

  // No borrarse a sí mismo
  if ($userId === (current_user()['id'] ?? 0)) {
    http_response_code(400); echo json_encode(['ok'=>false,'error'=>'No puedes eliminar tu propia cuenta']); exit;
  }
  // No borrar admins
  if ((int)$target['rol_id'] === 1) {
    http_response_code(400); echo json_encode(['ok'=>false,'error'=>'No puedes eliminar cuentas admin']); exit;
  }

  $pdo->beginTransaction();
  // Dependencias conocidas (ajusta si tu esquema difiere)
  $pdo->prepare('DELETE FROM profesor_imparte  WHERE profesor_id=?')->execute([$userId]);
  $pdo->prepare('DELETE FROM perfiles_profesor WHERE usuario_id=?')->execute([$userId]);
  $pdo->prepare('DELETE FROM perfiles_alumno   WHERE usuario_id=?')->execute([$userId]);
  // Finalmente, el usuario
  $pdo->prepare('DELETE FROM usuarios WHERE id=?')->execute([$userId]);
  $pdo->commit();

  echo json_encode(['ok'=>true, 'deleted_id'=>$userId], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
