<?php
// /public/admin/profesores/create.php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();
$u = current_user();

if (($u['role'] ?? '') !== 'admin') {
  flash('error', 'Acceso restringido a administradores.');
  header('Location: ' . PUBLIC_URL . '/dashboard.php');
  exit;
}


//
// Datos para selects (cargamos todo para dependencias en cliente)
//
$centros = pdo()->query('SELECT id, nombre FROM centros WHERE is_active=1 ORDER BY nombre ASC')->fetchAll();

$fams = pdo()->query('SELECT id, nombre FROM familias_profesionales WHERE is_active=1 ORDER BY nombre ASC')->fetchAll();

$cursos = pdo()->query('SELECT id, nombre, familia_id, orden FROM cursos WHERE is_active=1 ORDER BY familia_id ASC, orden ASC, nombre ASC')->fetchAll();

$asigs = pdo()->query('SELECT id, nombre, curso_id, familia_id, orden FROM asignaturas WHERE is_active=1 ORDER BY familia_id ASC, curso_id ASC, orden ASC, nombre ASC')->fetchAll();

//
// POST
//
//
// POST
//
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $service = new ProfesorService(pdo());

    // Profesor
    $centro_id = ($_POST['centro_id'] ?? '') !== '' ? (int) $_POST['centro_id'] : null;
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $notas = trim($_POST['notas'] ?? '');
    $activo = isset($_POST['is_active']) ? 1 : 0;

    // 1. Crear Profesor
    $profesor_id = $service->create([
      'centro_id' => $centro_id,
      'nombre' => $nombre,
      'apellidos' => $apellidos,
      'email' => $email,
      'telefono' => $telefono,
      'notas' => $notas,
      'is_active' => $activo
    ]);

    // 2. Asignaciones
    // Mapa para validar coherencia
    $cursoToFamilia = [];
    foreach ($cursos as $c)
      $cursoToFamilia[(int) $c['id']] = (int) $c['familia_id'];
    $asigToCurso = [];
    foreach ($asigs as $a)
      $asigToCurso[(int) $a['id']] = (int) $a['curso_id'];

    $mappings = [
      'cursoToFamilia' => $cursoToFamilia,
      'asigToCurso' => $asigToCurso
    ];

    $inputs = [
      'familias' => $_POST['asig_familia_id'] ?? [],
      'cursos' => $_POST['asig_curso_id'] ?? [],
      'asignaturas' => $_POST['asig_asignatura_id'] ?? [],
      'anios' => $_POST['asig_anio'] ?? [],
      'horas' => $_POST['asig_horas'] ?? [],
      'obs' => $_POST['asig_obs'] ?? [],
      'delete' => [], // En create no borramos nada
      'ids' => [], // Insert nuevo
    ];

    $service->saveAssignments($profesor_id, $centro_id, $inputs, $mappings);

    flash('success', 'Profesor creado con asignaciones (Vía Service).');
    header('Location: ' . PUBLIC_URL . '/admin/profesores/index.php');
    exit;

  } catch (Throwable $e) {
    flash('error', $e->getMessage());
    header('Location: ' . PUBLIC_URL . '/admin/profesores/create.php');
    exit;
  }
}

require_once __DIR__ . '/../../../partials/header.php';
?>

<div class="mb-6 flex items-center justify-between">
  <div>
    <h1 class="text-xl font-semibold tracking-tight">Nuevo profesor</h1>
    <p class="mt-1 text-sm text-slate-600">Registra al profesor y añade sus asignaciones (familia → curso → asignatura).
    </p>
  </div>
  <a href="<?= PUBLIC_URL ?>/admin/profesores/index.php"
    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
    Volver al listado
  </a>
</div>

