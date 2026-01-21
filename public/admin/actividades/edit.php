<?php
// /public/admin/actividades/edit.php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();

$u = current_user();

/**
 * DEBUG opcional (?debug=1)
 */
$DEBUG = isset($_GET['debug']) && $_GET['debug'] !== '0';
error_reporting(E_ALL);
ini_set('display_errors', '1');

$basePublic = PUBLIC_URL;

// --------- ID ----------
$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
  if ($DEBUG) {
    echo "[DBG] id inválido";
    exit;
  }
  flash('error', 'Actividad no válida.');
  header('Location: ' . $basePublic . '/admin/actividades/index.php');
  exit;
}

// --------- ACL (propietario profesor) ----------
if (($u['role'] ?? '') === 'admin') {
  // Admin solo visualiza
  flash('error', 'El administrador solo puede visualizar actividades.');
  header('Location: ' . $basePublic . '/admin/actividades/index.php');
  exit;
}

$profesorId = (int) ($u['profesor_id'] ?? 0);
if ($profesorId <= 0) {
  flash('error', 'No se ha podido identificar al profesor.');
  header('Location: ' . $basePublic . '/admin/actividades/index.php');
  exit;
}

$stCheck = pdo()->prepare('SELECT profesor_id FROM actividades WHERE id = :id LIMIT 1');
$stCheck->execute([':id' => $id]);
$owner = $stCheck->fetchColumn();

if (!$owner) {
  flash('error', 'La actividad no existe.');
  header('Location: ' . $basePublic . '/admin/actividades/index.php');
  exit;
}
if ((int) $owner !== $profesorId) {
  flash('error', 'No tienes permiso para editar esta actividad.');
  header('Location: ' . $basePublic . '/admin/actividades/index.php');
  exit;
}

// --------- Datos base para selects ----------
try {
  $familias = pdo()->query("SELECT id, nombre FROM familias_profesionales WHERE is_active=1 ORDER BY nombre ASC")->fetchAll();
  $cursos = pdo()->query("SELECT id, nombre, familia_id, orden FROM cursos WHERE is_active=1 ORDER BY familia_id ASC, orden ASC, nombre ASC")->fetchAll();
  $asigs = pdo()->query("SELECT id, nombre, curso_id, familia_id, orden FROM asignaturas WHERE is_active=1 ORDER BY familia_id ASC, curso_id ASC, orden ASC, nombre ASC")->fetchAll();
  $temas = pdo()->query("SELECT id, nombre, asignatura_id, numero FROM temas ORDER BY asignatura_id ASC, numero ASC, nombre ASC")->fetchAll();
} catch (Throwable $e) {
  if ($DEBUG) {
    echo "[DBG] Error datasets: " . h($e->getMessage());
    exit;
  }
  flash('error', 'No se pudieron cargar los datos.');
  header('Location: ' . $basePublic . '/admin/actividades/index.php');
  exit;
}

// --------- Cargar actividad ----------
$actividadService = new ActividadService(pdo());
$row = $actividadService->findFull($id);

if (!$row) {
  flash('error', 'Actividad no encontrada.');
  header('Location: ' . PUBLIC_URL . '/admin/actividades/index.php');
  exit;
}

// ACL: Propietario
if ((int) $row['profesor_id'] !== $profesorId) {
  flash('error', 'No tienes permiso para editar esta actividad.');
  header('Location: ' . PUBLIC_URL . '/admin/actividades/index.php');
  exit;
}

$tipoActual = (string) ($row['tipo'] ?? 'opcion_multiple');
$centroId = (int) ($row['centro_id'] ?? 0);

// Aliases para la vista
$tareaRow = $row;
$vfRow = $row;
$rcRow = $row;
$rhRow = $row;
$omRow = $row;
$omOpts = $row['om_options'] ?? [];
$empRows = $row; // El servicio debería devolver los pares en un formato compatible o ajustarlo

/**
 * ===== ESTADÍSTICAS DE USO Y VALORACIÓN =====
 */
$statsUso = [
  'profesores_usan' => null,
  'examenes' => null,
];
$statsValoracion = [
  'media' => null,
  'total' => 0,
];
$miValoracion = [
  'id' => null,
  'valoracion' => null,
  'comentario' => '',
];

try {
  // 1) Uso en exámenes (examenes_actividades + examenes)
  $sqlUso = "
    SELECT
      COUNT(DISTINCT e.profesor_id) AS profesores_usan,
      COUNT(DISTINCT ea.examen_id)  AS examenes
    FROM examenes_actividades ea
    INNER JOIN examenes e ON e.id = ea.examen_id
    WHERE ea.actividad_id = :aid
  ";
  $stUso = pdo()->prepare($sqlUso);
  $stUso->execute([':aid' => $id]);
  if ($rowUso = $stUso->fetch()) {
    $statsUso['profesores_usan'] = (int) ($rowUso['profesores_usan'] ?? 0);
    $statsUso['examenes'] = (int) ($rowUso['examenes'] ?? 0);
  }

  // 2) Valoraciones (tabla actividades_valoraciones con columna 'valoracion')
  //    Usamos table_columns para asegurarnos de que existe la columna correcta.
} catch (Throwable $e) {
  // Si algo falla (tabla aún no creada, etc.), simplemente dejamos statsUso por defecto.
}

// Utilidad: columnas de tabla
function table_columns(string $table): array
{
  try {
    $q = pdo()->prepare("SHOW COLUMNS FROM `$table`");
    $q->execute();
    $cols = [];
    foreach ($q->fetchAll() as $r) {
      $cols[] = (string) $r['Field'];
    }
    return $cols;
  } catch (Throwable $e) {
    return [];
  }
}

