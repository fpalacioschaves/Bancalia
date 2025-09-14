<?php
// /Bancalia/api/registro.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/auth.php'; // Debe definir $pdo (PDO conectado a bancalia)

function out(array $arr, int $status = 200): void {
  http_response_code($status);
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    out(['success'=>false,'error'=>'Método no permitido.'], 405);
  }

  // Campos básicos
  $nombre = trim((string)($_POST['nombre'] ?? ''));
  $email  = trim((string)($_POST['email'] ?? ''));
  $clave  = (string)($_POST['clave'] ?? '');
  $perfil = strtolower(trim((string)($_POST['perfil'] ?? '')));

  // Aceptar variantes comunes
  if ($perfil === '2') $perfil = 'profesor';
  if ($perfil === '3') $perfil = 'alumno';

  if ($nombre==='' || $email==='' || $clave==='') {
    out(['success'=>false,'error'=>'Completa nombre, email y contraseña.'], 400);
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    out(['success'=>false,'error'=>'Email no válido.'], 400);
  }
  if (!in_array($perfil, ['alumno','profesor'], true)) {
    out(['success'=>false,'error'=>'Perfil no válido.'], 400);
  }

  // Email único
  $st = $pdo->prepare('SELECT 1 FROM usuarios WHERE email=? LIMIT 1');
  $st->execute([$email]);
  if ($st->fetchColumn()) {
    out(['success'=>false,'error'=>'Ese email ya está registrado.'], 400);
  }

  $rolId = $perfil === 'profesor' ? 2 : 3;
  $hash  = password_hash($clave, PASSWORD_DEFAULT);

  $pdo->beginTransaction();

  // Crear usuario
  $ins = $pdo->prepare('INSERT INTO usuarios (nombre,email,password_hash,rol_id,estado) VALUES (?,?,?,?,?)');
  $ins->execute([$nombre,$email,$hash,$rolId,'activo']);
  $uid = (int)$pdo->lastInsertId();

  if ($perfil === 'alumno') {
    $grado_id = (int)($_POST['grado_id'] ?? 0);
    $curso_id = (int)($_POST['curso_id'] ?? 0);
    if ($grado_id<=0 || $curso_id<=0) {
      throw new RuntimeException('Selecciona grado y curso.');
    }
    // Curso pertenece al grado
    $chk = $pdo->prepare('SELECT 1 FROM cursos WHERE id=? AND grado_id=?');
    $chk->execute([$curso_id,$grado_id]);
    if (!$chk->fetchColumn()) {
      throw new RuntimeException('El curso no pertenece al grado seleccionado.');
    }
    // Guardar perfil alumno
    $pa = $pdo->prepare('INSERT INTO perfiles_alumno (usuario_id, curso_id) VALUES (?,?)');
    $pa->execute([$uid,$curso_id]);

  } else { // PROFESOR: permitir pares de varios grados
    // Aceptar tanto pares[] como pairs[] por compatibilidad
    $pares = $_POST['pares'] ?? ($_POST['pairs'] ?? []);
    if (!is_array($pares)) $pares = [$pares];
    $pares = array_values(array_unique(array_filter(array_map('strval',$pares))));
    if (!$pares) throw new RuntimeException('Añade al menos una asignación (curso↔asignatura).');

    $qCursoGrado = $pdo->prepare('SELECT grado_id FROM cursos WHERE id=?');
    $qAsigGrado  = $pdo->prepare('SELECT grado_id FROM asignaturas WHERE id=?');
    $qPivot      = $pdo->prepare('SELECT 1 FROM asignatura_curso WHERE asignatura_id=? AND curso_id=?');

    $insPI = $pdo->prepare('
      INSERT INTO profesor_imparte (profesor_id, grado_id, curso_id, asignatura_id)
      VALUES (?,?,?,?)
    ');

    // Deduplicar claves curso:asignatura para no chocar con UNIQUE
    $keys = [];
    foreach ($pares as $par) {
      if (!preg_match('~^(\d+):(\d+)$~', $par, $m)) {
        throw new RuntimeException('Formato de asignación inválido.');
      }
      $curso_id = (int)$m[1];
      $asig_id  = (int)$m[2];
      $key = $curso_id.':'.$asig_id;
      if (isset($keys[$key])) continue;
      $keys[$key] = true;

      // grado por curso
      $qCursoGrado->execute([$curso_id]); 
      $gCurso = $qCursoGrado->fetchColumn();
      if (!$gCurso) throw new RuntimeException('Curso inexistente.');

      // asignatura del mismo grado
      $qAsigGrado->execute([$asig_id]); 
      $gAsig  = $qAsigGrado->fetchColumn();
      if (!$gAsig) throw new RuntimeException('Asignatura inexistente.');

      if ((int)$gCurso !== (int)$gAsig) {
        throw new RuntimeException('Curso y asignatura pertenecen a grados distintos.');
      }

      // vínculo en pivot
      $qPivot->execute([$asig_id,$curso_id]);
      if (!$qPivot->fetchColumn()) {
        throw new RuntimeException('La asignatura no está asociada a ese curso.');
      }

      // Insertar con el grado correcto DE CADA PAR
      $insPI->execute([$uid,(int)$gCurso,$curso_id,$asig_id]);
    }
  }

  $pdo->commit();
  out(['success'=>true,'redirect'=>'/Bancalia/panel.php']);

} catch (Throwable $e) {
  if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
  $msg = $e->getMessage();
  if (str_contains($msg, 'uq_prof_curso_asig')) $msg = 'Esa combinación curso↔asignatura ya está añadida.';
  out(['success'=>false,'error'=>$msg], 400);
}
