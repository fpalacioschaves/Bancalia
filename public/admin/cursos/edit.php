<?php
// /public/admin/cursos/edit.php
declare(strict_types=1);
require_once __DIR__ . '/../../../middleware/require_admin.php';

$id = (int)($_GET['id'] ?? 0);

// Familias para el select
$fams = pdo()->query('SELECT id, nombre FROM familias_profesionales WHERE is_active=1 ORDER BY nombre ASC')->fetchAll();

// Cargar curso
$st = pdo()->prepare('SELECT * FROM cursos WHERE id=:id LIMIT 1');
$st->execute([':id'=>$id]);
$row = $st->fetch();
if (!$row) {
  flash('error','Curso no encontrado.');
  header('Location: ' . PUBLIC_URL . '/admin/cursos/index.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $familia_id = (int)($_POST['familia_id'] ?? 0);
    $nombre     = trim($_POST['nombre'] ?? '');
    $slug       = trim($_POST['slug'] ?? '');
    $desc       = trim($_POST['descripcion'] ?? '');
    $orden      = (int)($_POST['orden'] ?? 1);
    $activa     = isset($_POST['is_active']) ? 1 : 0;

    if ($familia_id <= 0) throw new RuntimeException('Selecciona una familia.');
    if ($nombre === '') throw new RuntimeException('El nombre es obligatorio.');
    if ($slug === '') $slug = str_slug($nombre);
    if ($orden <= 0) $orden = 1;

    // Validar familia
    $chkFam = pdo()->prepare('SELECT 1 FROM familias_profesionales WHERE id=:id LIMIT 1');
    $chkFam->execute([':id'=>$familia_id]);
    if (!$chkFam->fetch()) throw new RuntimeException('La familia seleccionada no existe.');

    // Unicidad por familia excluyendo el propio ID
    $chk = pdo()->prepare('SELECT 1 FROM cursos WHERE familia_id=:f AND slug=:s AND id<>:id LIMIT 1');
    $chk->execute([':f'=>$familia_id, ':s'=>$slug, ':id'=>$id]);
    if ($chk->fetch()) throw new RuntimeException('Ya existe un curso con ese slug en la misma familia.');

    $up = pdo()->prepare('
      UPDATE cursos
      SET familia_id=:f, nombre=:n, slug=:s, descripcion=:d, orden=:o, is_active=:a
      WHERE id=:id
    ');
    $up->execute([
      ':f'=>$familia_id, ':n'=>$nombre, ':s'=>$slug,
      ':d'=>$desc !== '' ? $desc : null,
      ':o'=>$orden, ':a'=>$activa, ':id'=>$id
    ]);

    flash('success','Curso actualizado correctamente.');
    header('Location: ' . PUBLIC_URL . '/admin/cursos/index.php');
    exit;
  } catch (Throwable $e) {
    flash('error', $e->getMessage());
    header('Location: ' . PUBLIC_URL . '/admin/cursos/edit.php?id=' . $id);
    exit;
  }
}

require_once __DIR__ . '/../../../partials/header.php';
?>

<div class="mb-6 flex items-center justify-between">
  <div>
    <h1 class="text-xl font-semibold tracking-tight">Editar curso</h1>
    <p class="mt-1 text-sm text-slate-600">Modifica los datos del curso y guarda los cambios.</p>
  </div>
  <a href="<?= PUBLIC_URL ?>/admin/cursos/index.php"
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
        <label for="orden" class="mb-1 block text-sm font-medium text-slate-700">Orden</label>
        <input id="orden" name="orden" type="number" value="<?= (int)$row['orden'] ?>" min="1"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
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
        <p class="mt-1 text-xs text-slate-500">Si lo dejas vacío al guardar, se generará a partir del nombre.</p>
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
      <label for="is_active" class="text-sm text-slate-700">Activo</label>
    </div>

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
      <a href="<?= PUBLIC_URL ?>/admin/cursos/index.php"
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

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>
