<?php
// /public/admin/examenes/index.php
declare(strict_types=1);
require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();

$u = current_user();
require_once __DIR__ . '/../../../partials/header.php';

$q      = trim($_GET['q'] ?? '');
$estado = $_GET['estado'] ?? '';

// Estados válidos según la BD
$allowedEstados = ['borrador', 'publicado'];

// Construcción de la consulta base
$sql = "SELECT e.id, e.titulo, e.estado, e.fecha, e.hora
        FROM examenes e";
$params = [];

$w = [];

// Búsqueda por título
if ($q !== '') {
  $w[] = 'e.titulo LIKE :q';
  $params[':q'] = "%{$q}%";
}

// Filtro por estado
if ($estado !== '' && in_array($estado, $allowedEstados, true)) {
  $w[] = 'e.estado = :estado';
  $params[':estado'] = $estado;
}

// Añadir WHERE si procede
if ($w) {
  $sql .= ' WHERE ' . implode(' AND ', $w);
}

// Orden: primero los que tienen fecha, luego por fecha+hora, y si no, por id descendente
$sql .= ' ORDER BY e.fecha IS NULL ASC, e.fecha ASC, e.hora ASC, e.id DESC';

$st = pdo()->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();
?>

<h1 class="text-xl font-semibold tracking-tight mb-4">Exámenes</h1>

<form method="get" action="" class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-[1fr,240px,auto,auto] items-stretch">
  <label class="sr-only" for="q">Buscar</label>
  <input
    id="q"
    type="search"
    name="q"
    value="<?= htmlspecialchars($q) ?>"
    placeholder="Buscar por título de examen"
    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm
placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
  />

  <select
    name="estado"
    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none
focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
    aria-label="Filtrar por estado"
  >
    <option value="">Todos los estados</option>
    <option value="borrador"  <?= $estado === 'borrador'  ? 'selected' : '' ?>>Borrador</option>
    <option value="publicado" <?= $estado === 'publicado' ? 'selected' : '' ?>>Publicado</option>
  </select>

  <button
    type="submit"
    class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 active:scale-[0.99] transition"
  >
    Buscar
  </button>

  <a
    href="<?= PUBLIC_URL ?>/admin/examenes/create.php"
    class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 active:scale-[0.99] transition"
  >
    Nuevo examen
  </a>
</form>

<?php if (!$rows): ?>
  <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center text-slate-500 text-sm">
    No hay ningún examen que coincida con los filtros.
  </div>
<?php else: ?>
  <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
      <thead class="bg-slate-50">
        <tr>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">ID</th>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Título</th>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Estado</th>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Fecha</th>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Hora</th>
          <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200">
        <?php foreach ($rows as $r): ?>
          <tr class="hover:bg-slate-50">
            <td class="px-4 py-3 text-sm text-slate-500">
              #<?= (int)$r['id'] ?>
            </td>
            <td class="px-4 py-3 text-sm text-slate-800">
              <?= htmlspecialchars($r['titulo']) ?>
            </td>
            <td class="px-4 py-3 text-sm">
              <?php if ($r['estado'] === 'publicado'): ?>
<span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[12px] font-medium
text-emerald-700 ring-1 ring-emerald-200">
                  Publicado
                </span>
              <?php else: ?>
<span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[12px] font-medium
text-slate-700 ring-1 ring-slate-200">
                  Borrador
                </span>
              <?php endif; ?>
            </td>
            <td class="px-4 py-3 text-sm text-slate-800">
              <?= $r['fecha'] !== null ? htmlspecialchars($r['fecha']) : '—' ?>
            </td>
            <td class="px-4 py-3 text-sm text-slate-800">
              <?= $r['hora'] !== null ? htmlspecialchars(substr($r['hora'], 0, 5)) : '—' ?>
            </td>
            <td class="px-4 py-3 text-sm">
              <div class="flex justify-end gap-2">
                <a
                  href="<?= PUBLIC_URL ?>/admin/examenes/edit.php?id=<?= (int)$r['id'] ?>"
class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm
font-medium text-slate-700 hover:bg-slate-100"
                >Editar</a>

                <a
                  href="<?= PUBLIC_URL ?>/admin/examenes/actividades.php?id=<?= (int)$r['id'] ?>"
class="inline-flex items-center rounded-md border border-indigo-300 px-2 py-1 text-xs font-medium
text-indigo-700 hover:bg-indigo-50"
                >
                  Actividades
                </a>

                <a
                  href="<?= PUBLIC_URL ?>/admin/examenes/preview.php?id=<?= (int)$r['id'] ?>"
class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs
font-medium text-slate-700 hover:bg-slate-100"
                >
                  Vista previa
                </a>

                <!-- 🔹 NUEVO BOTÓN: INTENTOS -->
                <a
href="<?= PUBLIC_URL ?>/admin/examenes/intentos.php?examen_id=<?= (int)$r['id'] ?>"
class="inline-flex items-center rounded-lg border border-fuchsia-200 bg-fuchsia-50 px-3 py-1.5
text-xs font-medium text-fuchsia-700 hover:bg-fuchsia-100"
                >
                  Intentos
                </a>

                <form method="post" action="<?= PUBLIC_URL ?>/admin/examenes/delete.php" onsubmit="return confirm('¿Eliminar este examen?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button
                    type="submit"
class="inline-flex items-center rounded-lg bg-rose-600 px-3 py-1.5 text-sm font-semibold text-white
hover:bg-rose-500"
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
