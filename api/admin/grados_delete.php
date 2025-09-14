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

  $st = $pdo->prepare('SELECT id FROM grados WHERE id=?');
  $st->execute([$id]);
  if (!$st->fetch()) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Grado no encontrado']); exit; }

  // Dependencias
  $stmt = $pdo->prepare('SELECT COUNT(*) FROM cursos WHERE grado_id=?');
  $stmt->execute([$id]);
  $cursos = (int)$stmt->fetchColumn();

  $stmt = $pdo->prepare('SELECT COUNT(*) FROM asignaturas WHERE grado_id=?');
  $stmt->execute([$id]);
  $asigs = (int)$stmt->fetchColumn();

  $stmt = $pdo->prepare('SELECT COUNT(*) FROM profesor_imparte WHERE grado_id=?');
  $stmt->execute([$id]);
  $imparte = (int)$stmt->fetchColumn();

  if ($cursos + $asigs + $imparte > 0) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>"No se puede eliminar: $cursos curso(s), $asigs asignatura(s) y $imparte relación(es) de profesores asociadas."], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $pdo->prepare('DELETE FROM grados WHERE id=?')->execute([$id]);
  echo json_encode(['ok'=>true,'deleted_id'=>$id], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
