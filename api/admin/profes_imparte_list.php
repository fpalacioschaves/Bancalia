<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../inc/auth.php';
require_login();
if (!is_admin()) { http_response_code(403); echo json_encode(['ok'=>false,'error'=>'Solo admin']); exit; }

try {
  $pid = (int)($_GET['profesor_id'] ?? 0);
  if ($pid<=0){ http_response_code(400); echo json_encode(['ok'=>false,'error'=>'profesor_id requerido']); exit; }

  $sql = "
    SELECT pi.profesor_id, pi.grado_id, g.nombre AS grado,
           pi.curso_id, c.nombre AS curso,
           pi.asignatura_id, a.nombre AS asignatura
    FROM profesor_imparte pi
    JOIN grados g ON g.id=pi.grado_id
    JOIN cursos c ON c.id=pi.curso_id
    JOIN asignaturas a ON a.id=pi.asignatura_id
    WHERE pi.profesor_id=?
    ORDER BY g.nombre, c.orden, c.nombre, a.nombre
  ";
  $st = $pdo->prepare($sql); $st->execute([$pid]);
  echo json_encode(['ok'=>true,'data'=>$st->fetchAll()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
