<?php
// /public/mis_pendientes.php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_login_or_redirect();

$u = current_user();
$role       = $u['role'] ?? '';
$profesorId = $u['profesor_id'] ?? null;

// Solo tiene sentido para profesor; si es admin, de momento mostramos info vacía
if ($role !== 'admin') {
  if (!$profesorId && !empty($u['email'])) {
    $st = pdo()->prepare('SELECT id FROM profesores WHERE email=:e LIMIT 1');
    $st->execute([':e' => $u['email']]);
    if ($row = $st->fetch()) {
      $profesorId = (int)$row['id'];
    }
  }
}

require_once __DIR__ . '/../partials/header.php';

if (!$profesorId) {
  ?>
  <div class="max-w-2xl mx-auto mt-10 rounded-xl border border-amber-200 bg-amber-50 px-6 py-5 text-sm text-amber-800">
    No se ha podido asociar tu usuario a un profesor en Bancalia.  
    Pide al administrador que vincule tu usuario con tu ficha de profesor.
  </div>
  <?php
  require_once __DIR__ . '/../partials/footer.php';
  exit;
}

// ==================== 1) LISTA DE EXÁMENES DEL PROFESOR ====================

$st = pdo()->prepare('
  SELECT
    e.id,
    e.titulo,
    e.fecha,
    e.hora,
    COUNT(ei.id) AS intentos_totales,
    SUM(CASE WHEN ei.corregido = 1 THEN 1 ELSE 0 END) AS intentos_corregidos,
    SUM(CASE WHEN ei.corregido IS NULL OR ei.corregido = 0 THEN 1 ELSE 0 END) AS intentos_pendientes
  FROM examenes e
  LEFT JOIN examen_intentos ei ON ei.examen_id = e.id
  WHERE e.profesor_id = :p
  GROUP BY e.id, e.titulo, e.fecha, e.hora
  ORDER BY e.fecha IS NULL ASC, e.fecha DESC, e.hora DESC, e.id DESC
');
$st->execute([':p' => $profesorId]);
$examenes = $st->fetchAll();

// ==================== 2) LISTA DE TAREAS CON RESPUESTAS SIN PUNTUACIÓN ====================

$st2 = pdo()->prepare('
  SELECT
    a.id          AS actividad_id,
    a.titulo      AS actividad_titulo,
    e.id          AS examen_id,
    e.titulo      AS examen_titulo,
    COUNT(er.id)  AS pendientes
  FROM examenes e
  JOIN examenes_actividades ea ON ea.examen_id = e.id
  JOIN actividades a            ON a.id = ea.actividad_id AND a.tipo = "tarea"
  JOIN examen_intentos ei       ON ei.examen_id = e.id
  JOIN examen_respuestas er     ON er.intento_id = ei.id AND er.actividad_id = a.id
  WHERE e.profesor_id = :p
    AND er.puntuacion IS NULL
  GROUP BY a.id, a.titulo, e.id, e.titulo
  HAVING pendientes > 0
  ORDER BY pendientes DESC, e.titulo ASC, a.titulo ASC
');
$st2->execute([':p' => $profesorId]);
$tareasPendientes = $st2->fetchAll();
?>

<div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
  <div>
    <h1 class="text-2xl font-semibold tracking-tight">Mis pendientes</h1>
    <p class="mt-1 text-sm text-slate-600">
      Un resumen de los exámenes e intentos que tienes por corregir, Paco.
    </p>
  </div>
  <a
    href="<?= PUBLIC_URL ?>/dashboard.php"
    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
  >
    Volver al panel
  </a>
</div>

<!-- ==================== BLOQUE EXÁMENES ==================== -->
<section id="examenes" class="mb-8">
  <div class="mb-3 flex items-center justify-between gap-2">
    <h2 class="text-lg font-semibold tracking-tight text-slate-900">
      Exámenes e intentos
    </h2>
    <p class="text-xs text-slate-500">
      Aquí ves, examen a examen, cuántos intentos tienes corregidos y cuántos siguen pendientes.
    </p>
  </div>

  <div class="rounded-xl border border-slate-200 bg-white overflow-hidden shadow-sm">
    <?php if (!$examenes): ?>
      <div class="px-4 py-6 text-sm text-slate-600">
        Todavía no tienes exámenes asociados a tu usuario.
      </div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Examen</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Fecha</th>
              <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-600">Intentos totales</th>
              <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-600">Corregidos</th>
              <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-600">Pendientes</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <?php foreach ($examenes as $ex): ?>
              <?php
                $tot   = (int)($ex['intentos_totales'] ?? 0);
                $corr  = (int)($ex['intentos_corregidos'] ?? 0);
                $pend  = (int)($ex['intentos_pendientes'] ?? 0);
                $fecha = $ex['fecha'] ? htmlspecialchars((string)$ex['fecha']) : '—';
                $hora  = $ex['hora']  ? htmlspecialchars(substr((string)$ex['hora'], 0, 5)) : '';
                $badgeClass = $pend > 0
                  ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'
                  : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
              ?>
              <tr class="hover:bg-slate-50">
                <td class="px-4 py-3">
                  <div class="text-sm font-medium text-slate-900">
                    <?= htmlspecialchars($ex['titulo'] ?? '') ?>
                  </div>
                </td>
                <td class="px-4 py-3 text-sm text-slate-700">
                  <?= $fecha ?><?= $hora ? ' · '.$hora : '' ?>
                </td>
                <td class="px-4 py-3 text-center text-sm text-slate-700">
                  <?= $tot ?>
                </td>
                <td class="px-4 py-3 text-center text-sm text-emerald-700">
                  <?= $corr ?>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?= $badgeClass ?>">
                    <?= $pend ?>
                  </span>
                </td>
                <td class="px-4 py-3 text-right">
                  <a
                    href="<?= PUBLIC_URL ?>/admin/examenes/intentos.php?examen_id=<?= (int)$ex['id'] ?>"
                    class="inline-flex items-center rounded-md border border-indigo-300 bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100"
                  >
                    Ver intentos
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- ==================== BLOQUE TAREAS ==================== -->
<section id="tareas" class="mb-10">
  <div class="mb-3 flex items-center justify-between gap-2">
    <h2 class="text-lg font-semibold tracking-tight text-slate-900">
      Tareas sin calificar
    </h2>
    <p class="text-xs text-slate-500">
      Actividades tipo <strong>tarea</strong> que tienen respuestas sin nota.
    </p>
  </div>

  <div class="rounded-xl border border-slate-200 bg-white overflow-hidden shadow-sm">
    <?php if (!$tareasPendientes): ?>
      <div class="px-4 py-6 text-sm text-slate-600">
        Ahora mismo no tienes tareas pendientes de calificar. Aprovecha y tómate un café 😄.
      </div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Examen</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Actividad (tarea)</th>
              <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-600">Respuestas pendientes</th>
              <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <?php foreach ($tareasPendientes as $t): ?>
              <?php $pend = (int)($t['pendientes'] ?? 0); ?>
              <tr class="hover:bg-slate-50">
                <td class="px-4 py-3">
                  <div class="text-sm font-medium text-slate-900">
                    <?= htmlspecialchars($t['examen_titulo'] ?? '') ?>
                  </div>
                  <div class="text-xs text-slate-500">
                    ID examen: <?= (int)$t['examen_id'] ?>
                  </div>
                </td>
                <td class="px-4 py-3">
                  <div class="text-sm text-slate-800">
                    <?= htmlspecialchars($t['actividad_titulo'] ?? '') ?>
                  </div>
                  <div class="text-xs text-slate-500">
                    ID actividad: <?= (int)$t['actividad_id'] ?>
                  </div>
                </td>
                <td class="px-4 py-3 text-center">
                  <span class="inline-flex items-center rounded-full bg-fuchsia-50 px-2 py-0.5 text-xs font-medium text-fuchsia-700 ring-1 ring-fuchsia-200">
                    <?= $pend ?>
                  </span>
                </td>
                <td class="px-4 py-3 text-right">
                  <a
                    href="<?= PUBLIC_URL ?>/admin/examenes/intentos.php?examen_id=<?= (int)$t['examen_id'] ?>"
                    class="inline-flex items-center rounded-md border border-fuchsia-300 bg-fuchsia-50 px-3 py-1.5 text-xs font-medium text-fuchsia-700 hover:bg-fuchsia-100"
                  >
                    Ver intentos
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
