<?php
// /public/admin/examenes/edit.php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();
$u = current_user();

// if (($u['role'] ?? '') !== 'admin') {
//   flash('error', 'Acceso restringido a administradores.');
//   header('Location: ' . PUBLIC_URL . '/dashboard.php');
//   exit;
// }

// ID del examen
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  exit('ID de examen inválido.');
}

// Listados para los selects
$profes = pdo()->query('
  SELECT id, nombre, apellidos
  FROM profesores
  WHERE is_active = 1
  ORDER BY apellidos ASC, nombre ASC
')->fetchAll();

$fams = pdo()->query('
  SELECT id, nombre
  FROM familias_profesionales
  WHERE is_active = 1
  ORDER BY nombre ASC
')->fetchAll();

$cursos = pdo()->query('
  SELECT c.id, c.nombre, f.nombre AS familia
  FROM cursos c
  JOIN familias_profesionales f ON f.id = c.familia_id
  WHERE c.is_active = 1
  ORDER BY f.nombre ASC, c.orden ASC, c.nombre ASC
')->fetchAll();

$asigs = pdo()->query('
  SELECT a.id, a.nombre, c.nombre AS curso, f.nombre AS familia
  FROM asignaturas a
  JOIN cursos c ON c.id = a.curso_id
  JOIN familias_profesionales f ON f.id = a.familia_id
  WHERE a.is_active = 1
  ORDER BY f.nombre ASC, c.nombre ASC, a.nombre ASC
')->fetchAll();

// Cargar examen actual
$stEx = pdo()->prepare('SELECT * FROM examenes WHERE id = :id LIMIT 1');
$stEx->execute([':id' => $id]);
$examen = $stEx->fetch(PDO::FETCH_ASSOC);

if (!$examen) {
  http_response_code(404);
  exit('Examen no encontrado.');
}

// Procesado del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $profesor_id    = (int)($_POST['profesor_id'] ?? 0);
    $familia_id     = (int)($_POST['familia_id'] ?? 0);
    $curso_id       = (int)($_POST['curso_id'] ?? 0);
    $asignatura_id  = (int)($_POST['asignatura_id'] ?? 0);
    $titulo         = trim($_POST['titulo'] ?? '');
    $descripcion    = trim($_POST['descripcion'] ?? '');
    $estado         = $_POST['estado'] ?? 'borrador';
    $fecha_raw      = trim($_POST['fecha'] ?? '');
    $hora_raw       = trim($_POST['hora'] ?? '');
    $duracion_raw   = trim($_POST['duracion_minutos'] ?? '');

    if ($profesor_id <= 0)   throw new RuntimeException('Selecciona un profesor.');
    if ($familia_id <= 0)    throw new RuntimeException('Selecciona una familia.');
    if ($curso_id <= 0)      throw new RuntimeException('Selecciona un curso.');
    if ($asignatura_id <= 0) throw new RuntimeException('Selecciona una asignatura.');
    if ($titulo === '')      throw new RuntimeException('El título es obligatorio.');

    if (!in_array($estado, ['borrador', 'publicado'], true)) {
      $estado = 'borrador';
    }

    $fecha = $fecha_raw !== '' ? $fecha_raw : null; // YYYY-MM-DD
    $hora  = $hora_raw  !== '' ? $hora_raw  : null; // HH:MM (el navegador ya da formato correcto)
    $duracion_minutos = $duracion_raw !== '' ? (int)$duracion_raw : null;

    // Validaciones de existencia (coherentes con FKs)
    $chkProf = pdo()->prepare('SELECT 1 FROM profesores WHERE id = :id AND is_active = 1 LIMIT 1');
    $chkProf->execute([':id' => $profesor_id]);
    if (!$chkProf->fetch()) {
      throw new RuntimeException('El profesor seleccionado no existe o no está activo.');
    }

    $chkFam = pdo()->prepare('SELECT 1 FROM familias_profesionales WHERE id = :id AND is_active = 1 LIMIT 1');
    $chkFam->execute([':id' => $familia_id]);
    if (!$chkFam->fetch()) {
      throw new RuntimeException('La familia seleccionada no existe o no está activa.');
    }

    $chkCurso = pdo()->prepare('SELECT 1 FROM cursos WHERE id = :id AND is_active = 1 LIMIT 1');
    $chkCurso->execute([':id' => $curso_id]);
    if (!$chkCurso->fetch()) {
      throw new RuntimeException('El curso seleccionado no existe o no está activo.');
    }

    $chkAsig = pdo()->prepare('SELECT 1 FROM asignaturas WHERE id = :id AND is_active = 1 LIMIT 1');
    $chkAsig->execute([':id' => $asignatura_id]);
    if (!$chkAsig->fetch()) {
      throw new RuntimeException('La asignatura seleccionada no existe o no está activa.');
    }

    // UPDATE EXACTO según estructura de `examenes`
    $upd = pdo()->prepare('
      UPDATE examenes
      SET
        profesor_id      = :profesor_id,
        familia_id       = :familia_id,
        curso_id         = :curso_id,
        asignatura_id    = :asignatura_id,
        titulo           = :titulo,
        descripcion      = :descripcion,
        estado           = :estado,
        fecha            = :fecha,
        hora             = :hora,
        duracion_minutos = :duracion_minutos
      WHERE id = :id
      LIMIT 1
    ');

    $upd->execute([
      ':profesor_id'      => $profesor_id,
      ':familia_id'       => $familia_id,
      ':curso_id'         => $curso_id,
      ':asignatura_id'    => $asignatura_id,
      ':titulo'           => $titulo,
      ':descripcion'      => $descripcion !== '' ? $descripcion : null,
      ':estado'           => $estado,
      ':fecha'            => $fecha,
      ':hora'             => $hora,
      ':duracion_minutos' => $duracion_minutos,
      ':id'               => $id,
    ]);

    flash('success', 'Examen actualizado correctamente.');
    header('Location: ' . PUBLIC_URL . '/admin/examenes/index.php');
    exit;

  } catch (Throwable $e) {
    flash('error', $e->getMessage());
    header('Location: ' . PUBLIC_URL . '/admin/examenes/edit.php?id=' . $id);
    exit;
  }
}

// Si llegamos aquí es GET normal: mostramos el formulario con los datos actuales
require_once __DIR__ . '/../../../partials/header.php';
?>

<div class="mb-6 flex items-center justify-between">
  <div>
    <h1 class="text-xl font-semibold tracking-tight">Editar examen</h1>
    <p class="mt-1 text-sm text-slate-600">
      Modifica los datos del examen seleccionado.
    </p>
  </div>
  <a href="<?= PUBLIC_URL ?>/admin/examenes/index.php"
     class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
    Volver al listado
  </a>
</div>

<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
  <form method="post" action="" class="space-y-5">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$examen['id'] ?>">

    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label for="profesor_id" class="mb-1 block text-sm font-medium text-slate-700">
          Profesor <span class="text-rose-600">*</span>
        </label>
        <select id="profesor_id" name="profesor_id" required
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
          <option value="">Selecciona un profesor…</option>
          <?php foreach ($profes as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= (int)$examen['profesor_id'] === (int)$p['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($p['apellidos'] . ', ' . $p['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="familia_id" class="mb-1 block text-sm font-medium text-slate-700">
          Familia <span class="text-rose-600">*</span>
        </label>
        <select id="familia_id" name="familia_id" required
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
          <option value="">Selecciona una familia…</option>
          <?php foreach ($fams as $f): ?>
            <option value="<?= (int)$f['id'] ?>" <?= (int)$examen['familia_id'] === (int)$f['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($f['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label for="curso_id" class="mb-1 block text-sm font-medium text-slate-700">
          Curso <span class="text-rose-600">*</span>
        </label>
        <select id="curso_id" name="curso_id" required
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
          <option value="">Selecciona un curso…</option>
          <?php foreach ($cursos as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (int)$examen['curso_id'] === (int)$c['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['familia'] . ' · ' . $c['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="asignatura_id" class="mb-1 block text-sm font-medium text-slate-700">
          Asignatura <span class="text-rose-600">*</span>
        </label>
        <select id="asignatura_id" name="asignatura_id" required
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
          <option value="">Selecciona una asignatura…</option>
          <?php foreach ($asigs as $a): ?>
            <option value="<?= (int)$a['id'] ?>" <?= (int)$examen['asignatura_id'] === (int)$a['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($a['familia'] . ' · ' . $a['curso'] . ' · ' . $a['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div>
      <label for="titulo" class="mb-1 block text-sm font-medium text-slate-700">
        Título del examen <span class="text-rose-600">*</span>
      </label>
      <input id="titulo" name="titulo" type="text" required
             value="<?= htmlspecialchars($examen['titulo']) ?>"
             class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
    </div>

    <div>
      <label for="descripcion" class="mb-1 block text-sm font-medium text-slate-700">
        Descripción (opcional)
      </label>
      <textarea id="descripcion" name="descripcion" rows="4"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
                placeholder="Añade detalles sobre el examen (contenidos, indicaciones para el alumnado, etc.)…"><?= htmlspecialchars((string)$examen['descripcion']) ?></textarea>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <div>
        <label for="estado" class="mb-1 block text-sm font-medium text-slate-700">
          Estado
        </label>
        <select id="estado" name="estado"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
          <option value="borrador" <?= $examen['estado'] === 'borrador' ? 'selected' : '' ?>>Borrador</option>
          <option value="publicado" <?= $examen['estado'] === 'publicado' ? 'selected' : '' ?>>Publicado</option>
        </select>
        <p class="mt-1 text-xs text-slate-500">
          Puedes dejarlo en borrador mientras lo preparas.
        </p>
      </div>

      <div>
        <label for="fecha" class="mb-1 block text-sm font-medium text-slate-700">
          Fecha del examen
        </label>
        <input id="fecha" name="fecha" type="date"
               value="<?= htmlspecialchars((string)$examen['fecha']) ?>"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
      </div>

      <div>
        <label for="hora" class="mb-1 block text-sm font-medium text-slate-700">
          Hora del examen
        </label>
        <input id="hora" name="hora" type="time"
               value="<?= $examen['hora'] ? htmlspecialchars(substr($examen['hora'], 0, 5)) : '' ?>"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <div>
        <label for="duracion_minutos" class="mb-1 block text-sm font-medium text-slate-700">
          Duración (minutos)
        </label>
        <input id="duracion_minutos" name="duracion_minutos" type="number" min="1" step="1"
               value="<?= htmlspecialchars((string)$examen['duracion_minutos']) ?>"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
               placeholder="Ej. 60">
        <p class="mt-1 text-xs text-slate-500">
          Puedes dejarlo vacío si no quieres fijar duración.
        </p>
      </div>
    </div>

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
      <a href="<?= PUBLIC_URL ?>/admin/examenes/index.php"
         class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
        Cancelar
      </a>
      <button type="submit"
              class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 active:scale-[0.99] transition">
        Guardar cambios
      </button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>
