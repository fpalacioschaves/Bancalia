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
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
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

$examenService = new ExamenService(pdo());

// Cargar examen actual
$examen = $examenService->find($id);

if (!$examen) {
  http_response_code(404);
  exit('Examen no encontrado.');
}

// Procesado del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $examenService->update($id, $_POST);

    flash('success', 'Examen actualizado correctamente.');
    header('Location: ' . PUBLIC_URL . '/admin/examenes/index.php');
    exit;

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
    <h1 class="text-xl font-semibold tracking-tight">Editar examen / hoja de actividades</h1>
    <p class="mt-1 text-sm text-slate-600">
      Modifica los datos del examen o práctica seleccionada.
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
    <input type="hidden" name="id" value="<?= (int) $examen['id'] ?>">

    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label for="profesor_id" class="mb-1 block text-sm font-medium text-slate-700">
          Profesor <span class="text-rose-600">*</span>
        </label>
        <select id="profesor_id" name="profesor_id" required
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
          <option value="">Selecciona un profesor…</option>
          <?php foreach ($profes as $p): ?>
            <option value="<?= (int) $p['id'] ?>" <?= (int) $examen['profesor_id'] === (int) $p['id'] ? 'selected' : '' ?>>
              <?= h($p['apellidos'] . ', ' . $p['nombre']) ?>
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
            <option value="<?= (int) $f['id'] ?>" <?= (int) $examen['familia_id'] === (int) $f['id'] ? 'selected' : '' ?>>
              <?= h($f['nombre']) ?>
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
            <option value="<?= (int) $c['id'] ?>" <?= (int) $examen['curso_id'] === (int) $c['id'] ? 'selected' : '' ?>>
              <?= h($c['familia'] . ' · ' . $c['nombre']) ?>
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
            <option value="<?= (int) $a['id'] ?>" <?= (int) $examen['asignatura_id'] === (int) $a['id'] ? 'selected' : '' ?>>
              <?= h($a['familia'] . ' · ' . $a['curso'] . ' · ' . $a['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div>
      <label for="titulo" class="mb-1 block text-sm font-medium text-slate-700">
        Título <span class="text-rose-600">*</span>
      </label>
      <input id="titulo" name="titulo" type="text" required value="<?= h($examen['titulo']) ?>"
        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
    </div>

    <div>
      <label for="descripcion" class="mb-1 block text-sm font-medium text-slate-700">
        Descripción (opcional)
      </label>
      <textarea id="descripcion" name="descripcion" rows="4"
        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
        placeholder="Añade detalles sobre el examen o la práctica (contenidos, indicaciones para el alumnado, etc.)…"><?= h((string) $examen['descripcion']) ?></textarea>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <div>
        <label for="tipo" class="mb-1 block text-sm font-medium text-slate-700">
          Tipo de prueba
        </label>
        <select id="tipo" name="tipo"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
          <?php
          $tipoActual = $examen['tipo'] ?? 'examen';
          ?>
          <option value="examen" <?= $tipoActual === 'examen' ? 'selected' : '' ?>>Examen formal</option>
          <option value="practica" <?= $tipoActual === 'practica' ? 'selected' : '' ?>>Hoja de actividades / práctica
          </option>
        </select>
        <p class="mt-1 text-xs text-slate-500">
          El tipo "Hoja de actividades" se puede usar para tareas o prácticas no necesariamente evaluables.
        </p>
      </div>

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
        <label for="duracion_minutos" class="mb-1 block text-sm font-medium text-slate-700">
          Duración (minutos)
        </label>
        <input id="duracion_minutos" name="duracion_minutos" type="number" min="1" step="1"
          value="<?= h((string) $examen['duracion_minutos']) ?>"
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