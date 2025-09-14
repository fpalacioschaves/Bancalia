<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../inc/auth.php';

try {
  $grado = (int)($_GET['grado_id'] ?? 0);
  $cids  = trim((string)($_GET['curso_ids'] ?? '')); // "1,2,3"
  $ids   = array_filter(array_map('intval', $cids ? explode(',', $cids) : []));

  $where = [];
  $args  = [];
  $join  = '';

  if ($grado > 0) { $where[] = 'a.grado_id=?'; $args[] = $grado; }
  if ($ids) { 
    $in = implode(',', array_fill(0, count($ids), '?'));
    $join = "JOIN asignatura_curso ac ON ac.asignatura_id=a.id JOIN cursos c ON c.id=ac.curso_id AND c.id IN ($in)";
    $args = array_merge($ids, $args);
  }

  $wsql = $where ? 'WHERE '.implode(' AND ', $where) : '';
  $sql = "
    SELECT DISTINCT a.id, a.nombre, a.codigo, a.grado_id
    FROM asignaturas a
    $join
    $wsql
    ORDER BY a.nombre
  ";

  $st = $pdo->prepare($sql);
  $st->execute($args);
  echo json_encode(['ok'=>true,'data'=>$st->fetchAll()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
