<?php
// /public/admin/actividades/edit.php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();
$u = current_user();


/**
 * Modo DEBUG opcional:
 *   - ?debug=1 evita redirecciones y header/footer, muestra checkpoints y errores en crudo.
 */
$DEBUG = isset($_GET['debug']) && $_GET['debug'] !== '0';
error_reporting(E_ALL);
ini_set('display_errors', '1');

$ROOT = realpath(dirname(__DIR__, 3)); // …/bancalia
if ($ROOT === false) { die('ERROR: no puedo resolver la raíz del proyecto.'); }

require_once $ROOT . '/lib/pdo.php';
require_once $ROOT . '/lib/auth.php';
require_once $ROOT . '/lib/acl.php'; // require_propietario_actividad()
function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$basePublic = '/Bancalia/public';

// --------- ID ----------
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  if ($DEBUG) { echo "[DBG] id inválido"; exit; }
  flash('error','Actividad no válida.');
  header('Location: ' . $basePublic . '/admin/actividades/index.php'); exit;
}

// --------- ACL (propietario profesor) ----------
if ($DEBUG) { echo "[DBG] ACL check…<br>\n"; }
require_propietario_actividad($id);
if ($DEBUG) { echo "[DBG] ACL OK<br>\n"; }

// --------- Cargas base para selects ----------
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

// --------- Cargar actividad ----------
$st = pdo()->prepare("
  SELECT a.*
  FROM actividades a
  WHERE a.id = :id
  LIMIT 1
");
$st->execute([':id'=>$id]);
$row = $st->fetch();

if (!$row) {
  if ($DEBUG) { echo "[DBG] actividad no encontrada"; exit; }
  flash('error','Actividad no encontrada.');
  header('Location: ' . $basePublic . '/admin/actividades/index.php'); exit;
}

// --------- Usuario / profesor_id resuelto (para consistencia) ----------
$u = current_user();
$userEmail  = (string)($u['email'] ?? '');
$profesorId = (int)($u['profesor_id'] ?? 0);
if ($profesorId <= 0 && $userEmail !== '') {
  $stP = pdo()->prepare('SELECT id FROM profesores WHERE email = :e LIMIT 1');
  $stP->execute([':e' => $userEmail]);
  if ($tmp = $stP->fetch()) $profesorId = (int)$tmp['id'];
}

if ($DEBUG) {
  echo "[DBG] user role=" . h((string)($u['role'] ?? '')) . " email=" . h($userEmail) . " pid=" . $profesorId . "<br>\n";
  echo "[DBG] actividad.profesor_id=" . (int)$row['profesor_id'] . " (owner)<br>\n";
}

// --------- POST: actualizar ----------
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    // Campos comunes
    $titulo        = trim($_POST['titulo'] ?? '');
    $descripcion   = trim($_POST['descripcion'] ?? '');
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
    if (!in_array($tipo, ['autocorregible','tarea_abierta'], true)) throw new RuntimeException('Tipo de actividad no válido.');
    if (!in_array($visibilidad, ['privada','publica'], true))        throw new RuntimeException('Visibilidad no válida.');
    if (!in_array($estado, ['borrador','publicada'], true))          throw new RuntimeException('Estado no válido.');

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

    // Update (dificultad como string o NULL)
    $up = pdo()->prepare('
      UPDATE actividades
      SET familia_id=:fam, curso_id=:cur, asignatura_id=:asi, tema_id=:tema,
          tipo=:tipo, visibilidad=:vis, estado=:est, titulo=:tit,
          descripcion=:des, dificultad=:dif, updated_at=NOW()
      WHERE id=:id
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
    ]);

    if ($DEBUG) { echo "[DBG] UPDATE OK<br>\n"; exit; }

    flash('success','Actividad actualizada correctamente.');
    header('Location: ' . $basePublic . '/admin/actividades/index.php'); exit;

  } catch (Throwable $e) {
    if ($DEBUG) { echo "[DBG][EXC] ".h($e->getMessage()); exit; }
    $errors[] = $e->getMessage();
  }
}

// --------- Render (con valores actuales) ----------
if (!$DEBUG) require_once $ROOT . '/partials/header.php';

$titulo        = (string)($row['titulo'] ?? '');
$descripcion   = (string)($row['descripcion'] ?? '');
$familia_id    = (int)($row['familia_id'] ?? 0);
$curso_id      = (int)($row['curso_id'] ?? 0);
$asignatura_id = (int)($row['asignatura_id'] ?? 0);
$tema_id       = isset($row['tema_id']) ? (int)$row['tema_id'] : 0;
$tipo          = (string)($row['tipo'] ?? 'autocorregible');
$visibilidad   = (string)($row['visibilidad'] ?? 'privada');
$estado        = (string)($row['estado'] ?? 'borrador');
$dificultad    = (string)($row['dificultad'] ?? '');
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
    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700">Título <span class="text-rose-600">*</span></label>
      <input name="titulo" type="text" required
             value="<?= h($titulo) ?>"
             class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Tipo <span class="text-rose-600">*</span></label>
        <select name="tipo" required
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
          <?php
            $opts = ['autocorregible'=>'Autocorregible','tarea_abierta'=>'Tarea / entrega abierta'];
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
          <?php
            $niveles = ['baja'=>'Baja','media'=>'Media','alta'=>'Alta'];
            foreach ($niveles as $val=>$label) {
              $sel = ($dificultad === $val) ? 'selected' : '';
              echo '<option value="'.h($val).'" '.$sel.'>'.h($label).'</option>';
            }
          ?>
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

      <div class="flex items-end">
        <button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
          Guardar cambios
        </button>
      </div>
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

  const currentFam = <?= (int)$familia_id ?>;
  const currentCur = <?= (int)$curso_id ?>;
  const currentAsi = <?= (int)$asignatura_id ?>;
  const currentTem = <?= (int)$tema_id ?>;

  function opt(v,t){const o=document.createElement('option'); o.value=v; o.textContent=t; return o;}

  function renderCursos(fid, selected=0){
    selCur.innerHTML=''; selCur.appendChild(opt('', '— Curso —'));
    cursosAll.filter(c=>parseInt(c.familia_id,10)===parseInt(fid,10))
             .forEach(c=>{const o=opt(c.id, c.nombre); if(parseInt(selected,10)===parseInt(c.id,10)) o.selected=true; selCur.appendChild(o);});
  }
  function renderAsigs(cid, selected=0){
    selAsi.innerHTML=''; selAsi.appendChild(opt('', '— Asignatura —'));
    asigsAll.filter(a=>parseInt(a.curso_id,10)===parseInt(cid,10))
            .forEach(a=>{const o=opt(a.id, a.nombre); if(parseInt(selected,10)===parseInt(a.id,10)) o.selected=true; selAsi.appendChild(o);});
  }
  function renderTemas(aid, selected=0){
    selTem.innerHTML=''; selTem.appendChild(opt('', '— Tema (opcional) —'));
    temasAll.filter(t=>parseInt(t.asignatura_id,10)===parseInt(aid,10))
            .forEach(t=>{const label=(t.numero?('T'+t.numero+' · '):'')+t.nombre; const o=opt(t.id, label); if(parseInt(selected,10)===parseInt(t.id,10)) o.selected=true; selTem.appendChild(o);});
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
</script>

<?php if (!$DEBUG) require_once $ROOT . '/partials/footer.php'; ?>
