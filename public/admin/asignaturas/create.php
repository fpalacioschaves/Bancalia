<?php
// /public/admin/asignaturas/create.php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();
$u = current_user();

/*if (($u['role'] ?? '') !== 'admin') {
  flash('error', 'Acceso restringido a administradores.');
  header('Location: ' . PUBLIC_URL . '/dashboard.php');
  exit;
}*/

$fams = pdo()->query('SELECT id, nombre FROM familias_profesionales WHERE is_active=1 ORDER BY nombre ASC')->fetchAll();
$cursos = pdo()->query('SELECT id, nombre, familia_id FROM cursos WHERE is_active=1 ORDER BY familia_id ASC, orden ASC, nombre ASC')->fetchAll();

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

    // Validar que el curso pertenece a la familia
    $st = pdo()->prepare('SELECT familia_id FROM cursos WHERE id=:id LIMIT 1');
    $st->execute([':id'=>$curso_id]);
    $c = $st->fetch();
    if (!$c) throw new RuntimeException('El curso seleccionado no existe.');
    if ((int)$c['familia_id'] !== $familia_id) {
      throw new RuntimeException('El curso no pertenece a la familia seleccionada.');
    }

    // Unicidad: slug por curso
    $chk = pdo()->prepare('SELECT 1 FROM asignaturas WHERE curso_id=:curso AND slug=:slug LIMIT 1');
    $chk->execute([':curso'=>$curso_id, ':slug'=>$slug]);
    if ($chk->fetch()) throw new RuntimeException('Ya existe una asignatura con ese slug en el curso seleccionado.');

    // Unicidad código (si se indica)
    if ($codigo !== '') {
      $chk2 = pdo()->prepare('SELECT 1 FROM asignaturas WHERE curso_id=:curso AND codigo=:cod LIMIT 1');
      $chk2->execute([':curso'=>$curso_id, ':cod'=>$codigo]);
      if ($chk2->fetch()) throw new RuntimeException('Ya existe una asignatura con ese código en el curso seleccionado.');
    }

    $ins = pdo()->prepare('
      INSERT INTO asignaturas (familia_id, curso_id, nombre, slug, codigo, horas, descripcion, orden, is_active)
      VALUES (:f, :c, :n, :s, :g, :h, :d, :o, :a)
    ');
    $ins->execute([
      ':f'=>$familia_id, ':c'=>$curso_id, ':n'=>$nombre, ':s'=>$slug,
      ':g'=>($codigo !== '' ? $codigo : null),
      ':h'=>$horas,
      ':d'=>($descripcion !== '' ? $descripcion : null),
      ':o'=>$orden, ':a'=>$activa
    ]);

    flash('success','Asignatura creada correctamente.');
    header('Location: ' . PUBLIC_URL . '/admin/asignaturas/index.php');
    exit;
  } catch (Throwable $e) {
    flash('error', $e->getMessage());
    header('Location: ' . PUBLIC_URL . '/admin/asignaturas/create.php');
    exit;
  }
}

require_once __DIR__ . '/../../../partials/header.php';
?>

<div class="mb-6 flex items-center justify-between">
  <div>
    <h1 class="text-xl font-semibold tracking-tight">Nueva asignatura</h1>
    <p class="mt-1 text-sm text-slate-600">Define familia, curso y datos de la asignatura.</p>
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
          <option value="">Selecciona una familia…</option>
          <?php foreach ($fams as $f): ?>
            <option value="<?= (int)$f['id'] ?>"><?= htmlspecialchars($f['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="curso_id" class="mb-1 block text-sm font-medium text-slate-700">Curso <span class="text-rose-600">*</span></label>
        <select id="curso_id" name="curso_id" required
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
          <option value="">Selecciona primero una familia…</option>
        </select>
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label for="nombre" class="mb-1 block text-sm font-medium text-slate-700">Nombre <span class="text-rose-600">*</span></label>
        <input id="nombre" name="nombre" type="text" required
               placeholder='Ej. "Bases de Datos", "Programación", "Formación y Orientación Laboral"'
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
      </div>

      <div>
        <label for="slug" class="mb-1 block text-sm font-medium text-slate-700">Slug (opcional)</label>
        <input id="slug" name="slug" type="text" placeholder="bases-datos, programacion"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
        <p class="mt-1 text-xs text-slate-500">Si lo dejas vacío, se generará a partir del nombre.</p>
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <div>
        <label for="codigo" class="mb-1 block text-sm font-medium text-slate-700">Código (opcional)</label>
        <input id="codigo" name="codigo" type="text" placeholder="BD, PR, FOL…"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
      </div>
      <div>
        <label for="horas" class="mb-1 block text-sm font-medium text-slate-700">Horas (opcional)</label>
        <input id="horas" name="horas" type="number" min="0" step="1"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
      </div>
      <div>
        <label for="orden" class="mb-1 block text-sm font-medium text-slate-700">Orden</label>
        <input id="orden" name="orden" type="number" value="1" min="1"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
      </div>
    </div>

    <div>
      <label for="descripcion" class="mb-1 block text-sm font-medium text-slate-700">Descripción (opcional)</label>
      <textarea id="descripcion" name="descripcion" rows="4"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
                placeholder="Breve descripción…"></textarea>
    </div>

    <div class="flex items-center gap-2">
      <input id="is_active" name="is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400" checked>
      <label for="is_active" class="text-sm text-slate-700">Activa</label>
    </div>

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
      <a href="<?= PUBLIC_URL ?>/admin/asignaturas/index.php"
         class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
        Cancelar
      </a>
      <button type="submit"
              class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 active:scale-[0.99] transition">
        Crear asignatura
      </button>
    </div>
  </form>
</div>

<script>
  // Poblado dependiente de cursos según familia (cliente)
  const cursosData = <?php
    $map = [];
    foreach ($cursos as $c) { $map[$c['familia_id']][] = ['id'=>$c['id'], 'nombre'=>$c['nombre']]; }
    echo json_encode($map, JSON_UNESCAPED_UNICODE);
  ?>;

  const selFam = document.getElementById('familia_id');
  const selCur = document.getElementById('curso_id');

  function renderCursos(fid) {
    selCur.innerHTML = '';
    const def = document.createElement('option');
    def.value = ''; def.textContent = 'Selecciona un curso…';
    selCur.appendChild(def);
    (cursosData[fid] || []).forEach(c => {
      const o = document.createElement('option');
      o.value = c.id; o.textContent = c.nombre;
      selCur.appendChild(o);
    });
  }

  selFam?.addEventListener('change', () => {
    const fid = parseInt(selFam.value || '0', 10);
    renderCursos(fid);
  }, { passive: true });
</script>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>
