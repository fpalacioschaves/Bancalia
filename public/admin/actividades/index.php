<?php
// /public/dashboard.php
declare(strict_types=1);
require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();

$u = current_user();
require_once __DIR__ . '/../../../partials/header.php';

$role = $u['role'] ?? '';
$profesorId = (int) ($u['profesor_id'] ?? 0);
$centroId = (int) ($u['centro_id'] ?? 0);

// Filtros y orden
$q = trim((string) ($_GET['q'] ?? ''));
$fam = (int) ($_GET['familia_id'] ?? 0);
$cur = (int) ($_GET['curso_id'] ?? 0);
$asi = (int) ($_GET['asignatura_id'] ?? 0);
$tipo = (string) ($_GET['tipo'] ?? '');
$dificultad = (string) ($_GET['dificultad'] ?? '');
$visibilidad = (string) ($_GET['visibilidad'] ?? '');
$estado = (string) ($_GET['estado'] ?? '');
$orden = (string) ($_GET['orden'] ?? 'fecha');

$actividadService = new ActividadService(pdo());
$rows = $actividadService->findAll([
  'q' => $q,
  'familia_id' => $fam,
  'curso_id' => $cur,
  'asignatura_id' => $asi,
  'tipo' => $tipo,
  'dificultad' => $dificultad,
  'visibilidad' => $visibilidad,
  'estado' => $estado,
  'orden' => $orden
], $u);

// Meta data para UI
$labelsTipos = ActividadService::getTipos();
$labelsDificultad = ActividadService::getDificultades();
$labelsVisibilidad = ActividadService::getVisibilidades();
$labelsEstado = ActividadService::getEstados();

$familias = pdo()->query("SELECT id, nombre FROM familias_profesionales WHERE is_active=1 ORDER BY nombre ASC")->fetchAll();
$cursosAll = pdo()->query("SELECT id, nombre, familia_id FROM cursos WHERE is_active=1 ORDER BY familia_id ASC, orden ASC, nombre ASC")->fetchAll();
$asigsAll = pdo()->query("SELECT id, nombre, curso_id FROM asignaturas WHERE is_active=1 ORDER BY curso_id ASC, orden ASC, nombre ASC")->fetchAll();

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
  <input name="q" value="<?= h($q) ?>" placeholder="Buscar por título / descripción"
    class="w-64 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">

  <select id="familia_id" name="familia_id"
    class="w-56 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus-border-slate-400">
    <option value="0">Todas las familias</option>
    <?php foreach ($familias as $f): ?>
      <option value="<?= (int) $f['id'] ?>" <?= $fam === (int) $f['id'] ? 'selected' : '' ?>>
        <?= h($f['nombre']) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <select id="curso_id" name="curso_id"
    class="w-56 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus-border-slate-400">
    <option value="0">Todos los cursos</option>
  </select>

  <select id="asignatura_id" name="asignatura_id"
    class="w-56 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus-border-slate-400">
    <option value="0">Todas las asignaturas</option>
  </select>

  <!-- Filtros avanzados -->
  <select name="tipo"
    class="w-56 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus-border-slate-400">
    <option value="">Todos los tipos</option>
    <?php foreach ($labelsTipos as $k => $v): ?>
      <option value="<?= h($k) ?>" <?= $tipo === $k ? 'selected' : '' ?>>
        <?= h($v) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <select name="dificultad"
    class="w-48 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus-border-slate-400">
    <option value="">Todas las dificultades</option>
    <?php foreach ($labelsDificultad as $k => $v): ?>
      <option value="<?= h($k) ?>" <?= $dificultad === $k ? 'selected' : '' ?>>
        <?= h($v) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <select name="visibilidad"
    class="w-48 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus-border-slate-400">
    <option value="">Todas las visibilidades</option>
    <?php foreach ($labelsVisibilidad as $k => $v): ?>
      <option value="<?= h($k) ?>" <?= $visibilidad === $k ? 'selected' : '' ?>>
        <?= h($v) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <select name="estado"
    class="w-48 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus-border-slate-400">
    <option value="">Todos los estados</option>
    <?php foreach ($labelsEstado as $k => $v): ?>
      <option value="<?= h($k) ?>" <?= $estado === $k ? 'selected' : '' ?>>
        <?= h($v) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <!-- Ordenar por -->
  <select name="orden"
    class="w-52 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus-border-slate-400">
    <option value="fecha" <?= $orden === 'fecha' ? 'selected' : '' ?>>Ordenar por fecha</option>
    <option value="popularidad" <?= $orden === 'popularidad' ? 'selected' : '' ?>>Ordenar por popularidad</option>
    <option value="dificultad" <?= $orden === 'dificultad' ? 'selected' : '' ?>>Ordenar por dificultad</option>
  </select>

  <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
    Buscar
  </button>
</form>

