<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../inc/auth.php'; // expone $pdo

try {
  $grado = (int)($_GET['grado_id'] ?? 0);
  $where = $grado > 0 ? 'WHERE grado_id=?' : '';
  $args  = $grado > 0 ? [$grado] : [];

  $st = $pdo->prepare("SELECT id, nombre, grado_id, orden FROM cursos $where ORDER BY orden, nombre");
  $st->execute($args);
  echo json_encode(['ok'=>true, 'data'=>$st->fetchAll()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
