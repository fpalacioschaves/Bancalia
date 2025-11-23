<?php
// /public/dashboard.php
declare(strict_types=1);
require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();

$u = current_user();
require_once __DIR__ . '/../../../partials/header.php';

$role       = $u['role'] ?? '';
$profesorId = (int)($u['profesor_id'] ?? 0);
$centroId   = (int)($u['centro_id'] ?? 0);

// Filtros básicos
$q   = trim((string)($_GET['q'] ?? ''));
$fam = (int)($_GET['familia_id'] ?? 0);
$cur = (int)($_GET['curso_id'] ?? 0);
$asi = (int)($_GET['asignatura_id'] ?? 0);

// Filtros avanzados (tipo, dificultad, visibilidad, estado)
$tipo        = (string)($_GET['tipo'] ?? '');
$dificultad  = (string)($_GET['dificultad'] ?? '');
$visibilidad = (string)($_GET['visibilidad'] ?? '');
$estado      = (string)($_GET['estado'] ?? '');

// Filtro de orden
$orden = (string)($_GET['orden'] ?? 'fecha');
$ordenesPermitidos = ['fecha', 'popularidad', 'dificultad'];
if (!in_array($orden, $ordenesPermitidos, true)) {
  $orden = 'fecha';
}

// Listas de valores permitidos (para validar filtros)
$tipos = [
  'opcion_multiple',
  'verdadero_falso',
  'respuesta_corta',
  'rellenar_huecos',
  'emparejar',
  'tarea'
];

$labelsTipos = [
  'opcion_multiple'  => 'Opción múltiple',
  'verdadero_falso'  => 'Verdadero / Falso',
  'respuesta_corta'  => 'Respuesta corta',
  'rellenar_huecos'  => 'Rellenar huecos',
  'emparejar'        => 'Emparejar',
  'tarea'            => 'Tarea / entrega larga',
];

$dificultades = ['baja', 'media', 'alta'];
$labelsDificultad = [
  'baja'  => 'Baja',
  'media' => 'Media',
  'alta'  => 'Alta',
];

// AHORA: privada / centro / pública
$visibilidades = ['privada', 'centro', 'publica'];
$labelsVisibilidad = [
  'privada' => 'Privada',
  'centro'  => 'Centro',
  'publica' => 'Pública',
];

$estados = ['borrador', 'publicada'];
$labelsEstado = [
  'borrador'   => 'Borrador',
  'publicada'  => 'Publicada',
];

// Datos para selects de familia / curso / asignatura
$familias  = pdo()->query("SELECT id, nombre FROM familias_profesionales WHERE is_active=1 ORDER BY nombre ASC")->fetchAll();
$cursosAll = pdo()->query("SELECT id, nombre, familia_id FROM cursos WHERE is_active=1 ORDER BY familia_id ASC, orden ASC, nombre ASC")->fetchAll();
$asigsAll  = pdo()->query("SELECT id, nombre, curso_id FROM asignaturas WHERE is_active=1 ORDER BY curso_id ASC, orden ASC, nombre ASC")->fetchAll();

// Asignaturas del profesor (para limitar públicas a su ámbito)
$misAsignaturas = [];
if ($role !== 'admin' && $profesorId > 0) {
  $st = pdo()->prepare('SELECT DISTINCT asignatura_id FROM profesor_asignacion WHERE profesor_id=:p');
  $st->execute([':p' => $profesorId]);
  $misAsignaturas = array_map('intval', array_column($st->fetchAll(), 'asignatura_id'));
}

// Query base (incluye popularidad)
$params = [];
$sql = "SELECT a.id,
               a.titulo,
               a.tipo,
               a.visibilidad,
               a.estado,
               a.dificultad,
               a.updated_at,
               a.profesor_id,
               a.centro_id,
               asig.nombre AS asignatura,
               c.nombre    AS curso,
               f.nombre    AS familia,
               COALESCE(pop.popularidad, 0) AS popularidad
        FROM actividades a
        JOIN asignaturas asig ON asig.id = a.asignatura_id
        JOIN cursos c         ON c.id = a.curso_id
        JOIN familias_profesionales f ON f.id = a.familia_id
        LEFT JOIN (
          SELECT ea.actividad_id,
                 COUNT(DISTINCT e.profesor_id) AS popularidad
          FROM examenes_actividades ea
          JOIN examenes e ON e.id = ea.examen_id
          GROUP BY ea.actividad_id
        ) pop ON pop.actividad_id = a.id";

