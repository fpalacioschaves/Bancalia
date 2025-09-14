<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../inc/auth.php';
require_login();
if (!is_admin()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Solo admin']); exit; }

try {
  $page    = max(1, (int)($_GET['page'] ?? 1));
  $per     = min(100, max(1, (int)($_GET['per_page'] ?? 10)));
  $gradoId = (int)($_GET['grado_id'] ?? 0);
  $cursoId = (int)($_GET['curso_id'] ?? 0);
  $q       = trim((string)($_GET['q'] ?? ''));

  $joins = [];
  $where = [];
  $args  = [];

  if ($gradoId > 0) { $where[] = 'a.grado_id = ?'; $args[] = $gradoId; }
  if ($cursoId > 0) { $joins[] = 'JOIN asignatura_curso acf ON acf.asignatura_id=a.id AND acf.curso_id=?'; $args[] = $cursoId; }
  if ($q !== '')    { $where[] = 'a.nombre LIKE ?'; $args[] = "%$q%"; }

  $jSql = $joins ? ' '.implode(' ', $joins).' ' : '';
  $wSql = $where ? 'WHERE '.implode(' AND ', $where) : '';

  // total (distinct asignaturas)
  $st = $pdo->prepare("SELECT COUNT(DISTINCT a.id)
    FROM asignaturas a
    $jSql
    $wSql");
  $st->execute($args);
  $total = (int)$st->fetchColumn();

  $offset = ($page - 1) * $per;

  // data
  $sql = "
    SELECT
      a.id, a.nombre, a.codigo, a.grado_id, g.nombre AS grado,
      COALESCE(t.temas,0) AS temas,
      GROUP_CONCAT(DISTINCT c.id ORDER BY c.orden, c.nombre SEPARATOR ',')   AS cursos_ids,
      GROUP_CONCAT(DISTINCT c.nombre ORDER BY c.orden, c.nombre SEPARATOR ' | ') AS cursos_nombres
    FROM asignaturas a
    JOIN grados g ON g.id=a.grado_id
    LEFT JOIN asignatura_curso ac ON ac.asignatura_id=a.id
    LEFT JOIN cursos c ON c.id=ac.curso_id
    LEFT JOIN (
      SELECT asignatura_id, COUNT(*) AS temas
      FROM temas GROUP BY asignatura_id
    ) t ON t.asignatura_id=a.id
    $jSql
    $wSql
    GROUP BY a.id, a.nombre, a.codigo, a.grado_id, g.nombre, t.temas
    ORDER BY g.nombre, a.nombre
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
