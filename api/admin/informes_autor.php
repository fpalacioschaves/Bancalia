<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../inc/auth.php';
require_login();
if (!is_admin()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Solo admin']); exit; }

try {
  $page = max(1, (int)($_GET['page'] ?? 1));
  $per  = min(100, max(1, (int)($_GET['per_page'] ?? 10)));
  $from = trim((string)($_GET['from'] ?? ''));
  $to   = trim((string)($_GET['to'] ?? ''));
  $tipo = (int)($_GET['tipo_id'] ?? 0);
  $vis  = trim((string)($_GET['visibilidad'] ?? ''));
  $autor= (int)($_GET['autor_id'] ?? 0);

  $where = ['u.rol_id = 2']; // solo profesores
  $args  = [];
  if ($from !== '') { $where[] = 'DATE(a.creado_en) >= ?'; $args[] = $from; }
  if ($to   !== '') { $where[] = 'DATE(a.creado_en) <= ?'; $args[] = $to; }
  if ($tipo > 0)    { $where[] = 'a.tipo_id = ?'; $args[] = $tipo; }
  if ($autor > 0)   { $where[] = 'a.autor_id = ?'; $args[] = $autor; }
  if ($vis !== '')  { $where[] = 'a.visibilidad = ?'; $args[] = $vis; }
  $wsql = 'WHERE '.implode(' AND ', $where);

  // total grupos
  $st = $pdo->prepare("
    SELECT COUNT(*) FROM (
      SELECT u.id
      FROM actividades a
      JOIN usuarios u ON u.id=a.autor_id
      $wsql
      GROUP BY u.id
    ) x
  ");
  $st->execute($args);
  $total = (int)$st->fetchColumn();

  $offset = ($page - 1) * $per;
  $sql = "
    SELECT
      u.id, u.nombre, u.email,
      COUNT(*) AS total,
      SUM(CASE WHEN a.visibilidad='compartida' THEN 1 ELSE 0 END) AS compartidas,
      SUM(CASE WHEN a.visibilidad='privada'   THEN 1 ELSE 0 END) AS privadas
    FROM actividades a
    JOIN usuarios u ON u.id = a.autor_id
    $wsql
    GROUP BY u.id, u.nombre, u.email
    ORDER BY total DESC, u.nombre ASC
    LIMIT $per OFFSET $offset
  ";
  $st = $pdo->prepare($sql);
  $st->execute($args);
  $rows = $st->fetchAll();

  echo json_encode(['ok'=>true,'data'=>$rows,'total'=>$total,'page'=>$page,'per_page'=>$per], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
