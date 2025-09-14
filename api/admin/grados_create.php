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
  $nombre = trim((string)($_POST['nombre'] ?? ''));
  if ($nombre === '') { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'El nombre es obligatorio']); exit; }

  // (Opcional) Evitar duplicados de nombre
  $st = $pdo->prepare('SELECT 1 FROM grados WHERE nombre = ?');
  $st->execute([$nombre]);
  if ($st->fetch()) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Ya existe un grado con ese nombre']); exit; }

  $pdo->prepare('INSERT INTO grados (nombre) VALUES (?)')->execute([$nombre]);
  $id = (int)$pdo->lastInsertId();

  echo json_encode(['ok'=>true,'data'=>['id'=>$id,'nombre'=>$nombre,'cursos'=>0]], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
