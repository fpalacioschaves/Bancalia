<?php
// /public/admin/actividades/edit.php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();
$u = current_user();

/**
 * Modo DEBUG opcional:
 *   - ?debug=1 evita redirecciones “ciegas” y muestra mensajes en crudo.
 */
$DEBUG = isset($_GET['debug']) && $_GET['debug'] !== '0';
error_reporting(E_ALL);
ini_set('display_errors', '1');

$basePublic = PUBLIC_URL;

// --------- ID ----------
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  if ($DEBUG) { echo "[DBG] id inválido"; exit; }
  flash('error','Actividad no válida.');
  header('Location: ' . $basePublic . '/admin/actividades/index.php'); exit;
}

// --------- ACL (propietario profesor) ----------
if ($u['role'] === 'admin') {
  // Admin solo visualiza
  flash('error', 'El administrador solo puede visualizar actividades.');
  header('Location: ' . $basePublic . '/admin/actividades/index.php'); exit;
}

$profesorId = (int)($u['profesor_id'] ?? 0);
if ($profesorId <= 0) {
  flash('error', 'No se ha podido identificar al profesor.');
  header('Location: ' . $basePublic . '/admin/actividades/index.php'); exit;
}

$stCheck = pdo()->prepare('SELECT profesor_id FROM actividades WHERE id = :id LIMIT 1');
$stCheck->execute([':id' => $id]);
$owner = $stCheck->fetchColumn();

if (!$owner) {
  flash('error', 'La actividad no existe.');
  header('Location: ' . $basePublic . '/admin/actividades/index.php'); exit;
}
if ((int)$owner !== $profesorId) {
  flash('error', 'No tienes permiso para editar esta actividad.');
  header('Location: ' . $basePublic . '/admin/actividades/index.php'); exit;
}

// --------- Datos base para selects ----------
try {
  $familias = pdo()->query("SELECT id, nombre FROM familias_profesionales WHERE is_active=1 ORDER BY nombre ASC")->fetchAll();
  $cursos   = pdo()->query("SELECT id, nombre, familia_id, orden FROM cursos WHERE is_active=1 ORDER BY familia_id ASC, orden ASC, nombre ASC")->fetchAll();
  $asigs    = pdo()->query("SELECT id, nombre, curso_id, familia_id, orden FROM asignaturas WHERE is_active=1 ORDER BY familia_id ASC, curso_id ASC, orden ASC, nombre ASC")->fetchAll();
  $temas    = pdo()->query("SELECT id, nombre, asignatura_id, numero FROM temas ORDER BY asignatura_id ASC, numero ASC, nombre ASC")->fetchAll();
} catch (Throwable $e) {
  if ($DEBUG) { echo "[DBG] Error datasets: ".h($e->getMessage()); exit; }
  flash('error','No se pudieron cargar los datos.');
  header('Location: ' . $basePublic . '/admin/actividades/index.php'); exit;
}

// --------- Cargar actividad + datos de tarea (si existen) ----------
$st = pdo()->prepare("SELECT * FROM actividades WHERE id=:id LIMIT 1");
$st->execute([':id'=>$id]);
$row = $st->fetch();

if (!$row) {
  if ($DEBUG) { echo "[DBG] actividad no encontrada"; exit; }
  flash('error','Actividad no encontrada.');
  header('Location: ' . $basePublic . '/admin/actividades/index.php'); exit;
}

$stTarea = pdo()->prepare("SELECT * FROM actividades_tarea WHERE actividad_id=:id LIMIT 1");
$stTarea->execute([':id'=>$id]);
$tareaRow = $stTarea->fetch() ?: [];