// Carga de valoraciones (media + la del profesor actual)
try {
  $colsVal = table_columns('actividades_valoraciones');
  if (!empty($colsVal) && in_array('valoracion', $colsVal, true)) {
    // Media y total de valoraciones
    $stAvg = pdo()->prepare("
      SELECT AVG(valoracion) AS media, COUNT(*) AS total
      FROM actividades_valoraciones
      WHERE actividad_id = :aid
    ");
    $stAvg->execute([':aid' => $id]);
    if ($rowAvg = $stAvg->fetch()) {
      $statsValoracion['media'] = $rowAvg['media'] !== null ? (float) $rowAvg['media'] : null;
      $statsValoracion['total'] = (int) ($rowAvg['total'] ?? 0);
    }

    // Valoración del profesor actual
    $stMine = pdo()->prepare("
      SELECT id, valoracion, comentario
      FROM actividades_valoraciones
      WHERE actividad_id = :aid AND profesor_id = :pid
      LIMIT 1
    ");
    $stMine->execute([
      ':aid' => $id,
      ':pid' => $profesorId,
    ]);
    if ($rowMine = $stMine->fetch()) {
      $miValoracion['id'] = (int) $rowMine['id'];
      $miValoracion['valoracion'] = (int) $rowMine['valoracion'];
      $miValoracion['comentario'] = (string) ($rowMine['comentario'] ?? '');
    }
  }
} catch (Throwable $e) {
  // Si la tabla no existe o hay cualquier problema, ignoramos silenciosamente.
}

// --------- POST: actualizar ----------
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $actividadService = new ActividadService(pdo());
    $actividadService->update($id, $_POST);

    // ===== VALORACIÓN DEL PROFESOR (1–5) =====
    try {
      $colsVal = table_columns('actividades_valoraciones');
      if (!empty($colsVal) && in_array('valoracion', $colsVal, true)) {
        $valRaw = $_POST['valoracion'] ?? '';
        $valoracion = ($valRaw !== '') ? (int) $valRaw : null;
        if ($valoracion !== null) {
          if ($valoracion < 1 || $valoracion > 5)
            throw new RuntimeException('La valoración debe estar entre 1 y 5.');

          $stVal = pdo()->prepare("SELECT id FROM actividades_valoraciones WHERE actividad_id = :aid AND profesor_id = :pid LIMIT 1");
          $stVal->execute([':aid' => $id, ':pid' => $profesorId]);
          $valId = $stVal->fetchColumn();

          if ($valId) {
            pdo()->prepare("UPDATE actividades_valoraciones SET valoracion = :val, updated_at = NOW() WHERE id = :id")
              ->execute([':val' => $valoracion, ':id' => $valId]);
          } else {
            pdo()->prepare("INSERT INTO actividades_valoraciones (actividad_id, profesor_id, valoracion, created_at, updated_at) VALUES (:aid, :pid, :val, NOW(), NOW())")
              ->execute([':aid' => $id, ':pid' => $profesorId, ':val' => $valoracion]);
          }
        }
      }
    } catch (Throwable $eVal) {
      if ($DEBUG)
        throw $eVal;
    }

    flash('success', 'Actividad actualizada correctamente.');
    header('Location: ' . PUBLIC_URL . '/admin/actividades/index.php');
    exit;

  } catch (Throwable $e) {
    if ($DEBUG) {
      echo "[DBG][EXC] " . h($e->getMessage());
      exit;
    }
    $errors[] = $e->getMessage();
  }
}
// --------- Render (valores actuales) ----------
require_once __DIR__ . '/../../../partials/header.php';

$titulo = (string) ($row['titulo'] ?? '');
$descripcion = (string) ($row['descripcion'] ?? '');
$familia_id = (int) ($row['familia_id'] ?? 0);
$curso_id = (int) ($row['curso_id'] ?? 0);
$asignatura_id = (int) ($row['asignatura_id'] ?? 0);
$tema_id = isset($row['tema_id']) ? (int) $row['tema_id'] : 0;
$tipo = (string) ($row['tipo'] ?? 'opcion_multiple');
$visibilidad = (string) ($row['visibilidad'] ?? 'privada');
$estado = (string) ($row['estado'] ?? 'borrador');
$dificultad = (string) ($row['dificultad'] ?? '');

// Tarea (si hay)
$t_instrucciones = (string) ($tareaRow['instrucciones'] ?? '');
$t_perm_texto = (int) ($tareaRow['perm_texto'] ?? 0);
$t_perm_archivo = (int) ($tareaRow['perm_archivo'] ?? 0);
$t_perm_enlace = (int) ($tareaRow['perm_enlace'] ?? 0);
$t_max_archivos = $tareaRow['max_archivos'] ?? '';
$t_max_peso_mb = $tareaRow['max_peso_mb'] ?? '';
$t_eval_modo = (string) ($tareaRow['evaluacion_modo'] ?? '');
$t_puntos_max = $tareaRow['puntuacion_max'] ?? '';
$t_rubrica_json = (string) ($tareaRow['rubrica_json'] ?? '');

// VF
$vf_respuesta_correcta = (string) ($vfRow['respuesta_correcta'] ?? '');
$vf_feedback_correcta = (string) ($vfRow['feedback_correcta'] ?? '');
$vf_feedback_incorrecta = (string) ($vfRow['feedback_incorrecta'] ?? '');

