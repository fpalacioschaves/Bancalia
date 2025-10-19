<?php
// /public/search.php
declare(strict_types=1);
require_once __DIR__ . '/../middleware/require_auth.php';
require_once __DIR__ . '/../partials/header.php';

$q = trim($_GET['q'] ?? '');
$hasQuery = ($q !== '');

$results = [
  'familias'    => [],
  'cursos'      => [],
  'asignaturas' => [],
  'temas'       => [],
  'centros'     => [],
  'profesores'  => [],
];

if ($hasQuery) {
  $like = "%{$q}%";

  // Familias
  $st = pdo()->prepare("
    SELECT id, nombre, slug, updated_at
    FROM familias_profesionales
    WHERE nombre LIKE :q OR slug LIKE :q OR descripcion LIKE :q
    ORDER BY nombre ASC
    LIMIT 10
  ");
  $st->execute([':q'=>$like]);
  $results['familias'] = $st->fetchAll();

  // Cursos (con familia)
  $st = pdo()->prepare("
    SELECT c.id, c.nombre, c.slug, c.orden, f.nombre AS familia
    FROM cursos c
    JOIN familias_profesionales f ON f.id=c.familia_id
    WHERE c.nombre LIKE :q OR c.slug LIKE :q OR f.nombre LIKE :q
    ORDER BY f.nombre ASC, c.orden ASC, c.nombre ASC
    LIMIT 10
  ");
  $st->execute([':q'=>$like]);
  $results['cursos'] = $st->fetchAll();

  // Asignaturas (con curso y familia)
  $st = pdo()->prepare("
    SELECT a.id, a.nombre, a.slug, a.codigo, c.nombre AS curso, f.nombre AS familia
    FROM asignaturas a
    JOIN cursos c ON c.id=a.curso_id
    JOIN familias_profesionales f ON f.id=a.familia_id
    WHERE a.nombre LIKE :q OR a.slug LIKE :q OR a.codigo LIKE :q
       OR c.nombre LIKE :q OR f.nombre LIKE :q
    ORDER BY f.nombre ASC, c.orden ASC, a.orden ASC, a.nombre ASC
    LIMIT 10
  ");
  $st->execute([':q'=>$like]);
  $results['asignaturas'] = $st->fetchAll();

  // Temas (con asignatura, curso, familia)
  $st = pdo()->prepare("
    SELECT t.id, t.nombre, t.slug, t.numero,
           a.nombre AS asignatura, c.nombre AS curso, f.nombre AS familia
    FROM temas t
    JOIN asignaturas a ON a.id=t.asignatura_id
    JOIN cursos c ON c.id=a.curso_id
    JOIN familias_profesionales f ON f.id=a.familia_id
    WHERE t.nombre LIKE :q OR t.slug LIKE :q
       OR a.nombre LIKE :q OR c.nombre LIKE :q OR f.nombre LIKE :q
    ORDER BY f.nombre ASC, c.orden ASC, a.orden ASC, t.numero ASC, t.nombre ASC
    LIMIT 10
  ");
  $st->execute([':q'=>$like]);
  $results['temas'] = $st->fetchAll();

  // Centros
  $st = pdo()->prepare("
    SELECT id, nombre, slug, provincia, comunidad
    FROM centros
    WHERE nombre LIKE :q OR slug LIKE :q OR codigo LIKE :q
       OR localidad LIKE :q OR provincia LIKE :q OR comunidad LIKE :q
    ORDER BY nombre ASC
    LIMIT 10
  ");
  $st->execute([':q'=>$like]);
  $results['centros'] = $st->fetchAll();

  // Profesores (con centro)
  $st = pdo()->prepare("
    SELECT p.id, p.nombre, p.apellidos, p.email, c.nombre AS centro
    FROM profesores p
    LEFT JOIN centros c ON c.id=p.centro_id
    WHERE p.nombre LIKE :q OR p.apellidos LIKE :q OR p.email LIKE :q OR c.nombre LIKE :q
    ORDER BY p.apellidos ASC, p.nombre ASC
    LIMIT 10
  ");
  $st->execute([':q'=>$like]);
  $results['profesores'] = $st->fetchAll();
}
?>

<div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
  <div>
    <h1 class="text-2xl font-semibold tracking-tight">Buscar</h1>
    <p class="mt-1 text-sm text-slate-600">Encuentra familias, cursos, asignaturas, temas, centros o profesores.</p>
  </div>

  <form method="get" action="<?= PUBLIC_URL ?>/search.php" class="flex items-center gap-2">
    <label for="q" class="sr-only">Buscar</label>
    <input id="q" name="q" type="search" value="<?= htmlspecialchars($q) ?>"
           placeholder="Escribe para buscar…"
           class="w-64 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400">
    <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Buscar</button>
  </form>
</div>

<?php if (!$hasQuery): ?>
  <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm text-slate-600">
    Escribe algo en el buscador de arriba y pulsa <strong>Buscar</strong>.
  </div>
<?php else: ?>
  <div class="grid grid-cols-1 gap-6">
    <!-- Familias -->
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
      <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold">
        Familias (<?= count($results['familias']) ?>)
      </div>
      <div class="p-4">
        <?php if (!$results['familias']): ?>
          <div class="text-sm text-slate-600">Sin resultados.</div>
        <?php else: ?>
          <ul class="divide-y divide-slate-200">
            <?php foreach ($results['familias'] as $r): ?>
              <li class="flex items-center justify-between py-2">
                <div>
                  <div class="text-sm font-medium text-slate-800"><?= htmlspecialchars($r['nombre']) ?></div>
                  <div class="text-xs text-slate-500">
                    <code class="rounded bg-slate-100 px-1.5 py-0.5"><?= htmlspecialchars($r['slug']) ?></code>
                  </div>
                </div>
                <a href="<?= PUBLIC_URL ?>/admin/familias/edit.php?id=<?= (int)$r['id'] ?>" class="text-sm text-indigo-600 hover:underline">Abrir</a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </section>

    <!-- Cursos -->
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
      <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold">
        Cursos (<?= count($results['cursos']) ?>)
      </div>
      <div class="p-4">
        <?php if (!$results['cursos']): ?>
          <div class="text-sm text-slate-600">Sin resultados.</div>
        <?php else: ?>
          <ul class="divide-y divide-slate-200">
            <?php foreach ($results['cursos'] as $r): ?>
              <li class="flex items-center justify-between py-2">
                <div>
                  <div class="text-sm font-medium text-slate-800"><?= htmlspecialchars($r['nombre']) ?></div>
                  <div class="text-xs text-slate-500">
                    <?= htmlspecialchars($r['familia']) ?> ·
                    <code class="rounded bg-slate-100 px-1.5 py-0.5"><?= htmlspecialchars($r['slug']) ?></code>
                  </div>
                </div>
                <a href="<?= PUBLIC_URL ?>/admin/cursos/edit.php?id=<?= (int)$r['id'] ?>" class="text-sm text-indigo-600 hover:underline">Abrir</a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </section>

    <!-- Asignaturas -->
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
      <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold">
        Asignaturas (<?= count($results['asignaturas']) ?>)
      </div>
      <div class="p-4">
        <?php if (!$results['asignaturas']): ?>
          <div class="text-sm text-slate-600">Sin resultados.</div>
        <?php else: ?>
          <ul class="divide-y divide-slate-200">
            <?php foreach ($results['asignaturas'] as $r): ?>
              <li class="flex items-center justify-between py-2">
                <div>
                  <div class="text-sm font-medium text-slate-800"><?= htmlspecialchars($r['nombre']) ?></div>
                  <div class="text-xs text-slate-500">
                    <?= htmlspecialchars($r['familia']) ?> → <?= htmlspecialchars($r['curso']) ?>
                    <?= $r['codigo'] ? '· '.htmlspecialchars($r['codigo']) : '' ?>
                  </div>
                </div>
                <a href="<?= PUBLIC_URL ?>/admin/asignaturas/edit.php?id=<?= (int)$r['id'] ?>" class="text-sm text-indigo-600 hover:underline">Abrir</a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </section>

    <!-- Temas -->
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
      <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold">
        Temas (<?= count($results['temas']) ?>)
      </div>
      <div class="p-4">
        <?php if (!$results['temas']): ?>
          <div class="text-sm text-slate-600">Sin resultados.</div>
        <?php else: ?>
          <ul class="divide-y divide-slate-200">
            <?php foreach ($results['temas'] as $r): ?>
              <li class="flex items-center justify-between py-2">
                <div>
                  <div class="text-sm font-medium text-slate-800">
                    <?= 'T'.(int)$r['numero'].' · '.htmlspecialchars($r['nombre']) ?>
                  </div>
                  <div class="text-xs text-slate-500">
                    <?= htmlspecialchars($r['familia']) ?> → <?= htmlspecialchars($r['curso']) ?> → <?= htmlspecialchars($r['asignatura']) ?>
                    <?php if (!empty($r['slug'])): ?>
                      · <code class="rounded bg-slate-100 px-1.5 py-0.5"><?= htmlspecialchars($r['slug']) ?></code>
                    <?php endif; ?>
                  </div>
                </div>
                <a href="<?= PUBLIC_URL ?>/admin/temas/edit.php?id=<?= (int)$r['id'] ?>" class="text-sm text-indigo-600 hover:underline">Abrir</a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </section>

    <!-- Centros -->
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
      <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold">
        Centros (<?= count($results['centros']) ?>)
      </div>
      <div class="p-4">
        <?php if (!$results['centros']): ?>
          <div class="text-sm text-slate-600">Sin resultados.</div>
        <?php else: ?>
          <ul class="divide-y divide-slate-200">
            <?php foreach ($results['centros'] as $r): ?>
              <li class="flex items-center justify-between py-2">
                <div>
                  <div class="text-sm font-medium text-slate-800"><?= htmlspecialchars($r['nombre']) ?></div>
                  <div class="text-xs text-slate-500">
                    <?= htmlspecialchars((string)$r['provincia'] ?: '—') ?>, <?= htmlspecialchars((string)$r['comunidad'] ?: '—') ?>
                    · <code class="rounded bg-slate-100 px-1.5 py-0.5"><?= htmlspecialchars($r['slug']) ?></code>
                  </div>
                </div>
                <a href="<?= PUBLIC_URL ?>/admin/centros/edit.php?id=<?= (int)$r['id'] ?>" class="text-sm text-indigo-600 hover:underline">Abrir</a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </section>

    <!-- Profesores -->
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
      <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold">
        Profesores (<?= count($results['profesores']) ?>)
      </div>
      <div class="p-4">
        <?php if (!$results['profesores']): ?>
          <div class="text-sm text-slate-600">Sin resultados.</div>
        <?php else: ?>
          <ul class="divide-y divide-slate-200">
            <?php foreach ($results['profesores'] as $r): ?>
              <li class="flex items-center justify-between py-2">
                <div>
                  <div class="text-sm font-medium text-slate-800">
                    <?= htmlspecialchars($r['apellidos'].', '.$r['nombre']) ?>
                  </div>
                  <div class="text-xs text-slate-500">
                    <?= htmlspecialchars($r['email']) ?>
                    · <?= htmlspecialchars((string)$r['centro'] ?: '—') ?>
                  </div>
                </div>
                <a href="<?= PUBLIC_URL ?>/admin/profesores/edit.php?id=<?= (int)$r['id'] ?>" class="text-sm text-indigo-600 hover:underline">Abrir</a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </section>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>