// --------- POST: actualizar ----------
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    // Campos comunes
    $titulo        = trim((string)($_POST['titulo'] ?? ''));
    $descripcion   = trim((string)($_POST['descripcion'] ?? ''));
    $familia_id    = (int)($_POST['familia_id'] ?? 0);
    $curso_id      = (int)($_POST['curso_id'] ?? 0);
    $asignatura_id = (int)($_POST['asignatura_id'] ?? 0);
    $tema_id       = ($_POST['tema_id'] ?? '') !== '' ? (int)$_POST['tema_id'] : null;
    $tipo          = trim((string)($_POST['tipo'] ?? ''));
    $visibilidad   = trim((string)($_POST['visibilidad'] ?? 'privada'));
    $estado        = trim((string)($_POST['estado'] ?? 'borrador'));

    // DIFICULTAD como ENUM (baja|media|alta) o vacío (NULL)
    $dificultad    = trim((string)($_POST['dificultad'] ?? ''));
    if ($dificultad !== '' && !in_array($dificultad, ['baja','media','alta'], true)) {
      throw new RuntimeException('Dificultad no válida.');
    }

    if ($titulo === '')      throw new RuntimeException('El título es obligatorio.');
    if ($familia_id <= 0)    throw new RuntimeException('Selecciona una familia.');
    if ($curso_id <= 0)      throw new RuntimeException('Selecciona un curso.');
    if ($asignatura_id <= 0) throw new RuntimeException('Selecciona una asignatura.');
    if (!in_array($tipo, ['opcion_multiple','verdadero_falso','respuesta_corta','rellenar_huecos','emparejar','tarea'], true)) throw new RuntimeException('Tipo de actividad no válido.');
    if (!in_array($visibilidad, ['privada','publica'], true))  throw new RuntimeException('Visibilidad no válida.');
    if (!in_array($estado, ['borrador','publicada'], true))    throw new RuntimeException('Estado no válido.');

    // Coherencias
    $stC = pdo()->prepare('SELECT familia_id FROM cursos WHERE id=:id LIMIT 1');
    $stC->execute([':id'=>$curso_id]);
    $c = $stC->fetch();
    if (!$c || (int)$c['familia_id'] !== $familia_id) {
      throw new RuntimeException('El curso no pertenece a la familia seleccionada.');
    }

    $stA = pdo()->prepare('SELECT curso_id FROM asignaturas WHERE id=:id LIMIT 1');
    $stA->execute([':id'=>$asignatura_id]);
    $a = $stA->fetch();
    if (!$a || (int)$a['curso_id'] !== $curso_id) {
      throw new RuntimeException('La asignatura no pertenece al curso seleccionado.');
    }

    if ($tema_id !== null) {
      $stT = pdo()->prepare('SELECT asignatura_id FROM temas WHERE id=:id LIMIT 1');
      $stT->execute([':id'=>$tema_id]);
      $t = $stT->fetch();
      if (!$t || (int)$t['asignatura_id'] !== $asignatura_id) {
        throw new RuntimeException('El tema no pertenece a la asignatura seleccionada.');
      }
    }

    // Update actividad
    $up = pdo()->prepare('
      UPDATE actividades
      SET familia_id=:fam, curso_id=:cur, asignatura_id=:asi, tema_id=:tema,
          tipo=:tipo, visibilidad=:vis, estado=:est, titulo=:tit,
          descripcion=:des, dificultad=:dif, updated_at=NOW()
      WHERE id=:id AND profesor_id=:prof
    ');
    $up->execute([
      ':fam'=>$familia_id,
      ':cur'=>$curso_id,
      ':asi'=>$asignatura_id,
      ':tema'=>$tema_id,
      ':tipo'=>$tipo,
      ':vis'=>$visibilidad,
      ':est'=>$estado,
      ':tit'=>$titulo,
      ':des'=>($descripcion!=='' ? $descripcion : null),
      ':dif'=>($dificultad!=='' ? $dificultad : null),
      ':id'=>$id,
      ':prof'=>$profesorId,
    ]);

    // ——— Específico de TAREA: UPSERT en actividades_tarea ———
    if ($tipo === 'tarea') {
      $instrucciones = trim((string)($_POST['t_instrucciones'] ?? ''));
      $perm_texto    = isset($_POST['t_perm_texto']) ? 1 : 0;
      $perm_archivo  = isset($_POST['t_perm_archivo']) ? 1 : 0;
      $perm_enlace   = isset($_POST['t_perm_enlace']) ? 1 : 0;
      $max_archivos  = ($_POST['t_max_archivos'] !== '') ? max(0,(int)$_POST['t_max_archivos']) : null;
      $max_peso_mb   = ($_POST['t_max_peso_mb'] !== '') ? max(0,(int)$_POST['t_max_peso_mb']) : null;
      $eval_modo     = trim((string)($_POST['t_evaluacion_modo'] ?? '')); // '' | puntos | rubrica
      if ($eval_modo !== '' && !in_array($eval_modo, ['puntos','rubrica'], true)) {
        throw new RuntimeException('Modo de evaluación no válido.');
      }
      $puntos_max    = ($_POST['t_puntuacion_max'] !== '') ? max(0,(int)$_POST['t_puntuacion_max']) : null;
      $rubrica_json  = trim((string)($_POST['t_rubrica_json'] ?? ''));

      if ($tareaRow) {
        // UPDATE
        $upT = pdo()->prepare('
          UPDATE actividades_tarea
          SET instrucciones=:inst, perm_texto=:pt, perm_archivo=:pa, perm_enlace=:pe,
              max_archivos=:maxf, max_peso_mb=:maxmb, evaluacion_modo=:modo,
              puntuacion_max=:pmax, rubrica_json=:rub, updated_at=NOW()
          WHERE actividad_id=:aid
        ');
        $upT->execute([
          ':inst'=>($instrucciones!==''?$instrucciones:null),
          ':pt'=>$perm_texto, ':pa'=>$perm_archivo, ':pe'=>$perm_enlace,
          ':maxf'=>$max_archivos, ':maxmb'=>$max_peso_mb,
          ':modo'=>($eval_modo!==''?$eval_modo:null),
          ':pmax'=>$puntos_max,
          ':rub'=>($rubrica_json!==''?$rubrica_json:null),
          ':aid'=>$id,
        ]);
      } else {
        // INSERT
        $insT = pdo()->prepare('
          INSERT INTO actividades_tarea
            (actividad_id, instrucciones, perm_texto, perm_archivo, perm_enlace,
             max_archivos, max_peso_mb, evaluacion_modo, puntuacion_max, rubrica_json,
             created_at, updated_at)
          VALUES
            (:aid, :inst, :pt, :pa, :pe, :maxf, :maxmb, :modo, :pmax, :rub, NOW(), NOW())
        ');
        $insT->execute([
          ':aid'=>$id,
          ':inst'=>($instrucciones!==''?$instrucciones:null),
          ':pt'=>$perm_texto, ':pa'=>$perm_archivo, ':pe'=>$perm_enlace,
          ':maxf'=>$max_archivos, ':maxmb'=>$max_peso_mb,
          ':modo'=>($eval_modo!==''?$eval_modo:null),
          ':pmax'=>$puntos_max,
          ':rub'=>($rubrica_json!==''?$rubrica_json:null),
        ]);
      }
    }
    // Si cambiaste a un tipo que NO es tarea, dejamos cualquier fila de tareas tal cual (no borramos).

    if ($DEBUG) { echo "[DBG] UPDATE OK"; exit; }
    flash('success','Actividad actualizada correctamente.');
    header('Location: ' . $basePublic . '/admin/actividades/index.php'); exit;

  } catch (Throwable $e) {
    if ($DEBUG) { echo "[DBG][EXC] ".h($e->getMessage()); exit; }
    $errors[] = $e->getMessage();
  }
}

// --------- Render (valores actuales) ----------
require_once __DIR__ . '/../../../partials/header.php';

$titulo        = (string)($row['titulo'] ?? '');
$descripcion   = (string)($row['descripcion'] ?? '');
$familia_id    = (int)($row['familia_id'] ?? 0);
$curso_id      = (int)($row['curso_id'] ?? 0);
$asignatura_id = (int)($row['asignatura_id'] ?? 0);
$tema_id       = isset($row['tema_id']) ? (int)$row['tema_id'] : 0;
$tipo          = (string)($row['tipo'] ?? 'opcion_multiple');
$visibilidad   = (string)($row['visibilidad'] ?? 'privada');
$estado        = (string)($row['estado'] ?? 'borrador');
$dificultad    = (string)($row['dificultad'] ?? '');

// Valores actuales de tarea (si hay)
$t_instrucciones = (string)($tareaRow['instrucciones'] ?? '');
$t_perm_texto    = (int)($tareaRow['perm_texto'] ?? 0);
$t_perm_archivo  = (int)($tareaRow['perm_archivo'] ?? 0);
$t_perm_enlace   = (int)($tareaRow['perm_enlace'] ?? 0);
$t_max_archivos  = $tareaRow['max_archivos'] ?? '';
$t_max_peso_mb   = $tareaRow['max_peso_mb'] ?? '';
$t_eval_modo     = (string)($tareaRow['evaluacion_modo'] ?? '');
$t_puntos_max    = $tareaRow['puntuacion_max'] ?? '';
$t_rubrica_json  = (string)($tareaRow['rubrica_json'] ?? '');
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
  <form method="post" action="" class="space-y-5">
    <?= csrf_field() ?>

    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700">Título <span class="text-rose-600">*</span></label>
      <input name="titulo" type="text" required
             value="<?= h($titulo) ?>"
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
              'verdadero_falso' => 'Veradero/falso',
              'respuesta_corta' => 'Respuesta corta',
              'rellenar_huecos' => 'Rellenar huecos',
              'emparejar'       => 'Emparejar',
              'tarea'           => 'Tarea / entrega abierta',
            ];
            foreach ($opts as $val=>$lab) {
              $sel = ($tipo === $val) ? 'selected' : '';
              echo '<option value="'.h($val).'" '.$sel.'>'.h($lab).'</option>';
            }
          ?>
        </select>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Dificultad</label>
        <select name="dificultad"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
          <option value="">—</option>
          <?php foreach (['baja'=>'Baja','media'=>'Media','alta'=>'Alta'] as $val=>$label): ?>
            <option value="<?= h($val) ?>" <?= $dificultad===$val?'selected':'' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Familia/Grado <span class="text-rose-600">*</span></label>
        <select id="familia_id" name="familia_id" required
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
          <?php foreach ($familias as $f): ?>
            <option value="<?= (int)$f['id'] ?>" <?= $familia_id===(int)$f['id']?'selected':'' ?>>
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
        <label class="mb-1 block text-sm font-medium text-slate-700">Asignatura <span class="text-rose-600">*</span></label>
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
          <?php foreach (['privada'=>'Privada','publica'=>'Pública'] as $v=>$lab): ?>
            <option value="<?= $v ?>" <?= $visibilidad===$v?'selected':'' ?>><?= $lab ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Estado</label>
        <select name="estado"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
          <?php foreach (['borrador'=>'Borrador','publicada'=>'Publicada'] as $v=>$lab): ?>
            <option value="<?= $v ?>" <?= $estado===$v?'selected':'' ?>><?= $lab ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- ====== BLOQUE ESPECÍFICO TAREA (solo cuando tipo = tarea) ====== -->
    <div id="bloqueTarea" class="<?= $tipo==='tarea' ? '' : 'hidden' ?> border-t pt-4">
      <h3 class="text-sm font-semibold text-slate-700 mb-2">Opciones de Tarea / Entrega</h3>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Instrucciones para el alumno</label>
        <textarea name="t_instrucciones" rows="3"
                  class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h($t_instrucciones) ?></textarea>
      </div>

      <div class="grid gap-4 sm:grid-cols-3">
        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" name="t_perm_texto" class="h-4 w-4 rounded border-slate-300" <?= $t_perm_texto? 'checked':'' ?>>
          Permitir texto
        </label>
        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" name="t_perm_archivo" class="h-4 w-4 rounded border-slate-300" <?= $t_perm_archivo? 'checked':'' ?>>
          Permitir archivos
        </label>
        <label class="inline-flex items-center gap-2 text-sm">
          <input type="checkbox" name="t_perm_enlace" class="h-4 w-4 rounded border-slate-300" <?= $t_perm_enlace? 'checked':'' ?>>
          Permitir enlaces
        </label>
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Máx. archivos</label>
          <input type="number" min="0" name="t_max_archivos" value="<?= h((string)$t_max_archivos) ?>"
                 class="w-40 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Máx. tamaño por archivo (MB)</label>
          <input type="number" min="0" name="t_max_peso_mb" value="<?= h((string)$t_max_peso_mb) ?>"
                 class="w-56 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-3">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Evaluación</label>
          <select name="t_evaluacion_modo"
                  class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
            <?php
              $ops = [''=>'— Sin evaluación —','puntos'=>'Puntuación','rubrica'=>'Rúbrica'];
              foreach ($ops as $v=>$lab) {
                $sel = ($t_eval_modo === $v) ? 'selected' : '';
                echo '<option value="'.h($v).'" '.$sel.'>'.h($lab).'</option>';
              }
            ?>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Puntuación máxima</label>
          <input type="number" min="0" name="t_puntuacion_max" value="<?= h((string)$t_puntos_max) ?>"
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
    <!-- ====== /BLOQUE TAREA ====== -->

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
      <a href="<?= $basePublic ?>/admin/actividades/index.php"
         class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Cancelar</a>
      <button class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
        Guardar cambios
      </button>
    </div>

  </form>