$where = [];

// Visibilidad según rol
if ($role === 'admin') {
  // Admin ve todo, sin restricciones
} else {
  if ($misAsignaturas) {
    // Con asignaturas vinculadas:
    // - Siempre ve sus actividades
    // - Ve públicas SOLO de sus asignaturas
    // - Ve actividades de centro SI coinciden centro_id
    $in = implode(',', array_fill(0, count($misAsignaturas), '?'));
    $where[] = "(
      a.profesor_id = ?
      OR (a.visibilidad = 'publica' AND a.asignatura_id IN ($in))
      OR (a.visibilidad = 'centro' AND a.centro_id = ?)
    )";
    $params[] = $profesorId;
    $params   = array_merge($params, $misAsignaturas);
    $params[] = $centroId;
  } else {
    // Sin asignaturas vinculadas:
    // - ve lo suyo
    // - ve todas públicas
    // - ve centro si coincide centro_id
    $where[] = "(
      a.profesor_id = ?
      OR a.visibilidad = 'publica'
      OR (a.visibilidad = 'centro' AND a.centro_id = ?)
    )";
    $params[] = $profesorId;
    $params[] = $centroId;
  }
}

// Filtro de texto
if ($q !== '') {
  $where[] = "(a.titulo LIKE ? OR a.descripcion LIKE ?)";
  $params[] = "%$q%";
  $params[] = "%$q%";
}

// Filtros por jerarquía académica
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

// Filtros avanzados (tipo, dificultad, visibilidad, estado)
if ($tipo !== '' && in_array($tipo, $tipos, true)) {
  $where[] = "a.tipo = ?";
  $params[] = $tipo;
}

if ($dificultad !== '' && in_array($dificultad, $dificultades, true)) {
  $where[] = "a.dificultad = ?";
  $params[] = $dificultad;
}

if ($visibilidad !== '' && in_array($visibilidad, $visibilidades, true)) {
  $where[] = "a.visibilidad = ?";
  $params[] = $visibilidad;
}

if ($estado !== '' && in_array($estado, $estados, true)) {
  $where[] = "a.estado = ?";
  $params[] = $estado;
}

if ($where) {
  $sql .= " WHERE " . implode(' AND ', $where);
}

// Orden
$orderSql = 'a.updated_at DESC, a.id DESC'; // fecha por defecto
if ($orden === 'popularidad') {
  $orderSql = 'popularidad DESC, a.updated_at DESC';
} elseif ($orden === 'dificultad') {
  $orderSql = 'a.dificultad ASC, a.updated_at DESC';
}

$sql .= " ORDER BY $orderSql LIMIT 200";

