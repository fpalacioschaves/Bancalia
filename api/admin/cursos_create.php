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
  $orden    = isset($_POST['orden']) && $_POST['orden'] !== '' ? (int)$_POST['orden'] : null;

  if ($grado_id <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Selecciona un grado']); exit; }
  if ($nombre === '') { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'El nombre es obligatorio']); exit; }

  // Grado existe
  $st = $pdo->prepare('SELECT 1 FROM grados WHERE id=?');
  $st->execute([$grado_id]);
  if (!$st->fetch()) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Grado no válido']); exit; }

  if ($orden === null) {
    $st = $pdo->prepare('SELECT COALESCE(MAX(orden),0)+1 FROM cursos WHERE grado_id=?');
    $st->execute([$grado_id]);
    $orden = (int)$st->fetchColumn();
  }

  $st = $pdo->prepare('INSERT INTO cursos (grado_id, nombre, orden) VALUES (?,?,?)');
  $st->execute([$grado_id, $nombre, $orden]);
  $id = (int)$pdo->lastInsertId();

  $st = $pdo->prepare('SELECT c.id, c.nombre, c.orden, c.grado_id, g.nombre AS grado FROM cursos c JOIN grados g ON g.id=c.grado_id WHERE c.id=?');
  $st->execute([$id]);
  $row = $st->fetch();

  echo json_encode(['ok'=>true,'data'=>$row], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
