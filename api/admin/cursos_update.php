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
  $orden    = isset($_POST['orden']) && $_POST['orden'] !== '' ? (int)$_POST['orden'] : null;
  $grado_id = isset($_POST['grado_id']) && $_POST['grado_id'] !== '' ? (int)$_POST['grado_id'] : null;

  if ($id <= 0)         { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'ID requerido']); exit; }
  if ($nombre === '')   { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'El nombre es obligatorio']); exit; }

  $st = $pdo->prepare('SELECT id, grado_id, orden FROM cursos WHERE id=?');
  $st->execute([$id]);
  $curso = $st->fetch();
  if (!$curso) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Curso no encontrado']); exit; }

  $new_grado = $grado_id ?? (int)$curso['grado_id'];
  if ($new_grado <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Grado no válido']); exit; }

  // Si cambia de grado, valida dependencias
  if ($new_grado !== (int)$curso['grado_id']) {
    // Existen dependencias?
    $cntA = (int)$pdo->prepare('SELECT COUNT(*) FROM perfiles_alumno WHERE curso_id=?')->execute([$id]) ?: 0;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM perfiles_alumno WHERE curso_id=?');
    $stmt->execute([$id]);
    $alumnos = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM profesor_imparte WHERE curso_id=?');
    $stmt->execute([$id]);
    $prof = (int)$stmt->fetchColumn();

    if ($alumnos + $prof > 0) {
      http_response_code(400);
      echo json_encode(['ok'=>false,'error'=>'No puedes cambiar el grado de un curso con dependencias (alumnos/profesores asignados).'], JSON_UNESCAPED_UNICODE);
      exit;
    }

    // Grado destino existe
    $st = $pdo->prepare('SELECT 1 FROM grados WHERE id=?');
    $st->execute([$new_grado]);
    if (!$st->fetch()) {
      http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Grado destino no válido']); exit;
    }
  }

  if ($orden === null) { $orden = (int)$curso['orden']; }

  $st = $pdo->prepare('UPDATE cursos SET nombre=?, orden=?, grado_id=? WHERE id=?');
  $st->execute([$nombre, $orden, $new_grado, $id]);

  $st = $pdo->prepare('SELECT c.id, c.nombre, c.orden, c.grado_id, g.nombre AS grado FROM cursos c JOIN grados g ON g.id=c.grado_id WHERE c.id=?');
  $st->execute([$id]);
  $row = $st->fetch();

  echo json_encode(['ok'=>true,'data'=>$row], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