// RC
$rc_modo = (string) ($rcRow['modo'] ?? 'palabras_clave');
$rc_case = (int) ($rcRow['case_sensitive'] ?? 0);
$rc_acentos = (int) ($rcRow['normalizar_acentos'] ?? 0);
$rc_trim = (int) ($rcRow['trim_espacios'] ?? 1);
$rc_palabras_json = (string) ($rcRow['palabras_clave_json'] ?? '');
$rc_coinc_min = ($rcRow['coincidencia_minima'] ?? '');
$rc_puntos_max = ($rcRow['puntuacion_max'] ?? '');
$rc_regex_pat = (string) ($rcRow['regex_pattern'] ?? '');
$rc_regex_flags = (string) ($rcRow['regex_flags'] ?? '');
$rc_resp_muestra = (string) ($rcRow['respuesta_muestra'] ?? '');
$rc_fb_ok = (string) ($rcRow['feedback_correcta'] ?? '');
$rc_fb_fail = (string) ($rcRow['feedback_incorrecta'] ?? '');

// RH
$rh_enunciado_html = (string) ($rhRow['enunciado_html'] ?? '');
$rh_huecos_json = (string) ($rhRow['huecos_json'] ?? '');
$rh_case = (int) ($rhRow['case_sensitive'] ?? 0);
$rh_acentos = (int) ($rhRow['normalizar_acentos'] ?? 0);
$rh_trim = (int) ($rhRow['trim_espacios'] ?? 1);
$rh_pmax = ($rhRow['puntuacion_max'] ?? '');
$rh_fb_ok = (string) ($rhRow['feedback_correcta'] ?? '');
$rh_fb_fail = (string) ($rhRow['feedback_incorrecta'] ?? '');

// OM (lectura de tablas OM + opciones)
$om_enunciado_html = (string) ($omRow['enunciado_html'] ?? '');
$om_fb_ok = (string) ($omRow['feedback_correcta'] ?? '');
$om_fb_fail = (string) ($omRow['feedback_incorrecta'] ?? '');

// Si hubo POST con errores, respetamos lo enviado por el usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tipo === 'opcion_multiple') {
  $om_enunciado_html = (string) ($_POST['om_enunciado_html'] ?? $om_enunciado_html);
  $om_fb_ok = (string) ($_POST['om_feedback_correcta'] ?? $om_fb_ok);
  $om_fb_fail = (string) ($_POST['om_feedback_incorrecta'] ?? $om_fb_fail);

  $postOpts = $_POST['om_opciones'] ?? [];
  if (is_array($postOpts)) {
    $omOpts = [];
    foreach ($postOpts as $i => $txt) {
      $txt = trim((string) $txt);
      if ($txt === '' and $i > 3)
        continue;
      $omOpts[] = [
        'opcion_html' => $txt,
        'es_correcta' => (isset($_POST['om_correcta']) && (int) $_POST['om_correcta'] === (int) $i) ? 1 : 0,
        'orden' => $i + 1,
      ];
    }
  }
}

// Aseguramos al menos 4 filas visuales
if (empty($omOpts)) {
  $omOpts = [];
}
while (count($omOpts) < 4) {
  $omOpts[] = ['opcion_html' => '', 'es_correcta' => 0, 'orden' => count($omOpts) + 1];
}

// EMP
$empRows = $empRows ?? [];

// Valoración actual del profesor
$miValActual = (int) ($miValoracion['valoracion'] ?? 0);
$mediaVal = $statsValoracion['media'];
$totalVals = (int) ($statsValoracion['total'] ?? 0);

?>
<div class="mb-6 flex items-center justify-between">
  <div>
    <h1 class="text-xl font-semibold tracking-tight">Editar actividad</h1>
    <p class="mt-1 text-sm text-slate-600">Actualiza los datos y guarda los cambios.</p>
  </div>
  <a href="<?= $basePublic ?>/admin/actividades/index.php"
    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
    Volver al listado
  </a>
</div>

