<?php
// /public/dashboard.php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_login_or_redirect();

$u = current_user();
require_once __DIR__ . '/../partials/header.php';


/**
 * Si el usuario no es admin, garantizamos que tenga profesor_id:
 * - Intentamos localizar su ficha por email.
 * - Si no existe, la creamos mínima y guardamos profesor_id en sesión.
 */
$profesorId = 0;
if (($u['role'] ?? '') !== 'admin') {
  $profesorId = (int) ($u['profesor_id'] ?? 0);

  if ($profesorId <= 0) {
    $email = trim((string) ($u['email'] ?? ''));
    if ($email !== '') {
      $stFind = pdo()->prepare('SELECT id FROM profesores WHERE email = :e LIMIT 1');
      $stFind->execute([':e' => $email]);
      if ($row = $stFind->fetch()) {
        $profesorId = (int) $row['id'];
        $_SESSION['user']['profesor_id'] = $profesorId;
      }
    }
    if ($profesorId <= 0) {
      $ins = pdo()->prepare('
        INSERT INTO profesores (nombre, apellidos, email, is_active, created_at, updated_at)
        VALUES (:n, :a, :e, 1, NOW(), NOW())
      ');
      $nombre = trim((string) ($u['nombre'] ?? 'Profesor'));
      $apellidos = '';
      $emailDb = ($email !== '' ? $email : null);
      $ins->execute([':n' => $nombre, ':a' => $apellidos, ':e' => $emailDb]);

      $profesorId = (int) pdo()->lastInsertId();
      $_SESSION['user']['profesor_id'] = $profesorId;
      flash('success', 'Se ha inicializado tu ficha de profesor.');
    }
  }
} else {
  // Admin: usa su propio profesor_id si lo tiene (opcional)
  $profesorId = (int) ($u['profesor_id'] ?? 0);
}

// Aviso suave si no logramos profesor_id
if ($profesorId <= 0) {
  flash('error', 'No ha sido posible identificar tu perfil de profesor.');
}

// Datos de referencia
$centros = pdo()->query('SELECT id, nombre FROM centros WHERE is_active=1 ORDER BY nombre ASC')->fetchAll();
$fams = pdo()->query('SELECT id, nombre FROM familias_profesionales WHERE is_active=1 ORDER BY nombre ASC')->fetchAll();
$cursos = pdo()->query('SELECT id, nombre, familia_id, orden FROM cursos WHERE is_active=1 ORDER BY familia_id ASC, orden ASC, nombre ASC')->fetchAll();
$asigs = pdo()->query('SELECT id, nombre, curso_id, familia_id, orden FROM asignaturas WHERE is_active=1 ORDER BY familia_id ASC, curso_id ASC, orden ASC, nombre ASC')->fetchAll();

// Profesor
$prof = null;
if ($profesorId > 0) {
  $st = pdo()->prepare('SELECT * FROM profesores WHERE id=:id LIMIT 1');
  $st->execute([':id' => $profesorId]);
  $prof = $st->fetch();
}

// Asignaciones
$asigRows = [];
if ($profesorId > 0) {
  $asignaciones = pdo()->prepare('
    SELECT pa.id, pa.familia_id, pa.curso_id, pa.asignatura_id, pa.anio_academico, pa.horas, pa.observaciones, pa.is_active,
           f.nombre AS familia, c.nombre AS curso, a.nombre AS asignatura
    FROM profesor_asignacion pa
    JOIN familias_profesionales f ON f.id = pa.familia_id
    JOIN cursos c ON c.id = pa.curso_id
    JOIN asignaturas a ON a.id = pa.asignatura_id
    WHERE pa.profesor_id = :p
    ORDER BY f.nombre ASC, c.orden ASC, a.orden ASC, a.nombre ASC
  ');
  $asignaciones->execute([':p' => $profesorId]);
  $asigRows = $asignaciones->fetchAll();
}

// POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (function_exists('csrf_check')) {
      csrf_check($_POST['csrf'] ?? null);
    }

    $service = new ProfesorService(pdo());

    $centro_id = ($_POST['centro_id'] ?? '') !== '' ? (int) $_POST['centro_id'] : null;
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $notas = trim($_POST['notas'] ?? '');
    $activo = isset($_POST['is_active']) ? 1 : 0;

    if ($profesorId <= 0)
      throw new RuntimeException('Tu ficha de profesor no está inicializada.');

    // 1. Update Datos Básicos
    $service->update($profesorId, [
      'centro_id' => $centro_id,
      'nombre' => $nombre,
      'apellidos' => $apellidos,
      'email' => $email,
      'telefono' => $telefono,
      'notas' => $notas,
      'is_active' => $activo
    ]);

    // 2. Update Asignaciones
    // Preparamos mapas (mappings)
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
      'ids' => $_POST['pa_id'] ?? [],
      'delete' => $_POST['asig_delete'] ?? [],
    ];

    $service->saveAssignments($profesorId, $centro_id, $inputs, $mappings);

    flash('success', 'Perfil actualizado (Vía Service).');
    header('Location: ' . PUBLIC_URL . '/mi-perfil.php');
    exit;

  } catch (Throwable $e) {
    $msg = $e->getMessage();
    if (stripos($msg, 'uq_pa') !== false)
      $msg = 'Asignación duplicada en el mismo año.';
    flash('error', $msg);
    header('Location: ' . PUBLIC_URL . '/mi-perfil.php');
    exit;
  }
}
?>