<!-- Tabla de actividades -->
<div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
  <table id="actTable" class="min-w-full divide-y divide-slate-200">
    <thead class="bg-slate-50">
      <tr>
        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Título</th>
        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Tipo</th>
        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Dificultad</th>
        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Familia</th>
        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Curso</th>
        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Asignatura</th>
        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Visibilidad</th>
        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Estado</th>
        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">
          Popularidad
        </th>
        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Acciones</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-200 bg-white">
      <?php foreach ($rows as $r): ?>
        <?php $esMia = ($role === 'profesor' && (int) $r['profesor_id'] === $profesorId); ?>
        <tr>
          <td class="px-3 py-2 text-sm font-medium text-slate-800">
            <?= h($r['titulo']) ?>
          </td>
          <td class="px-3 py-2 text-sm">
            <?= h($labelsTipos[$r['tipo']] ?? $r['tipo']) ?>
          </td>
          <td class="px-3 py-2 text-sm">
            <?= h($labelsDificultad[$r['dificultad']] ?? $r['dificultad']) ?>
          </td>
          <td class="px-3 py-2 text-sm">
            <?= h($r['familia']) ?>
          </td>
          <td class="px-3 py-2 text-sm">
            <?= h($r['curso']) ?>
          </td>
          <td class="px-3 py-2 text-sm">
            <?= h($r['asignatura']) ?>
          </td>

          <!-- VISIBILIDAD: privada / centro / pública -->
          <td class="px-3 py-2 text-sm">
            <?php if ($r['visibilidad'] === 'publica'): ?>
              <span
                class="inline-flex items-center gap-1 whitespace-nowrap rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                🌍 Pública
              </span>
            <?php elseif ($r['visibilidad'] === 'centro'): ?>
              <span
                class="inline-flex items-center gap-1 whitespace-nowrap rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700">
                🏫 Centro
              </span>
            <?php else: ?>
              <span
                class="inline-flex items-center gap-1 whitespace-nowrap rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">
                🔒 Privada
              </span>
            <?php endif; ?>
          </td>

          <td class="px-3 py-2 text-sm">
            <?= h($labelsEstado[$r['estado']] ?? $r['estado']) ?>
          </td>

          <!-- POPULARIDAD CON TOOLTIP -->
          <td class="px-3 py-2 text-sm">
            <span title="Número de profesores distintos que han incluido esta actividad en algún examen">
              <?= (int) $r['popularidad'] ?> prof.
            </span>
          </td>

          <!-- ACCIONES -->
          <td class="px-3 py-2 text-right">
            <?php if ($role === 'profesor'): ?>
              <?php if ($esMia): ?>
                <div class="inline-flex items-center gap-2">
                  <a href="<?= PUBLIC_URL ?>/admin/actividades/edit.php?id=<?= (int) $r['id'] ?>"
                    class="rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100">
                    Editar
                  </a>

                  <form method="post" action="<?= PUBLIC_URL ?>/admin/actividades/duplicate.php" class="inline"
                    onsubmit="return confirm('¿Duplicar esta actividad?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                    <button
                      class="rounded-md border border-indigo-300 bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                      Duplicar
                    </button>
                  </form>

                  <form method="post" action="<?= PUBLIC_URL ?>/admin/actividades/delete.php"
                    onsubmit="return confirm('¿Eliminar actividad?')" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                    <button
                      class="rounded-md border border-rose-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                      Eliminar
                    </button>
                  </form>
                </div>
              <?php elseif ($r['visibilidad'] === 'publica' || $r['visibilidad'] === 'centro'): ?>
                <!-- Actividad visible de otro profe: se puede duplicar -->
                <form method="post" action="<?= PUBLIC_URL ?>/admin/actividades/duplicate.php" class="inline"
                  onsubmit="return confirm('¿Duplicar esta actividad en tu banco privado?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                  <button
                    class="rounded-md border border-indigo-300 bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                    Duplicar
                  </button>
                </form>
              <?php else: ?>
                <span class="text-xs text-slate-400">Sin permisos</span>
              <?php endif; ?>
            <?php else: ?>
              <span class="text-xs text-slate-400">Sin acciones</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script src="<?= h(PUBLIC_URL) ?>/assets/js/table-config.js"></script>
<script>
  // Selects en cascada
  const cursosAll = <?= json_encode($cursosAll, JSON_UNESCAPED_UNICODE) ?>;
  const asigsAll = <?= json_encode($asigsAll, JSON_UNESCAPED_UNICODE) ?>;

  const selFam = document.getElementById('familia_id');
  const selCur = document.getElementById('curso_id');
  const selAsi = document.getElementById('asignatura_id');

  const currentFam = <?= (int) $fam ?>;
  const currentCur = <?= (int) $cur ?>;
  const currentAsi = <?= (int) $asi ?>;

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

  // Datatable init
  document.addEventListener('DOMContentLoaded', () => {
    TableManager.init('#actTable', {
      columns: [
        { select: 9, sortable: false }
      ]
    });
  });
</script>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>