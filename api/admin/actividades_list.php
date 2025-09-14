<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../inc/auth.php';
require_login();
if (!is_admin()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Solo admin']); exit; }

try {
  $page  = max(1, (int)($_GET['page'] ?? 1));
  $per   = min(100, max(1, (int)($_GET['per_page'] ?? 10)));
  $q     = trim((string)($_GET['q'] ?? ''));
  $tipo  = (int)($_GET['tipo_id'] ?? 0);
  $autor = (int)($_GET['autor_id'] ?? 0);
  $vis   = trim((string)($_GET['visibilidad'] ?? ''));
  $est   = trim((string)($_GET['estado'] ?? ''));

  $where = [];
  $args  = [];
  if ($q !== '')        { $where[] = 'a.titulo LIKE ?'; $args[] = "%$q%"; }
  if ($tipo > 0)        { $where[] = 'a.tipo_id = ?';   $args[] = $tipo; }
  if ($autor > 0)       { $where[] = 'a.autor_id = ?';  $args[] = $autor; }
  if ($vis !== '')      { $where[] = 'a.visibilidad = ?'; $args[] = $vis; }
  if ($est !== '')      { $where[] = 'a.estado = ?';      $args[] = $est; }
  $wsql = $where ? 'WHERE '.implode(' AND ', $where) : '';

  // Total
  $st = $pdo->prepare("SELECT COUNT(*) FROM actividades a $wsql");
  $st->execute($args);
  $total = (int)$st->fetchColumn();

  // Datos
  $offset = ($page - 1) * $per;
  $sql = "
    SELECT a.id, a.titulo, a.visibilidad, a.estado, a.creado_en,
           a.tipo_id, t.nombre AS tipo_nombre, t.clave AS tipo_clave,
           u.id AS autor_id, u.nombre AS autor_nombre, u.email AS autor_email
    FROM actividades a
    LEFT JOIN actividad_tipos t ON t.id = a.tipo_id
    JOIN usuarios u ON u.id = a.autor_id
    $wsql
    ORDER BY a.creado_en DESC, a.id DESC
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
