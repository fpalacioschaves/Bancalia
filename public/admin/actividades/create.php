<?php
// /public/admin/actividades/create.php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();
$u = current_user();

if (($u['role'] ?? '') !== 'admin') {
  flash('error', 'Acceso restringido a administradores.');
  header('Location: ' . PUBLIC_URL . '/dashboard.php');
  exit;
}

$userEmail  = (string)($u['email'] ?? '');
$profesorId = (int)($u['profesor_id'] ?? 0);

// Resolver profesor_id por email si no viene
if ($profesorId <= 0 && $userEmail !== '') {
  $stP = pdo()->prepare('SELECT id FROM profesores WHERE email = :e LIMIT 1');
  $stP->execute([':e' => $userEmail]);
  $rowP = $stP->fetch();
  if ($rowP) $profesorId = (int)$rowP['id'];
}

// Datos para selects (precargados)
try {
  $familias = pdo()->query("SELECT id, nombre FROM familias_profesionales WHERE is_active=1 ORDER BY nombre ASC")->fetchAll();
  $cursos   = pdo()->query("SELECT id, nombre, familia_id, orden FROM cursos WHERE is_active=1 ORDER BY familia_id ASC, orden ASC, nombre ASC")->fetchAll();
  $asigs    = pdo()->query("SELECT id, nombre, curso_id, familia_id, orden FROM asignaturas WHERE is_active=1 ORDER BY familia_id ASC, curso_id ASC, orden ASC, nombre ASC")->fetchAll();
  $temas    = pdo()->query("SELECT id, nombre, asignatura_id, numero FROM temas ORDER BY asignatura_id ASC, numero ASC, nombre ASC")->fetchAll();
} catch (Throwable $e) {
  die('Error cargando datos base: ' . h($e->getMessage()));
}

$errors = [];

// POST: guardar actividad (campos comunes)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if ($profesorId <= 0) {
      throw new RuntimeException('No se pudo resolver tu identificador de profesor. Ve a “Mi Perfil” y guarda tus datos (el email debe coincidir).');
    }
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

    // Insert (dificultad como string o NULL)
    $ins = pdo()->prepare('
      INSERT INTO actividades
        (profesor_id, familia_id, curso_id, asignatura_id, tema_id,
         tipo, visibilidad, estado, titulo, descripcion, dificultad,
         created_at, updated_at)
      VALUES
        (:prof, :fam, :cur, :asi, :tema,
         :tipo, :vis, :est, :tit, :des, :dif,
         NOW(), NOW())
    ');
    $ins->execute([
      ':prof'=>$profesorId,
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
    ]);

    if ($DEBUG) {
      echo "[DBG] INSERT OK id=" . (int)pdo()->lastInsertId();
      exit;
    }

    // Redirección normal
    require_once $ROOT . '/lib/auth.php';
    flash('success','Actividad creada correctamente.');
    header('Location: ' . '/Bancalia/public/admin/actividades/index.php');
    exit;

  } catch (Throwable $e) {
    $errors[] = $e->getMessage();
  }
}

// Render
if (!$DEBUG) require_once $ROOT . '/partials/header.php';
?>

<?php if ($DEBUG): ?>
  <div class="p-4 text-sm">[DBG] render start</div>
<?php endif; ?>

<div class="mb-6 flex items-center justify-between">
  <div>
    <h1 class="text-xl font-semibold tracking-tight">Nueva actividad</h1>
    <p class="mt-1 text-sm text-slate-600">Crea una actividad. Los campos específicos del tipo los añadiremos después.</p>
  </div>
  <a href="/Bancalia/public/admin/actividades/index.php"
     class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
    Volver al listado
  </a>
</div>

<?php if ($profesorId <= 0): ?>
  <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
    No se ha podido vincular tu usuario con un <strong>profesor</strong>. Comprueba que tu email (<?= h($userEmail) ?>)
    está dado de alta en <em>Profesores</em> o edita tu perfil en <a class="underline" href="/Bancalia/public/mi-perfil.php">Mi Perfil</a>.
  </div>
<?php endif; ?>

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
             value="<?= h($_POST['titulo'] ?? '') ?>"
             class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Tipo <span class="text-rose-600">*</span></label>
        <select name="tipo" required
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
          <?php
            $tipoSel = (string)($_POST['tipo'] ?? '');
            $opts = [
              'autocorregible' => 'Autocorregible',
              'tarea_abierta'  => 'Tarea / entrega abierta',
            ];
            foreach ($opts as $val => $lab) {
              $sel = ($tipoSel === $val) ? 'selected' : '';
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
            $difSel = (string)($_POST['dificultad'] ?? '');
            $niveles = ['baja'=>'Baja','media'=>'Media','alta'=>'Alta'];
            foreach ($niveles as $val=>$label) {
              $sel = ($difSel === $val) ? 'selected' : '';
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
          <option value="">— Selecciona familia —</option>
          <?php
            $famSel = (int)($_POST['familia_id'] ?? 0);
            foreach ($familias as $f) {
              $sel = ($famSel === (int)$f['id']) ? 'selected' : '';
              echo '<option value="'.(int)$f['id'].'" '.$sel.'>'.h($f['nombre']).'</option>';
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
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h($_POST['descripcion'] ?? '') ?></textarea>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Visibilidad</label>
        <select name="visibilidad"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
          <?php
            $visSel = (string)($_POST['visibilidad'] ?? 'privada');
            foreach (['privada'=>'Privada','publica'=>'Pública'] as $v=>$lab) {
              $sel = ($visSel === $v) ? 'selected' : '';
              echo '<option value="'.$v.'" '.$sel.'>'.$lab.'</option>';
            }
          ?>
        </select>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Estado</label>
        <select name="estado"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
          <?php
            $estSel = (string)($_POST['estado'] ?? 'borrador');
            foreach (['borrador'=>'Borrador','publicada'=>'Publicada'] as $v=>$lab) {
              $sel = ($estSel === $v) ? 'selected' : '';
              echo '<option value="'.$v.'" '.$sel.'>'.$lab.'</option>';
            }
          ?>
        </select>
      </div>

      <div class="flex items-end">
        <button class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
          Guardar actividad
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

  const currentFam = <?= (int)($_POST['familia_id'] ?? 0) ?>;
  const currentCur = <?= (int)($_POST['curso_id'] ?? 0) ?>;
  const currentAsi = <?= (int)($_POST['asignatura_id'] ?? 0) ?>;
  const currentTem = <?= (int)($_POST['tema_id'] ?? 0) ?>;

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
