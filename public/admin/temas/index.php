<?php
// /public/dashboard.php
declare(strict_types=1);
require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();

$u = current_user();
require_once __DIR__ . '/../../../partials/header.php';


$q         = trim($_GET['q'] ?? '');
$famId     = (int)($_GET['familia_id'] ?? 0);
$cursoId   = (int)($_GET['curso_id'] ?? 0);
$asigId    = (int)($_GET['asignatura_id'] ?? 0);

// Cargar datos para filtros
$fams = pdo()->query('SELECT id, nombre FROM familias_profesionales WHERE is_active=1 ORDER BY nombre ASC')->fetchAll();
$cursos = pdo()->query('SELECT c.id, c.nombre, c.familia_id, f.nombre AS familia
                        FROM cursos c JOIN familias_profesionales f ON f.id=c.familia_id
                        WHERE c.is_active=1
                        ORDER BY f.nombre ASC, c.orden ASC, c.nombre ASC')->fetchAll();
$asigs = pdo()->query('SELECT a.id, a.nombre, a.curso_id, a.familia_id, c.nombre AS curso, f.nombre AS familia
                       FROM asignaturas a
                       JOIN cursos c ON c.id=a.curso_id
                       JOIN familias_profesionales f ON f.id=a.familia_id
                       WHERE a.is_active=1
                       ORDER BY f.nombre ASC, c.orden ASC, a.orden ASC, a.nombre ASC')->fetchAll();

$sql = "SELECT t.id, t.nombre, t.slug, t.numero, t.is_active, t.updated_at,
               a.nombre AS asignatura, c.nombre AS curso, f.nombre AS familia
        FROM temas t
        JOIN asignaturas a ON a.id=t.asignatura_id
        JOIN cursos c ON c.id=a.curso_id
        JOIN familias_profesionales f ON f.id=a.familia_id";
$params = [];
$w = [];

if ($q !== '') {
  $w[] = '(t.nombre LIKE :q OR t.slug LIKE :q OR a.nombre LIKE :q OR c.nombre LIKE :q OR f.nombre LIKE :q)';
  $params[':q'] = "%{$q}%";
}
if ($famId > 0) {
  $w[] = 'a.familia_id = :fam';
  $params[':fam'] = $famId;
}
if ($cursoId > 0) {
  $w[] = 'a.curso_id = :curso';
  $params[':curso'] = $cursoId;
}
if ($asigId > 0) {
  $w[] = 't.asignatura_id = :asig';
  $params[':asig'] = $asigId;
}
if ($w) $sql .= ' WHERE '.implode(' AND ', $w);
$sql .= ' ORDER BY f.nombre ASC, c.orden ASC, a.orden ASC, t.numero ASC, t.nombre ASC';

$st = pdo()->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();
?>
<h1 class="text-xl font-semibold tracking-tight mb-4">Temas</h1>

<form method="get" action="" class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-[1fr,220px,220px,260px,auto,auto] items-stretch">
  <input
    type="search" name="q" value="<?= htmlspecialchars($q) ?>"
    placeholder="Buscar por tema/asignatura/curso/familia…"
    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400" />

  <select name="familia_id"
          class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
    <option value="0">Todas las familias</option>
    <?php foreach ($fams as $f): ?>
      <option value="<?= (int)$f['id'] ?>" <?= $famId===(int)$f['id']?'selected':'' ?>>
        <?= htmlspecialchars($f['nombre']) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <select name="curso_id"
          class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
    <option value="0">Todos los cursos</option>
    <?php foreach ($cursos as $c): ?>
      <option value="<?= (int)$c['id'] ?>" data-familia="<?= (int)$c['familia_id'] ?>" <?= $cursoId===(int)$c['id']?'selected':'' ?>>
        <?= htmlspecialchars($c['familia'].' — '.$c['nombre']) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <select name="asignatura_id"
          class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
    <option value="0">Todas las asignaturas</option>
    <?php foreach ($asigs as $a): ?>
      <option value="<?= (int)$a['id'] ?>"
              data-familia="<?= (int)$a['familia_id'] ?>"
              data-curso="<?= (int)$a['curso_id'] ?>"
              <?= $asigId===(int)$a['id']?'selected':'' ?>>
        <?= htmlspecialchars($a['familia'].' → '.$a['curso'].' → '.$a['nombre']) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <button type="submit"
          class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 active:scale-[0.99] transition">
    Buscar
  </button>

  <a href="<?= PUBLIC_URL ?>/admin/temas/create.php"
     class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 active:scale-[0.99] transition">
    + Nuevo tema
  </a>
</form>

<?php if (!$rows): ?>
  <div class="rounded-lg border border-slate-200 bg-white p-6 text-slate-600">
    No hay temas todavía.
    <a href="<?= PUBLIC_URL ?>/admin/temas/create.php" class="text-indigo-600 hover:underline font-medium">Crea el primero</a>.
  </div>
<?php else: ?>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-slate-200">
      <thead class="bg-slate-50">
        <tr>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Familia</th>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Curso</th>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Asignatura</th>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">#</th>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Tema</th>
          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Slug</th>
          <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-600">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200">
        <?php foreach ($rows as $r): ?>
          <tr class="hover:bg-slate-50">
            <td class="px-4 py-3 text-sm text-slate-800"><?= htmlspecialchars($r['familia']) ?></td>
            <td class="px-4 py-3 text-sm text-slate-800"><?= htmlspecialchars($r['curso']) ?></td>
            <td class="px-4 py-3 text-sm text-slate-800"><?= htmlspecialchars($r['asignatura']) ?></td>
            <td class="px-4 py-3 text-sm text-slate-800"><?= (int)$r['numero'] ?></td>
            <td class="px-4 py-3 text-sm text-slate-800"><?= htmlspecialchars($r['nombre']) ?></td>
            <td class="px-4 py-3 text-sm text-slate-700"><code class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px]"><?= htmlspecialchars($r['slug']) ?></code></td>
            <td class="px-4 py-3 text-sm">
              <div class="flex justify-end gap-2">
                <a href="<?= PUBLIC_URL ?>/admin/temas/edit.php?id=<?= (int)$r['id'] ?>"
                   class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100">Editar</a>
                <form method="post" action="<?= PUBLIC_URL ?>/admin/temas/delete.php" onsubmit="return confirm('¿Eliminar este tema?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button type="submit"
                          class="inline-flex items-center rounded-lg bg-rose-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-rose-500">Eliminar</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<script>
  // Dependencias de filtros (opcional)
  const selFam = document.querySelector('select[name="familia_id"]');
  const selCurso = document.querySelector('select[name="curso_id"]');
  const selAsig = document.querySelector('select[name="asignatura_id"]');

  const cursosOptions = Array.from(selCurso?.options || []);
  const asigOptions = Array.from(selAsig?.options || []);

  selFam?.addEventListener('change', () => {
    const fid = parseInt(selFam.value || '0', 10);

    // Cursos
    selCurso.innerHTML = '';
    const optAllC = document.createElement('option'); optAllC.value = '0'; optAllC.textContent = 'Todos los cursos';
    selCurso.appendChild(optAllC);
    cursosOptions.forEach(o => {
      const f = parseInt(o.getAttribute('data-familia') || '0', 10);
      if (!f || fid === 0 || f === fid) selCurso.appendChild(o.cloneNode(true));
    });

    // Asignaturas
    selAsig.innerHTML = '';
    const optAllA = document.createElement('option'); optAllA.value = '0'; optAllA.textContent = 'Todas las asignaturas';
    selAsig.appendChild(optAllA);
    asigOptions.forEach(o => {
      const f = parseInt(o.getAttribute('data-familia') || '0', 10);
      if (!f || fid === 0 || f === fid) selAsig.appendChild(o.cloneNode(true));
    });
  }, { passive: true });

  selCurso?.addEventListener('change', () => {
    const cid = parseInt(selCurso.value || '0', 10);
    selAsig.innerHTML = '';
    const optAllA = document.createElement('option'); optAllA.value = '0'; optAllA.textContent = 'Todas las asignaturas';
    selAsig.appendChild(optAllA);
    asigOptions.forEach(o => {
      const c = parseInt(o.getAttribute('data-curso') || '0', 10);
      if (!c || cid === 0 || c === cid) selAsig.appendChild(o.cloneNode(true));
    });
  }, { passive: true });
</script>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>
