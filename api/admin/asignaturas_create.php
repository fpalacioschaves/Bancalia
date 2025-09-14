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

  $grado_id = (int)($_POST['grado_id'] ?? 0);
  $nombre   = trim((string)($_POST['nombre'] ?? ''));
  $codigo   = trim((string)($_POST['codigo'] ?? ''));

  if ($grado_id <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Selecciona un grado']); exit; }
  if ($nombre === '') { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'El nombre es obligatorio']); exit; }

  // Grado válido
  $st = $pdo->prepare('SELECT 1 FROM grados WHERE id=?');
  $st->execute([$grado_id]);
  if (!$st->fetch()) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Grado no válido']); exit; }

  // (Opcional) evitar duplicados por (grado_id, nombre)
  $st = $pdo->prepare('SELECT 1 FROM asignaturas WHERE grado_id=? AND nombre=?');
  $st->execute([$grado_id, $nombre]);
  if ($st->fetch()) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Ya existe esa asignatura en el grado']); exit; }

  $st = $pdo->prepare('INSERT INTO asignaturas (grado_id, nombre, codigo) VALUES (?,?,?)');
  $st->execute([$grado_id, $nombre, $codigo !== '' ? $codigo : null]);
  $id = (int)$pdo->lastInsertId();

  $st = $pdo->prepare('
    SELECT a.id, a.nombre, a.codigo, a.grado_id, g.nombre AS grado, 0 AS temas
    FROM asignaturas a JOIN grados g ON g.id=a.grado_id WHERE a.id=?');
  $st->execute([$id]);
  $row = $st->fetch();

  echo json_encode(['ok'=>true,'data'=>$row], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
