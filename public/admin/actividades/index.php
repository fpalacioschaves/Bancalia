<?php
// /public/dashboard.php
declare(strict_types=1);
require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();

$u = current_user();
require_once __DIR__ . '/../../../partials/header.php';



$role = $u['role'] ?? '';
$profesorId = (int)($u['profesor_id'] ?? 0);


// Filtros
$q   = trim((string)($_GET['q'] ?? ''));
$fam = (int)($_GET['familia_id'] ?? 0);
$cur = (int)($_GET['curso_id'] ?? 0);
$asi = (int)($_GET['asignatura_id'] ?? 0);

// Datos para selects
$familias  = pdo()->query("SELECT id, nombre FROM familias_profesionales WHERE is_active=1 ORDER BY nombre ASC")->fetchAll();
$cursosAll = pdo()->query("SELECT id, nombre, familia_id FROM cursos WHERE is_active=1 ORDER BY familia_id ASC, orden ASC, nombre ASC")->fetchAll();
$asigsAll  = pdo()->query("SELECT id, nombre, curso_id FROM asignaturas WHERE is_active=1 ORDER BY curso_id ASC, orden ASC, nombre ASC")->fetchAll();

// Asignaturas del profesor (para ampliar visibilidad a públicas de su ámbito)
$misAsignaturas = [];
if ($role !== 'admin' && $profesorId > 0) {
  $st = pdo()->prepare('SELECT DISTINCT asignatura_id FROM profesor_asignacion WHERE profesor_id=:p');
  $st->execute([':p' => $profesorId]);
  $misAsignaturas = array_map('intval', array_column($st->fetchAll(), 'asignatura_id'));
}

// Query
$params = [];
$sql = "SELECT a.id, a.titulo, a.tipo, a.visibilidad, a.estado, a.updated_at,
               a.profesor_id,  /* necesario para saber si la actividad es mía */
               asig.nombre AS asignatura, c.nombre AS curso, f.nombre AS familia
        FROM actividades a
        JOIN asignaturas asig ON asig.id = a.asignatura_id
        JOIN cursos c ON c.id = a.curso_id
        JOIN familias_profesionales f ON f.id = a.familia_id";

$where = [];

if ($role === 'admin') {
  // Admin ve todo (solo lectura)
} else {
  if ($misAsignaturas) {
    $in = implode(',', array_fill(0, count($misAsignaturas), '?'));
    $where[] = "(a.profesor_id = ? OR (a.visibilidad = 'publica' AND a.asignatura_id IN ($in)))";
    $params[] = $profesorId;
    $params = array_merge($params, $misAsignaturas);
  } else {
    // Si no tiene asignaturas vinculadas, solo ve lo suyo
    $where[] = "a.profesor_id = ?";
    $params[] = $profesorId;
  }
}

// Filtros adicionales
if ($q !== '') {
  $where[] = "(a.titulo LIKE ? OR a.descripcion LIKE ?)";
  $params[] = "%$q%";
  $params[] = "%$q%";
}
if ($fam > 0) {
  $where[] = "a.familia_id = ?";
  $params[] = $fam;
}
if ($cur > 0) {
  $where[] = "a.curso_id = ?";
  $params[] = $cur;
}
if ($asi > 0) {
  $where[] = "a.asignatura_id = ?";
  $params[] = $asi;
}

if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY a.updated_at DESC, a.id DESC LIMIT 200";

$stList = pdo()->prepare($sql);
$stList->execute($params);
$rows = $stList->fetchAll();

require_once __DIR__ . '/../../../partials/header.php';
?>

<div class="mb-6 flex items-center justify-between">
  <div>
    <h1 class="text-xl font-semibold tracking-tight">Actividades</h1>
  </div>
  <?php if (($u['role'] ?? '') === 'profesor'): ?>
    <a href="<?= PUBLIC_URL ?>/admin/actividades/create.php"
       class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 active:scale-[0.99] transition">
      + Nueva actividad
    </a>
  <?php endif; ?>
</div>

<!-- Barra de filtros -->
<form method="get" action="" class="mb-4 flex flex-wrap items-center gap-3">
  <input name="q" value="<?= h($q) ?>" placeholder="Buscar por título/asignatura/curso/familia"
         class="w-64 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">

  <select id="familia_id" name="familia_id"
          class="w-56 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
    <option value="0">Todas las familias</option>
    <?php foreach ($familias as $f): ?>
      <option value="<?= (int)$f['id'] ?>" <?= $fam === (int)$f['id'] ? 'selected' : '' ?>><?= h($f['nombre']) ?></option>
    <?php endforeach; ?>
  </select>

  <select id="curso_id" name="curso_id"
          class="w-56 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
    <option value="0">Todos los cursos</option>
  </select>

  <select id="asignatura_id" name="asignatura_id"
          class="w-56 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
    <option value="0">Todas las asignaturas</option>
  </select>

  <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
    Buscar
  </button>
