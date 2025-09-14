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

  $st = $pdo->prepare('SELECT id FROM cursos WHERE id=?');
  $st->execute([$id]);
  if (!$st->fetch()) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Curso no encontrado']); exit; }

  // Dependencias
  $stmt = $pdo->prepare('SELECT COUNT(*) FROM perfiles_alumno WHERE curso_id=?');
  $stmt->execute([$id]);
  $alumnos = (int)$stmt->fetchColumn();

  $stmt = $pdo->prepare('SELECT COUNT(*) FROM profesor_imparte WHERE curso_id=?');
  $stmt->execute([$id]);
  $prof = (int)$stmt->fetchColumn();

  if ($alumnos + $prof > 0) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>"No se puede eliminar: curso con $alumnos alumno(s) y $prof relación(es) de profesor."], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $st = $pdo->prepare('DELETE FROM cursos WHERE id=?');
  $st->execute([$id]);

  echo json_encode(['ok'=>true,'deleted_id'=>$id], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
