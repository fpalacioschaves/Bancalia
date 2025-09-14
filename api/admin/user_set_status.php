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
  $estado = trim((string)($_POST['estado'] ?? ''));

  if ($userId <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'user_id requerido']); exit; }
  if (!in_array($estado, ['activo','inactivo'], true)) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Estado inválido']); exit; }

  $st = $pdo->prepare('SELECT id, rol_id FROM usuarios WHERE id=?');
  $st->execute([$userId]);
  $target = $st->fetch();
  if (!$target) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Usuario no encontrado']); exit; }

  // No desactivarse a sí mismo
  if ($userId === (current_user()['id'] ?? 0) && $estado === 'inactivo') {
    http_response_code(400); echo json_encode(['ok'=>false,'error'=>'No puedes desactivar tu propia cuenta']); exit;
  }
  // No tocar otros admin
  if ((int)$target['rol_id'] === 1) {
    http_response_code(400); echo json_encode(['ok'=>false,'error'=>'No puedes modificar cuentas admin']); exit;
  }

  $st = $pdo->prepare('UPDATE usuarios SET estado=? WHERE id=?');
  $st->execute([$estado, $userId]);

  echo json_encode(['ok'=>true, 'user_id'=>$userId, 'estado'=>$estado], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
