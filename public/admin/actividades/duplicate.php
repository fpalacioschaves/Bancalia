<?php
// /public/admin/actividades/duplicate.php
declare(strict_types=1);

// RUTA CORRECTA → solo subir 3 niveles
require_once __DIR__ . '/../../../config.php';

require_login_or_redirect();

$u          = current_user();
$role       = $u['role'] ?? '';
$profesorId = (int)($u['profesor_id'] ?? 0);
$centroId   = (int)($u['centro_id'] ?? 0);

if (!in_array($role, ['profesor', 'admin'], true)) {
  http_response_code(403);
  echo 'Acceso denegado';
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . PUBLIC_URL . '/dashboard.php');
  exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
  header('Location: ' . PUBLIC_URL . '/dashboard.php');
  exit;
}

$db = pdo();
$db->beginTransaction();

try {
  // 1. Cargar actividad original
  $st = $db->prepare('SELECT * FROM actividades WHERE id = :id');
  $st->execute([':id' => $id]);
  $act = $st->fetch(PDO::FETCH_ASSOC);

  if (!$act) {
    throw new RuntimeException('Actividad no encontrada');
  }

  // 2. Comprobar permisos: propia o visible (si no es admin)
  if ($role !== 'admin') {
    $esMia     = ((int)$act['profesor_id'] === $profesorId);
    $visible   = in_array($act['visibilidad'], ['publica', 'centro'], true);

    if (!$esMia && !$visible) {
      throw new RuntimeException('No tienes permisos para duplicar esta actividad');
    }
  }

  $oldId = (int)$act['id'];

  // 3. Preparar datos de la nueva actividad
  $nuevoTitulo = $act['titulo'] . ' (copia)';
  if (mb_strlen($nuevoTitulo) > 255) {
    $nuevoTitulo = mb_substr($nuevoTitulo, 0, 252) . '...';
  }

  $nuevoProfesorId = $profesorId ?: (int)$act['profesor_id'];
  $nuevoCentroId   = $centroId ?: (int)($act['centro_id'] ?? 0);

  // Siempre duplicamos como privada y borrador
  $nuevoVisibilidad = 'privada';
  $nuevoEstado      = 'borrador';

  // 4. Insertar en actividades (ahora con centro_id)
  $stIns = $db->prepare("
    INSERT INTO actividades
      (profesor_id, centro_id, familia_id, curso_id, asignatura_id, tema_id, tipo,
       titulo, descripcion, dificultad, visibilidad, estado, created_at, updated_at)
    VALUES
      (:profesor_id, :centro_id, :familia_id, :curso_id, :asignatura_id, :tema_id, :tipo,
       :titulo, :descripcion, :dificultad, :visibilidad, :estado, NOW(), NOW())
  ");

  $stIns->execute([
    ':profesor_id'   => $nuevoProfesorId,
    ':centro_id'     => $nuevoCentroId,
    ':familia_id'    => $act['familia_id'],
    ':curso_id'      => $act['curso_id'],
    ':asignatura_id' => $act['asignatura_id'],
    ':tema_id'       => $act['tema_id'],
    ':tipo'          => $act['tipo'],
    ':titulo'        => $nuevoTitulo,
    ':descripcion'   => $act['descripcion'],
    ':dificultad'    => $act['dificultad'],
    ':visibilidad'   => $nuevoVisibilidad,
    ':estado'        => $nuevoEstado,
  ]);

  $newId = (int)$db->lastInsertId();

  // ---- DUPLICADO ESPECÍFICO POR TIPO ----

  switch ($act['tipo']) {
    case 'opcion_multiple':
      // Config principal
      $db->prepare("
        INSERT INTO actividades_om
          (actividad_id, barajar, enunciado_html, puntuacion_max,
           feedback_correcta, feedback_incorrecta, created_at, updated_at)
        SELECT :newId, barajar, enunciado_html, puntuacion_max,
               feedback_correcta, feedback_incorrecta, NOW(), NOW()
        FROM actividades_om WHERE actividad_id=:oldId
      ")->execute([':newId'=>$newId, ':oldId'=>$oldId]);

      // Opciones
      $db->prepare("
        INSERT INTO actividades_om_opciones
          (actividad_id, opcion_html, es_correcta, orden, created_at, updated_at)
        SELECT :newId, opcion_html, es_correcta, orden, NOW(), NOW()
        FROM actividades_om_opciones WHERE actividad_id=:oldId
      ")->execute([':newId'=>$newId, ':oldId'=>$oldId]);
      break;

    case 'verdadero_falso':
      $db->prepare("
        INSERT INTO actividades_vf
          (actividad_id, respuesta_correcta, feedback_correcta, feedback_incorrecta, created_at, updated_at)
        SELECT :newId, respuesta_correcta, feedback_correcta, feedback_incorrecta, NOW(), NOW()
        FROM actividades_vf WHERE actividad_id=:oldId
      ")->execute([':newId'=>$newId, ':oldId'=>$oldId]);
      break;

    case 'respuesta_corta':
      $db->prepare("
        INSERT INTO actividades_rc
          (actividad_id, modo, case_sensitive, normalizar_acentos, trim_espacios,
           palabras_clave_json, coincidencia_minima, puntuacion_max,
           regex_pattern, regex_flags, respuesta_muestra,
           feedback_correcta, feedback_incorrecta,
           created_at, updated_at)
        SELECT :newId, modo, case_sensitive, normalizar_acentos, trim_espacios,
               palabras_clave_json, coincidencia_minima, puntuacion_max,
               regex_pattern, regex_flags, respuesta_muestra,
               feedback_correcta, feedback_incorrecta,
               NOW(), NOW()
        FROM actividades_rc WHERE actividad_id=:oldId
      ")->execute([':newId'=>$newId, ':oldId'=>$oldId]);
      break;

    case 'rellenar_huecos':
      $db->prepare("
        INSERT INTO actividades_rh
          (actividad_id, enunciado_html, huecos_json,
           case_sensitive, normalizar_acentos, trim_espacios,
           puntuacion_max, feedback_correcta, feedback_incorrecta,
           created_at, updated_at)
        SELECT :newId, enunciado_html, huecos_json,
               case_sensitive, normalizar_acentos, trim_espacios,
               puntuacion_max, feedback_correcta, feedback_incorrecta,
               NOW(), NOW()
        FROM actividades_rh WHERE actividad_id=:oldId
      ")->execute([':newId'=>$newId, ':oldId'=>$oldId]);
      break;

    case 'emparejar':
      // Config EMP
      $db->prepare("
        INSERT INTO actividades_emp
          (actividad_id, barajar, instrucciones_html, puntuacion_max,
           modo_puntuacion, barajar_izquierda, barajar_derecha,
           feedback_correcta, feedback_incorrecta,
           created_at, updated_at)
        SELECT :newId, barajar, instrucciones_html, puntuacion_max,
               modo_puntuacion, barajar_izquierda, barajar_derecha,
               feedback_correcta, feedback_incorrecta,
               NOW(), NOW()
        FROM actividades_emp WHERE actividad_id=:oldId
      ")->execute([':newId'=>$newId, ':oldId'=>$oldId]);

      // Pares
      $db->prepare("
        INSERT INTO actividades_emp_pares
          (actividad_id, izquierda_html, derecha_html,
           alternativas_derecha_json, grupo,
           orden_izq, orden_der, activo,
           created_at, updated_at)
        SELECT :newId, izquierda_html, derecha_html,
               alternativas_derecha_json, grupo,
               orden_izq, orden_der, activo,
               NOW(), NOW()
        FROM actividades_emp_pares WHERE actividad_id=:oldId
      ")->execute([':newId'=>$newId, ':oldId'=>$oldId]);
      break;

    case 'tarea':
      $db->prepare("
        INSERT INTO actividades_tarea
          (actividad_id, instrucciones,
           perm_texto, perm_archivo, perm_enlace,
           max_archivos, max_peso_mb,
           evaluacion_modo, puntuacion_max, rubrica_json,
           created_at, updated_at)
        SELECT :newId, instrucciones,
               perm_texto, perm_archivo, perm_enlace,
               max_archivos, max_peso_mb,
               evaluacion_modo, puntuacion_max, rubrica_json,
               NOW(), NOW()
        FROM actividades_tarea WHERE actividad_id=:oldId
      ")->execute([':newId'=>$newId, ':oldId'=>$oldId]);
      break;
  }

  $db->commit();

  header('Location: ' . PUBLIC_URL . '/admin/actividades/edit.php?id=' . $newId);
  exit;

} catch (Throwable $e) {
  $db->rollBack();
  echo 'Error al duplicar la actividad: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
  exit;
}
