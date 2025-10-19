<?php
// /public/admin/asignaturas/edit.php
declare(strict_types=1);
require_once __DIR__ . '/../../../middleware/require_admin.php';

$id = (int)($_GET['id'] ?? 0);

$fams = pdo()->query('SELECT id, nombre FROM familias_profesionales WHERE is_active=1 ORDER BY nombre ASC')->fetchAll();
$cursos = pdo()->query('SELECT id, nombre, familia_id FROM cursos WHERE is_active=1 ORDER BY familia_id ASC, orden ASC, nombre ASC')->fetchAll();

$st = pdo()->prepare('SELECT * FROM asignaturas WHERE id=:id LIMIT 1');
$st->execute([':id'=>$id]);
$row = $st->fetch();
if (!$row) {
  flash('error','Asignatura no encontrada.');
  header('Location: ' . PUBLIC_URL . '/admin/asignaturas/index.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $familia_id = (int)($_POST['familia_id'] ?? 0);
    $curso_id   = (int)($_POST['curso_id'] ?? 0);
    $nombre     = trim($_POST['nombre'] ?? '');
    $slug       = trim($_POST['slug'] ?? '');
    $codigo     = trim($_POST['codigo'] ?? '');
    $horas      = $_POST['horas'] !== '' ? (int)$_POST['horas'] : null;
    $descripcion= trim($_POST['descripcion'] ?? '');
    $orden      = (int)($_POST['orden'] ?? 1);
    $activa     = isset($_POST['is_active']) ? 1 : 0;

    if ($familia_id <= 0) throw new RuntimeException('Selecciona una familia.');
    if ($curso_id <= 0) throw new RuntimeException('Selecciona un curso.');
    if ($nombre === '') throw new RuntimeException('El nombre es obligatorio.');
    if ($slug === '') $slug = str_slug($nombre);
    if ($orden <= 0) $orden = 1;

    // Validar consistencia curso-familia
    $stc = pdo()->prepare('SELECT familia_id FROM cursos WHERE id=:id LIMIT 1');
    $stc->execute([':id'=>$curso_id]);
    $c = $stc->fetch();
    if (!$c) throw new RuntimeException('El curso seleccionado no existe.');
    if ((int)$c['familia_id'] !== $familia_id) {
      throw new RuntimeException('El curso no pertenece a la familia seleccionada.');
    }

    // Unicidad slug por curso excluyendo el propio
    $chk = pdo()->prepare('SELECT 1 FROM asignaturas WHERE curso_id=:curso AND slug=:slug AND id<>:id LIMIT 1');
    $chk->execute([':curso'=>$curso_id, ':slug'=>$slug, ':id'=>$id]);
    if ($chk->fetch()) throw new RuntimeException('Ya existe una asignatura con ese slug en el curso seleccionado.');

    // Unicidad código (si se indica) excluyendo el propio
    if ($codigo !== '') {
      $chk2 = pdo()->prepare('SELECT 1 FROM asignaturas WHERE curso_id=:curso AND codigo=:cod AND id<>:id LIMIT 1');
      $chk2->execute([':curso'=>$curso_id, ':cod'=>$codigo, ':id'=>$id]);
      if ($chk2->fetch()) throw new RuntimeException('Ya existe una asignatura con ese código en el curso seleccionado.');
    }

    $up = pdo()->prepare('
      UPDATE asignaturas
      SET familia_id=:f, curso_id=:c, nombre=:n, slug=:s, codigo=:g, horas=:h,
          descripcion=:d, orden=:o, is_active=:a
      WHERE id=:id
    ');
    $up->execute([
      ':f'=>$familia_id, ':c'=>$curso_id, ':n'=>$nombre, ':s'=>$slug,
      ':g'=>($codigo !== '' ? $codigo : null),
      ':h'=>$horas,
      ':d'=>($descripcion !== '' ? $descripcion : null),
      ':o'=>$orden, ':a'=>$activa, ':id'=>$id
    ]);

    flash('success','Asignatura actualizada correctamente.');
    header('Location: ' . PUBLIC_URL . '/admin/asignaturas/index.php');
    exit;
  } catch (Throwable $e) {
    flash('error', $e->getMessage());
    header('Location: ' . PUBLIC_URL . '/admin/asignaturas/edit.php?id='.$id);
    exit;
  }
}

require_once __DIR__ . '/../../../partials/header.php';
?>

<div class="mb-6 flex items-center justify-between">
  <div>
    <h1 class="text-xl font-semibold tracking-tight">Editar asignatura</h1>
    <p class="mt-1 text-sm text-slate-600">Actualiza los datos y guarda los cambios.</p>
  </div>
  <a href="<?= PUBLIC_URL ?>/admin/asignaturas/index.php"
     class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
    Volver al listado
  </a>
</div>

<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
  <form method="post" action="" class="space-y-5">
    <?= csrf_field() ?>

    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label for="familia_id" class="mb-1 block text-sm font-medium text-slate-700">Familia <span class="text-rose-600">*</span></label>
        <select id="familia_id" name="familia_id" required
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
          <?php foreach ($fams as $f): ?>
            <option value="<?= (int)$f['id'] ?>" <?= (int)$row['familia_id']===(int)$f['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($f['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="curso_id" class="mb-1 block text-sm font-medium text-slate-700">Curso <span class="text-rose-600">*</span></label>
        <select id="curso_id" name="curso_id" required
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
          <!-- opciones renderizadas por JS -->
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
        <p class="mt-1 text-xs text-slate-500">Se puede dejar vacío para regenerar a partir del nombre.</p>
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <div>
        <label for="codigo" class="mb-1 block text-sm font-medium text-slate-700">Código</label>
        <input id="codigo" name="codigo" type="text" value="<?= htmlspecialchars((string)$row['codigo']) ?>"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
      </div>
      <div>
        <label for="horas" class="mb-1 block text-sm font-medium text-slate-700">Horas</label>
        <input id="horas" name="horas" type="number" min="0" step="1"
               value="<?= $row['horas'] !== null ? (int)$row['horas'] : '' ?>"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
      </div>
      <div>
        <label for="orden" class="mb-1 block text-sm font-medium text-slate-700">Orden</label>
        <input id="orden" name="orden" type="number" min="1" value="<?= (int)$row['orden'] ?>"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
      </div>
    </div>

    <div>
      <label for="descripcion" class="mb-1 block text-sm font-medium text-slate-700">Descripción</label>
      <textarea id="descripcion" name="descripcion" rows="4"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
      ><?= htmlspecialchars((string)$row['descripcion']) ?></textarea>
    </div>

    <div class="flex items-center gap-2">
      <input id="is_active" name="is_active" type="checkbox"
             class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
             <?= (int)$row['is_active']===1 ? 'checked' : '' ?>>
      <label for="is_active" class="text-sm text-slate-700">Activa</label>
    </div>

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
      <a href="<?= PUBLIC_URL ?>/admin/asignaturas/index.php"
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
  const cursosData = <?php
    $map = [];
    foreach ($cursos as $c) { $map[$c['familia_id']][] = ['id'=>$c['id'], 'nombre'=>$c['nombre']]; }
    echo json_encode($map, JSON_UNESCAPED_UNICODE);
  ?>;

  const selFam = document.getElementById('familia_id');
  const selCur = document.getElementById('curso_id');
  const currentFam = <?= (int)$row['familia_id'] ?>;
  const currentCur = <?= (int)$row['curso_id'] ?>;

  function renderCursos(fid, selectedId = 0) {
    selCur.innerHTML = '';
    (cursosData[fid] || []).forEach(c => {
      const o = document.createElement('option');
      o.value = c.id; o.textContent = c.nombre;
      if (c.id === selectedId) o.selected = true;
      selCur.appendChild(o);
    });
  }

  renderCursos(currentFam, currentCur);
  selFam?.addEventListener('change', () => {
    const fid = parseInt(selFam.value || '0', 10);
    renderCursos(fid, 0);
  }, { passive: true });
</script>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>
