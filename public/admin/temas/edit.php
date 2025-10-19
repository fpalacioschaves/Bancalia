<?php
// /public/admin/temas/edit.php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();
$u = current_user();


$id = (int)($_GET['id'] ?? 0);

// Datos base para selects
$fams = pdo()->query('SELECT id, nombre FROM familias_profesionales WHERE is_active=1 ORDER BY nombre ASC')->fetchAll();
$cursos = pdo()->query('SELECT id, nombre, familia_id FROM cursos WHERE is_active=1 ORDER BY familia_id ASC, orden ASC, nombre ASC')->fetchAll();
$asigs = pdo()->query('SELECT id, nombre, curso_id, familia_id FROM asignaturas WHERE is_active=1 ORDER BY familia_id ASC, curso_id ASC, orden ASC, nombre ASC')->fetchAll();

// Tema
$st = pdo()->prepare('SELECT t.*, a.curso_id, a.familia_id
                      FROM temas t
                      JOIN asignaturas a ON a.id = t.asignatura_id
                      WHERE t.id=:id LIMIT 1');
$st->execute([':id'=>$id]);
$row = $st->fetch();

if (!$row) {
  flash('error','Tema no encontrado.');
  header('Location: ' . PUBLIC_URL . '/admin/temas/index.php');
  exit;
}

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

    // Validar asignatura existente
    $chkA = pdo()->prepare('SELECT 1 FROM asignaturas WHERE id=:id LIMIT 1');
    $chkA->execute([':id'=>$asignatura_id]);
    if (!$chkA->fetch()) throw new RuntimeException('La asignatura seleccionada no existe.');

    // Unicidades por asignatura (excluyendo el propio tema)
    $chk1 = pdo()->prepare('SELECT 1 FROM temas WHERE asignatura_id=:a AND slug=:s AND id<>:id LIMIT 1');
    $chk1->execute([':a'=>$asignatura_id, ':s'=>$slug, ':id'=>$id]);
    if ($chk1->fetch()) throw new RuntimeException('Ya existe un tema con ese slug en la asignatura seleccionada.');

    $chk2 = pdo()->prepare('SELECT 1 FROM temas WHERE asignatura_id=:a AND numero=:n AND id<>:id LIMIT 1');
    $chk2->execute([':a'=>$asignatura_id, ':n'=>$numero, ':id'=>$id]);
    if ($chk2->fetch()) throw new RuntimeException('Ya existe un tema con ese número en la asignatura seleccionada.');

    $up = pdo()->prepare('
      UPDATE temas
      SET asignatura_id=:a, nombre=:n, slug=:s, numero=:num, descripcion=:d, is_active=:act
      WHERE id=:id
    ');
    $up->execute([
      ':a'=>$asignatura_id, ':n'=>$nombre, ':s'=>$slug, ':num'=>$numero,
      ':d'=>($descripcion !== '' ? $descripcion : null),
      ':act'=>$activa, ':id'=>$id
    ]);

    flash('success','Tema actualizado correctamente.');
    header('Location: ' . PUBLIC_URL . '/admin/temas/index.php');
    exit;
  } catch (Throwable $e) {
    flash('error', $e->getMessage());
    header('Location: ' . PUBLIC_URL . '/admin/temas/edit.php?id=' . $id);
    exit;
  }
}

require_once __DIR__ . '/../../../partials/header.php';

// Valores actuales para preselección
$currentFamilia = (int)$row['familia_id'];
$currentCurso   = (int)$row['curso_id'];
$currentAsig    = (int)$row['asignatura_id'];
?>
<div class="mb-6 flex items-center justify-between">
  <div>
    <h1 class="text-xl font-semibold tracking-tight">Editar tema</h1>
    <p class="mt-1 text-sm text-slate-600">Actualiza los datos del tema y guarda los cambios.</p>
  </div>
  <a href="<?= PUBLIC_URL ?>/admin/temas/index.php"
     class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
    Volver al listado
  </a>
</div>