<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
  <form method="post" action="" class="space-y-6" id="profForm">
    <?= csrf_field() ?>

    <!-- Datos del profesor -->
    <div class="grid gap-4 sm:grid-cols-3">
      <div class="sm:col-span-2">
        <label for="nombre" class="mb-1 block text-sm font-medium text-slate-700">Nombre</label>
        <input id="nombre" name="nombre" type="text" required
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
      </div>
      <div>
        <label for="apellidos" class="mb-1 block text-sm font-medium text-slate-700">Apellidos</label>
        <input id="apellidos" name="apellidos" type="text" required
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <div>
        <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
        <input id="email" name="email" type="email" required
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
      </div>
      <div>
        <label for="telefono" class="mb-1 block text-sm font-medium text-slate-700">Teléfono</label>
        <input id="telefono" name="telefono" type="text"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
          placeholder="+34 600 000 000">
      </div>
      <div>
        <label for="centro_id" class="mb-1 block text-sm font-medium text-slate-700">Centro</label>
        <select id="centro_id" name="centro_id"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
          <option value="">— Selecciona centro —</option>
          <?php foreach ($centros as $c): ?>
            <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="mt-1 text-xs text-slate-500">Se usará como centro de las asignaciones creadas (puedes cambiar esto más
          adelante si quieres asignaciones por centro).</p>
      </div>
    </div>

    <div>
      <label for="notas" class="mb-1 block text-sm font-medium text-slate-700">Notas (opcional)</label>
      <textarea id="notas" name="notas" rows="3"
        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400"></textarea>
    </div>

    <div class="flex items-center gap-2">
      <input id="is_active" name="is_active" type="checkbox"
        class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400" checked>
      <label for="is_active" class="text-sm text-slate-700">Activo</label>
    </div>

    <!-- Asignaciones -->
    <div class="pt-2">
      <div class="mb-3 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-700">Asignaciones del profesor</h2>
        <button type="button" id="btnAddRow"
          class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-1.5 text-sm font-semibold text-white hover:bg-slate-800">
          + Añadir asignación
        </button>
      </div>

      <div class="overflow-hidden rounded-xl border border-slate-200">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">
                Familia/Grado</th>
              <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Curso</th>
              <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Asignatura
              </th>
              <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Año</th>
              <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Horas</th>
              <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Notas</th>
              <th class="px-3 py-2"></th>
            </tr>
          </thead>
          <tbody id="rowsBody" class="divide-y divide-slate-200 bg-white">
            <!-- filas dinámicas -->
          </tbody>
        </table>
      </div>

      <p class="mt-2 text-xs text-slate-500">
        Pista: puedes añadir varias filas. Los select se filtran en cascada (familia → curso → asignatura).
      </p>
    </div>

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
      <a href="<?= PUBLIC_URL ?>/admin/profesores/index.php"
        class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
        Cancelar
      </a>
      <button type="submit"
        class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 active:scale-[0.99] transition">
        Crear profesor
      </button>
    </div>
  </form>
</div>

