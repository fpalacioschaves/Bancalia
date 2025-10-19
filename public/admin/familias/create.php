<?php
// /public/admin/familias/create.php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();
$u = current_user();

if (($u['role'] ?? '') !== 'admin') {
  flash('error', 'Acceso restringido a administradores.');
  header('Location: ' . PUBLIC_URL . '/dashboard.php');
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

    // unicidad
    $chk = pdo()->prepare('SELECT 1 FROM familias_profesionales WHERE nombre=:n OR slug=:s LIMIT 1');
    $chk->execute([':n'=>$nombre, ':s'=>$slug]);
    if ($chk->fetch()) {
      throw new RuntimeException('Nombre o slug ya existen.');
    }

    $st = pdo()->prepare('
      INSERT INTO familias_profesionales (nombre, slug, descripcion, is_active)
      VALUES (:n, :s, :d, :a)
    ');
    $st->execute([
      ':n'=>$nombre,
      ':s'=>$slug,
      ':d'=>$desc !== '' ? $desc : null,
      ':a'=>$activa
    ]);

    flash('success', 'Familia creada correctamente.');
    header('Location: ' . PUBLIC_URL . '/admin/familias/index.php');
    exit;
  } catch (Throwable $e) {
    flash('error', $e->getMessage());
    header('Location: ' . PUBLIC_URL . '/admin/familias/create.php');
    exit;
  }
}

require_once __DIR__ . '/../../../partials/header.php';
?>

<!-- Breadcrumb / encabezado -->
<div class="mb-6 flex items-center justify-between">
  <div>
    <h1 class="text-xl font-semibold tracking-tight">Nueva familia</h1>
    <p class="mt-1 text-sm text-slate-600">Crea una familia profesional para clasificar tus ciclos.</p>
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
        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400"
        placeholder="Informática y comunicaciones"
      >
      <p class="mt-1 text-xs text-slate-500">El nombre debe ser único.</p>
    </div>

    <div>
      <label for="slug" class="mb-1 block text-sm font-medium text-slate-700">Slug (opcional)</label>
      <input
        id="slug" name="slug" type="text"
        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400"
        placeholder="informatica-comunicaciones"
      >
      <p class="mt-1 text-xs text-slate-500">Si lo dejas vacío, se generará automáticamente a partir del nombre.</p>
    </div>

    <div>
      <label for="descripcion" class="mb-1 block text-sm font-medium text-slate-700">Descripción</label>
      <textarea
        id="descripcion" name="descripcion" rows="4"
        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400"
        placeholder="Breve descripción de la familia profesional…"
      ></textarea>
    </div>

    <div class="flex items-center gap-2">
      <input id="is_active" name="is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400" checked>
      <label for="is_active" class="text-sm text-slate-700">Activa</label>
    </div>

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
      <a href="<?= PUBLIC_URL ?>/admin/familias/index.php"
         class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
        Cancelar
      </a>
      <button type="submit"
              class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 active:scale-[0.99] transition">
        Crear familia
      </button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>