</form>

<!-- Tabla idéntica al resto -->
<div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
  <table class="min-w-full divide-y divide-slate-200">
    <thead class="bg-slate-50">
      <tr>
        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Título</th>
        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Tipo</th>
        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Familia</th>
        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Curso</th>
        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Asignatura</th>
        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Visibilidad</th>
        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Estado</th>
        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Acciones</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-200 bg-white">
      <?php foreach ($rows as $r): ?>
        <?php $esMia = ($role === 'profesor' && (int)$r['profesor_id'] === $profesorId); ?>
        <tr>
          <td class="px-3 py-2 text-sm font-medium text-slate-800"><?= h($r['titulo']) ?></td>
          <td class="px-3 py-2 text-sm"><?= h($r['tipo']) ?></td>
          <td class="px-3 py-2 text-sm"><?= h($r['familia']) ?></td>
          <td class="px-3 py-2 text-sm"><?= h($r['curso']) ?></td>
          <td class="px-3 py-2 text-sm"><?= h($r['asignatura']) ?></td>
          <td class="px-3 py-2 text-sm"><?= h($r['visibilidad']) ?></td>
          <td class="px-3 py-2 text-sm"><?= h($r['estado']) ?></td>
          <td class="px-3 py-2 text-right">
            <?php if ($esMia): ?>
              <div class="inline-flex items-center gap-2">
                <a href="<?= PUBLIC_URL ?>/admin/actividades/edit.php?id=<?= (int)$r['id'] ?>"
                   class="rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100">
                  Editar
                </a>
                <form method="post" action="<?= PUBLIC_URL ?>/admin/actividades/delete.php"
                      onsubmit="return confirm('¿Eliminar actividad?')"
                      class="inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="rounded-md border border-rose-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                    Eliminar
                  </button>
                </form>
              </div>
            <?php elseif ($role === 'profesor'): ?>
              <span class="text-xs text-slate-400">Sin permisos</span>
            <?php else: ?>
              <span class="text-xs text-slate-400">Sin acciones</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>

      <?php if (!$rows): ?>
        <tr>
          <td colspan="8" class="px-3 py-6 text-center text-sm text-slate-500">
            No hay actividades que cumplan los filtros.
          </td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
  // Selects en cascada
  const cursosAll = <?= json_encode($cursosAll, JSON_UNESCAPED_UNICODE) ?>;
  const asigsAll  = <?= json_encode($asigsAll,  JSON_UNESCAPED_UNICODE) ?>;

  const selFam = document.getElementById('familia_id');
  const selCur = document.getElementById('curso_id');
  const selAsi = document.getElementById('asignatura_id');

  const currentFam = <?= (int)$fam ?>;
  const currentCur = <?= (int)$cur ?>;
  const currentAsi = <?= (int)$asi ?>;

  function opt(v, t) {
    const o = document.createElement('option');
    o.value = v;
    o.textContent = t;
    return o;
  }

  function renderCursos(fid, selected = 0) {
    const all = cursosAll.filter(c => parseInt(c.familia_id, 10) === parseInt(fid, 10));
    selCur.innerHTML = '';
    selCur.appendChild(opt(0, 'Todos los cursos'));
    all.forEach(c => {
      const o = opt(c.id, c.nombre);
      if (parseInt(selected, 10) === parseInt(c.id, 10)) o.selected = true;
      selCur.appendChild(o);
    });
  }

  function renderAsigs(cid, selected = 0) {
    const all = asigsAll.filter(a => parseInt(a.curso_id, 10) === parseInt(cid, 10));
    selAsi.innerHTML = '';
    selAsi.appendChild(opt(0, 'Todas las asignaturas'));
    all.forEach(a => {
      const o = opt(a.id, a.nombre);
      if (parseInt(selected, 10) === parseInt(a.id, 10)) o.selected = true;
      selAsi.appendChild(o);
    });
  }

  if (currentFam > 0) {
    renderCursos(currentFam, currentCur);
    if (currentCur > 0) renderAsigs(currentCur, currentAsi);
  } else {
    selCur.innerHTML = '';
    selCur.appendChild(opt(0, 'Todos los cursos'));
    selAsi.innerHTML = '';
    selAsi.appendChild(opt(0, 'Todas las asignaturas'));
  }

  selFam.addEventListener('change', () => {
    const fid = parseInt(selFam.value || '0', 10);
    renderCursos(fid, 0);
    selAsi.innerHTML = '';
    selAsi.appendChild(opt(0, 'Todas las asignaturas'));
  }, { passive: true });

  selCur.addEventListener('change', () => {
    const cid = parseInt(selCur.value || '0', 10);
    if (cid > 0) {
      renderAsigs(cid, 0);
    } else {
      selAsi.innerHTML = '';
      selAsi.appendChild(opt(0, 'Todas las asignaturas'));
    }
  }, { passive: true });
</script>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>
