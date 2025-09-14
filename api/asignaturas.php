<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require __DIR__.'/../inc/db.php';

$gradoId = filter_input(INPUT_GET, 'grado_id', FILTER_VALIDATE_INT);
if (!$gradoId) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'grado_id requerido'], JSON_UNESCAPED_UNICODE); exit; }

try {
  $st = $pdo->prepare("SELECT id, nombre FROM asignaturas WHERE grado_id=? ORDER BY nombre");
  $st->execute([$gradoId]);
  echo json_encode(['ok'=>true,'data'=>$st->fetchAll()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
