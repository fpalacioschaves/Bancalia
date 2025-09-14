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

  $st = $pdo->prepare('SELECT id FROM asignaturas WHERE id=?');
  $st->execute([$id]);
  if (!$st->fetch()) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Asignatura no encontrada']); exit; }

  // Dependencias: temas, RA, perfiles_profesor
  $stmt = $pdo->prepare('SELECT COUNT(*) FROM temas WHERE asignatura_id=?');
  $stmt->execute([$id]); $temas = (int)$stmt->fetchColumn();

  $stmt = $pdo->prepare('SELECT COUNT(*) FROM ra WHERE asignatura_id=?');
  $stmt->execute([$id]); $ras = (int)$stmt->fetchColumn();

  $stmt = $pdo->prepare('SELECT COUNT(*) FROM perfiles_profesor WHERE asignatura_id=?');
  $stmt->execute([$id]); $prof = (int)$stmt->fetchColumn();

  if ($temas + $ras + $prof > 0) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>"No se puede eliminar: $temas tema(s), $ras RA(s) y $prof relación(es) de profesor asociadas."], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $st = $pdo->prepare('DELETE FROM asignaturas WHERE id=?');
  $st->execute([$id]);

  echo json_encode(['ok'=>true,'deleted_id'=>$id], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
