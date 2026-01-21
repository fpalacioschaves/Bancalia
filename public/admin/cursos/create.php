<?php
// /public/admin/cursos/create.php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();
$u = current_user();

/*if (($u['role'] ?? '') !== 'admin') {
  flash('error', 'Acceso restringido a administradores.');
  header('Location: ' . PUBLIC_URL . '/dashboard.php');
  exit;
}*/

$familiaService = new FamiliaService(pdo());
$fams = $familiaService->findAll(['onlyActive' => true]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);
    $cursoService = new CursoService(pdo());
    $cursoService->create($_POST);

    flash('success', 'Curso creado correctamente.');
    header('Location: ' . PUBLIC_URL . '/admin/cursos/index.php');
    exit;
  } catch (Throwable $e) {
    flash('error', $e->getMessage());
    header('Location: ' . PUBLIC_URL . '/admin/cursos/create.php');
    exit;
  }
}

require_once __DIR__ . '/../../../partials/header.php';
?>

<div class="mb-6 flex items-center justify-between">
  <div>
    <h1 class="text-xl font-semibold tracking-tight">Nuevo curso</h1>
    <p class="mt-1 text-sm text-slate-600">Añade un curso (1º, 2º, Máster…) asociado a una familia.</p>
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
        <label for="familia_id" class="mb-1 block text-sm font-medium text-slate-700">Familia <span
            class="text-rose-600">*</span></label>
        <select id="familia_id" name="familia_id" required
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
          <option value="">Selecciona una familia…</option>
          <?php foreach ($fams as $f): ?>
            <option value="<?= (int) $f['id'] ?>"><?= h($f['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="orden" class="mb-1 block text-sm font-medium text-slate-700">Orden</label>
        <input id="orden" name="orden" type="number" value="1" min="1"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
        <p class="mt-1 text-xs text-slate-500">Controla la posición en el listado dentro de la familia.</p>
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label for="nombre" class="mb-1 block text-sm font-medium text-slate-700">Nombre <span
            class="text-rose-600">*</span></label>
        <input id="nombre" name="nombre" type="text" required placeholder='Ej. "1º", "2º", "Máster"'
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
      </div>

      <div>
        <label for="slug" class="mb-1 block text-sm font-medium text-slate-700">Slug (opcional)</label>
        <input id="slug" name="slug" type="text" placeholder="1o, 2o, master"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
        <p class="mt-1 text-xs text-slate-500">Si lo dejas vacío, se generará a partir del nombre.</p>
      </div>
    </div>

    <div>
      <label for="descripcion" class="mb-1 block text-sm font-medium text-slate-700">Descripción</label>
      <textarea id="descripcion" name="descripcion" rows="4"
        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
        placeholder="Añade detalles del curso (opcional)…"></textarea>
    </div>

    <div class="flex items-center gap-2">
      <input id="is_active" name="is_active" type="checkbox"
        class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400" checked>
      <label for="is_active" class="text-sm text-slate-700">Activo</label>
    </div>

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
      <a href="<?= PUBLIC_URL ?>/admin/cursos/index.php"
        class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
        Cancelar
      </a>
      <button type="submit"
        class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 active:scale-[0.99] transition">
        Crear curso
      </button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>