<?php
// /public/admin/familias/edit.php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();
$u = current_user();


$id = (int)($_GET['id'] ?? 0);

$st = pdo()->prepare('SELECT * FROM familias_profesionales WHERE id=:id LIMIT 1');
$st->execute([':id'=>$id]);
$row = $st->fetch();

if (!$row) {
  flash('error', 'Familia no encontrada.');
  header('Location: ' . PUBLIC_URL . '/admin/familias/index.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $nombre = trim($_POST['nombre'] ?? '');
    $slug   = trim($_POST['slug'] ?? '');
    $desc   = trim($_POST['descripcion'] ?? '');
    $activa = isset($_POST['is_active']) ? 1 : 0;

    if ($nombre === '') {
      throw new RuntimeException('El nombre es obligatorio.');
    }
    if ($slug === '') {
      $slug = str_slug($nombre);
    }

    // unicidad excluyendo el actual
    $chk = pdo()->prepare('SELECT 1 FROM familias_profesionales WHERE (nombre=:n OR slug=:s) AND id<>:id LIMIT 1');
    $chk->execute([':n'=>$nombre, ':s'=>$slug, ':id'=>$id]);
    if ($chk->fetch()) {
      throw new RuntimeException('Nombre o slug ya están en uso.');
    }

    $up = pdo()->prepare('
      UPDATE familias_profesionales
      SET nombre=:n, slug=:s, descripcion=:d, is_active=:a
      WHERE id=:id
    ');
    $up->execute([
      ':n'=>$nombre,
      ':s'=>$slug,
      ':d'=>$desc !== '' ? $desc : null,
      ':a'=>$activa,
      ':id'=>$id
    ]);

    flash('success', 'Familia actualizada correctamente.');
    header('Location: ' . PUBLIC_URL . '/admin/familias/index.php');
    exit;
  } catch (Throwable $e) {
    flash('error', $e->getMessage());
    header('Location: ' . PUBLIC_URL . '/admin/familias/edit.php?id=' . $id);
    exit;
  }
}

require_once __DIR__ . '/../../../partials/header.php';
?>

<!-- Breadcrumb / encabezado -->
<div class="mb-6 flex items-center justify-between">
  <div>
    <h1 class="text-xl font-semibold tracking-tight">Editar familia</h1>
    <p class="mt-1 text-sm text-slate-600">Modifica los datos y guarda los cambios.</p>
  </div>
  <a href="<?= PUBLIC_URL ?>/admin/familias/index.php"
     class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
    Volver al listado
  </a>
</div>

<!-- Card formulario -->
<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
  <form method="post" action="" class="space-y-5">
    <?= csrf_field() ?>

    <div>
      <label for="nombre" class="mb-1 block text-sm font-medium text-slate-700">Nombre <span class="text-rose-600">*</span></label>
      <input
        id="nombre" name="nombre" type="text" required
        value="<?= htmlspecialchars($row['nombre']) ?>"
        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400"
        placeholder="Informática y comunicaciones"
      >
    </div>

    <div>
      <label for="slug" class="mb-1 block text-sm font-medium text-slate-700">Slug</label>
      <input
        id="slug" name="slug" type="text"
        value="<?= htmlspecialchars($row['slug']) ?>"
        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400"
        placeholder="informatica-comunicaciones"
      >
      <p class="mt-1 text-xs text-slate-500">Si lo dejas vacío al guardar, se regenerará a partir del nombre.</p>
    </div>

    <div>
      <label for="descripcion" class="mb-1 block text-sm font-medium text-slate-700">Descripción</label>
      <textarea
        id="descripcion" name="descripcion" rows="4"
        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400"
        placeholder="Breve descripción de la familia profesional…"
      ><?= htmlspecialchars((string)$row['descripcion']) ?></textarea>
    </div>

    <div class="flex items-center gap-2">
      <input
        id="is_active" name="is_active" type="checkbox"
        class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
        <?= (int)$row['is_active'] === 1 ? 'checked' : '' ?>
      >
      <label for="is_active" class="text-sm text-slate-700">Activa</label>
    </div>

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
      <a href="<?= PUBLIC_URL ?>/admin/familias/index.php"
         class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
        Cancelar
      </a>

      <div class="flex gap-2">
        <button type="submit"
                class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 active:scale-[0.99] transition">
          Guardar cambios
        </button>
      </div>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>
