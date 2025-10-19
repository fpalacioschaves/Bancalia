<?php
// /public/admin/cursos/index.php
declare(strict_types=1);
require_once __DIR__ . '/../../../middleware/require_admin.php';
require_once __DIR__ . '/../../../partials/header.php';

$q = trim($_GET['q'] ?? '');
$fam = (int)($_GET['familia_id'] ?? 0);

// Familias para el filtro
$fams = pdo()->query('SELECT id, nombre FROM familias_profesionales WHERE is_active=1 ORDER BY nombre ASC')->fetchAll();

$sql = "SELECT c.id, c.nombre, c.slug, c.orden, c.is_active, c.updated_at,
               f.nombre AS familia
        FROM cursos c
        JOIN familias_profesionales f ON f.id = c.familia_id";
$params = [];

$w = [];
if ($q !== '') {
  $w[] = '(c.nombre LIKE :q OR c.slug LIKE :q OR f.nombre LIKE :q)';
  $params[':q'] = "%{$q}%";
}
if ($fam > 0) {
  $w[] = 'c.familia_id = :fam';
  $params[':fam'] = $fam;
}
if ($w) {
  $sql .= ' WHERE '.implode(' AND ', $w);
}
$sql .= ' ORDER BY f.nombre ASC, c.orden ASC, c.nombre ASC';

$st = pdo()->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();
?>

<h1 class="text-xl font-semibold tracking-tight mb-4">Cursos</h1>

<form method="get" action="" class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-[1fr,240px,auto,auto] items-stretch">
  <label class="sr-only" for="q">Buscar</label>
  <input
    id="q"
    type="search"
    name="q"
    value="<?= htmlspecialchars($q) ?>"
    placeholder="Buscar por curso, slug o familia"
    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
  />

  <select
    name="familia_id"
    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
    aria-label="Filtrar por familia"
  >
    <option value="0">Todas las familias</option>
    <?php foreach ($fams as $f): ?>
      <option value="<?= (int)$f['id'] ?>" <?= $fam===(int)$f['id'] ? 'selected' : '' ?>>
        <?= htmlspecialchars($f['nombre']) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <button
    type="submit"
    class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 active:scale-[0.99] transition"
  >
    Buscar
  </button>

  <a
    href="<?= PUBLIC_URL ?>/admin/cursos/create.php"
    class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 active:scale-[0.99] transition"
  >
    + Nuevo curso
  </a>
</form>

<?php if (!$rows): ?>
  <div class="rounded-lg border border-slate-200 bg-white p-6 text-slate-600">
    No hay cursos todavía.
    <a href="<?= PUBLIC_URL ?>/admin/cursos/create.php" class="text-indigo-600 hover:underline font-medium">Crea el primero</a>.
  </div>
<?php else: ?>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-slate-200">
      <thead class="bg-slate-50">
        <tr>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Familia</th>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Curso</th>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Slug</th>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Orden</th>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Activa</th>
          <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200">
        <?php foreach ($rows as $r): ?>
          <tr class="hover:bg-slate-50">
            <td class="px-4 py-3 text-sm text-slate-800"><?= htmlspecialchars($r['familia']) ?></td>
            <td class="px-4 py-3 text-sm text-slate-800"><?= htmlspecialchars($r['nombre']) ?></td>
            <td class="px-4 py-3 text-sm text-slate-700"><code class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px]"><?= htmlspecialchars($r['slug']) ?></code></td>
            <td class="px-4 py-3 text-sm text-slate-800"><?= (int)$r['orden'] ?></td>
            <td class="px-4 py-3 text-sm">
              <?php if ((int)$r['is_active'] === 1): ?>
                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[12px] font-medium text-emerald-700 ring-1 ring-emerald-200">Sí</span>
              <?php else: ?>
                <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-[12px] font-medium text-rose-700 ring-1 ring-rose-200">No</span>
              <?php endif; ?>
            </td>
            <td class="px-4 py-3 text-sm">
              <div class="flex justify-end gap-2">
                <a
                  href="<?= PUBLIC_URL ?>/admin/cursos/edit.php?id=<?= (int)$r['id'] ?>"
                  class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100"
                >Editar</a>

                <form method="post" action="<?= PUBLIC_URL ?>/admin/cursos/delete.php" onsubmit="return confirm('¿Eliminar este curso?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button
                    type="submit"
                    class="inline-flex items-center rounded-lg bg-rose-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-rose-500"
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