</div>

<script>
  // Datos precargados
  const cursosAll = <?= json_encode($cursos, JSON_UNESCAPED_UNICODE) ?>;
  const asigsAll  = <?= json_encode($asigs,  JSON_UNESCAPED_UNICODE) ?>;
  const temasAll  = <?= json_encode($temas,  JSON_UNESCAPED_UNICODE) ?>;

  const selFam = document.getElementById('familia_id');
  const selCur = document.getElementById('curso_id');
  const selAsi = document.getElementById('asignatura_id');
  const selTem = document.getElementById('tema_id');
  const selTipo = document.getElementById('tipo');
  const bloqueTarea = document.getElementById('bloqueTarea');

  const currentFam = <?= (int)$familia_id ?>;
  const currentCur = <?= (int)$curso_id ?>;
  const currentAsi = <?= (int)$asignatura_id ?>;
  const currentTem = <?= (int)$tema_id ?>;
  const currentTipo = <?= json_encode($tipo) ?>;

  function opt(v,t){const o=document.createElement('option'); o.value=v; o.textContent=t; return o;}

  function renderCursos(fid, selected=0){
    selCur.innerHTML=''; selCur.appendChild(opt('', '— Curso —'));
    cursosAll
      .filter(c=>parseInt(c.familia_id,10)===parseInt(fid||'0',10))
      .forEach(c=>{
        const o=opt(c.id, c.nombre);
        if(parseInt(selected,10)===parseInt(c.id,10)) o.selected=true;
        selCur.appendChild(o);
      });
  }
  function renderAsigs(cid, selected=0){
    selAsi.innerHTML=''; selAsi.appendChild(opt('', '— Asignatura —'));
    asigsAll
      .filter(a=>parseInt(a.curso_id,10)===parseInt(cid||'0',10))
      .forEach(a=>{
        const o=opt(a.id, a.nombre);
        if(parseInt(selected,10)===parseInt(a.id,10)) o.selected=true;
        selAsi.appendChild(o);
      });
  }
  function renderTemas(aid, selected=0){
    selTem.innerHTML=''; selTem.appendChild(opt('', '— Tema (opcional) —'));
    temasAll
      .filter(t=>parseInt(t.asignatura_id,10)===parseInt(aid||'0',10))
      .forEach(t=>{
        const label=(t.numero?('T'+t.numero+' · '):'')+t.nombre;
        const o=opt(t.id, label);
        if(parseInt(selected,10)===parseInt(t.id,10)) o.selected=true;
        selTem.appendChild(o);
      });
  }

  // Estado inicial
  if (currentFam>0) renderCursos(currentFam, currentCur);
  if (currentCur>0) renderAsigs(currentCur, currentAsi);
  if (currentAsi>0) renderTemas(currentAsi, currentTem);

  selFam.addEventListener('change', ()=>{
    const fid=parseInt(selFam.value||'0',10);
    renderCursos(fid, 0);
    selAsi.innerHTML=''; selAsi.appendChild(opt('', '— Asignatura —'));
    selTem.innerHTML=''; selTem.appendChild(opt('', '— Tema (opcional) —'));
  }, {passive:true});

  selCur.addEventListener('change', ()=>{
    const cid=parseInt(selCur.value||'0',10);
    renderAsigs(cid, 0);
    selTem.innerHTML=''; selTem.appendChild(opt('', '— Tema (opcional) —'));
  }, {passive:true});

  selAsi.addEventListener('change', ()=>{
    const aid=parseInt(selAsi.value||'0',10);
    renderTemas(aid, 0);
  }, {passive:true});

  function toggleTarea(){ bloqueTarea.classList.toggle('hidden', selTipo.value !== 'tarea'); }
  selTipo.addEventListener('change', toggleTarea, {passive:true});
  if (currentTipo) selTipo.value = currentTipo;
  toggleTarea();
</script>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>
