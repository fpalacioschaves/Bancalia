<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../inc/auth.php';
require_login();
if (!is_admin()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Solo admin']); exit; }

try {
  $from = trim((string)($_GET['from'] ?? ''));
  $to   = trim((string)($_GET['to'] ?? ''));
  $tipo = (int)($_GET['tipo_id'] ?? 0);
  $vis  = trim((string)($_GET['visibilidad'] ?? ''));
  $autor= (int)($_GET['autor_id'] ?? 0);

  $where = [];
  $args  = [];
  if ($from !== '') { $where[] = 'DATE(a.creado_en) >= ?'; $args[] = $from; }
  if ($to   !== '') { $where[] = 'DATE(a.creado_en) <= ?'; $args[] = $to; }
  if ($tipo > 0)    { $where[] = 'a.tipo_id = ?'; $args[] = $tipo; }
  if ($autor > 0)   { $where[] = 'a.autor_id = ?'; $args[] = $autor; }
  if ($vis !== '')  { $where[] = 'a.visibilidad = ?'; $args[] = $vis; }

  $wsql = $where ? 'WHERE '.implode(' AND ', $where) : '';

  $sql = "
    SELECT
      COUNT(*) AS total,
      SUM(CASE WHEN a.visibilidad='compartida' THEN 1 ELSE 0 END) AS compartidas,
      SUM(CASE WHEN a.visibilidad='privada' THEN 1 ELSE 0 END)    AS privadas,
      COUNT(DISTINCT a.autor_id) AS autores_activos,
      COUNT(DISTINCT a.tipo_id)  AS tipos_distintos
    FROM actividades a
    $wsql
  ";
  $st = $pdo->prepare($sql);
  $st->execute($args);
  $row = $st->fetch() ?: ['total'=>0,'compartidas'=>0,'privadas'=>0,'autores_activos'=>0,'tipos_distintos'=>0];

  echo json_encode(['ok'=>true,'data'=>$row], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
