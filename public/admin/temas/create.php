<?php
// /public/admin/temas/create.php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();
$u = current_user();

/*if (($u['role'] ?? '') !== 'admin') {
  flash('error', 'Acceso restringido a administradores.');
  header('Location: ' . PUBLIC_URL . '/dashboard.php');
  exit;
}*/

// Datos para selects
$fams = pdo()->query('SELECT id, nombre FROM familias_profesionales WHERE is_active=1 ORDER BY nombre ASC')->fetchAll();
$cursos = pdo()->query('SELECT id, nombre, familia_id FROM cursos WHERE is_active=1 ORDER BY familia_id ASC, orden ASC, nombre ASC')->fetchAll();
$asigs = pdo()->query('SELECT id, nombre, curso_id, familia_id FROM asignaturas WHERE is_active=1 ORDER BY familia_id ASC, curso_id ASC, orden ASC, nombre ASC')->fetchAll();

// Para autocompletar "numero" (siguiente disponible por asignatura)
$tmp = pdo()->query('SELECT asignatura_id, COALESCE(MAX(numero),0)+1 AS next_num FROM temas GROUP BY asignatura_id')->fetchAll();
$nextNumByAsig = [];
foreach ($tmp as $t) { $nextNumByAsig[(int)$t['asignatura_id']] = (int)$t['next_num']; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $asignatura_id = (int)($_POST['asignatura_id'] ?? 0);
    $nombre        = trim($_POST['nombre'] ?? '');
    $slug          = trim($_POST['slug'] ?? '');
    $numero        = (int)($_POST['numero'] ?? 1);
    $descripcion   = trim($_POST['descripcion'] ?? '');
    $activa        = isset($_POST['is_active']) ? 1 : 0;

    if ($asignatura_id <= 0) throw new RuntimeException('Selecciona una asignatura.');
    if ($nombre === '') throw new RuntimeException('El nombre es obligatorio.');
    if ($slug === '') $slug = str_slug($nombre);
    if ($numero <= 0) $numero = 1;

    // Validación asignatura existente
    $chkA = pdo()->prepare('SELECT 1 FROM asignaturas WHERE id=:id LIMIT 1');
    $chkA->execute([':id'=>$asignatura_id]);
    if (!$chkA->fetch()) throw new RuntimeException('La asignatura seleccionada no existe.');

    // Unicidades por asignatura
    $chk1 = pdo()->prepare('SELECT 1 FROM temas WHERE asignatura_id=:a AND slug=:s LIMIT 1');
    $chk1->execute([':a'=>$asignatura_id, ':s'=>$slug]);
    if ($chk1->fetch()) throw new RuntimeException('Ya existe un tema con ese slug en la asignatura seleccionada.');

    $chk2 = pdo()->prepare('SELECT 1 FROM temas WHERE asignatura_id=:a AND numero=:n LIMIT 1');
    $chk2->execute([':a'=>$asignatura_id, ':n'=>$numero]);
    if ($chk2->fetch()) throw new RuntimeException('Ya existe un tema con ese número en la asignatura seleccionada.');

    $ins = pdo()->prepare('
      INSERT INTO temas (asignatura_id, nombre, slug, numero, descripcion, is_active)
      VALUES (:a, :n, :s, :num, :d, :act)
    ');
    $ins->execute([
      ':a'=>$asignatura_id,
      ':n'=>$nombre,
      ':s'=>$slug,
      ':num'=>$numero,
      ':d'=>($descripcion !== '' ? $descripcion : null),
      ':act'=>$activa
    ]);

    flash('success','Tema creado correctamente.');
    header('Location: ' . PUBLIC_URL . '/admin/temas/index.php');
    exit;
  } catch (Throwable $e) {
    flash('error', $e->getMessage());
    header('Location: ' . PUBLIC_URL . '/admin/temas/create.php');
    exit;
  }
}

require_once __DIR__ . '/../../../partials/header.php';
?>

<div class="mb-6 flex items-center justify-between">
  <div>
    <h1 class="text-xl font-semibold tracking-tight">Nuevo tema</h1>
    <p class="mt-1 text-sm text-slate-600">Crea un tema para una asignatura concreta.</p>
  </div>
  <a href="<?= PUBLIC_URL ?>/admin/temas/index.php"
     class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
    Volver al listado
  </a>
</div>