$stList = pdo()->prepare($sql);
$stList->execute($params);
$rows = $stList->fetchAll();

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
  <input
    name="q"
    value="<?= h($q) ?>"
    placeholder="Buscar por título / descripción"
    class="w-64 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
  >

  <select
    id="familia_id"
    name="familia_id"
    class="w-56 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus-border-slate-400"
  >
    <option value="0">Todas las familias</option>
    <?php foreach ($familias as $f): ?>
      <option value="<?= (int)$f['id'] ?>" <?= $fam === (int)$f['id'] ? 'selected' : '' ?>>
        <?= h($f['nombre']) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <select
    id="curso_id"
    name="curso_id"
    class="w-56 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus-border-slate-400"
  >
    <option value="0">Todos los cursos</option>
  </select>

  <select
    id="asignatura_id"
    name="asignatura_id"
    class="w-56 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus-border-slate-400"
  >
    <option value="0">Todas las asignaturas</option>
  </select>

  <!-- Filtros avanzados -->
  <select
    name="tipo"
    class="w-56 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus-border-slate-400"
  >
    <option value="">Todos los tipos</option>
    <?php foreach ($tipos as $t): ?>
      <option value="<?= h($t) ?>" <?= $tipo === $t ? 'selected' : '' ?>>
        <?= h($labelsTipos[$t] ?? $t) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <select
    name="dificultad"
    class="w-48 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus-border-slate-400"
  >
    <option value="">Todas las dificultades</option>
    <?php foreach ($dificultades as $d): ?>
      <option value="<?= h($d) ?>" <?= $dificultad === $d ? 'selected' : '' ?>>
        <?= h($labelsDificultad[$d] ?? $d) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <select
    name="visibilidad"
    class="w-48 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus-border-slate-400"
  >
    <option value="">Todas las visibilidades</option>
    <?php foreach ($visibilidades as $v): ?>
      <option value="<?= h($v) ?>" <?= $visibilidad === $v ? 'selected' : '' ?>>
        <?= h($labelsVisibilidad[$v] ?? $v) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <select
    name="estado"
    class="w-48 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus-border-slate-400"
  >
    <option value="">Todos los estados</option>
    <?php foreach ($estados as $e): ?>
      <option value="<?= h($e) ?>" <?= $estado === $e ? 'selected' : '' ?>>
        <?= h($labelsEstado[$e] ?? $e) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <!-- Ordenar por -->
  <select
    name="orden"
    class="w-52 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus-border-slate-400"
  >
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
  <table class="min-w-full divide-y divide-slate-200">
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
          <span
            class="ml-1 text-[10px] text-slate-400 align-middle"
            title="Número de profesores distintos que han incluido esta actividad en algún examen"
          >
          </span>
        </th>
        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Acciones</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-200 bg-white">
      <?php foreach ($rows as $r): ?>
        <?php $esMia = ($role === 'profesor' && (int)$r['profesor_id'] === $profesorId); ?>
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
              <span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                🌍 Pública
              </span>
            <?php elseif ($r['visibilidad'] === 'centro'): ?>
              <span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700">
                🏫 Centro
              </span>
            <?php else: ?>
              <span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">
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
              <?= (int)$r['popularidad'] ?> prof.
            </span>
          </td>

          <!-- ACCIONES -->
          <td class="px-3 py-2 text-right">
            <?php if ($role === 'profesor'): ?>
              <?php if ($esMia): ?>
                <div class="inline-flex items-center gap-2">
                  <a
                    href="<?= PUBLIC_URL ?>/admin/actividades/edit.php?id=<?= (int)$r['id'] ?>"
                    class="rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100"
                  >
                    Editar
                  </a>

                  <form
                    method="post"
                    action="<?= PUBLIC_URL ?>/admin/actividades/duplicate.php"
                    class="inline"
                    onsubmit="return confirm('¿Duplicar esta actividad?');"
                  >
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button
                      class="rounded-md border border-indigo-300 bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100"
                    >
                      Duplicar
                    </button>
                  </form>

                  <form
                    method="post"
                    action="<?= PUBLIC_URL ?>/admin/actividades/delete.php"
                    onsubmit="return confirm('¿Eliminar actividad?')"
                    class="inline"
                  >
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button
                      class="rounded-md border border-rose-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50"
                    >
                      Eliminar
                    </button>
                  </form>
                </div>
              <?php elseif ($r['visibilidad'] === 'publica' || $r['visibilidad'] === 'centro'): ?>
                <!-- Actividad visible de otro profe: se puede duplicar -->
                <form
                  method="post"
                  action="<?= PUBLIC_URL ?>/admin/actividades/duplicate.php"
                  class="inline"
                  onsubmit="return confirm('¿Duplicar esta actividad en tu banco privado?');"
                >
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button
                    class="rounded-md border border-indigo-300 bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100"
                  >
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

      <?php if (!$rows): ?>
        <tr>
          <td colspan="10" class="px-3 py-6 text-center text-sm text-slate-500">
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
