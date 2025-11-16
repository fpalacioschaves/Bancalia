<?php
// /public/admin/examenes/create.php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();
$u = current_user();

// NO restringimos solo a admin: también pueden entrar profesores si el menú les da acceso.
// if (($u['role'] ?? '') !== 'admin') {
//   flash('error', 'Acceso restringido a administradores.');
//   header('Location: ' . PUBLIC_URL . '/dashboard.php');
//   exit;
// }

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
    $tipo           = $_POST['tipo'] ?? 'examen';

    if ($profesor_id <= 0)   throw new RuntimeException('Selecciona un profesor.');
    if ($familia_id <= 0)    throw new RuntimeException('Selecciona una familia.');
    if ($curso_id <= 0)      throw new RuntimeException('Selecciona un curso.');
    if ($asignatura_id <= 0) throw new RuntimeException('Selecciona una asignatura.');
    if ($titulo === '')      throw new RuntimeException('El título es obligatorio.');

    if (!in_array($estado, ['borrador', 'publicado'], true)) {
      $estado = 'borrador';
    }

    if (!in_array($tipo, ['examen', 'practica'], true)) {
      $tipo = 'examen';
    }

    $fecha = $fecha_raw !== '' ? $fecha_raw : null; // YYYY-MM-DD
    $hora  = $hora_raw  !== '' ? $hora_raw  : null; // HH:MM
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

    // Inserción EXACTA según estructura de `examenes` + campo `tipo`
    $ins = pdo()->prepare('
      INSERT INTO examenes (
        profesor_id,
        familia_id,
        curso_id,
        asignatura_id,
        titulo,
        descripcion,
        estado,
        tipo,
        fecha,
        hora,
        duracion_minutos
      ) VALUES (
        :profesor_id,
        :familia_id,
        :curso_id,
        :asignatura_id,
        :titulo,
        :descripcion,
        :estado,
        :tipo,
        :fecha,
        :hora,
        :duracion_minutos
      )
    ');

    $ins->execute([
      ':profesor_id'      => $profesor_id,
      ':familia_id'       => $familia_id,
      ':curso_id'         => $curso_id,
      ':asignatura_id'    => $asignatura_id,
      ':titulo'           => $titulo,
      ':descripcion'      => $descripcion !== '' ? $descripcion : null,
      ':estado'           => $estado,
      ':tipo'             => $tipo,
      ':fecha'            => $fecha,
      ':hora'             => $hora,
      ':duracion_minutos' => $duracion_minutos
    ]);

    flash('success', 'Examen creado correctamente.');
    header('Location: ' . PUBLIC_URL . '/admin/examenes/index.php');
    exit;

  } catch (Throwable $e) {
    flash('error', $e->getMessage());
    header('Location: ' . PUBLIC_URL . '/admin/examenes/create.php');
    exit;
  }
}

require_once __DIR__ . '/../../../partials/header.php';
?>

<div class="mb-6 flex items-center justify-between">
  <div>
    <h1 class="text-xl font-semibold tracking-tight">Nuevo examen / hoja de actividades</h1>
    <p class="mt-1 text-sm text-slate-600">
      Crea un examen formal o una hoja de actividades asociada a profesor, familia, curso y asignatura.
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

    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label for="profesor_id" class="mb-1 block text-sm font-medium text-slate-700">
          Profesor <span class="text-rose-600">*</span>
        </label>
        <select id="profesor_id" name="profesor_id" required
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
          <option value="">Selecciona un profesor…</option>
          <?php foreach ($profes as $p): ?>
            <option value="<?= (int)$p['id'] ?>">
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
            <option value="<?= (int)$f['id'] ?>">
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
            <option value="<?= (int)$c['id'] ?>">
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
            <option value="<?= (int)$a['id'] ?>">
              <?= htmlspecialchars($a['familia'] . ' · ' . $a['curso'] . ' · ' . $a['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div>
      <label for="titulo" class="mb-1 block text-sm font-medium text-slate-700">
        Título <span class="text-rose-600">*</span>
      </label>
      <input id="titulo" name="titulo" type="text" required
             placeholder='Ej. "Examen 1ª Evaluación – Bases de Datos" o "Práctica Tema 3 – Arrays"'
             class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
    </div>

    <div>
      <label for="descripcion" class="mb-1 block text-sm font-medium text-slate-700">
        Descripción (opcional)
      </label>
      <textarea id="descripcion" name="descripcion" rows="4"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
                placeholder="Añade detalles sobre el examen o la hoja de actividades (contenidos, indicaciones para el alumnado, etc.)…"></textarea>
    </div>

    <!-- Tipo de prueba: examen o práctica -->
    <div class="grid gap-4 sm:grid-cols-3">
      <div>
        <label for="tipo" class="mb-1 block text-sm font-medium text-slate-700">
          Tipo de prueba
        </label>
        <select id="tipo" name="tipo"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
          <option value="examen" selected>Examen formal</option>
          <option value="practica">Hoja de actividades / práctica</option>
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
          <option value="borrador" selected>Borrador</option>
          <option value="publicado">Publicado</option>
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
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
               placeholder="Ej. 60">
        <p class="mt-1 text-xs text-slate-500">
          Puedes dejarlo vacío si no quieres fijar duración.
        </p>
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label for="fecha" class="mb-1 block text-sm font-medium text-slate-700">
          Fecha del examen / práctica
        </label>
        <input id="fecha" name="fecha" type="date"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
      </div>

      <div>
        <label for="hora" class="mb-1 block text-sm font-medium text-slate-700">
          Hora
        </label>
        <input id="hora" name="hora" type="time"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
      </div>
    </div>

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
      <a href="<?= PUBLIC_URL ?>/admin/examenes/index.php"
         class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
        Cancelar
      </a>
      <button type="submit"
              class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 active:scale-[0.99] transition">
        Crear examen
      </button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>

