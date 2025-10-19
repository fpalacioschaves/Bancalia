<?php
// /public/admin/centros/index.php
declare(strict_types=1);

require_once __DIR__ . '/../../../middleware/require_auth.php';
require_once __DIR__ . '/../../../lib/auth.php';
require_once __DIR__ . '/../../../partials/header.php';

$u = current_user();
if (!$u || !in_array(($u['role'] ?? ''), ['admin','profesor'], true)) {
  $_SESSION['flash'] = 'Acceso restringido.';
  header('Location: ' . PUBLIC_URL . '/auth/login.php'); exit;
}

// Búsqueda
$q = trim((string)($_GET['q'] ?? ''));
$params = [];
$sql = 'SELECT id, nombre, slug, localidad, provincia, comunidad, telefono, email, web, is_active FROM centros';
if ($q !== '') {
  $sql .= ' WHERE nombre LIKE :q OR localidad LIKE :q OR provincia LIKE :q OR comunidad LIKE :q OR slug LIKE :q';
  $params[':q'] = '%' . $q . '%';
}
$sql .= ' ORDER BY nombre ASC';

$st = pdo()->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();
?>
<div class="mb-6 flex items-center justify-between">
  <div>
    <h1 class="text-xl font-semibold tracking-tight">Centros</h1>
    <p class="mt-1 text-sm text-slate-600">Gestión de centros (admin y profesor).</p>
  </div>
  <a href="<?= PUBLIC_URL ?>/admin/centros/create.php"
     class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">Nuevo centro</a>
</div>

<form method="get" action="" class="mb-4">
  <div class="flex gap-2">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por nombre, localidad, provincia…"
           class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
    <button class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Buscar</button>
  </div>
</form>

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
  <table class="min-w-full divide-y divide-slate-200">
    <thead class="bg-slate-50">
      <tr>
        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Nombre</th>
        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Ubicación</th>
        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Contacto</th>
        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Estado</th>
        <th class="px-3 py-2"></th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-200">
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="px-3 py-2">
            <div class="font-medium"><?= htmlspecialchars($r['nombre']) ?></div>
            <div class="text-xs text-slate-500"><?= htmlspecialchars($r['slug']) ?></div>
          </td>
          <td class="px-3 py-2 text-sm text-slate-700">
            <?= htmlspecialchars(implode(', ', array_filter([$r['localidad'], $r['provincia'], $r['comunidad']]))) ?>
          </td>
          <td class="px-3 py-2 text-sm text-slate-700">
            <?php if ($r['telefono']): ?><div><?= htmlspecialchars($r['telefono']) ?></div><?php endif; ?>
            <?php if ($r['email']): ?><div class="text-slate-600"><?= htmlspecialchars($r['email']) ?></div><?php endif; ?>
          </td>
          <td class="px-3 py-2">
            <?php if ((int)$r['is_active']===1): ?>
              <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">Activo</span>
            <?php else: ?>
              <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">Inactivo</span>
            <?php endif; ?>
          </td>
          <td class="px-3 py-2 text-right">
            <div class="flex justify-end gap-2">
              <a href="<?= PUBLIC_URL ?>/admin/centros/edit.php?id=<?= (int)$r['id'] ?>"
                 class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm hover:bg-slate-100">Editar</a>
              <form method="post" action="<?= PUBLIC_URL ?>/admin/centros/delete.php" onsubmit="return confirm('¿Seguro que quieres borrar este centro?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="rounded-lg bg-rose-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-rose-500">Borrar</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr><td colspan="5" class="px-3 py-6 text-center text-sm text-slate-500">No hay centros.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>

