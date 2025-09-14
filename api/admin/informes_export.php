<?php
declare(strict_types=1);

require __DIR__ . '/../../inc/auth.php';
require_login();
if (!is_admin()) { http_response_code(403); die('Solo admin'); }

$group = ($_GET['group'] ?? '') === 'autor' ? 'autor' : 'tipo';

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

header('Content-Type: text/csv; charset=utf-8');
$fname = 'informes_' . $group . '_' . date('Ymd_His') . '.csv';
header('Content-Disposition: attachment; filename="'.$fname.'"');

$fp = fopen('php://output', 'w');
if ($group === 'tipo') {
  fputcsv($fp, ['Tipo','Total','Compartidas','Privadas']);
  $sql = "
    SELECT COALESCE(t.nombre,'Sin tipo') AS tipo,
           COUNT(*) AS total,
           SUM(CASE WHEN a.visibilidad='compartida' THEN 1 ELSE 0 END) AS compartidas,
           SUM(CASE WHEN a.visibilidad='privada'   THEN 1 ELSE 0 END) AS privadas
    FROM actividades a
    LEFT JOIN actividad_tipos t ON t.id=a.tipo_id
    $wsql
    GROUP BY tipo
    ORDER BY total DESC, tipo ASC
  ";
  $st = $pdo->prepare($sql); $st->execute($args);
  while ($r = $st->fetch()) {
    fputcsv($fp, [$r['tipo'], $r['total'], $r['compartidas'], $r['privadas']]);
  }
} else {
  fputcsv($fp, ['Profesor','Email','Total','Compartidas','Privadas']);
  $sql = "
    SELECT u.nombre, u.email,
           COUNT(*) AS total,
           SUM(CASE WHEN a.visibilidad='compartida' THEN 1 ELSE 0 END) AS compartidas,
           SUM(CASE WHEN a.visibilidad='privada'   THEN 1 ELSE 0 END) AS privadas
    FROM actividades a
    JOIN usuarios u ON u.id=a.autor_id
    WHERE u.rol_id=2 " . ($wsql ? ' AND '.substr($wsql, 6) : '') . "
    GROUP BY u.nombre, u.email
    ORDER BY total DESC, u.nombre ASC
  ";
  $st = $pdo->prepare($sql); $st->execute($args);
  while ($r = $st->fetch()) {
    fputcsv($fp, [$r['nombre'], $r['email'], $r['total'], $r['compartidas'], $r['privadas']]);
  }
}
fclose($fp);
