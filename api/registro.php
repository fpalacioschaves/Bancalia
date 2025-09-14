<?php
declare(strict_types=1);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
header('Content-Type: application/json; charset=utf-8');

require __DIR__.'/../inc/db.php';

function bad(string $m, int $c=400): never {
  http_response_code($c);
  echo json_encode(['success'=>false,'error'=>$m], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  // Campos comunes
  $rol    = $_POST['rol']   ?? '';
  $nombre = trim((string)($_POST['nombre'] ?? ''));
  $email  = trim((string)($_POST['email']  ?? ''));
  $clave  = (string)($_POST['clave'] ?? '');

  if (!in_array($rol,['alumno','profesor'], true)) bad('Rol inválido.');
  if ($nombre==='' || $email==='' || $clave==='') bad('Faltan campos obligatorios.');
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) bad('Email no válido.');
  if (strlen($clave) < 6) bad('La contraseña debe tener al menos 6 caracteres.');

  // Mapeo de roles de tu BD: 2=profesor, 3=alumno
  $rol_id = $rol==='alumno' ? 3 : 2;

  // Email único
  $st = $pdo->prepare('SELECT 1 FROM usuarios WHERE email = ? LIMIT 1');
  $st->execute([$email]);
  if ($st->fetch()) bad('Email ya registrado.');

  $pdo->beginTransaction();

  // Crear usuario
  $hash = password_hash($clave, PASSWORD_DEFAULT);
  $st = $pdo->prepare('INSERT INTO usuarios (nombre, email, password_hash, rol_id, estado) VALUES (?,?,?,?,?)');
  $st->execute([$nombre, $email, $hash, $rol_id, 'activo']);
  $uid = (int)$pdo->lastInsertId();
  if ($uid <= 0) { $pdo->rollBack(); bad('No se pudo crear el usuario.'); }

  $resumen = ['usuario_id'=>$uid];

  if ($rol === 'alumno') {
    // ---- ALUMNO ----
    $a_grado = (int)($_POST['a_grado'] ?? 0);
    $a_curso = (int)($_POST['a_curso'] ?? 0);
    if (!$a_grado || !$a_curso) bad('Selecciona grado y curso.');

    // Validar relación curso↔grado
    $chk = $pdo->prepare('SELECT 1 FROM cursos WHERE id=? AND grado_id=?');
    $chk->execute([$a_curso, $a_grado]);
    if (!$chk->fetch()) bad('La combinación grado/curso no es válida.');

    // Guardar perfil alumno
    $pdo->prepare('INSERT INTO perfiles_alumno (usuario_id, curso_id) VALUES (?,?)')->execute([$uid, $a_curso]);

    $resumen['alumno'] = ['curso_id'=>$a_curso];

  } else {
    // ---- PROFESOR con múltiples combinaciones ----
    // Esperamos combos[0][grado_id], combos[0][curso_id], combos[0][asignatura_id], combos[1]..., etc.
    $combos = $_POST['combos'] ?? null;

    // Compatibilidad: si vinieran los antiguos campos, los convertimos a combos
    if (!$combos && isset($_POST['p_grado'])) {
      $g = (int)($_POST['p_grado'] ?? 0);
      $cursos = isset($_POST['p_cursos']) ? array_values(array_unique(array_map('intval',(array)$_POST['p_cursos']))) : [];
      $asigs  = isset($_POST['p_asignaturas']) ? array_values(array_unique(array_map('intval',(array)$_POST['p_asignaturas']))) : [];
      $combos = [];
      foreach ($cursos as $cid) {
        foreach ($asigs as $aid) {
          $combos[] = ['grado_id'=>$g, 'curso_id'=>$cid, 'asignatura_id'=>$aid];
        }
      }
    }

    if (!$combos || !is_array($combos)) bad('Añade al menos una combinación de grado/curso/asignatura.');

    // Normalizar y validar estructura
    $norm = [];
    foreach ($combos as $i => $row) {
      $g = (int)($row['grado_id'] ?? 0);
      $c = (int)($row['curso_id'] ?? 0);
      $a = (int)($row['asignatura_id'] ?? 0);
      if (!$g || !$c || !$a) bad("Combinación #".($i+1)." incompleta.");
      $norm[] = ['grado_id'=>$g, 'curso_id'=>$c, 'asignatura_id'=>$a];
    }
    if (!$norm) bad('No hay combinaciones válidas.');

    // Validar cada combinación contra el catálogo
    $insPI = $pdo->prepare('INSERT INTO profesor_imparte (profesor_id, grado_id, curso_id, asignatura_id) VALUES (?,?,?,?)');

    $insertadas = 0;
    foreach ($norm as $idx => $cb) {
      $g = $cb['grado_id']; $c = $cb['curso_id']; $a = $cb['asignatura_id'];

      // Curso pertenece al grado
      $st = $pdo->prepare('SELECT 1 FROM cursos WHERE id=? AND grado_id=?');
      $st->execute([$c, $g]);
      if (!$st->fetch()) bad('El curso seleccionado no pertenece al grado (comb. #'.($idx+1).').');

      // Asignatura pertenece al grado
      $st = $pdo->prepare('SELECT 1 FROM asignaturas WHERE id=? AND grado_id=?');
      $st->execute([$a, $g]);
      if (!$st->fetch()) bad('La asignatura seleccionada no pertenece al grado (comb. #'.($idx+1).').');

      // Insertar
      $insPI->execute([$uid, $g, $c, $a]);
      $insertadas += $insPI->rowCount();
    }

    if ($insertadas === 0) { $pdo->rollBack(); bad('No se insertó ninguna combinación.'); }

    // (Opcional) combinación principal en perfiles_profesor (una sola fila por PK usuario_id)
    $primera = $norm[0];
    $pdo->prepare('DELETE FROM perfiles_profesor WHERE usuario_id=?')->execute([$uid]);
    $pdo->prepare('INSERT INTO perfiles_profesor (usuario_id, curso_id, asignatura_id) VALUES (?,?,?)')
        ->execute([$uid, $primera['curso_id'], $primera['asignatura_id']]);

    $resumen['profesor'] = [
      'combinaciones_recibidas' => count($norm),
      'imparte_insertadas'      => $insertadas,
      'principal'               => $primera
    ];
  }

  $pdo->commit();
  echo json_encode([
    'success'=>true,
    'message'=>'Registro insertado correctamente.',
    'resumen'=>$resumen,
    'redirect'=>'/Bancalia/panel.php'
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(400);
  echo json_encode(['success'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
