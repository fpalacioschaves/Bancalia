<?php
// /public/admin/actividades/create.php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();

$u = current_user();
$userEmail = (string) ($u['email'] ?? '');
$role = (string) ($u['role'] ?? '');
$profesorId = (int) ($u['profesor_id'] ?? 0);

// Admin: solo lectura → no puede crear
if ($role === 'admin') {
  flash('error', 'El administrador no puede crear actividades.');
  header('Location: ' . PUBLIC_URL . '/admin/actividades/index.php');
  exit;
}

// Resolver profesor_id por email si no viene
if ($role === 'profesor' && $profesorId <= 0 && $userEmail !== '') {
  $stP = pdo()->prepare('SELECT id FROM profesores WHERE email = :e LIMIT 1');
  $stP->execute([':e' => $userEmail]);
  if ($rowP = $stP->fetch()) {
    $profesorId = (int) $rowP['id'];
    $_SESSION['user']['profesor_id'] = $profesorId; // persistimos en sesión
  }
}

// Profesor sin profesor_id aún
if ($role !== 'profesor' || $profesorId <= 0) {
  flash('error', 'No se ha podido identificar tu ficha de profesor.');
  header('Location: ' . PUBLIC_URL . '/mi-perfil.php');
  exit;
}

// 🔹 NUEVO: obtener centro_id del profesor
$centroId = null;
$stCentro = pdo()->prepare('SELECT centro_id FROM profesores WHERE id = :id LIMIT 1');
$stCentro->execute([':id' => $profesorId]);
$centroId = (int) ($stCentro->fetchColumn() ?? 0);
if ($centroId <= 0) {
  $centroId = null; // por si el profesor aún no tiene centro asignado
}

// Datos para selects (precargados)
try {
  $familias = pdo()->query("SELECT id, nombre FROM familias_profesionales WHERE is_active=1 ORDER BY nombre ASC")->fetchAll();
  $cursos = pdo()->query("SELECT id, nombre, familia_id, orden FROM cursos WHERE is_active=1 ORDER BY familia_id ASC, orden ASC, nombre ASC")->fetchAll();
  $asigs = pdo()->query("SELECT id, nombre, curso_id, familia_id, orden FROM asignaturas WHERE is_active=1 ORDER BY familia_id ASC, curso_id ASC, orden ASC, nombre ASC")->fetchAll();
  $temas = pdo()->query("SELECT id, nombre, asignatura_id, numero FROM temas ORDER BY asignatura_id ASC, numero ASC, nombre ASC")->fetchAll();
} catch (Throwable $e) {
  die('Error cargando datos base: ' . h($e->getMessage()));
}

$errors = [];

// POST: guardar actividad (campos comunes + bloques específicos)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $actividadService = new ActividadService(pdo());
    $actividadId = $actividadService->create($_POST, $profesorId, $centroId);

    flash('success', 'Actividad creada correctamente.');
    header('Location: ' . PUBLIC_URL . '/admin/actividades/index.php');
    exit;

  } catch (Throwable $e) {
    $errors[] = $e->getMessage();
  }
}

// Render
require_once __DIR__ . '/../../../partials/header.php';
?>

