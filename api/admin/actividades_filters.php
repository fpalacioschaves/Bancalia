<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../inc/auth.php';
require_login();
if (!is_admin()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Solo admin']); exit; }

try {
  // Tipos
  $tipos = $pdo->query('SELECT id, clave, nombre FROM actividad_tipos ORDER BY id')->fetchAll();

  // Profesores
  $st = $pdo->prepare('SELECT id, nombre, email FROM usuarios WHERE rol_id=2 AND estado="activo" ORDER BY nombre');
  $st->execute();
  $profes = $st->fetchAll();

  echo json_encode(['ok'=>true,'tipos'=>$tipos,'profesores'=>$profes], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
