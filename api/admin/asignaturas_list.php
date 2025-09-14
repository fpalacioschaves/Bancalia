<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../inc/auth.php';
require_login();
if (!is_admin()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Solo admin']); exit; }

try {
  $page   = max(1, (int)($_GET['page'] ?? 1));
  $per    = min(100, max(1, (int)($_GET['per_page'] ?? 10)));
  $grado  = (int)($_GET['grado_id'] ?? 0);
  $q      = trim((string)($_GET['q'] ?? ''));

  $where = [];
  $args  = [];
  if ($grado > 0) { $where[] = 'a.grado_id = ?'; $args[] = $grado; }
  if ($q !== '')  { $where[] = 'a.nombre LIKE ?'; $args[] = "%$q%"; }
  $wsql = $where ? 'WHERE '.implode(' AND ', $where) : '';

  // total
  $st = $pdo->prepare("SELECT COUNT(*) FROM asignaturas a $wsql");
  $st->execute($args);
  $total = (int)$st->fetchColumn();

  $offset = ($page - 1) * $per;
  $st = $pdo->prepare("
    SELECT a.id, a.nombre, a.codigo, a.grado_id, g.nombre AS grado,
           COALESCE(t.temas,0) AS temas
    FROM asignaturas a
    JOIN grados g ON g.id = a.grado_id
    LEFT JOIN (
      SELECT asignatura_id, COUNT(*) AS temas
      FROM temas
      GROUP BY asignatura_id
    ) t ON t.asignatura_id = a.id
    $wsql
    ORDER BY g.nombre, a.nombre
    LIMIT $per OFFSET $offset
  ");
  $st->execute($args);
  $rows = $st->fetchAll();

  echo json_encode(['ok'=>true,'data'=>$rows,'total'=>$total,'page'=>$page,'per_page'=>$per], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
