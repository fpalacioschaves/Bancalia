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
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'ID requerido']); exit; }

  $st = $pdo->prepare('SELECT id, titulo FROM actividades WHERE id=?');
  $st->execute([$id]);
  $act = $st->fetch();
  if (!$act) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Actividad no encontrada']); exit; }

  // Dependencias que NO tienen ON DELETE CASCADE (según tu SQL):
  $st = $pdo->prepare('SELECT COUNT(*) FROM examenes_actividades WHERE actividad_id=?');
  $st->execute([$id]);
  $ex_cnt = (int)$st->fetchColumn();

  if ($ex_cnt > 0) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>"No se puede eliminar: forma parte de $ex_cnt examen(es)."], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // El resto de tablas relacionadas llevan CASCADE; borramos la actividad
  $del = $pdo->prepare('DELETE FROM actividades WHERE id=?');
  $del->execute([$id]);

  echo json_encode(['ok'=>true,'deleted_id'=>$id], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
