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

  $cursoIds = $_POST['curso_ids'] ?? [];
  if (!is_array($cursoIds)) $cursoIds = [$cursoIds];
  $cursoIds = array_values(array_unique(array_map('intval', $cursoIds)));
  $cursoIds = array_filter($cursoIds, fn($v)=>$v>0);

  if ($id <= 0)       { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'ID requerido']); exit; }
  if ($nombre === '') { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'El nombre es obligatorio']); exit; }

  $st = $pdo->prepare('SELECT id, grado_id FROM asignaturas WHERE id=?');
  $st->execute([$id]);
  $row = $st->fetch();
  if (!$row) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'Asignatura no encontrada']); exit; }

  $new_grado = $grado_id ?? (int)$row['grado_id'];

  // Validar grado
  $st = $pdo->prepare('SELECT 1 FROM grados WHERE id=?');
  $st->execute([$new_grado]);
  if (!$st->fetch()) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Grado no válido']); exit; }

  // Duplicado (nombre dentro del grado)
  $st = $pdo->prepare('SELECT 1 FROM asignaturas WHERE grado_id=? AND nombre=? AND id<>?');
  $st->execute([$new_grado, $nombre, $id]);
  if ($st->fetch()) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Ya existe esa asignatura en el grado']); exit; }

  // Validar cursos (si vienen)
  if ($cursoIds) {
    $in = implode(',', array_fill(0, count($cursoIds), '?'));
    $params = $cursoIds; $params[] = $new_grado;
    $chk = $pdo->prepare("SELECT COUNT(*) FROM cursos WHERE id IN ($in) AND grado_id=?");
    $chk->execute($params);
    if ((int)$chk->fetchColumn() !== count($cursoIds)) {
      http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Algún curso no pertenece al grado seleccionado']); exit;
    }
  }

  $pdo->beginTransaction();

  $pdo->prepare('UPDATE asignaturas SET nombre=?, codigo=?, grado_id=? WHERE id=?')
      ->execute([$nombre, $codigo !== '' ? $codigo : null, $new_grado, $id]);

  // Reemplazar vínculos de cursos
  $pdo->prepare('DELETE FROM asignatura_curso WHERE asignatura_id=?')->execute([$id]);
  if ($cursoIds) {
    $ins = $pdo->prepare('INSERT INTO asignatura_curso (asignatura_id, curso_id) VALUES (?,?)');
    foreach ($cursoIds as $cid) $ins->execute([$id, $cid]);
  }

  $pdo->commit();

  $st = $pdo->prepare("
    SELECT a.id, a.nombre, a.codigo, a.grado_id, g.nombre AS grado,
           (SELECT COUNT(*) FROM temas t WHERE t.asignatura_id=a.id) AS temas,
           (SELECT GROUP_CONCAT(c.id)    FROM asignatura_curso ac JOIN cursos c ON c.id=ac.curso_id WHERE ac.asignatura_id=a.id) AS cursos_ids,
           (SELECT GROUP_CONCAT(c.nombre SEPARATOR ' | ') FROM asignatura_curso ac JOIN cursos c ON c.id=ac.curso_id WHERE ac.asignatura_id=a.id) AS cursos_nombres
    FROM asignaturas a JOIN grados g ON g.id=a.grado_id WHERE a.id=?");
  $st->execute([$id]);
  $data = $st->fetch();

  echo json_encode(['ok'=>true,'data'=>$data], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
