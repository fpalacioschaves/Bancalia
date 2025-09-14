<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../inc/auth.php';
require_login();
if (!is_admin()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Solo admin']); exit; }

try {
  $page = max(1, (int)($_GET['page'] ?? 1));
  $per  = min(100, max(1, (int)($_GET['per_page'] ?? 10)));
  $q    = trim((string)($_GET['q'] ?? ''));

  $where = [];
  $args  = [];
  if ($q !== '') { $where[] = 'g.nombre LIKE ?'; $args[] = "%$q%"; }
  $wsql = $where ? 'WHERE '.implode(' AND ', $where) : '';

  // total
  $st = $pdo->prepare("SELECT COUNT(*) FROM grados g $wsql");
  $st->execute($args);
  $total = (int)$st->fetchColumn();

  // data
  $offset = ($page - 1) * $per;
  $st = $pdo->prepare("
    SELECT g.id, g.nombre, COALESCE(c.cursos,0) AS cursos
    FROM grados g
    LEFT JOIN (
      SELECT grado_id, COUNT(*) AS cursos
      FROM cursos
      GROUP BY grado_id
    ) c ON c.grado_id = g.id
    $wsql
    ORDER BY g.nombre
    LIMIT $per OFFSET $offset
  ");
  $st->execute($args);
  $rows = $st->fetchAll();

  echo json_encode(['ok'=>true,'data'=>$rows,'total'=>$total,'page'=>$page,'per_page'=>$per], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
