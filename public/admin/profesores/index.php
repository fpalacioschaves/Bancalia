<?php
// /public/admin/profesores/index.php
declare(strict_types=1);
require_once __DIR__ . '/../../../middleware/require_admin.php';
require_once __DIR__ . '/../../../partials/header.php';

$q       = trim($_GET['q'] ?? '');
$centro  = (int)($_GET['centro_id'] ?? 0);
$activos = ($_GET['activos'] ?? '1') === '0' ? 0 : 1;

$centros = pdo()->query('SELECT id, nombre FROM centros WHERE is_active=1 ORDER BY nombre ASC')->fetchAll();

$sql = "SELECT p.id, p.nombre, p.apellidos, p.email, p.telefono, p.is_active, c.nombre AS centro
        FROM profesores p
        LEFT JOIN centros c ON c.id = p.centro_id";
$params = [];
$w = [];
if ($q !== '') {
  $w[] = "(p.nombre LIKE :q OR p.apellidos LIKE :q OR p.email LIKE :q OR p.telefono LIKE :q)";
  $params[':q'] = "%{$q}%";
}
if ($centro > 0) {
  $w[] = "p.centro_id = :cid";
  $params[':cid'] = $centro;
}
$w[] = "p.is_active = :a";
$params[':a'] = $activos;

if ($w) $sql .= ' WHERE '.implode(' AND ', $w);
$sql .= ' ORDER BY p.apellidos ASC, p.nombre ASC';

$st = pdo()->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();
?>

<h1 class="text-xl font-semibold tracking-tight mb-4">Profesores</h1>

<form method="get" action="" class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-[1fr,260px,160px,auto,auto] items-stretch">
  <input
    type="search" name="q" value="<?= htmlspecialchars($q) ?>"
    placeholder="Buscar por nombre, apellidos, email o teléfono…"
    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400" />

  <select name="centro_id"
          class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
    <option value="0">Todos los centros</option>
    <?php foreach ($centros as $c): ?>
      <option value="<?= (int)$c['id'] ?>" <?= $centro===(int)$c['id']?'selected':'' ?>>
        <?= htmlspecialchars($c['nombre']) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <select name="activos"
          class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
    <option value="1" <?= $activos===1?'selected':'' ?>>Activos</option>
    <option value="0" <?= $activos===0?'selected':'' ?>>Inactivos</option>
  </select>

  <button type="submit"
          class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 active:scale-[0.99] transition">
    Buscar
  </button>

  <a href="<?= PUBLIC_URL ?>/admin/profesores/create.php"
     class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 active:scale-[0.99] transition">
    + Nuevo profesor
  </a>
</form>

<?php if (!$rows): ?>
  <div class="rounded-lg border border-slate-200 bg-white p-6 text-slate-600">
    No hay profesores con estos filtros.
  </div>
<?php else: ?>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-slate-200">
      <thead class="bg-slate-50">
        <tr>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Profesor</th>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Email</th>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Teléfono</th>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Centro</th>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Activo</th>
          <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200">
        <?php foreach ($rows as $r): ?>
          <tr class="hover:bg-slate-50">
            <td class="px-4 py-3 text-sm text-slate-800 font-medium">
              <?= htmlspecialchars($r['apellidos'].', '.$r['nombre']) ?>
            </td>
            <td class="px-4 py-3 text-sm text-slate-800"><?= htmlspecialchars($r['email']) ?></td>
            <td class="px-4 py-3 text-sm text-slate-800"><?= htmlspecialchars((string)$r['telefono'] ?: '—') ?></td>
            <td class="px-4 py-3 text-sm text-slate-800"><?= htmlspecialchars((string)$r['centro'] ?: '—') ?></td>
            <td class="px-4 py-3 text-sm">
              <?php if ((int)$r['is_active'] === 1): ?>
                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[12px] font-medium text-emerald-700 ring-1 ring-emerald-200">Sí</span>
              <?php else: ?>
                <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-[12px] font-medium text-rose-700 ring-1 ring-rose-200">No</span>
              <?php endif; ?>
            </td>
            <td class="px-4 py-3 text-sm">
              <div class="flex justify-end gap-2">
                <a href="<?= PUBLIC_URL ?>/admin/profesores/edit.php?id=<?= (int)$r['id'] ?>"
                   class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100">Editar</a>
                <form method="post" action="<?= PUBLIC_URL ?>/admin/profesores/delete.php"
                      onsubmit="return confirm('¿Eliminar este profesor?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button type="submit"
                          class="inline-flex items-center rounded-lg bg-rose-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-rose-500">Eliminar</button>
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
