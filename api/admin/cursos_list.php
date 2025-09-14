<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../inc/auth.php';
require_login();
if (!is_admin()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Solo admin']); exit; }

try {
  $page     = max(1, (int)($_GET['page'] ?? 1));
  $per      = min(100, max(1, (int)($_GET['per_page'] ?? 10)));
  $grado_id = (int)($_GET['grado_id'] ?? 0);
  $q        = trim((string)($_GET['q'] ?? ''));

  $where = [];
  $args  = [];
  if ($grado_id > 0) { $where[] = 'c.grado_id = ?'; $args[] = $grado_id; }
  if ($q !== '')     { $where[] = 'c.nombre LIKE ?'; $args[] = "%$q%"; }
  $wsql = $where ? 'WHERE '.implode(' AND ', $where) : '';

  $st = $pdo->prepare("SELECT COUNT(*) FROM cursos c $wsql");
  $st->execute($args);
  $total = (int)$st->fetchColumn();

  $offset = ($page - 1) * $per;
  $st = $pdo->prepare("
    SELECT c.id, c.nombre, c.orden, c.grado_id, g.nombre AS grado
    FROM cursos c
    JOIN grados g ON g.id = c.grado_id
    $wsql
    ORDER BY g.nombre, c.orden, c.nombre
    LIMIT $per OFFSET $offset
  ");
  $st->execute($args);
  $rows = $st->fetchAll();

  echo json_encode(['ok'=>true,'data'=>$rows,'total'=>$total,'page'=>$page,'per_page'=>$per], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