<div class="mb-6 flex items-center justify-between">
  <div>
    <h1 class="text-xl font-semibold tracking-tight">Nueva actividad</h1>
    <p class="mt-1 text-sm text-slate-600">Crea una actividad. Los campos específicos del tipo aparecen cuando proceda.
    </p>
  </div>
  <a href="<?= PUBLIC_URL ?>/admin/actividades/index.php"
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
      <input name="titulo" type="text" required value="<?= h($_POST['titulo'] ?? '') ?>"
        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Tipo <span class="text-rose-600">*</span></label>
        <select name="tipo" id="tipo" required
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
          <?php
          $tipoSel = (string) ($_POST['tipo'] ?? '');
          $opts = [
            'opcion_multiple' => 'Opción múltiple',
            'verdadero_falso' => 'Veradero/falso',
            'respuesta_corta' => 'Respuesta corta',
            'rellenar_huecos' => 'Rellenar huecos',
            'emparejar' => 'Emparejar',
            'tarea' => 'Tarea / entrega abierta',
          ];
          foreach ($opts as $val => $lab) {
            $sel = ($tipoSel === $val) ? 'selected' : '';
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
          <?php
          $difSel = (string) ($_POST['dificultad'] ?? '');
          $niveles = ['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta'];
          foreach ($niveles as $val => $label) {
            $sel = ($difSel === $val) ? 'selected' : '';
            echo '<option value="' . h($val) . '" ' . $sel . '>' . h($label) . '</option>';
          }
          ?>
        </select>
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Familia/Grado <span
            class="text-rose-600">*</span></label>
        <select id="familia_id" name="familia_id" required
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
          <option value="">— Selecciona familia —</option>
          <?php
          $famSel = (int) ($_POST['familia_id'] ?? 0);
          foreach ($familias as $f) {
            $sel = ($famSel === (int) $f['id']) ? 'selected' : '';
            echo '<option value="' . (int) $f['id'] . '" ' . $sel . '>' . h($f['nombre']) . '</option>';
          }
          ?>
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
        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h($_POST['descripcion'] ?? '') ?></textarea>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Visibilidad</label>
        <select name="visibilidad"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
          <?php
          $visSel = (string) ($_POST['visibilidad'] ?? 'privada');
          $visOps = [
            'privada' => 'Privada',
            'centro' => 'Centro',
            'publica' => 'Pública',
          ];
          foreach ($visOps as $v => $lab) {
            $sel = ($visSel === $v) ? 'selected' : '';
            echo '<option value="' . h($v) . '" ' . $sel . '>' . h($lab) . '</option>';
          }
          ?>
        </select>

      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Estado</label>
        <select name="estado"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
          <?php
          $estSel = (string) ($_POST['estado'] ?? 'borrador');
          foreach (['borrador' => 'Borrador', 'publicada' => 'Publicada'] as $v => $lab) {
            $sel = ($estSel === $v) ? 'selected' : '';
            echo '<option value="' . $v . '" ' . $sel . '>' . $lab . '</option>';
          }
          ?>
        </select>
      </div>
    </div>

    <!-- ====== BLOQUE ESPECÍFICO: OPCIÓN MÚLTIPLE ====== -->
    <div id="bloqueOM" class="hidden border-t pt-4">
      <h3 class="text-sm font-semibold text-slate-700 mb-2">Configuración Opción múltiple</h3>

      <div class="space-y-3">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Enunciado (HTML permitido) <span
              class="text-rose-600">*</span></label>
          <textarea name="om_enunciado_html" rows="3"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h($_POST['om_enunciado_html'] ?? '') ?></textarea>
        </div>

        <div>
          <label class="mb-2 block text-sm font-medium text-slate-700">Opciones (marca la correcta)</label>
          <?php
          // reconstruimos 4 inputs por defecto
          $optsPrev = $_POST['om_opciones'] ?? [];
          if (!is_array($optsPrev))
            $optsPrev = [];
          for ($i = 0; $i < 4; $i++):
            $val = (string) ($optsPrev[$i] ?? '');
            ?>
            <div class="flex items-start gap-3 mb-2">
              <input type="radio" name="om_correcta" value="<?= $i ?>" class="mt-2 h-4 w-4 border-slate-300"
                <?= (isset($_POST['om_correcta']) && (string) $_POST['om_correcta'] === (string) $i) ? 'checked' : '' ?>>
              <textarea name="om_opciones[]" rows="2" placeholder="Opción <?= $i + 1 ?>"
                class="flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h($val) ?></textarea>
            </div>
          <?php endfor; ?>
          <p class="mt-1 text-xs text-slate-500">Deja vacías las que no uses. Debe haber al menos 2 opciones con
            contenido.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Feedback si acierta</label>
            <textarea name="om_feedback_correcta" rows="2"
              class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h($_POST['om_feedback_correcta'] ?? '') ?></textarea>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Feedback si falla</label>
            <textarea name="om_feedback_incorrecta" rows="2"
              class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h($_POST['om_feedback_incorrecta'] ?? '') ?></textarea>
          </div>
        </div>
      </div>
    </div>
    <!-- ====== /BLOQUE OM ====== -->

    <!-- ====== BLOQUE ESPECÍFICO EMPAREJAR ====== -->
    <div id="bloqueEMP" class="hidden border-t pt-4">
      <h3 class="text-sm font-semibold text-slate-700 mb-2">Configuración Emparejar</h3>

      <p class="text-xs text-slate-600 mb-2">Introduce pares (izquierda ↔ derecha). Se ignorarán filas vacías.</p>

      <div id="empRows" class="space-y-2">
        <?php
        $empIzq = $_POST['emp_izq'] ?? [''];
        $empDer = $_POST['emp_der'] ?? [''];
        if (!is_array($empIzq))
          $empIzq = [''];
        if (!is_array($empDer))
          $empDer = [''];
        $n = max(count($empIzq), count($empDer), 4);
        for ($i = 0; $i < $n; $i++):
          ?>
          <div class="grid gap-2 sm:grid-cols-2">
            <input type="text" name="emp_izq[]" value="<?= h((string) ($empIzq[$i] ?? '')) ?>"
              class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"
              placeholder="Elemento izquierdo">
            <input type="text" name="emp_der[]" value="<?= h((string) ($empDer[$i] ?? '')) ?>"
              class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"
              placeholder="Elemento derecho">
          </div>
        <?php endfor; ?>
      </div>

      <button type="button" id="empAddRow"
        class="mt-2 inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100">
        + Añadir fila
      </button>
    </div>
    <!-- ====== /BLOQUE EMPAREJAR ====== -->

    <!-- ====== BLOQUE ESPECÍFICO TAREA ====== -->
    <div id="bloqueTarea" class="hidden border-t pt-4">
      <h3 class="text-sm font-semibold text-slate-700 mb-2">Opciones de Tarea / Entrega</h3>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Instrucciones para el alumno</label>
        <textarea name="t_instrucciones" rows="3"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h($_POST['t_instrucciones'] ?? '') ?></textarea>
      </div>

      <div class="grid gap-4 sm:grid-cols-3">
        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" name="t_perm_texto" class="h-4 w-4 rounded border-slate-300"
            <?= isset($_POST['t_perm_texto']) ? 'checked' : '' ?>>
          Permitir texto
        </label>
        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" name="t_perm_archivo" class="h-4 w-4 rounded border-slate-300"
            <?= isset($_POST['t_perm_archivo']) ? 'checked' : '' ?>>
          Permitir archivos
        </label>
        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" name="t_perm_enlace" class="h-4 w-4 rounded border-slate-300"
            <?= isset($_POST['t_perm_enlace']) ? 'checked' : '' ?>>
          Permitir enlaces
        </label>
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Máx. archivos</label>
          <input type="number" min="0" name="t_max_archivos" value="<?= h((string) ($_POST['t_max_archivos'] ?? '')) ?>"
            class="w-40 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Máx. tamaño por archivo (MB)</label>
          <input type="number" min="0" name="t_max_peso_mb" value="<?= h((string) ($_POST['t_max_peso_mb'] ?? '')) ?>"
            class="w-56 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-3">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Evaluación</label>
          <select name="t_evaluacion_modo"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
            <?php
            $modoSel = (string) ($_POST['t_evaluacion_modo'] ?? '');
            $ops = ['' => '— Sin evaluación —', 'puntos' => 'Puntuación', 'rubrica' => 'Rúbrica'];
            foreach ($ops as $v => $lab) {
              $sel = ($modoSel === $v) ? 'selected' : '';
              echo '<option value="' . h($v) . '" ' . $sel . '>' . h($lab) . '</option>';
            }
            ?>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Puntuación máxima</label>
          <input type="number" min="0" name="t_puntuacion_max"
            value="<?= h((string) ($_POST['t_puntuacion_max'] ?? '')) ?>"
            class="w-48 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"
            placeholder="Ej: 10">
        </div>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Rúbrica (JSON)</label>
        <textarea name="t_rubrica_json" rows="4"
          class="w-full font-mono rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"
          placeholder='[{"criterio":"Presentación","max":2},{"criterio":"Contenido","max":8}]'><?= h($_POST['t_rubrica_json'] ?? '') ?></textarea>
        <p class="mt-1 text-xs text-slate-500">Opcional. Solo si usas evaluación por rúbrica.</p>
      </div>
    </div>
    <!-- ====== /BLOQUE TAREA ====== -->

    <!-- ====== BLOQUE ESPECÍFICO VERDADERO/FALSO ====== -->
    <div id="bloqueVF" class="hidden border-t pt-4">
      <h3 class="text-sm font-semibold text-slate-700 mb-2">Configuración Verdadero/Falso</h3>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Respuesta correcta <span
            class="text-rose-600">*</span></label>
        <select name="vf_respuesta_correcta"
          class="w-48 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
          <?php
          $vfSel = (string) ($_POST['vf_respuesta_correcta'] ?? '');
          foreach (['verdadero' => 'Verdadero', 'falso' => 'Falso'] as $v => $lab) {
            $sel = ($vfSel === $v) ? 'selected' : '';
            echo '<option value="' . h($v) . '" ' . $sel . '>' . h($lab) . '</option>';
          }
          ?>
        </select>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 mt-3">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Feedback si acierta</label>
          <textarea name="vf_feedback_correcta" rows="3"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h($_POST['vf_feedback_correcta'] ?? '') ?></textarea>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Feedback si falla</label>
          <textarea name="vf_feedback_incorrecta" rows="3"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h($_POST['vf_feedback_incorrecta'] ?? '') ?></textarea>
        </div>
      </div>
    </div>
    <!-- ====== /BLOQUE VERDADERO/FALSO ====== -->

    <!-- ====== BLOQUE ESPECÍFICO RESPUESTA CORTA ====== -->
    <div id="bloqueRC" class="hidden border-t pt-4">
      <h3 class="text-sm font-semibold text-slate-700 mb-2">Configuración Respuesta corta</h3>

      <div class="grid gap-4 sm:grid-cols-3">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Modo de corrección <span
              class="text-rose-600">*</span></label>
          <select name="rc_modo" id="rc_modo"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
            <?php
            $rcModo = (string) ($_POST['rc_modo'] ?? 'palabras_clave');
            $ops = ['palabras_clave' => 'Palabras clave', 'regex' => 'Regex'];
            foreach ($ops as $v => $lab) {
              $sel = ($rcModo === $v) ? 'selected' : '';
              echo '<option value="' . h($v) . '" ' . $sel . '>' . h($lab) . '</option>';
            }
            ?>
          </select>
        </div>
        <label class="inline-flex items-center gap-2 text-sm mt-6">
          <input type="checkbox" name="rc_case_sensitive" class="h-4 w-4 rounded border-slate-300"
            <?= isset($_POST['rc_case_sensitive']) ? 'checked' : '' ?>>
          Sensible a mayúsculas
        </label>
        <label class="inline-flex items-center gap-2 text-sm mt-6">
          <input type="checkbox" name="rc_normalizar_acentos" class="h-4 w-4 rounded border-slate-300"
            <?= isset($_POST['rc_normalizar_acentos']) ? 'checked' : '' ?>>
          Normalizar acentos
        </label>
        <label class="inline-flex items-center gap-2 text-sm mt-6">
          <input type="checkbox" name="rc_trim" class="h-4 w-4 rounded border-slate-300"
            <?= isset($_POST['rc_trim']) ? 'checked' : '' ?>>
          Ignorar espacios extremos
        </label>
      </div>

      <!-- Sub-bloque: Palabras clave -->
      <div id="rc_palabras" class="mt-3 hidden">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Palabras clave (JSON)</label>
          <textarea name="rc_palabras_clave_json" rows="4"
            class="w-full font-mono rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"
            placeholder='[{"palabra":"osmosis","peso":1},{"palabra":"membrana","peso":1}]'><?= h($_POST['rc_palabras_clave_json'] ?? '') ?></textarea>
          <p class="mt-1 text-xs text-slate-500">Cada objeto puede incluir "palabra" y "peso".</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 mt-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">% coincidencia mínima</label>
            <input type="number" min="0" max="100" name="rc_coincidencia_minima"
              value="<?= h((string) ($_POST['rc_coincidencia_minima'] ?? '')) ?>"
              class="w-48 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"
              placeholder="Ej: 60">
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Puntuación máxima</label>
            <input type="number" min="0" name="rc_puntuacion_max"
              value="<?= h((string) ($_POST['rc_puntuacion_max'] ?? '')) ?>"
              class="w-48 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"
              placeholder="Ej: 10">
          </div>
        </div>
      </div>

      <!-- Sub-bloque: Regex -->
      <div id="rc_regex" class="mt-3 hidden">
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Patrón regex <span
                class="text-rose-600">*</span></label>
            <input type="text" name="rc_regex_pattern" value="<?= h((string) ($_POST['rc_regex_pattern'] ?? '')) ?>"
              class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"
              placeholder="^\\s*respuesta\\s*$">
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Flags</label>
            <input type="text" name="rc_regex_flags" value="<?= h((string) ($_POST['rc_regex_flags'] ?? '')) ?>"
              class="w-48 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"
              placeholder="i, u, m...">
          </div>
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-3 mt-3">
        <div class="sm:col-span-3">
          <label class="mb-1 block text-sm font-medium text-slate-700">Respuesta de ejemplo</label>
          <input type="text" name="rc_respuesta_muestra"
            value="<?= h((string) ($_POST['rc_respuesta_muestra'] ?? '')) ?>"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"
            placeholder="Opcional, guía para el alumno">
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Feedback si acierta</label>
          <textarea name="rc_feedback_correcta" rows="3"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h($_POST['rc_feedback_correcta'] ?? '') ?></textarea>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Feedback si falla</label>
          <textarea name="rc_feedback_incorrecta" rows="3"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h($_POST['rc_feedback_incorrecta'] ?? '') ?></textarea>
        </div>
      </div>
    </div>
    <!-- ====== /BLOQUE RESPUESTA CORTA ====== -->

    <!-- ====== BLOQUE ESPECÍFICO RELLENAR HUECOS ====== -->
    <div id="bloqueRH" class="hidden border-t pt-4">
      <h3 class="text-sm font-semibold text-slate-700 mb-2">Configuración Rellenar huecos</h3>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Texto con huecos <span
            class="text-rose-600">*</span></label>
        <textarea name="rh_plantilla" rows="4"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"
          placeholder="Ejemplo: &quot;El protocolo {{1}} funciona en la capa {{2}} del modelo OSI.&quot;"><?= h($_POST['rh_plantilla'] ?? '') ?></textarea>
        <p class="mt-1 text-xs text-slate-500">Usa marcadores <code>{{1}}</code>, <code>{{2}}</code>, ... para cada
          hueco.</p>
      </div>

      <div class="grid gap-4 sm:grid-cols-3 mt-2">
        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" name="rh_case_sensitive" class="h-4 w-4 rounded border-slate-300"
            <?= isset($_POST['rh_case_sensitive']) ? 'checked' : '' ?>>
          Sensible a mayúsculas
        </label>
        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" name="rh_normalizar_acentos" class="h-4 w-4 rounded border-slate-300"
            <?= isset($_POST['rh_normalizar_acentos']) ? 'checked' : '' ?>>
          Normalizar acentos
        </label>
        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" name="rh_trim" class="h-4 w-4 rounded border-slate-300"
            <?= isset($_POST['rh_trim']) ? 'checked' : '' ?>>
          Ignorar espacios extremos
        </label>
      </div>

      <div class="mt-3">
        <label class="mb-1 block text-sm font-medium text-slate-700">Soluciones (JSON) <span
            class="text-rose-600">*</span></label>
        <textarea name="rh_soluciones_json" rows="4"
          class="w-full font-mono rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"
          placeholder='["TCP","Transporte"]  — o —  [["TCP","Protocolo de Control de Transmisión"],["Capa 4","Transporte"]]'><?= h($_POST['rh_soluciones_json'] ?? '') ?></textarea>
        <p class="mt-1 text-xs text-slate-500">
          Opción 1 (array plano): una solución por hueco si solo hay 1.<br>
          Opción 2 (array de arrays): lista de soluciones por cada hueco en orden (<code>{{1}}</code>,
          <code>{{2}}</code>, ...).
        </p>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 mt-2">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Puntuación máxima</label>
          <input type="number" min="0" name="rh_puntuacion_max"
            value="<?= h((string) ($_POST['rh_puntuacion_max'] ?? '')) ?>"
            class="w-48 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"
            placeholder="Ej: 10">
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 mt-2">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Feedback si acierta</label>
          <textarea name="rh_feedback_correcta" rows="3"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h($_POST['rh_feedback_correcta'] ?? '') ?></textarea>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Feedback si falla</label>
          <textarea name="rh_feedback_incorrecta" rows="3"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h($_POST['rh_feedback_incorrecta'] ?? '') ?></textarea>
        </div>
      </div>
    </div>
    <!-- ====== /BLOQUE RELLENAR HUECOS ====== -->

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
      <a href="<?= PUBLIC_URL ?>/admin/actividades/index.php"
        class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Cancelar</a>
      <button
        class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
        Guardar actividad
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

  // Bloques específicos
  const bloqueOM = document.getElementById('bloqueOM');
  const bloqueEMP = document.getElementById('bloqueEMP');
  const bloqueTarea = document.getElementById('bloqueTarea');
  const bloqueVF = document.getElementById('bloqueVF');
  const bloqueRC = document.getElementById('bloqueRC');
  const bloqueRH = document.getElementById('bloqueRH');

  // RC subbloques
  const rcModoSel = document.getElementById('rc_modo');
  const rcBloqPal = document.getElementById('rc_palabras');
  const rcBloqReg = document.getElementById('rc_regex');

  const currentFam = <?= (int) ($_POST['familia_id'] ?? 0) ?>;
  const currentCur = <?= (int) ($_POST['curso_id'] ?? 0) ?>;
  const currentAsi = <?= (int) ($_POST['asignatura_id'] ?? 0) ?>;
  const currentTem = <?= (int) ($_POST['tema_id'] ?? 0) ?>;
  const currentTipo = <?= json_encode((string) ($_POST['tipo'] ?? '')) ?>;
  const currentRcModo = <?= json_encode((string) ($_POST['rc_modo'] ?? 'palabras_clave')) ?>;

  function opt(v, t) { const o = document.createElement('option'); o.value = v; o.textContent = t; return o; }

  function renderCursos(fid, selected = 0) {
    selCur.innerHTML = ''; selCur.appendChild(opt('', '— Curso —'));
    cursosAll.filter(c => parseInt(c.familia_id, 10) === parseInt(fid || '0', 10))
      .forEach(c => { const o = opt(c.id, c.nombre); if (parseInt(selected, 10) === parseInt(c.id, 10)) o.selected = true; selCur.appendChild(o); });
  }
  function renderAsigs(cid, selected = 0) {
    selAsi.innerHTML = ''; selAsi.appendChild(opt('', '— Asignatura —'));
    asigsAll.filter(a => parseInt(a.curso_id, 10) === parseInt(cid || '0', 10))
      .forEach(a => { const o = opt(a.id, a.nombre); if (parseInt(selected, 10) === parseInt(a.id, 10)) o.selected = true; selAsi.appendChild(o); });
  }
  function renderTemas(aid, selected = 0) {
    selTem.innerHTML = ''; selTem.appendChild(opt('', '— Tema (opcional) —'));
    temasAll.filter(t => parseInt(t.asignatura_id, 10) === parseInt(aid || '0', 10))
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
    if (bloqueOM) bloqueOM.classList.toggle('hidden', t !== 'opcion_multiple');
    if (bloqueEMP) bloqueEMP.classList.toggle('hidden', t !== 'emparejar');
    if (bloqueTarea) bloqueTarea.classList.toggle('hidden', t !== 'tarea');
    if (bloqueVF) bloqueVF.classList.toggle('hidden', t !== 'verdadero_falso');
    if (bloqueRC) bloqueRC.classList.toggle('hidden', t !== 'respuesta_corta');
    if (bloqueRH) bloqueRH.classList.toggle('hidden', t !== 'rellenar_huecos');
  }

  function toggleRcSub() {
    if (!rcModoSel) return;
    const m = rcModoSel.value || 'palabras_clave';
    if (rcBloqPal) rcBloqPal.classList.toggle('hidden', m !== 'palabras_clave');
    if (rcBloqReg) rcBloqReg.classList.toggle('hidden', m !== 'regex');
  }

  selTipo.addEventListener('change', toggleBloques, { passive: true });
  if (rcModoSel) rcModoSel.addEventListener('change', toggleRcSub, { passive: true });

  // Botón añadir fila (Emparejar)
  const empContainer = document.getElementById('empRows');
  const empAdd = document.getElementById('empAddRow');
  if (empAdd && empContainer) {
    empAdd.addEventListener('click', () => {
      const row = document.createElement('div');
      row.className = 'grid gap-2 sm:grid-cols-2';
      row.innerHTML = `
        <input type="text" name="emp_izq[]" class="rounded-lg border border-slate-300 bg_WHITE px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400" placeholder="Elemento izquierdo">
        <input type="text" name="emp_der[]" class="rounded-lg border border-slate-300 bg_WHITE px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400" placeholder="Elemento derecho">
      `;
      empContainer.appendChild(row);
    }, { passive: true });
  }

  // Estado inicial (mostrar bloque según el tipo actual)
  if (<?= json_encode((string) ($_POST['tipo'] ?? '')) ?>) {
    selTipo.value = <?= json_encode((string) ($_POST['tipo'] ?? '')) ?>;
  }
  toggleBloques();

  // Subbloques RC
  if (rcModoSel && <?= json_encode((string) ($_POST['rc_modo'] ?? 'palabras_clave')) ?>) {
    rcModoSel.value = <?= json_encode((string) ($_POST['rc_modo'] ?? 'palabras_clave')) ?>;
  }
  toggleRcSub();
</script>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>