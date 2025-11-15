<?php
// /public/admin/examenes/actividades.php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();
$u = current_user();

// ID del examen
$examenId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($examenId <= 0) {
  http_response_code(400);
  exit('ID de examen inválido.');
}

// Cargar examen con contexto
$stEx = pdo()->prepare('
  SELECT e.*, 
         p.nombre     AS profesor_nombre,
         p.apellidos  AS profesor_apellidos,
         a.nombre     AS asignatura_nombre,
         c.nombre     AS curso_nombre,
         f.nombre     AS familia_nombre
  FROM examenes e
  LEFT JOIN profesores p              ON p.id = e.profesor_id
  LEFT JOIN asignaturas a            ON a.id = e.asignatura_id
  LEFT JOIN cursos c                 ON c.id = e.curso_id
  LEFT JOIN familias_profesionales f ON f.id = e.familia_id
  WHERE e.id = :id
  LIMIT 1
');
$stEx->execute([':id' => $examenId]);
$examen = $stEx->fetch(PDO::FETCH_ASSOC);

if (!$examen) {
  http_response_code(404);
  exit('Examen no encontrado.');
}

// Actividades ya asociadas al examen
$stEa = pdo()->prepare('
  SELECT actividad_id, orden, puntuacion
  FROM examenes_actividades
  WHERE examen_id = :id
  ORDER BY orden ASC, id ASC
');
$stEa->execute([':id' => $examenId]);
$eaRows = $stEa->fetchAll(PDO::FETCH_ASSOC);

$mapEA = [];
foreach ($eaRows as $r) {
  $mapEA[(int)$r['actividad_id']] = [
    'orden'      => (int)$r['orden'],
    'puntuacion' => $r['puntuacion'],
  ];
}

// --- Filtros para lista de actividades ---
$q = trim($_GET['q'] ?? '');

$whereActs  = [];
$paramsActs = [];

/*
  IMPORTANTE:
  - Antes usábamos (int)$u['id'], que es el ID de USUARIO.
  - Las actividades usan profesor_id (tabla profesores / id de profesor).
  - Aquí tiene sentido filtrar por el profesor del EXAMEN, no por el usuario logado.
*/

// SI EL USUARIO ES PROFESOR → solo ve actividades del profesor de ESTE examen
if (($u['role'] ?? '') === 'profesor') {
  $profesorIdExamen = (int)($examen['profesor_id'] ?? 0);
  if ($profesorIdExamen > 0) {
    $whereActs[]               = 'a.profesor_id = :profesor_id';
    $paramsActs[':profesor_id'] = $profesorIdExamen;
  }
}

// Búsqueda por texto
if ($q !== '') {
  $whereActs[]      = 'a.titulo LIKE :q';
  $paramsActs[':q'] = "%{$q}%";
}

// SELECT real según tu BD de actividades
$sqlActs = "
  SELECT 
    a.id,
    a.titulo,
    a.tipo,
    a.dificultad,
    a.visibilidad,
    a.estado,
    a.created_at
  FROM actividades a
";

if ($whereActs) {
  $sqlActs .= ' WHERE ' . implode(' AND ', $whereActs);
}

$sqlActs .= ' ORDER BY a.created_at DESC';

$stActs = pdo()->prepare($sqlActs);
$stActs->execute($paramsActs);
$actividades = $stActs->fetchAll(PDO::FETCH_ASSOC);

// PROCESAR GUARDADO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $posted = $_POST['actividades'] ?? [];

    pdo()->beginTransaction();

    // Borrar asociaciones anteriores
    $del = pdo()->prepare('DELETE FROM examenes_actividades WHERE examen_id = :id');
    $del->execute([':id' => $examenId]);

    // Insertar nuevas
    $ins = pdo()->prepare('
      INSERT INTO examenes_actividades (examen_id, actividad_id, orden, puntuacion)
      VALUES (:examen_id, :actividad_id, :orden, :puntuacion)
    ');

    foreach ($posted as $actId => $data) {
      $actId    = (int)$actId;
      $selected = isset($data['selected']) ? (int)$data['selected'] : 0;

      if ($actId <= 0 || $selected !== 1) {
        continue;
      }

      $orden = isset($data['orden']) ? (int)$data['orden'] : 1;
      if ($orden <= 0) $orden = 1;

      $puntuacion = isset($data['puntuacion']) && $data['puntuacion'] !== ''
        ? (float)$data['puntuacion']
        : null;

      $ins->execute([
        ':examen_id'    => $examenId,
        ':actividad_id' => $actId,
        ':orden'        => $orden,
        ':puntuacion'   => $puntuacion,
      ]);
    }

    pdo()->commit();

    flash('success', 'Actividades del examen actualizadas correctamente.');
    header('Location: ' . PUBLIC_URL . '/admin/examenes/actividades.php?id=' . $examenId);
    exit;

  } catch (Throwable $e) {
    if (pdo()->inTransaction()) pdo()->rollBack();
    flash('error', $e->getMessage());
    header('Location: ' . PUBLIC_URL . '/admin/examenes/actividades.php?id=' . $examenId);
    exit;
  }
}

require_once __DIR__ . '/../../../partials/header.php';
?>

<div class="mb-6 flex items-center justify-between">
  <div>
    <h1 class="text-xl font-semibold tracking-tight">Actividades del examen</h1>
    <p class="mt-1 text-sm text-slate-600">
      Asocia actividades al examen <strong><?= htmlspecialchars($examen['titulo']) ?></strong>.
    </p>
    <p class="mt-1 text-xs text-slate-500">
      Profesor: <?= htmlspecialchars(($examen['profesor_apellidos'] ?? '') . ' ' . ($examen['profesor_nombre'] ?? '')) ?><br>
      Familia: <?= htmlspecialchars($examen['familia_nombre'] ?? '') ?> ·
      Curso: <?= htmlspecialchars($examen['curso_nombre'] ?? '') ?> ·
      Asignatura: <?= htmlspecialchars($examen['asignatura_nombre'] ?? '') ?>
    </p>
  </div>

  <a href="<?= PUBLIC_URL ?>/admin/examenes/index.php"
     class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
    Volver a exámenes
  </a>
</div>

<!-- BUSCADOR -->
<div class="mb-4">
  <form method="get" action="" class="flex flex-wrap items-center gap-3">
    <input type="hidden" name="id" value="<?= (int)$examenId ?>">
    <input
      type="search"
      name="q"
      value="<?= htmlspecialchars($q) ?>"
      placeholder="Buscar actividad por título"
      class="w-64 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm"
    >
    <button
      type="submit"
      class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800"
    >
      Buscar
    </button>
    <a
      href="<?= PUBLIC_URL ?>/admin/examenes/actividades.php?id=<?= (int)$examenId ?>"
      class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-100"
    >
      Limpiar
    </a>
  </form>
</div>

<?php if (!$actividades): ?>
  <div class="rounded-lg border border-slate-200 bg-white p-6 text-slate-600">
    No hay actividades disponibles.
  </div>

<?php else: ?>

  <form method="post" action="" class="space-y-4">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$examenId ?>">

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-3 py-2">Incluir</th>
            <th class="px-3 py-2">Título</th>
            <th class="px-3 py-2">Tipo</th>
            <th class="px-3 py-2">Dificultad</th>
            <th class="px-3 py-2">Visibilidad</th>
            <th class="px-3 py-2">Estado</th>
            <th class="px-3 py-2">Orden</th>
            <th class="px-3 py-2">Puntuación</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <?php
          $defaultOrden = 1;
          foreach ($actividades as $a):
            $actId = (int)$a['id'];
            $sel   = $mapEA[$actId] ?? null;
            $checked    = $sel !== null;
            $orden      = $sel['orden']      ?? $defaultOrden++;
            $puntuacion = $sel['puntuacion'] ?? '';
          ?>
            <tr class="hover:bg-slate-50">
              <td class="px-3 py-2">
                <input
                  type="checkbox"
                  name="actividades[<?= $actId ?>][selected]"
                  value="1"
                  <?= $checked ? 'checked' : '' ?>
                >
              </td>

              <td class="px-3 py-2">
                <strong><?= htmlspecialchars($a['titulo']) ?></strong>
                <div class="text-xs text-slate-500">ID: <?= $actId ?></div>
                <div class="text-xs text-slate-500">Creada: <?= htmlspecialchars($a['created_at']) ?></div>
              </td>

              <td class="px-3 py-2"><?= htmlspecialchars($a['tipo']) ?></td>

              <td class="px-3 py-2">
                <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">
                  <?= htmlspecialchars($a['dificultad']) ?>
                </span>
              </td>

              <td class="px-3 py-2">
                <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">
                  <?= htmlspecialchars($a['visibilidad']) ?>
                </span>
              </td>

              <td class="px-3 py-2">
                <?php if ($a['estado'] === 'publicada'): ?>
                  <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] text-emerald-700">Publicada</span>
                <?php else: ?>
                  <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-700">Borrador</span>
                <?php endif; ?>
              </td>

              <td class="px-3 py-2">
                <input
                  type="number"
                  name="actividades[<?= $actId ?>][orden]"
                  value="<?= (int)$orden ?>"
                  min="1"
                  class="w-20 rounded-lg border border-slate-300 px-2 py-1 text-xs"
                >
              </td>

              <td class="px-3 py-2">
                <input
                  type="number"
                  step="0.25"
                  min="0"
                  name="actividades[<?= $actId ?>][puntuacion]"
                  value="<?= htmlspecialchars((string)$puntuacion) ?>"
                  class="w-20 rounded-lg border border-slate-300 px-2 py-1 text-xs"
                >
              </td>
            </tr>

          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="flex justify-end gap-2">
      <a href="<?= PUBLIC_URL ?>/admin/examenes/index.php"
         class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm">
        Volver sin guardar
      </a>
      <button type="submit"
              class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
        Guardar actividades del examen
      </button>
    </div>
  </form>
<?php endif; ?>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>
