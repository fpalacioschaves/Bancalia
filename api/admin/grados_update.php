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
  $id     = (int)($_POST['id'] ?? 0);
  $nombre = trim((string)($_POST['nombre'] ?? ''));

  if ($id <= 0)          { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'ID requerido']); exit; }
  if ($nombre === '')    { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'El nombre es obligatorio']); exit; }

  $st = $pdo->prepare('SELECT id FROM grados WHERE id=?');
  $st->execute([$id]);
  if (!$st->fetch()) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Grado no encontrado']); exit; }

  // (Opcional) Evitar duplicados de nombre (ignorando el mismo ID)
  $st = $pdo->prepare('SELECT 1 FROM grados WHERE nombre=? AND id<>?');
  $st->execute([$nombre, $id]);
  if ($st->fetch()) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Ya existe un grado con ese nombre']); exit; }

  $pdo->prepare('UPDATE grados SET nombre=? WHERE id=?')->execute([$nombre, $id]);

  // devolver con nº cursos
  $st = $pdo->prepare('SELECT COUNT(*) FROM cursos WHERE grado_id=?');
  $st->execute([$id]);
  $cursos = (int)$st->fetchColumn();

  echo json_encode(['ok'=>true,'data'=>['id'=>$id,'nombre'=>$nombre,'cursos'=>$cursos]], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