<?php if ($errors): ?>
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
      <?php foreach ($errors as $msg): ?>
          <div>• <?= h($msg) ?></div>
      <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
  <form method="post" action="" class="space-y-5" id="formActividad">
    <?= csrf_field() ?>

    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700">Título <span class="text-rose-600">*</span></label>
      <input name="titulo" type="text" required value="<?= h($titulo) ?>"
        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Tipo <span class="text-rose-600">*</span></label>
        <select name="tipo" id="tipo" required
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
          <?php
          $opts = [
            'opcion_multiple' => 'Opción múltiple',
            'verdadero_falso' => 'Verdadero/falso',
            'respuesta_corta' => 'Respuesta corta',
            'rellenar_huecos' => 'Rellenar huecos',
            'emparejar' => 'Emparejar',
            'tarea' => 'Tarea / entrega abierta',
          ];
          foreach ($opts as $val => $lab) {
            $sel = ($tipo === $val) ? 'selected' : '';
            echo '<option value="' . h($val) . '" ' . $sel . '>' . h($lab) . '</option>';
          }
          ?>
        </select>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Dificultad</label>
        <select name="dificultad"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
          <option value="">—</option>
          <?php foreach (['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta'] as $val => $label): ?>
              <option value="<?= h($val) ?>" <?= $dificultad === $val ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Familia/Grado <span
            class="text-rose-600">*</span></label>
        <select id="familia_id" name="familia_id" required
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
          <?php foreach ($familias as $f): ?>
              <option value="<?= (int) $f['id'] ?>" <?= $familia_id === (int) $f['id'] ? 'selected' : '' ?>>
                <?= h($f['nombre']) ?>
              </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Curso <span class="text-rose-600">*</span></label>
        <select id="curso_id" name="curso_id" required
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
          <option value="">— Curso —</option>
        </select>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Asignatura <span
            class="text-rose-600">*</span></label>
        <select id="asignatura_id" name="asignatura_id" required
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
          <option value="">— Asignatura —</option>
        </select>
      </div>
    </div>

    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700">Tema</label>
      <select id="tema_id" name="tema_id"
        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
        <option value="">— Tema (opcional) —</option>
      </select>
    </div>

    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700">Descripción</label>
      <textarea name="descripcion" rows="4"
        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h($descripcion) ?></textarea>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Visibilidad</label>
        <select name="visibilidad"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
          <?php
          $visOps = [
            'privada' => 'Privada',
            'centro' => 'Centro',
            'publica' => 'Pública',
          ];
          foreach ($visOps as $v => $lab): ?>
              <option value="<?= h($v) ?>" <?= $visibilidad === $v ? 'selected' : '' ?>><?= h($lab) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Estado</label>
        <select name="estado"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
          <?php foreach (['borrador' => 'Borrador', 'publicada' => 'Publicada'] as $v => $lab): ?>
              <option value="<?= $v ?>" <?= $estado === $v ? 'selected' : '' ?>><?= $lab ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- ====== BLOQUE TAREA ====== -->
    <div id="bloqueTarea" class="<?= $tipo === 'tarea' ? '' : 'hidden' ?> border-t pt-4">
      <h3 class="text-sm font-semibold text-slate-700 mb-2">Opciones de Tarea / Entrega</h3>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Instrucciones para el alumno</label>
        <textarea name="t_instrucciones" rows="3"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h($t_instrucciones) ?></textarea>
      </div>

      <div class="grid gap-4 sm:grid-cols-3">
        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" name="t_perm_texto" class="h-4 w-4 rounded border-slate-300" <?= $t_perm_texto ? 'checked' : '' ?>>
          Permitir texto
        </label>
        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" name="t_perm_archivo" class="h-4 w-4 rounded border-slate-300" <?= $t_perm_archivo ? 'checked' : '' ?>>
          Permitir archivos
        </label>
        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" name="t_perm_enlace" class="h-4 w-4 rounded border-slate-300" <?= $t_perm_enlace ? 'checked' : '' ?>>
          Permitir enlaces
        </label>
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Máx. archivos</label>
          <input type="number" min="0" name="t_max_archivos" value="<?= h((string) $t_max_archivos) ?>"
            class="w-40 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Máx. tamaño por archivo (MB)</label>
          <input type="number" min="0" name="t_max_peso_mb" value="<?= h((string) $t_max_peso_mb) ?>"
            class="w-56 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-3">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Evaluación</label>
          <select name="t_evaluacion_modo"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
            <?php
            $ops = ['' => '— Sin evaluación —', 'puntos' => 'Puntuación', 'rubrica' => 'Rúbrica'];
            foreach ($ops as $v => $lab) {
              $sel = ($t_eval_modo === $v) ? 'selected' : '';
              echo '<option value="' . h($v) . '" ' . $sel . '>' . h($lab) . '</option>';
            }
            ?>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Puntuación máxima</label>
          <input type="number" min="0" name="t_puntuacion_max" value="<?= h((string) $t_puntos_max) ?>"
            class="w-48 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"
            placeholder="Ej: 10">
        </div>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Rúbrica (JSON)</label>
        <textarea name="t_rubrica_json" rows="4"
          class="w-full font-mono rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"
          placeholder='[{"criterio":"Presentación","max":2},{"criterio":"Contenido","max":8}]'><?= h($t_rubrica_json) ?></textarea>
        <p class="mt-1 text-xs text-slate-500">Opcional. Solo si usas evaluación por rúbrica.</p>
      </div>
    </div>
    <!-- /TAREA -->

    <!-- ====== V/F ====== -->
    <div id="bloqueVF" class="<?= $tipo === 'verdadero_falso' ? '' : 'hidden' ?> border-t pt-4">
      <h3 class="text-sm font-semibold text-slate-700 mb-2">Configuración Verdadero/Falso</h3>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Respuesta correcta <span
            class="text-rose-600">*</span></label>
        <select name="vf_respuesta_correcta"
          class="w-48 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
          <?php foreach (['verdadero' => 'Verdadero', 'falso' => 'Falso'] as $v => $lab): ?>
              <option value="<?= h($v) ?>" <?= $vf_respuesta_correcta === $v ? 'selected' : '' ?>><?= h($lab) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 mt-3">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Feedback si acierta</label>
          <textarea name="vf_feedback_correcta" rows="3"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h($vf_feedback_correcta) ?></textarea>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Feedback si falla</label>
          <textarea name="vf_feedback_incorrecta" rows="3"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h($vf_feedback_incorrecta) ?></textarea>
        </div>
      </div>
    </div>
    <!-- /V/F -->

    <!-- ====== RESPUESTA CORTA ====== -->
    <div id="bloqueRC" class="<?= $tipo === 'respuesta_corta' ? '' : 'hidden' ?> border-t pt-4">
      <h3 class="text-sm font-semibold text-slate-700 mb-2">Configuración Respuesta corta</h3>

      <div class="grid gap-4 sm:grid-cols-3">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Modo de corrección <span
              class="text-rose-600">*</span></label>
          <select name="rc_modo" id="rc_modo"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
            <?php
            $opsRC = ['palabras_clave' => 'Palabras clave', 'regex' => 'Regex'];
            foreach ($opsRC as $v => $lab) {
              $sel = ($rc_modo === $v) ? 'selected' : '';
              echo '<option value="' . h($v) . '" ' . $sel . '>' . h($lab) . '</option>';
            }
            ?>
          </select>
        </div>
        <label class="inline-flex items-center gap-2 text-sm mt-6">
          <input type="checkbox" name="rc_case_sensitive" class="h-4 w-4 rounded border-slate-300" <?= $rc_case ? 'checked' : '' ?>>
          Sensible a mayúsculas
        </label>
        <label class="inline-flex items-center gap-2 text-sm mt-6">
          <input type="checkbox" name="rc_normalizar_acentos" class="h-4 w-4 rounded border-slate-300" <?= $rc_acentos ? 'checked' : '' ?>>
          Normalizar acentos
        </label>
        <label class="inline-flex items-center gap-2 text-sm mt-6">
          <input type="checkbox" name="rc_trim" class="h-4 w-4 rounded border-slate-300" <?= $rc_trim ? 'checked' : '' ?>>
          Ignorar espacios extremos
        </label>
      </div>

      <!-- Palabras clave -->
      <div id="rc_palabras" class="mt-3 <?= $rc_modo === 'palabras_clave' ? '' : 'hidden' ?>">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Palabras clave (JSON)</label>
          <textarea name="rc_palabras_clave_json" rows="4"
            class="w-full font-mono rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"
            placeholder='[{"palabra":"osmosis","peso":1},{"palabra":"membrana","peso":1}]'><?= h($rc_palabras_json) ?></textarea>
          <p class="mt-1 text-xs text-slate-500">Cada objeto puede incluir "palabra" y "peso".</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 mt-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">% coincidencia mínima</label>
            <input type="number" min="0" max="100" name="rc_coincidencia_minima"
              value="<?= h((string) $rc_coinc_min) ?>"
              class="w-48 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"
              placeholder="Ej: 60">
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Puntuación máxima</label>
            <input type="number" min="0" name="rc_puntuacion_max" value="<?= h((string) $rc_puntos_max) ?>"
              class="w-48 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"
              placeholder="Ej: 10">
          </div>
        </div>
      </div>

      <!-- Regex -->
      <div id="rc_regex" class="mt-3 <?= $rc_modo === 'regex' ? '' : 'hidden' ?>">
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Patrón regex <span
                class="text-rose-600">*</span></label>
            <input type="text" name="rc_regex_pattern" value="<?= h($rc_regex_pat) ?>"
              class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"
              placeholder="^\\s*respuesta\\s*$">
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Flags</label>
            <input type="text" name="rc_regex_flags" value="<?= h($rc_regex_flags) ?>"
              class="w-48 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"
              placeholder="i, u, m...">
          </div>
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-3 mt-3">
        <div class="sm:col-span-3">
          <label class="mb-1 block text-sm font-medium text-slate-700">Respuesta de ejemplo</label>
          <input type="text" name="rc_respuesta_muestra" value="<?= h($rc_resp_muestra) ?>"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"
            placeholder="Opcional, guía para el alumno">
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Feedback si acierta</label>
          <textarea name="rc_feedback_correcta" rows="3"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h($rc_fb_ok) ?></textarea>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Feedback si falla</label>
          <textarea name="rc_feedback_incorrecta" rows="3"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h($rc_fb_fail) ?></textarea>
        </div>
      </div>
    </div>
    <!-- /RC -->

    <!-- ====== RELLENAR HUECOS ====== -->
    <div id="bloqueRH" class="<?= $tipo === 'rellenar_huecos' ? '' : 'hidden' ?> border-t pt-4">
      <h3 class="text-sm font-semibold text-slate-700 mb-2">Configuración Rellenar huecos</h3>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Texto con huecos <span
            class="text-rose-600">*</span></label>
        <textarea name="rh_enunciado_html" rows="4"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"
          placeholder="Ej: &quot;El lenguaje {{1}} es para bases de datos {{2}}&quot;. Usa {{n}} para cada hueco."><?= h($rh_enunciado_html) ?></textarea>
        <p class="mt-1 text-xs text-slate-500">Usa marcadores <code>{{1}}</code>, <code>{{2}}</code>, ...</p>
      </div>

      <div class="mt-3">
        <label class="mb-1 block text-sm font-medium text-slate-700">Soluciones (JSON)</label>
        <textarea name="rh_huecos_json" rows="4"
          class="w-full font-mono rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"
          placeholder='["SQL","relacionales"]  — o —  [["SQL","Structured Query Language"],["relacionales","SQL"]]'><?= h($rh_huecos_json) ?></textarea>
      </div>

      <div class="grid gap-4 sm:grid-cols-3 mt-2">
        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" name="rh_case_sensitive" class="h-4 w-4 rounded border-slate-300" <?= $rh_case ? 'checked' : '' ?>>
          Sensible a mayúsculas
        </label>
        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" name="rh_normalizar_acentos" class="h-4 w-4 rounded border-slate-300" <?= $rh_acentos ? 'checked' : '' ?>>
          Normalizar acentos
        </label>
        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" name="rh_trim" class="h-4 w-4 rounded border-slate-300" <?= $rh_trim ? 'checked' : '' ?>>
          Ignorar espacios extremos
        </label>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 mt-2">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Puntuación máxima</label>
          <input type="number" min="0" name="rh_puntuacion_max" value="<?= h((string) $rh_pmax) ?>"
            class="w-48 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"
            placeholder="Ej: 10">
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 mt-2">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Feedback si acierta</label>
          <textarea name="rh_feedback_correcta" rows="3"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h($rh_fb_ok) ?></textarea>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Feedback si falla</label>
          <textarea name="rh_feedback_incorrecta" rows="3"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h($rh_fb_fail) ?></textarea>
        </div>
      </div>
    </div>
    <!-- /RH -->

    <!-- ====== BLOQUE ESPECÍFICO: OPCIÓN MÚLTIPLE ====== -->
    <div id="bloqueOM" class="<?= $tipo === 'opcion_multiple' ? '' : 'hidden' ?> border-t pt-4">
      <h3 class="text-sm font-semibold text-slate-700 mb-2">Configuración Opción múltiple</h3>

      <div class="space-y-3">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">
            Enunciado (HTML permitido) <span class="text-rose-600">*</span>
          </label>
          <textarea name="om_enunciado_html" rows="3"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h($om_enunciado_html) ?></textarea>
        </div>

        <div>
          <label class="mb-2 block text-sm font-medium text-slate-700">Opciones (marca la correcta)</label>

          <div id="omList" class="space-y-2">
            <?php foreach ($omOpts as $i => $opt): ?>
                <div class="om-row flex items-start gap-3">
                  <input type="radio" name="om_correcta" value="<?= (int) $i ?>" class="mt-2 h-4 w-4 border-slate-300"
                    <?= ((int) $opt['es_correcta'] === 1 ? 'checked' : '') ?>>
                  <textarea name="om_opciones[]" rows="2" placeholder="Opción <?= (int) $i + 1 ?>"
                    class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h((string) $opt['opcion_html']) ?></textarea>
                  <button type="button"
                    class="om-del inline-flex shrink-0 items-center rounded-lg border border-slate-300 px-2 py-1 text-xs text-slate-700 hover:bg-slate-50">
                    Eliminar
                  </button>
                </div>
            <?php endforeach; ?>
          </div>

          <div class="mt-2">
            <button type="button" id="omAdd"
              class="inline-flex items-center rounded-lg bg-slate-800 px-3 py-1.5 text-sm font-medium text-white hover:bg-slate-700">
              Añadir opción
            </button>
            <p class="mt-1 text-xs text-slate-500">Debe haber al menos 2 opciones con contenido y una marcada como
              correcta.</p>
          </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Feedback si acierta</label>
            <textarea name="om_feedback_correcta" rows="2"
              class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h($om_fb_ok) ?></textarea>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Feedback si falla</label>
            <textarea name="om_feedback_incorrecta" rows="2"
              class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h($om_fb_fail) ?></textarea>
          </div>
        </div>
      </div>
    </div>
    <!-- ====== /BLOQUE OM ====== -->

    <!-- ====== EMPAREJAR ====== -->
    <div id="bloqueEMP" class="<?= $tipo === 'emparejar' ? '' : 'hidden' ?> border-t pt-4">
      <h3 class="text-sm font-semibold text-slate-700 mb-2">Configuración Emparejar</h3>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm border border-slate-200 rounded-lg">
          <thead class="bg-slate-50">
            <tr class="text-left">
              <th class="p-2 border-b">Izquierda (HTML)</th>
              <th class="p-2 border-b">Derecha (HTML)</th>
              <th class="p-2 border-b">Alternativas derecha (JSON)</th>
              <th class="p-2 border-b">Grupo</th>
              <th class="p-2 border-b">Ord. Izq</th>
              <th class="p-2 border-b">Ord. Der</th>
              <th class="p-2 border-b">Activo</th>
              <th class="p-2 border-b"></th>
            </tr>
          </thead>
          <tbody id="empTBody">
            <?php
            $rowsEmp = $empRows ?: [
              ['izquierda_html' => '', 'derecha_html' => '', 'alternativas_derecha_json' => '', 'grupo' => '', 'orden_izq' => 1, 'orden_der' => 1, 'activo' => 1]
            ];
            foreach ($rowsEmp as $r):
              ?>
                <tr class="align-top">
                  <td class="p-2 border-b"><textarea name="emp_izq[]" rows="2"
                      class="w-64 rounded border border-slate-300 px-2 py-1"><?= h((string) ($r['izquierda_html'] ?? '')) ?></textarea>
                  </td>
                  <td class="p-2 border-b"><textarea name="emp_der[]" rows="2"
                      class="w-64 rounded border border-slate-300 px-2 py-1"><?= h((string) ($r['derecha_html'] ?? '')) ?></textarea>
                  </td>
                  <td class="p-2 border-b"><textarea name="emp_alt_der[]" rows="2"
                      class="w-64 font-mono rounded border border-slate-300 px-2 py-1"
                      placeholder='["Sinónimo 1","Sinónimo 2"]'><?= h((string) ($r['alternativas_derecha_json'] ?? '')) ?></textarea>
                  </td>
                  <td class="p-2 border-b"><input type="text" name="emp_grupo[]"
                      value="<?= h((string) ($r['grupo'] ?? '')) ?>" class="w-28 rounded border border-slate-300 px-2 py-1">
                  </td>
                  <td class="p-2 border-b"><input type="number" name="emp_orden_izq[]"
                      value="<?= h((string) ($r['orden_izq'] ?? 1)) ?>"
                      class="w-20 rounded border border-slate-300 px-2 py-1"></td>
                  <td class="p-2 border-b"><input type="number" name="emp_orden_der[]"
                      value="<?= h((string) ($r['orden_der'] ?? 1)) ?>"
                      class="w-20 rounded border border-slate-300 px-2 py-1"></td>
                  <td class="p-2 border-b"><input type="checkbox" name="emp_activo[]" <?= (int) ($r['activo'] ?? 1) ? 'checked' : '' ?>></td>
                  <td class="p-2 border-b"><button type="button"
                      class="text-rose-600 hover:underline emp-del-row">Eliminar</button></td>
                </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="mt-3">
        <button type="button" id="empAddRow"
          class="inline-flex items-center rounded-lg bg-slate-800 px-3 py-1.5 text-sm font-medium text-white hover:bg-slate-700">Añadir
          fila</button>
      </div>
    </div>
    <!-- /EMP -->

    <!-- ===== BLOQUE ESTADÍSTICAS Y VALORACIÓN (AL FINAL) ===== -->
    <div class="mt-4 border-t pt-4">
      <h3 class="text-sm font-semibold text-slate-700 mb-2">Estadísticas de uso y valoración</h3>

      <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
          <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Usada por</div>
          <div class="mt-1 text-lg font-semibold text-slate-800">
            <?= $statsUso['profesores_usan'] !== null ? (int) $statsUso['profesores_usan'] : '—' ?>
            <span class="text-sm font-normal text-slate-600">profesores</span>
          </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
          <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Incluida en</div>
          <div class="mt-1 text-lg font-semibold text-slate-800">
            <?= $statsUso['examenes'] !== null ? (int) $statsUso['examenes'] : '—' ?>
            <span class="text-sm font-normal text-slate-600">exámenes</span>
          </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
          <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Valoración media</div>
          <div class="mt-1 text-lg font-semibold text-slate-800">
            <?php if ($mediaVal !== null): ?>
                <?= number_format($mediaVal, 1, ',', '') ?> / 5
                <span class="text-sm font-normal text-slate-600">(<?= $totalVals ?>
                  voto<?= $totalVals === 1 ? '' : 's' ?>)</span>
            <?php else: ?>
                <span class="text-sm font-normal text-slate-600">Sin valoraciones todavía</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="mt-3">
        <label class="mb-1 block text-sm font-medium text-slate-700">
          Tu valoración de esta actividad
        </label>
        <select name="valoracion"
          class="inline-block w-48 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
          <option value="">— Sin valorar —</option>
          <?php for ($i = 1; $i <= 5; $i++): ?>
              <option value="<?= $i ?>" <?= $miValActual === $i ? 'selected' : '' ?>>
                <?= $i ?> ★
              </option>
          <?php endfor; ?>
        </select>
        <p class="mt-1 text-xs text-slate-500">
          Esta valoración es privada y sólo suma a la media global.
        </p>
      </div>
    </div>
    <!-- /ESTADÍSTICAS -->

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
      <a href="<?= $basePublic ?>/admin/actividades/index.php"
        class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Cancelar</a>
      <button
        class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
        Guardar cambios
      </button>
    </div>

  </form>
