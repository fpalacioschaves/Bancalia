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

  $pid = (int)($_POST['profesor_id'] ?? 0);
  if ($pid<=0){ http_response_code(400); echo json_encode(['ok'=>false,'error'=>'profesor_id requerido']); exit; }

  // profesor válido y rol=2
  $st = $pdo->prepare('SELECT rol_id FROM usuarios WHERE id=?'); $st->execute([$pid]);
  $rol = (int)$st->fetchColumn();
  if ($rol !== 2){ http_response_code(400); echo json_encode(['ok'=>false,'error'=>'El usuario no es profesor']); exit; }

  $pairs = $_POST['pairs'] ?? [];
  if (!is_array($pairs)) $pairs = [$pairs];
  $pairs = array_values(array_unique(array_filter(array_map('strval',$pairs))));

  $pdo->beginTransaction();
  $pdo->prepare('DELETE FROM profesor_imparte WHERE profesor_id=?')->execute([$pid]);

  if ($pairs){
    $ins = $pdo->prepare('INSERT INTO profesor_imparte (profesor_id, grado_id, curso_id, asignatura_id) VALUES (?,?,?,?)');
    foreach ($pairs as $p){
      if (!preg_match('~^(\d+):(\d+)$~', $p, $m)) { continue; }
      $curso_id = (int)$m[1]; $asig_id = (int)$m[2];

      $st = $pdo->prepare('SELECT grado_id FROM cursos WHERE id=?'); $st->execute([$curso_id]);
      $grado_id = (int)$st->fetchColumn();
      if (!$grado_id){ $pdo->rollBack(); http_response_code(400); echo json_encode(['ok'=>false,'error'=>"Curso $curso_id inexistente"]); exit; }

      $st = $pdo->prepare('SELECT 1 FROM asignaturas WHERE id=? AND grado_id=?'); $st->execute([$asig_id,$grado_id]);
      if (!$st->fetch()){ $pdo->rollBack(); http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Asignatura no pertenece al grado del curso']); exit; }

      $st = $pdo->prepare('SELECT 1 FROM asignatura_curso WHERE asignatura_id=? AND curso_id=?');
      $st->execute([$asig_id,$curso_id]);
      if (!$st->fetch()){ $pdo->rollBack(); http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Asignatura no asociada a ese curso']); exit; }

      $ins->execute([$pid,$grado_id,$curso_id,$asig_id]);
    }
  }

  $pdo->commit();
  echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
  if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
