<?php
// /public/admin/familias/index.php
declare(strict_types=1);
require_once __DIR__ . '/../../../middleware/require_admin.php';
require_once __DIR__ . '/../../../partials/header.php';

$q = trim($_GET['q'] ?? '');
$sql = 'SELECT id, nombre, slug, is_active, updated_at FROM familias_profesionales';
$params = [];
if ($q !== '') {
  $sql .= ' WHERE nombre LIKE :q OR slug LIKE :q';
  $params[':q'] = "%{$q}%";
}
$sql .= ' ORDER BY nombre ASC';

$st = pdo()->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();
?>

<h1 class="text-xl font-semibold tracking-tight mb-4">Familias Profesionales</h1>

<form method="get" action="" class="mb-5 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
  <label class="sr-only" for="q">Buscar</label>
  <input
    id="q"
    type="search"
    name="q"
    value="<?= htmlspecialchars($q) ?>"
    placeholder="Buscar por nombre o slug"
    class="w-full sm:w-96 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
  />
  <button
    type="submit"
    class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 active:scale-[0.99] transition"
    title="Buscar familias"
  >
    Buscar
  </button>

  <a
    href="<?= PUBLIC_URL ?>/admin/familias/create.php"
    class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 active:scale-[0.99] transition"
    title="Crear nueva familia"
  >
    + Nueva familia
  </a>
</form>

<?php if (!$rows): ?>
  <div class="rounded-lg border border-slate-200 bg-white p-6 text-slate-600">
    No hay familias todavía.
    <a href="<?= PUBLIC_URL ?>/admin/familias/create.php" class="text-indigo-600 hover:underline font-medium">Crea la primera</a>.
  </div>
<?php else: ?>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-slate-200">
      <thead class="bg-slate-50">
        <tr>
          <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Nombre</th>
          <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Slug</th>
          <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Activa</th>
          <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Actualizado</th>
          <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200">
        <?php foreach ($rows as $r): ?>
          <tr class="hover:bg-slate-50">
            <td class="px-4 py-3 text-sm text-slate-800"><?= htmlspecialchars($r['nombre']) ?></td>
            <td class="px-4 py-3 text-sm text-slate-700"><code class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px]"><?= htmlspecialchars($r['slug']) ?></code></td>
            <td class="px-4 py-3 text-sm">
              <?php if ((int)$r['is_active'] === 1): ?>
                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[12px] font-medium text-emerald-700 ring-1 ring-emerald-200">Sí</span>
              <?php else: ?>
                <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-[12px] font-medium text-rose-700 ring-1 ring-rose-200">No</span>
              <?php endif; ?>
            </td>
            <td class="px-4 py-3 text-sm text-slate-600"><?= htmlspecialchars($r['updated_at']) ?></td>
            <td class="px-4 py-3 text-sm">
              <div class="flex justify-end gap-2">
                <a
                  href="<?= PUBLIC_URL ?>/admin/familias/edit.php?id=<?= (int)$r['id'] ?>"
                  class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100"
                  title="Editar"
                >Editar</a>

                <form method="post" action="<?= PUBLIC_URL ?>/admin/familias/delete.php" onsubmit="return confirm('¿Eliminar esta familia?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button
                    type="submit"
                    class="inline-flex items-center rounded-lg bg-rose-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-rose-500"
                    title="Eliminar"
                  >Eliminar</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>