</div>

<script>
  // Datos precargados
  const cursosAll = <?= json_encode($cursos, JSON_UNESCAPED_UNICODE) ?>;
  const asigsAll = <?= json_encode($asigs, JSON_UNESCAPED_UNICODE) ?>;
  const temasAll = <?= json_encode($temas, JSON_UNESCAPED_UNICODE) ?>;

  const selFam = document.getElementById('familia_id');
  const selCur = document.getElementById('curso_id');
  const selAsi = document.getElementById('asignatura_id');
  const selTem = document.getElementById('tema_id');
  const selTipo = document.getElementById('tipo');

  const bloqueTarea = document.getElementById('bloqueTarea');
  const bloqueVF = document.getElementById('bloqueVF');
  const bloqueRC = document.getElementById('bloqueRC');
  const bloqueRH = document.getElementById('bloqueRH');
  const bloqueOM = document.getElementById('bloqueOM');
  const bloqueEMP = document.getElementById('bloqueEMP');

  const rcModoSel = document.getElementById('rc_modo');
  const rcBloqPal = document.getElementById('rc_palabras');
  const rcBloqReg = document.getElementById('rc_regex');

  const currentFam = <?= (int) $familia_id ?>;
  const currentCur = <?= (int) $curso_id ?>;
  const currentAsi = <?= (int) $asignatura_id ?>;
  const currentTem = <?= (int) $tema_id ?>;

  function opt(v, t) { const o = document.createElement('option'); o.value = v; o.textContent = t; return o; }

  function renderCursos(fid, selected = 0) {
    selCur.innerHTML = ''; selCur.appendChild(opt('', '— Curso —'));
    cursosAll
      .filter(c => parseInt(c.familia_id, 10) === parseInt(fid || '0', 10))
      .forEach(c => {
        const o = opt(c.id, c.nombre);
        if (parseInt(selected, 10) === parseInt(c.id, 10)) o.selected = true;
        selCur.appendChild(o);
      });
  }
  function renderAsigs(cid, selected = 0) {
    selAsi.innerHTML = ''; selAsi.appendChild(opt('', '— Asignatura —'));
    asigsAll
      .filter(a => parseInt(a.curso_id, 10) === parseInt(cid || '0', 10))
      .forEach(a => {
        const o = opt(a.id, a.nombre);
        if (parseInt(selected, 10) === parseInt(a.id, 10)) o.selected = true;
        selAsi.appendChild(o);
      });
  }
  function renderTemas(aid, selected = 0) {
    selTem.innerHTML = ''; selTem.appendChild(opt('', '— Tema (opcional) —'));
    temasAll
      .filter(t => parseInt(t.asignatura_id, 10) === parseInt(aid || '0', 10))
      .forEach(t => {
        const label = (t.numero ? ('T' + t.numero + ' · ') : '') + t.nombre;
        const o = opt(t.id, label);
        if (parseInt(selected, 10) === parseInt(t.id, 10)) o.selected = true;
        selTem.appendChild(o);
      });
  }

  if (currentFam > 0) renderCursos(currentFam, currentCur);
  if (currentCur > 0) renderAsigs(currentCur, currentAsi);
  if (currentAsi > 0) renderTemas(currentAsi, currentTem);

  selFam.addEventListener('change', () => {
    const fid = parseInt(selFam.value || '0', 10);
    renderCursos(fid, 0);
    selAsi.innerHTML = ''; selAsi.appendChild(opt('', '— Asignatura —'));
    selTem.innerHTML = ''; selTem.appendChild(opt('', '— Tema (opcional) —'));
  }, { passive: true });

  selCur.addEventListener('change', () => {
    const cid = parseInt(selCur.value || '0', 10);
    renderAsigs(cid, 0);
    selTem.innerHTML = ''; selTem.appendChild(opt('', '— Tema (opcional) —'));
  }, { passive: true });

  selAsi.addEventListener('change', () => {
    const aid = parseInt(selAsi.value || '0', 10);
    renderTemas(aid, 0);
  }, { passive: true });

  function toggleBloques() {
    const t = selTipo.value;
    if (bloqueTarea) bloqueTarea.classList.toggle('hidden', t !== 'tarea');
    if (bloqueVF) bloqueVF.classList.toggle('hidden', t !== 'verdadero_falso');
    if (bloqueRC) bloqueRC.classList.toggle('hidden', t !== 'respuesta_corta');
    if (bloqueRH) bloqueRH.classList.toggle('hidden', t !== 'rellenar_huecos');
    if (bloqueOM) bloqueOM.classList.toggle('hidden', t !== 'opcion_multiple');
    if (bloqueEMP) bloqueEMP.classList.toggle('hidden', t !== 'emparejar');
  }

  function toggleRcSub() {
    if (!rcModoSel) return;
    const m = rcModoSel.value || 'palabras_clave';
    if (rcBloqPal) rcBloqPal.classList.toggle('hidden', m !== 'palabras_clave');
    if (rcBloqReg) rcBloqReg.classList.toggle('hidden', m !== 'regex');
  }

  selTipo.addEventListener('change', toggleBloques, { passive: true });
  if (rcModoSel) rcModoSel.addEventListener('change', toggleRcSub, { passive: true });

  toggleBloques();
  toggleRcSub();

  // ===== Emparejar: añadir/eliminar filas =====
  const empTBody = document.getElementById('empTBody');
  const empAddRow = document.getElementById('empAddRow');
  function bindEmpDelete(btn) {
    btn.addEventListener('click', () => {
      const tr = btn.closest('tr');
      if (tr && empTBody.rows.length > 1) tr.remove();
      else {
        tr.querySelectorAll('textarea,input').forEach(el => {
          if (el.type === 'checkbox') el.checked = true;
          else el.value = (el.name.includes('orden_') ? '1' : '');
        });
      }
    }, { passive: true });
  }
  if (empTBody) {
    empTBody.querySelectorAll('.emp-del-row').forEach(bindEmpDelete);
  }
  if (empAddRow) {
    empAddRow.addEventListener('click', () => {
      const tr = document.createElement('tr');
      tr.className = 'align-top';
      tr.innerHTML = `
        <td class="p-2 border-b"><textarea name="emp_izq[]" rows="2" class="w-64 rounded border border-slate-300 px-2 py-1"></textarea></td>
        <td class="p-2 border-b"><textarea name="emp_der[]" rows="2" class="w-64 rounded border border-slate-300 px-2 py-1"></textarea></td>
        <td class="p-2 border-b"><textarea name="emp_alt_der[]" rows="2" class="w-64 font-mono rounded border border-slate-300 px-2 py-1" placeholder='["Sinónimo 1","Sinónimo 2"]'></textarea></td>
        <td class="p-2 border-b"><input type="text" name="emp_grupo[]" class="w-28 rounded border border-slate-300 px-2 py-1"></td>
        <td class="p-2 border-b"><input type="number" name="emp_orden_izq[]" value="1" class="w-20 rounded border border-slate-300 px-2 py-1"></td>
        <td class="p-2 border-b"><input type="number" name="emp_orden_der[]" value="1" class="w-20 rounded border border-slate-300 px-2 py-1"></td>
        <td class="p-2 border-b"><input type="checkbox" name="emp_activo[]" checked></td>
        <td class="p-2 border-b"><button type="button" class="text-rose-600 hover:underline emp-del-row">Eliminar</button></td>
      `;
      empTBody.appendChild(tr);
      bindEmpDelete(tr.querySelector('.emp-del-row'));
    }, { passive: true });
  }

  // ===== Opción múltiple: añadir/eliminar/reindexar =====
  (function () {
    const omList = document.getElementById('omList');
    const omAdd = document.getElementById('omAdd');

    function reindexOm() {
      if (!omList) return;
      const rows = omList.querySelectorAll('.om-row');
      rows.forEach((row, i) => {
        const radio = row.querySelector('input[type=radio][name="om_correcta"]');
        const ta = row.querySelector('textarea[name="om_opciones[]"]');
        if (radio) radio.value = i;
        if (ta && ta.placeholder) ta.placeholder = `Opción ${i + 1}`;
      });
    }

    function bindDel(btn) {
      btn.addEventListener('click', () => {
        const row = btn.closest('.om-row');
        if (!row || !omList) return;
        const rows = omList.querySelectorAll('.om-row');
        if (rows.length > 2) {
          const wasChecked = row.querySelector('input[type=radio]')?.checked;
          row.remove();
          reindexOm();
          if (wasChecked) {
            const radios = omList.querySelectorAll('input[type=radio][name="om_correcta"]');
            if (radios.length) radios[0].checked = true;
          }
        } else {
          row.querySelectorAll('textarea').forEach(t => t.value = '');
          const r = row.querySelector('input[type=radio]');
          if (r) r.checked = false;
        }
      }, { passive: true });
    }

    function createRow() {
      const div = document.createElement('div');
      div.className = 'om-row flex items-start gap-3';
      div.innerHTML = `
        <input type="radio" name="om_correcta" class="mt-2 h-4 w-4 border-slate-300">
        <textarea name="om_opciones[]" rows="2" class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"></textarea>
        <button type="button" class="om-del inline-flex shrink-0 items-center rounded-lg border border-slate-300 px-2 py-1 text-xs text-slate-700 hover:bg-slate-50">
          Eliminar
        </button>
      `;
      omList.appendChild(div);
      bindDel(div.querySelector('.om-del'));
      reindexOm();
    }

    if (omList) {
      omList.querySelectorAll('.om-del').forEach(bindDel);
      reindexOm();
    }
    if (omAdd) {
      omAdd.addEventListener('click', () => {
        createRow();
      }, { passive: true });
    }
  })();

</script>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>