<div class="mb-6 flex items-center justify-between">
  <div>
    <h1 class="text-xl font-semibold tracking-tight">Mi Perfil</h1>
    <p class="mt-1 text-sm text-slate-600">Actualiza tus datos y asignaciones.</p>
  </div>
</div>

<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
  <form method="post" action="" class="space-y-6" id="profForm">
    <?php if (function_exists('csrf_field')): ?>
      <?= csrf_field() ?>
    <?php endif; ?>

    <div class="grid gap-4 sm:grid-cols-3">
      <div class="sm:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Nombre</label>
        <input name="nombre" type="text" required value="<?= h($prof['nombre'] ?? '') ?>"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Apellidos</label>
        <input name="apellidos" type="text" required value="<?= h($prof['apellidos'] ?? '') ?>"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
        <input name="email" type="email" value="<?= h((string) ($prof['email'] ?? '')) ?>"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Teléfono</label>
        <input name="telefono" type="text" value="<?= h((string) ($prof['telefono'] ?? '')) ?>"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Centro</label>
        <select name="centro_id"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
          <option value="">— Selecciona centro —</option>
          <?php foreach ($centros as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= ((int) ($prof['centro_id'] ?? 0) === (int) $c['id']) ? 'selected' : '' ?>>
              <?= h($c['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="mt-1 text-xs text-slate-500">Se aplicará a las nuevas asignaciones.</p>
      </div>
    </div>

    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700">Notas</label>
      <textarea name="notas" rows="3"
        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= h((string) ($prof['notas'] ?? '')) ?></textarea>
    </div>

    <div class="flex items-center gap-2">
      <input id="is_active" name="is_active" type="checkbox"
        class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400" <?= ((int) ($prof['is_active'] ?? 1) === 1 ? 'checked' : '') ?>>
      <label for="is_active" class="text-sm text-slate-700">Activo</label>
    </div>

    <!-- Asignaciones -->
    <div class="pt-2">
      <div class="mb-3 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-700">Mis asignaciones</h2>
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
            <?php foreach ($asigRows as $idx => $r): ?>
              <tr>
                <td class="px-3 py-2">
                  <input type="hidden" name="pa_id[]" value="<?= (int) $r['id'] ?>">
                  <select name="asig_familia_id[]"
                    class="famSel w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:ring-2 focus:ring-slate-400">
                    <option value="">— Familia/Grado —</option>
                    <?php foreach ($fams as $f): ?>
                      <option value="<?= (int) $f['id'] ?>" <?= (int) $r['familia_id'] === (int) $f['id'] ? 'selected' : '' ?>>
                        <?= h($f['nombre']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td class="px-3 py-2">
                  <select name="asig_curso_id[]"
                    class="cursoSel w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:ring-2 focus:ring-slate-400"></select>
                </td>
                <td class="px-3 py-2">
                  <select name="asig_asignatura_id[]"
                    class="asigSel w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:ring-2 focus:ring-slate-400"></select>
                </td>
                <td class="px-3 py-2">
                  <input name="asig_anio[]" type="text" value="<?= h($r['anio_academico']) ?>" placeholder="2025-2026"
                    required
                    class="w-28 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:ring-2 focus:ring-slate-400">
                </td>
                <td class="px-3 py-2">
                  <input name="asig_horas[]" type="number" min="0" value="<?= h((string) $r['horas']) ?>"
                    class="w-20 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:ring-2 focus:ring-slate-400">
                </td>
                <td class="px-3 py-2">
                  <input name="asig_obs[]" type="text" value="<?= h((string) $r['observaciones']) ?>"
                    class="w-48 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:ring-2 focus:ring-slate-400"
                    placeholder="Notas">
                </td>
                <td class="px-3 py-2 text-right">
                  <label class="inline-flex items-center gap-1 text-xs text-rose-700">
                    <input type="checkbox" name="asig_delete[]" value="<?= $idx ?>"
                      class="h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-400">
                    Quitar
                  </label>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <p class="mt-2 text-xs text-slate-500">Los select se filtran en cascada. Marca “Quitar” para eliminar una
        asignación existente.</p>
    </div>

    <div class="flex justify-end">
      <button type="submit"
        class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
        Guardar cambios
      </button>
    </div>
  </form>
</div>

<!-- Shared JS -->
<script src="<?= h(PUBLIC_URL) ?>/assets/js/assignment-manager.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    new AssignmentManager({
      containerId: 'rowsBody',
      btnAddId: 'btnAddRow',
      familias: <?= json_encode($fams, JSON_UNESCAPED_UNICODE) ?>,
      cursos: <?= json_encode($cursos, JSON_UNESCAPED_UNICODE) ?>,
      asignaturas: <?= json_encode($asigs, JSON_UNESCAPED_UNICODE) ?>
    });
  });
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>