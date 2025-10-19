<?php
// /public/dashboard.php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_login_or_redirect();

$u = current_user();
require_once __DIR__ . '/../partials/header.php';
$role = $u['role'] ?? '';
$profesorId = $u['profesor_id'] ?? null;

// ---------- ADMIN ----------
if ($role === 'admin') {
  // KPIs
  $kpis = [
    'familias'     => (int) pdo()->query('SELECT COUNT(*) FROM familias_profesionales')->fetchColumn(),
    'cursos'       => (int) pdo()->query('SELECT COUNT(*) FROM cursos')->fetchColumn(),
    'asignaturas'  => (int) pdo()->query('SELECT COUNT(*) FROM asignaturas')->fetchColumn(),
    'temas'        => (int) pdo()->query('SELECT COUNT(*) FROM temas')->fetchColumn(),
    'centros'      => (int) pdo()->query('SELECT COUNT(*) FROM centros')->fetchColumn(),
    'profesores'   => (int) pdo()->query('SELECT COUNT(*) FROM profesores')->fetchColumn(),
  ];

  // Recientes (limit 5)
  $recent_fams = pdo()->query("
    SELECT id, nombre, updated_at
    FROM familias_profesionales
    ORDER BY updated_at DESC
    LIMIT 5
  ")->fetchAll();

  $recent_cursos = pdo()->query("
    SELECT c.id, c.nombre, f.nombre AS familia, c.updated_at
    FROM cursos c
    JOIN familias_profesionales f ON f.id=c.familia_id
    ORDER BY c.updated_at DESC
    LIMIT 5
  ")->fetchAll();

  $recent_asig = pdo()->query("
    SELECT a.id, a.nombre, c.nombre AS curso, f.nombre AS familia, a.updated_at
    FROM asignaturas a
    JOIN cursos c ON c.id=a.curso_id
    JOIN familias_profesionales f ON f.id=a.familia_id
    ORDER BY a.updated_at DESC
    LIMIT 5
  ")->fetchAll();

  $recent_temas = pdo()->query("
    SELECT t.id, t.nombre, t.numero, t.updated_at,
           a.nombre AS asignatura, c.nombre AS curso, f.nombre AS familia
    FROM temas t
    JOIN asignaturas a ON a.id=t.asignatura_id
    JOIN cursos c ON c.id=a.curso_id
    JOIN familias_profesionales f ON f.id=a.familia_id
    ORDER BY t.updated_at DESC, t.numero ASC
    LIMIT 5
  ")->fetchAll();

  $recent_centros = pdo()->query("
    SELECT id, nombre, provincia, comunidad, updated_at
    FROM centros
    ORDER BY updated_at DESC
    LIMIT 5
  ")->fetchAll();
}
?>

<!-- Encabezado -->
<div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
  <div>
    <h1 class="text-2xl font-semibold tracking-tight">Panel</h1>
    <p class="mt-1 text-sm text-slate-600">Bienvenido, <?= htmlspecialchars($u['nombre'] ?? '') ?>.</p>
  </div>

  <!-- Buscador global -->
  <form method="get" action="<?= PUBLIC_URL ?>/search.php" class="flex items-center gap-2">
    <label for="q" class="sr-only">Buscar</label>
    <input id="q" name="q" type="search" placeholder="Buscar en Bancalia…"
           class="w-64 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400">
    <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Buscar</button>
  </form>
</div>

<?php if ($role === 'admin'): ?>

  <!-- ======= ADMIN: KPIs ======= -->
  <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <a href="<?= PUBLIC_URL ?>/admin/familias/index.php" class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow transition">
      <div class="text-xs font-medium text-slate-500">Familias</div>
      <div class="mt-2 text-3xl font-semibold"><?= number_format($kpis['familias']) ?></div>
      <div class="mt-2 text-xs text-slate-400 group-hover:text-slate-600">Gestionar familias →</div>
    </a>

    <a href="<?= PUBLIC_URL ?>/admin/cursos/index.php" class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow transition">
      <div class="text-xs font-medium text-slate-500">Cursos</div>
      <div class="mt-2 text-3xl font-semibold"><?= number_format($kpis['cursos']) ?></div>
      <div class="mt-2 text-xs text-slate-400 group-hover:text-slate-600">Gestionar cursos →</div>
    </a>

    <a href="<?= PUBLIC_URL ?>/admin/asignaturas/index.php" class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow transition">
      <div class="text-xs font-medium text-slate-500">Asignaturas</div>
      <div class="mt-2 text-3xl font-semibold"><?= number_format($kpis['asignaturas']) ?></div>
      <div class="mt-2 text-xs text-slate-400 group-hover:text-slate-600">Gestionar asignaturas →</div>
    </a>

    <a href="<?= PUBLIC_URL ?>/admin/temas/index.php" class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow transition">
      <div class="text-xs font-medium text-slate-500">Temas</div>
      <div class="mt-2 text-3xl font-semibold"><?= number_format($kpis['temas']) ?></div>
      <div class="mt-2 text-xs text-slate-400 group-hover:text-slate-600">Gestionar temas →</div>
    </a>

    <a href="<?= PUBLIC_URL ?>/admin/centros/index.php" class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow transition">
      <div class="text-xs font-medium text-slate-500">Centros</div>
      <div class="mt-2 text-3xl font-semibold"><?= number_format($kpis['centros']) ?></div>
      <div class="mt-2 text-xs text-slate-400 group-hover:text-slate-600">Gestionar centros →</div>
    </a>

    <a href="<?= PUBLIC_URL ?>/admin/profesores/index.php" class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow transition">
      <div class="text-xs font-medium text-slate-500">Profesores</div>
      <div class="mt-2 text-3xl font-semibold"><?= number_format($kpis['profesores']) ?></div>
      <div class="mt-2 text-xs text-slate-400 group-hover:text-slate-600">Gestionar profesores →</div>
    </a>
  </div>

  <!-- Accesos rápidos 
  <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <a href="<?= PUBLIC_URL ?>/admin/familias/create.php" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow transition">
      <div class="text-sm font-semibold">+ Nueva familia</div>
      <p class="mt-1 text-xs text-slate-600">Crea una familia profesional.</p>
    </a>
    <a href="<?= PUBLIC_URL ?>/admin/cursos/create.php" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow transition">
      <div class="text-sm font-semibold">+ Nuevo curso</div>
      <p class="mt-1 text-xs text-slate-600">Añade un curso a una familia.</p>
    </a>
    <a href="<?= PUBLIC_URL ?>/admin/asignaturas/create.php" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow transition">
      <div class="text-sm font-semibold">+ Nueva asignatura</div>
      <p class="mt-1 text-xs text-slate-600">Define una asignatura para un curso.</p>
    </a>
    <a href="<?= PUBLIC_URL ?>/admin/temas/create.php" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow transition">
      <div class="text-sm font-semibold">+ Nuevo tema</div>
      <p class="mt-1 text-xs text-slate-600">Crea un tema dentro de una asignatura.</p>
    </a>
    <a href="<?= PUBLIC_URL ?>/admin/centros/create.php" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:shadow transition">
      <div class="text-sm font-semibold">+ Nuevo centro</div>
      <p class="mt-1 text-xs text-slate-600">Registra un centro educativo.</p>
    </a>
  </div>
-->
  <!-- Recientes -->
  <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <!-- Familias + Cursos -->
    <div class="rounded-xl border border-slate-200 bg-white p-0 shadow-sm overflow-hidden">
      <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold">Últimas familias</div>
      <div class="p-4">
        <?php if (!$recent_fams): ?>
          <div class="text-sm text-slate-600">Sin cambios recientes.</div>
        <?php else: ?>
          <ul class="divide-y divide-slate-200">
            <?php foreach ($recent_fams as $r): ?>
              <li class="flex items-center justify-between py-2">
                <div class="text-sm text-slate-800"><?= htmlspecialchars($r['nombre']) ?></div>
                <div class="text-xs text-slate-500"><?= htmlspecialchars($r['updated_at']) ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

      <div class="border-t border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold">Últimos cursos</div>
      <div class="p-4">
        <?php if (!$recent_cursos): ?>
          <div class="text-sm text-slate-600">Sin cambios recientes.</div>
        <?php else: ?>
          <ul class="divide-y divide-slate-200">
            <?php foreach ($recent_cursos as $r): ?>
              <li class="py-2">
                <div class="text-sm font-medium text-slate-800"><?= htmlspecialchars($r['nombre']) ?></div>
                <div class="text-xs text-slate-500"><?= htmlspecialchars($r['familia']) ?> · <?= htmlspecialchars($r['updated_at']) ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>

    <!-- Temas + Asignaturas -->
    <div class="rounded-xl border border-slate-200 bg-white p-0 shadow-sm overflow-hidden">
      <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold">Últimos temas</div>
      <div class="p-4">
        <?php if (!$recent_temas): ?>
          <div class="text-sm text-slate-600">Sin cambios recientes.</div>
        <?php else: ?>
          <ul class="divide-y divide-slate-200">
            <?php foreach ($recent_temas as $r): ?>
              <li class="py-2">
                <div class="text-sm font-medium text-slate-800">
                  <?= 'T'.(int)$r['numero'].' · '.htmlspecialchars($r['nombre']) ?>
                </div>
                <div class="text-xs text-slate-500">
                  <?= htmlspecialchars($r['familia']) ?> → <?= htmlspecialchars($r['curso']) ?> → <?= htmlspecialchars($r['asignatura']) ?>
                  · <?= htmlspecialchars($r['updated_at']) ?>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>

      <div class="border-t border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold">Últimas asignaturas</div>
      <div class="p-4">
        <?php if (!$recent_asig): ?>
          <div class="text-sm text-slate-600">Sin cambios recientes.</div>
        <?php else: ?>
          <ul class="divide-y divide-slate-200">
            <?php foreach ($recent_asig as $r): ?>
              <li class="py-2">
                <div class="text-sm font-medium text-slate-800"><?= htmlspecialchars($r['nombre']) ?></div>
                <div class="text-xs text-slate-500"><?= htmlspecialchars($r['familia']) ?> → <?= htmlspecialchars($r['curso']) ?> · <?= htmlspecialchars($r['updated_at']) ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>

    <!-- Centros -->
    <div class="rounded-xl border border-slate-200 bg-white p-0 shadow-sm overflow-hidden">
      <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold">Últimos centros</div>
      <div class="p-4">
        <?php if (!$recent_centros): ?>
          <div class="text-sm text-slate-600">Sin cambios recientes.</div>
        <?php else: ?>
          <ul class="divide-y divide-slate-200">
            <?php foreach ($recent_centros as $r): ?>
              <li class="py-2">
                <div class="text-sm font-medium text-slate-800"><?= htmlspecialchars($r['nombre']) ?></div>
                <div class="text-xs text-slate-500">
                  <?= htmlspecialchars((string)($r['provincia'] ?? '—')) ?>, <?= htmlspecialchars((string)($r['comunidad'] ?? '—')) ?>
                  · <?= htmlspecialchars($r['updated_at']) ?>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>

<?php else: ?>
  <?php
  // ---------- PROFESOR ----------
  // Resolver profesor_id si no viene en sesión (por si acaso)
  if (!$profesorId && !empty($u['email'])) {
    $st = pdo()->prepare('SELECT id FROM profesores WHERE email=:e LIMIT 1');
    $st->execute([':e'=>$u['email']]);
    if ($row = $st->fetch()) $profesorId = (int)$row['id'];
  }

  // KPIs del profesor (distintos por asignación)
  $kpi_prof = [
    'cursos'      => 0,
    'asignaturas' => 0,
    'temas'       => 0,
    'centros'     => 0,
  ];

  if ($profesorId) {
    $kpi_prof['cursos'] = (int) pdo()->prepare('SELECT COUNT(DISTINCT curso_id) FROM profesor_asignacion WHERE profesor_id=:p')
                                      ->execute([':p'=>$profesorId]) ?: 0;
    $stmt = pdo()->prepare('SELECT COUNT(DISTINCT curso_id) AS n FROM profesor_asignacion WHERE profesor_id=:p');
    $stmt->execute([':p'=>$profesorId]);
    $kpi_prof['cursos'] = (int)($stmt->fetchColumn() ?: 0);

    $stmt = pdo()->prepare('SELECT COUNT(DISTINCT asignatura_id) AS n FROM profesor_asignacion WHERE profesor_id=:p');
    $stmt->execute([':p'=>$profesorId]);
    $kpi_prof['asignaturas'] = (int)($stmt->fetchColumn() ?: 0);

    $stmt = pdo()->prepare('
      SELECT COUNT(*) AS n
      FROM temas t
      INNER JOIN profesor_asignacion pa ON pa.asignatura_id = t.asignatura_id
      WHERE pa.profesor_id = :p
    ');
    $stmt->execute([':p'=>$profesorId]);
    $kpi_prof['temas'] = (int)($stmt->fetchColumn() ?: 0);

    $stmt = pdo()->prepare('SELECT COUNT(DISTINCT centro_id) AS n FROM profesor_asignacion WHERE profesor_id=:p AND centro_id IS NOT NULL');
    $stmt->execute([':p'=>$profesorId]);
    $kpi_prof['centros'] = (int)($stmt->fetchColumn() ?: 0);
  }

  // Recientes profesor (limit 5)
  $prof_recent_cursos = $prof_recent_asig = $prof_recent_temas = [];
  if ($profesorId) {
    $st = pdo()->prepare('
      SELECT DISTINCT c.id, c.nombre, MAX(pa.updated_at) AS updated_at
      FROM profesor_asignacion pa
      JOIN cursos c ON c.id = pa.curso_id
      WHERE pa.profesor_id = :p
      GROUP BY c.id, c.nombre
      ORDER BY updated_at DESC
      LIMIT 5
    ');
    $st->execute([':p'=>$profesorId]);
    $prof_recent_cursos = $st->fetchAll();

    $st = pdo()->prepare('
      SELECT DISTINCT a.id, a.nombre, MAX(pa.updated_at) AS updated_at
      FROM profesor_asignacion pa
      JOIN asignaturas a ON a.id = pa.asignatura_id
      WHERE pa.profesor_id = :p
      GROUP BY a.id, a.nombre
      ORDER BY updated_at DESC
      LIMIT 5
    ');
    $st->execute([':p'=>$profesorId]);
    $prof_recent_asig = $st->fetchAll();

    $st = pdo()->prepare('
      SELECT t.id, t.nombre, t.numero, t.updated_at,
             a.nombre AS asignatura, c.nombre AS curso, f.nombre AS familia
      FROM temas t
      JOIN asignaturas a ON a.id = t.asignatura_id
      JOIN cursos c ON c.id = a.curso_id
      JOIN familias_profesionales f ON f.id = a.familia_id
      JOIN profesor_asignacion pa ON pa.asignatura_id = a.id
      WHERE pa.profesor_id = :p
      ORDER BY t.updated_at DESC, t.numero ASC
      LIMIT 5
    ');
    $st->execute([':p'=>$profesorId]);
    $prof_recent_temas = $st->fetchAll();
  }
  ?>

  <!-- ======= PROFESOR: KPIs (mismo estilo) ======= -->
  <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="text-xs font-medium text-slate-500">Tus cursos</div>
      <div class="mt-2 text-3xl font-semibold"><?= number_format($kpi_prof['cursos']) ?></div>
    </div>
    <div class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="text-xs font-medium text-slate-500">Tus asignaturas</div>
      <div class="mt-2 text-3xl font-semibold"><?= number_format($kpi_prof['asignaturas']) ?></div>
    </div>
    <div class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="text-xs font-medium text-slate-500">Tus temas</div>
      <div class="mt-2 text-3xl font-semibold"><?= number_format($kpi_prof['temas']) ?></div>
    </div>
    <div class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="text-xs font-medium text-slate-500">Tus centros</div>
      <div class="mt-2 text-3xl font-semibold"><?= number_format($kpi_prof['centros']) ?></div>
    </div>
  </div>

  <!-- ======= PROFESOR: Recientes (mismo patrón de tarjetas) ======= -->
  <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <!-- Tus cursos -->
    <div class="rounded-xl border border-slate-200 bg-white p-0 shadow-sm overflow-hidden">
      <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold">Tus últimos cursos</div>
      <div class="p-4">
        <?php if (!$prof_recent_cursos): ?>
          <div class="text-sm text-slate-600">Sin cursos asignados.</div>
        <?php else: ?>
          <ul class="divide-y divide-slate-200">
            <?php foreach ($prof_recent_cursos as $r): ?>
              <li class="py-2">
                <div class="text-sm font-medium text-slate-800"><?= htmlspecialchars($r['nombre']) ?></div>
                <div class="text-xs text-slate-500"><?= htmlspecialchars($r['updated_at']) ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>

    <!-- Tus temas -->
    <div class="rounded-xl border border-slate-200 bg-white p-0 shadow-sm overflow-hidden">
      <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold">Tus últimos temas</div>
      <div class="p-4">
        <?php if (!$prof_recent_temas): ?>
          <div class="text-sm text-slate-600">Sin temas todavía.</div>
        <?php else: ?>
          <ul class="divide-y divide-slate-200">
            <?php foreach ($prof_recent_temas as $r): ?>
              <li class="py-2">
                <div class="text-sm font-medium text-slate-800">
                  <?= 'T'.(int)$r['numero'].' · '.htmlspecialchars($r['nombre']) ?>
                </div>
                <div class="text-xs text-slate-500">
                  <?= htmlspecialchars($r['familia']) ?> → <?= htmlspecialchars($r['curso']) ?> → <?= htmlspecialchars($r['asignatura']) ?>
                  · <?= htmlspecialchars($r['updated_at']) ?>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>

    <!-- Tus asignaturas -->
    <div class="rounded-xl border border-slate-200 bg-white p-0 shadow-sm overflow-hidden">
      <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold">Tus últimas asignaturas</div>
      <div class="p-4">
        <?php if (!$prof_recent_asig): ?>
          <div class="text-sm text-slate-600">Sin asignaturas todavía.</div>
        <?php else: ?>
          <ul class="divide-y divide-slate-200">
            <?php foreach ($prof_recent_asig as $r): ?>
              <li class="py-2">
                <div class="text-sm font-medium text-slate-800"><?= htmlspecialchars($r['nombre']) ?></div>
                <div class="text-xs text-slate-500"><?= htmlspecialchars($r['updated_at']) ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>

<?php endif; ?>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>

