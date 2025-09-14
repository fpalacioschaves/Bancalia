<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../inc/auth.php';
require_login();
if (!is_admin()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Solo admin']); exit; }

try {
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405); echo json_encode(['ok'=>false,'error'=>'Método no permitido']); exit;
  }

  $id       = (int)($_POST['id'] ?? 0);
  $nombre   = trim((string)($_POST['nombre'] ?? ''));
  $codigo   = trim((string)($_POST['codigo'] ?? ''));
  $grado_id = isset($_POST['grado_id']) && $_POST['grado_id'] !== '' ? (int)$_POST['grado_id'] : null;

  if ($id <= 0)       { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'ID requerido']); exit; }
  if ($nombre === '') { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'El nombre es obligatorio']); exit; }

  $st = $pdo->prepare('SELECT id, grado_id FROM asignaturas WHERE id=?');
  $st->execute([$id]);
  $row = $st->fetch();
  if (!$row) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Asignatura no encontrada']); exit; }

  $new_grado = $grado_id ?? (int)$row['grado_id'];

  // Grado válido
  $st = $pdo->prepare('SELECT 1 FROM grados WHERE id=?');
  $st->execute([$new_grado]);
  if (!$st->fetch()) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Grado no válido']); exit; }

  // Duplicados por (grado_id, nombre)
  $st = $pdo->prepare('SELECT 1 FROM asignaturas WHERE grado_id=? AND nombre=? AND id<>?');
  $st->execute([$new_grado, $nombre, $id]);
  if ($st->fetch()) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Ya existe esa asignatura en el grado']); exit; }

  $st = $pdo->prepare('UPDATE asignaturas SET nombre=?, codigo=?, grado_id=? WHERE id=?');
  $st->execute([$nombre, $codigo !== '' ? $codigo : null, $new_grado, $id]);

  $st = $pdo->prepare('
    SELECT a.id, a.nombre, a.codigo, a.grado_id, g.nombre AS grado,
           (SELECT COUNT(*) FROM temas t WHERE t.asignatura_id=a.id) AS temas
    FROM asignaturas a JOIN grados g ON g.id=a.grado_id WHERE a.id=?');
  $st->execute([$id]);
  $data = $st->fetch();

  echo json_encode(['ok'=>true,'data'=>$data], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