<script>
  // Datos en cliente (para selects dependientes)
  const familias = <?php echo json_encode($fams, JSON_UNESCAPED_UNICODE); ?>;
  const cursosAll = <?php echo json_encode($cursos, JSON_UNESCAPED_UNICODE); ?>;
  const asigsAll = <?php echo json_encode($asigs, JSON_UNESCAPED_UNICODE); ?>;

  // Mapas: familia -> cursos, curso -> asignaturas
  const cursosByFam = {};
  cursosAll.forEach(c => {
    (cursosByFam[c.familia_id] ||= []).push({ id: c.id, nombre: c.nombre });
  });

  const asigByCurso = {};
  asigsAll.forEach(a => {
    (asigByCurso[a.curso_id] ||= []).push({ id: a.id, nombre: a.nombre });
  });

  const rowsBody = document.getElementById('rowsBody');
  const btnAddRow = document.getElementById('btnAddRow');

  function opt(val, label) { const o = document.createElement('option'); o.value = val; o.textContent = label; return o; }

  function renderCursosSelect(sel, familiaId) {
    sel.innerHTML = '';
    sel.appendChild(opt('', '— Curso —'));
    (cursosByFam[familiaId] || []).forEach(c => sel.appendChild(opt(c.id, c.nombre)));
  }

  function renderAsigSelect(sel, cursoId) {
    sel.innerHTML = '';
    sel.appendChild(opt('', '— Asignatura —'));
    (asigByCurso[cursoId] || []).forEach(a => sel.appendChild(opt(a.id, a.nombre)));
  }

  function addRow(prefill = {}) {
    const tr = document.createElement('tr');

    tr.innerHTML = `
      <td class="px-3 py-2">
        <select name="asig_familia_id[]" class="w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400"></select>
      </td>
      <td class="px-3 py-2">
        <select name="asig_curso_id[]" class="w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400"><option value="">— Curso —</option></select>
      </td>
      <td class="px-3 py-2">
        <select name="asig_asignatura_id[]" class="w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400"><option value="">— Asignatura —</option></select>
      </td>
      <td class="px-3 py-2">
        <input name="asig_anio[]" type="text" value="<?= (($m = (int) date('n')) >= 9 ? date('Y') . '-' . (date('Y') + 1) : (date('Y') - 1) . '-' . date('Y')) ?>"
               placeholder="2025-2026" required
               class="w-28 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
      </td>
      <td class="px-3 py-2">
        <input name="asig_horas[]" type="number" min="0"
               class="w-20 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
      </td>
      <td class="px-3 py-2">
        <input name="asig_obs[]" type="text"
               class="w-48 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400" placeholder="Notas">
      </td>
      <td class="px-3 py-2 text-right">
        <button type="button" class="inline-flex items-center rounded-lg bg-rose-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-rose-500 btnDel">Quitar</button>
      </td>
    `;

    const selFam = tr.querySelector('select[name="asig_familia_id[]"]');
    const selCurso = tr.querySelector('select[name="asig_curso_id[]"]');
    const selAsig = tr.querySelector('select[name="asig_asignatura_id[]"]');

    // Rellenar familias
    selFam.appendChild(opt('', '— Familia/Grado —'));
    familias.forEach(f => selFam.appendChild(opt(f.id, f.nombre)));

    // Prefills si vienen
    if (prefill.familia_id) {
      selFam.value = String(prefill.familia_id);
      renderCursosSelect(selCurso, prefill.familia_id);
    }
    if (prefill.curso_id) {
      if (!selCurso.options.length || !selCurso.value) renderCursosSelect(selCurso, prefill.familia_id);
      selCurso.value = String(prefill.curso_id);
      renderAsigSelect(selAsig, prefill.curso_id);
    }
    if (prefill.asignatura_id) {
      if (!selAsig.options.length || !selAsig.value) renderAsigSelect(selAsig, prefill.curso_id);
      selAsig.value = String(prefill.asignatura_id);
    }
    if (prefill.anio) tr.querySelector('input[name="asig_anio[]"]').value = prefill.anio;
    if (prefill.horas) tr.querySelector('input[name="asig_horas[]"]').value = prefill.horas;
    if (prefill.obs) tr.querySelector('input[name="asig_obs[]"]').value = prefill.obs;

    // Eventos cascada
    selFam.addEventListener('change', () => {
      const fid = parseInt(selFam.value || '0', 10);
      renderCursosSelect(selCurso, fid);
      renderAsigSelect(selAsig, 0);
    }, { passive: true });

    selCurso.addEventListener('change', () => {
      const cid = parseInt(selCurso.value || '0', 10);
      renderAsigSelect(selAsig, cid);
    }, { passive: true });

    // Eliminar fila
    tr.querySelector('.btnDel').addEventListener('click', () => tr.remove());

    rowsBody.appendChild(tr);
  }

  btnAddRow.addEventListener('click', () => addRow());
  // Empieza con una fila vacía
  addRow();
</script>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>