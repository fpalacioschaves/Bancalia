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
  $rol  = (int)($_GET['rol'] ?? 0);

  $where = [];
  $args  = [];
  if ($q !== '') {
    $where[] = '(u.nombre LIKE ? OR u.email LIKE ?)';
    $args[] = "%$q%";
    $args[] = "%$q%";
  }
  if (in_array($rol, [1,2,3], true)) {
    $where[] = 'u.rol_id = ?';
    $args[]  = $rol;
  }
  $wsql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

  $st = $pdo->prepare("SELECT COUNT(*) FROM usuarios u $wsql");
  $st->execute($args);
  $total = (int)$st->fetchColumn();

  $offset = ($page - 1) * $per;
  $st = $pdo->prepare("SELECT u.id, u.nombre, u.email, u.estado, u.rol_id, r.nombre AS rol
                       FROM usuarios u JOIN roles r ON r.id=u.rol_id
                       $wsql
                       ORDER BY u.creado_en DESC
                       LIMIT $per OFFSET $offset");
  $st->execute($args);
  $rows = $st->fetchAll();

  echo json_encode(['ok'=>true, 'data'=>$rows, 'total'=>$total, 'page'=>$page, 'per_page'=>$per], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false, 'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