<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
  <form method="post" action="" class="space-y-5" id="temaForm">
    <?= csrf_field() ?>

    <div class="grid gap-4 sm:grid-cols-3">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Familia</label>
        <select id="familia_id"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
          <option value="">Selecciona…</option>
          <?php foreach ($fams as $f): ?>
            <option value="<?= (int)$f['id'] ?>"><?= htmlspecialchars($f['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Curso</label>
        <select id="curso_id"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
          <option value="">Selecciona una familia…</option>
        </select>
      </div>

      <div>
        <label for="asignatura_id" class="mb-1 block text-sm font-medium text-slate-700">Asignatura <span class="text-rose-600">*</span></label>
        <select id="asignatura_id" name="asignatura_id" required
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
          <option value="">Selecciona un curso…</option>
        </select>
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label for="nombre" class="mb-1 block text-sm font-medium text-slate-700">Nombre <span class="text-rose-600">*</span></label>
        <input id="nombre" name="nombre" type="text" required
               placeholder='Ej. "T1. Introducción a SQL"'
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
      </div>
      <div>
        <label for="slug" class="mb-1 block text-sm font-medium text-slate-700">Slug (opcional)</label>
        <input id="slug" name="slug" type="text" placeholder="t1-introduccion-sql"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
        <p class="mt-1 text-xs text-slate-500">Si lo dejas vacío, se generará desde el nombre.</p>
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <div>
        <label for="numero" class="mb-1 block text-sm font-medium text-slate-700">Número <span class="text-rose-600">*</span></label>
        <input id="numero" name="numero" type="number" value="1" min="1"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
        <p class="mt-1 text-xs text-slate-500">Posición del tema dentro de la asignatura (se sugiere automáticamente).</p>
      </div>
      <div class="sm:col-span-2">
        <label for="descripcion" class="mb-1 block text-sm font-medium text-slate-700">Descripción</label>
        <textarea id="descripcion" name="descripcion" rows="3"
                  class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
                  placeholder="Descripción breve del tema (opcional)…"></textarea>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <input id="is_active" name="is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400" checked>
      <label for="is_active" class="text-sm text-slate-700">Activo</label>
    </div>

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
      <a href="<?= PUBLIC_URL ?>/admin/temas/index.php"
         class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
        Cancelar
      </a>
      <button type="submit"
              class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 active:scale-[0.99] transition">
        Crear tema
      </button>
    </div>
  </form>
</div>

<script>
  // Datos en cliente para selects dependientes
  const cursosData = <?php
    $byFam = [];
    foreach ($cursos as $c) { $byFam[$c['familia_id']][] = ['id'=>$c['id'], 'nombre'=>$c['nombre']]; }
    echo json_encode($byFam, JSON_UNESCAPED_UNICODE);
  ?>;

  const asigData = <?php
    $byCurso = [];
    foreach ($asigs as $a) { $byCurso[$a['curso_id']][] = ['id'=>$a['id'], 'nombre'=>$a['nombre']]; }
    echo json_encode($byCurso, JSON_UNESCAPED_UNICODE);
  ?>;

  const nextNumero = <?php
    echo json_encode($nextNumByAsig, JSON_UNESCAPED_UNICODE);
  ?>;

  const selFam   = document.getElementById('familia_id');
  const selCurso = document.getElementById('curso_id');
  const selAsig  = document.getElementById('asignatura_id');
  const numeroEl = document.getElementById('numero');
  const nombreEl = document.getElementById('nombre');
  const slugEl   = document.getElementById('slug');

  function option(value, text) {
    const o = document.createElement('option'); o.value = value; o.textContent = text; return o;
  }

  function renderCursos(fid) {
    selCurso.innerHTML = '';
    if (!fid) { selCurso.appendChild(option('', 'Selecciona una familia…')); renderAsigs(null); return; }
    selCurso.appendChild(option('', 'Selecciona…'));
    (cursosData[fid] || []).forEach(c => selCurso.appendChild(option(c.id, c.nombre)));
    renderAsigs(null);
  }

  function renderAsigs(cid) {
    selAsig.innerHTML = '';
    if (!cid) { selAsig.appendChild(option('', 'Selecciona un curso…')); return; }
    selAsig.appendChild(option('', 'Selecciona…'));
    (asigData[cid] || []).forEach(a => selAsig.appendChild(option(a.id, a.nombre)));
  }

  // Sugerir automáticamente el número al elegir asignatura
  function maybeSuggestNumero() {
    const aid = parseInt(selAsig.value || '0', 10);
    if (!aid) return;
    const next = nextNumero[aid] || 1;
    if (!numeroEl.value || parseInt(numeroEl.value, 10) <= 1) {
      numeroEl.value = next;
    }
  }

  // Autogenerar slug desde nombre si está vacío
  function slugify(str) {
    return (str || '')
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/(^-|-$)/g, '')
      .substring(0, 180);
  }

  nombreEl.addEventListener('blur', () => {
    if (!slugEl.value.trim()) slugEl.value = slugify(nombreEl.value);
  });

  // Cascada de selects
  selFam.addEventListener('change', () => {
    const fid = parseInt(selFam.value || '0', 10);
    renderCursos(fid);
  }, { passive: true });

  selCurso.addEventListener('change', () => {
    const cid = parseInt(selCurso.value || '0', 10);
    renderAsigs(cid);
  }, { passive: true });

  selAsig.addEventListener('change', maybeSuggestNumero, { passive: true });

  // Inicialización (todo vacío al entrar)
  renderCursos(null);
  renderAsigs(null);
</script>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>