<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
  <form method="post" action="" class="space-y-5">
    <?= csrf_field() ?>

    <div class="grid gap-4 sm:grid-cols-3">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Familia</label>
        <select id="familia_id"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
          <?php foreach ($fams as $f): ?>
            <option value="<?= (int)$f['id'] ?>" <?= $currentFamilia===(int)$f['id']?'selected':'' ?>>
              <?= htmlspecialchars($f['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Curso</label>
        <select id="curso_id"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
          <!-- relleno por JS -->
        </select>
      </div>
      <div>
        <label for="asignatura_id" class="mb-1 block text-sm font-medium text-slate-700">Asignatura <span class="text-rose-600">*</span></label>
        <select id="asignatura_id" name="asignatura_id" required
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
          <!-- relleno por JS -->
        </select>
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label for="nombre" class="mb-1 block text-sm font-medium text-slate-700">Nombre <span class="text-rose-600">*</span></label>
        <input id="nombre" name="nombre" type="text" required
               value="<?= htmlspecialchars($row['nombre']) ?>"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
      </div>
      <div>
        <label for="slug" class="mb-1 block text-sm font-medium text-slate-700">Slug</label>
        <input id="slug" name="slug" type="text"
               value="<?= htmlspecialchars($row['slug']) ?>"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
        <p class="mt-1 text-xs text-slate-500">Déjalo vacío al guardar para generarlo desde el nombre.</p>
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <div>
        <label for="numero" class="mb-1 block text-sm font-medium text-slate-700">Número <span class="text-rose-600">*</span></label>
        <input id="numero" name="numero" type="number" min="1"
               value="<?= (int)$row['numero'] ?>"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
      </div>
      <div class="sm:col-span-2">
        <label for="descripcion" class="mb-1 block text-sm font-medium text-slate-700">Descripción</label>
        <textarea id="descripcion" name="descripcion" rows="3"
                  class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
        ><?= htmlspecialchars((string)$row['descripcion']) ?></textarea>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <input id="is_active" name="is_active" type="checkbox"
             class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
             <?= (int)$row['is_active']===1 ? 'checked' : '' ?>>
      <label for="is_active" class="text-sm text-slate-700">Activo</label>
    </div>

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
      <a href="<?= PUBLIC_URL ?>/admin/temas/index.php"
         class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
        Cancelar
      </a>
      <button type="submit"
              class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 active:scale-[0.99] transition">
        Guardar cambios
      </button>
    </div>
  </form>
</div>

<script>
  // Construir mapas para selects dependientes
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

  const selFam = document.getElementById('familia_id');
  const selCurso = document.getElementById('curso_id');
  const selAsig = document.getElementById('asignatura_id');

  const currentFam  = <?= (int)$currentFamilia ?>;
  const currentCurso= <?= (int)$currentCurso ?>;
  const currentAsig = <?= (int)$currentAsig ?>;

  function renderCursos(fid, selectedId = 0) {
    selCurso.innerHTML = '';
    (cursosData[fid] || []).forEach(c => {
      const o = document.createElement('option');
      o.value = c.id; o.textContent = c.nombre;
      if (c.id === selectedId) o.selected = true;
      selCurso.appendChild(o);
    });
  }

  function renderAsigs(cid, selectedId = 0) {
    selAsig.innerHTML = '';
    (asigData[cid] || []).forEach(a => {
      const o = document.createElement('option');
      o.value = a.id; o.textContent = a.nombre;
      if (a.id === selectedId) o.selected = true;
      selAsig.appendChild(o);
    });
  }

  // Inicializar selects con valores actuales
  selFam.value = String(currentFam);
  renderCursos(currentFam, currentCurso);
  renderAsigs(currentCurso, currentAsig);

  // Cambios en cascada
  selFam.addEventListener('change', () => {
    const fid = parseInt(selFam.value || '0', 10);
    renderCursos(fid, 0);
    renderAsigs(0, 0);
  }, { passive: true });

  selCurso.addEventListener('change', () => {
    const cid = parseInt(selCurso.value || '0', 10);
    renderAsigs(cid, 0);
  }, { passive: true });
</script>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>
