<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require __DIR__.'/../inc/db.php';

try {
  $st = $pdo->query("SELECT id, nombre FROM grados ORDER BY nombre");
  echo json_encode(['ok'=>true,'data'=>$st->fetchAll()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
