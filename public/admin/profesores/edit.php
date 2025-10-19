<?php
// /public/admin/profesores/edit.php
declare(strict_types=1);
require_once __DIR__ . '/../../../middleware/require_admin.php';

// --------- CARGA PREVIA ---------
$id = (int)($_GET['id'] ?? 0);

// Datos de referencia para selects
$centros = pdo()->query('SELECT id, nombre FROM centros WHERE is_active=1 ORDER BY nombre ASC')->fetchAll();
$fams = pdo()->query('SELECT id, nombre FROM familias_profesionales WHERE is_active=1 ORDER BY nombre ASC')->fetchAll();
$cursos = pdo()->query('SELECT id, nombre, familia_id, orden FROM cursos WHERE is_active=1 ORDER BY familia_id ASC, orden ASC, nombre ASC')->fetchAll();
$asigs = pdo()->query('SELECT id, nombre, curso_id, familia_id, orden FROM asignaturas WHERE is_active=1 ORDER BY familia_id ASC, curso_id ASC, orden ASC, nombre ASC')->fetchAll();

// Profesor
$st = pdo()->prepare('SELECT * FROM profesores WHERE id=:id LIMIT 1');
$st->execute([':id'=>$id]);
$prof = $st->fetch();
if (!$prof) {
  flash('error','Profesor no encontrado.');
  header('Location: ' . PUBLIC_URL . '/admin/profesores/index.php');
  exit;
}

// Asignaciones actuales
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
$asignaciones->execute([':p'=>$id]);
$asigRows = $asignaciones->fetchAll();

// --------- POST (ACTUALIZAR) ---------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    // ----- Datos profesor -----
    $centro_id = ($_POST['centro_id'] ?? '') !== '' ? (int)$_POST['centro_id'] : null;
    $nombre    = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $notas     = trim($_POST['notas'] ?? '');
    $activo    = isset($_POST['is_active']) ? 1 : 0;

    if ($nombre === '' || $apellidos === '') throw new RuntimeException('Nombre y apellidos son obligatorios.');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email no válido.');

    // Email único (excluye el propio)
    $chk = pdo()->prepare('SELECT 1 FROM profesores WHERE email=:e AND id<>:id LIMIT 1');
    $chk->execute([':e'=>$email, ':id'=>$id]);
    if ($chk->fetch()) throw new RuntimeException('Ya existe otro profesor con ese email.');

    // Centro válido si viene
    if ($centro_id !== null) {
      $chkC = pdo()->prepare('SELECT 1 FROM centros WHERE id=:id LIMIT 1');
      $chkC->execute([':id'=>$centro_id]);
      if (!$chkC->fetch()) throw new RuntimeException('El centro seleccionado no existe.');
    }

    // Actualizar profesor
    $up = pdo()->prepare('
      UPDATE profesores
      SET centro_id=:c, nombre=:n, apellidos=:a, email=:e, telefono=:t, notas=:no, is_active=:ac
      WHERE id=:id
    ');
    $up->execute([
      ':c'=>$centro_id, ':n'=>$nombre, ':a'=>$apellidos, ':e'=>$email,
      ':t'=>($telefono !== '' ? $telefono : null),
      ':no'=>($notas !== '' ? $notas : null),
      ':ac'=>$activo, ':id'=>$id
    ]);

    // ----- Asignaciones -----
    // Mapa de coherencia: curso->familia, asig->curso
    $cursoToFamilia = [];
    foreach ($cursos as $c) $cursoToFamilia[(int)$c['id']] = (int)$c['familia_id'];
    $asigToCurso = [];
    foreach ($asigs as $a) $asigToCurso[(int)$a['id']] = (int)$a['curso_id'];

    // Arrays paralelos desde el form
    $paIds   = $_POST['pa_id']            ?? []; // id de profesor_asignacion (vacío si es nueva)
    $famArr  = $_POST['asig_familia_id']  ?? [];
    $curArr  = $_POST['asig_curso_id']    ?? [];
    $asiArr  = $_POST['asig_asignatura_id'] ?? [];
    $anioArr = $_POST['asig_anio']        ?? [];
    $hrsArr  = $_POST['asig_horas']       ?? [];
    $obsArr  = $_POST['asig_obs']         ?? [];
    $delArr  = $_POST['asig_delete']      ?? []; // índices marcados para borrar

    // Normaliza índices (usamos el máximo de arrays)
    $maxLen = max(count($famArr), count($curArr), count($asiArr), count($paIds));

    $insAsig = pdo()->prepare('
      INSERT INTO profesor_asignacion (profesor_id, centro_id, familia_id, curso_id, asignatura_id, anio_academico, horas, observaciones, is_active)
      VALUES (:p, :centro, :fam, :curso, :asig, :anio, :hrs, :obs, 1)
    ');
    $upAsig = pdo()->prepare('
      UPDATE profesor_asignacion
      SET familia_id=:fam, curso_id=:curso, asignatura_id=:asig, anio_academico=:anio, horas=:hrs, observaciones=:obs
      WHERE id=:id AND profesor_id=:p
    ');
    $delAsig = pdo()->prepare('DELETE FROM profesor_asignacion WHERE id=:id AND profesor_id=:p');

    for ($i=0; $i<$maxLen; $i++) {
      $paId = (int)($paIds[$i] ?? 0);
      $fam  = (int)($famArr[$i] ?? 0);
      $cur  = (int)($curArr[$i] ?? 0);
      $asi  = (int)($asiArr[$i] ?? 0);
      $anio = trim($anioArr[$i] ?? '');
      $hrs  = trim($hrsArr[$i] ?? '');
      $obs  = trim($obsArr[$i] ?? '');

      $deleteThis = in_array((string)$i, $delArr, true);

      // Si es fila existente y marcada para borrar → borrar y seguir
      if ($paId > 0 && $deleteThis) {
        $delAsig->execute([':id'=>$paId, ':p'=>$id]);
        continue;
      }

      // Fila totalmente vacía → ignorar
      $allEmpty = ($fam===0 && $cur===0 && $asi===0 && $anio==='' && $hrs==='' && $obs==='');
      if ($allEmpty) continue;

      // Validaciones mínimas para nuevas o actualizaciones
      if ($fam <= 0)  throw new RuntimeException('En una asignación falta la familia/grado.');
      if ($cur <= 0)  throw new RuntimeException('En una asignación falta el curso.');
      if ($asi <= 0)  throw new RuntimeException('En una asignación falta la asignatura.');
      if ($anio === '') throw new RuntimeException('En una asignación falta el año académico (ej. 2025-2026).');

      // Coherencia curso->familia
      if (!isset($cursoToFamilia[$cur]) || $cursoToFamilia[$cur] !== $fam) {
        throw new RuntimeException('Una asignación no es coherente: el curso no pertenece a la familia seleccionada.');
      }
      // Coherencia asignatura->curso
      if (!isset($asigToCurso[$asi]) || $asigToCurso[$asi] !== $cur) {
        throw new RuntimeException('Una asignación no es coherente: la asignatura no pertenece al curso seleccionado.');
      }

      $hrsVal = ($hrs !== '' ? max(0, (int)$hrs) : null);

      if ($paId > 0) {
        // UPDATE
        $upAsig->execute([
          ':fam'=>$fam, ':curso'=>$cur, ':asig'=>$asi, ':anio'=>$anio,
          ':hrs'=>$hrsVal, ':obs'=>($obs !== '' ? $obs : null),
          ':id'=>$paId, ':p'=>$id
        ]);
      } else {
        // INSERT (usa centro del profesor)
        $insAsig->execute([
          ':p'=>$id, ':centro'=>$centro_id, ':fam'=>$fam, ':curso'=>$cur, ':asig'=>$asi,
          ':anio'=>$anio, ':hrs'=>$hrsVal, ':obs'=>($obs !== '' ? $obs : null)
        ]);
      }
    }

    flash('success','Profesor actualizado.');
    header('Location: ' . PUBLIC_URL . '/admin/profesores/edit.php?id='.$id);
    exit;

  } catch (Throwable $e) {
    // Tratar posibles violaciones de UNIQUE uq_pa
    $msg = $e->getMessage();
    if (stripos($msg, 'uq_pa') !== false) {
      $msg = 'Asignación duplicada (mismo centro/familia/curso/asignatura/año).';
    }
    flash('error', $msg);
    header('Location: ' . PUBLIC_URL . '/admin/profesores/edit.php?id='.$id);
    exit;
  }
}

require_once __DIR__ . '/../../../partials/header.php';
?>

<div class="mb-6 flex items-center justify-between">
  <div>
    <h1 class="text-xl font-semibold tracking-tight">Editar profesor</h1>
    <p class="mt-1 text-sm text-slate-600">Actualiza datos y sus asignaciones.</p>
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
               value="<?= htmlspecialchars($prof['nombre']) ?>"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
      </div>
      <div>
        <label for="apellidos" class="mb-1 block text-sm font-medium text-slate-700">Apellidos</label>
        <input id="apellidos" name="apellidos" type="text" required
               value="<?= htmlspecialchars($prof['apellidos']) ?>"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <div>
        <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
        <input id="email" name="email" type="email" required
               value="<?= htmlspecialchars($prof['email']) ?>"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
      </div>
      <div>
        <label for="telefono" class="mb-1 block text-sm font-medium text-slate-700">Teléfono</label>
        <input id="telefono" name="telefono" type="text"
               value="<?= htmlspecialchars((string)$prof['telefono']) ?>"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
      </div>
      <div>
        <label for="centro_id" class="mb-1 block text-sm font-medium text-slate-700">Centro</label>
        <select id="centro_id" name="centro_id"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
          <option value="">— Selecciona centro —</option>
          <?php foreach ($centros as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (int)$prof['centro_id']===(int)$c['id']?'selected':'' ?>>
              <?= htmlspecialchars($c['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="mt-1 text-xs text-slate-500">Se aplicará a las nuevas asignaciones.</p>
      </div>
    </div>

    <div>
      <label for="notas" class="mb-1 block text-sm font-medium text-slate-700">Notas</label>
      <textarea id="notas" name="notas" rows="3"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400"
      ><?= htmlspecialchars((string)$prof['notas']) ?></textarea>
    </div>

    <div class="flex items-center gap-2">
      <input id="is_active" name="is_active" type="checkbox"
             class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
             <?= (int)$prof['is_active']===1 ? 'checked' : '' ?>>
      <label for="is_active" class="text-sm text-slate-700">Activo</label>
    </div>

    <!-- Asignaciones -->
    <div class="pt-2">
      <div class="mb-3 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-700">Asignaciones</h2>
        <button type="button" id="btnAddRow"
                class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-1.5 text-sm font-semibold text-white hover:bg-slate-800">
          + Añadir asignación
        </button>
      </div>

      <div class="overflow-hidden rounded-xl border border-slate-200">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Familia/Grado</th>
              <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Curso</th>
              <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Asignatura</th>
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
                  <input type="hidden" name="pa_id[]" value="<?= (int)$r['id'] ?>">
                  <select name="asig_familia_id[]" class="famSel w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
                    <option value="">— Familia/Grado —</option>
                    <?php foreach ($fams as $f): ?>
                      <option value="<?= (int)$f['id'] ?>" <?= (int)$r['familia_id']===(int)$f['id']?'selected':'' ?>>
                        <?= htmlspecialchars($f['nombre']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td class="px-3 py-2">
                  <select name="asig_curso_id[]" class="cursoSel w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
                    <!-- opciones por JS -->
                  </select>
                </td>
                <td class="px-3 py-2">
                  <select name="asig_asignatura_id[]" class="asigSel w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
                    <!-- opciones por JS -->
                  </select>
                </td>
                <td class="px-3 py-2">
                  <input name="asig_anio[]" type="text" value="<?= htmlspecialchars($r['anio_academico']) ?>" placeholder="2025-2026"
                         class="w-28 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
                </td>
                <td class="px-3 py-2">
                  <input name="asig_horas[]" type="number" min="0" value="<?= htmlspecialchars((string)$r['horas']) ?>"
                         class="w-20 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
                </td>
                <td class="px-3 py-2">
                  <input name="asig_obs[]" type="text" value="<?= htmlspecialchars((string)$r['observaciones']) ?>"
                         class="w-48 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400" placeholder="Notas">
                </td>
                <td class="px-3 py-2 text-right">
                  <label class="inline-flex items-center gap-1 text-xs text-rose-700">
                    <input type="checkbox" name="asig_delete[]" value="<?= $idx ?>" class="h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-400">
                    Quitar
                  </label>
                </td>
              </tr>
            <?php endforeach; ?>
            <!-- Nuevas filas se añadirán aquí por JS -->
          </tbody>
        </table>
      </div>

      <p class="mt-2 text-xs text-slate-500">Los selects se filtran en cascada. Marca “Quitar” para eliminar una asignación existente.</p>
    </div>

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
      <a href="<?= PUBLIC_URL ?>/admin/profesores/index.php"
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

<script>
  // Datos en cliente para dependencias
  const familias = <?php echo json_encode($fams, JSON_UNESCAPED_UNICODE); ?>;
  const cursosAll = <?php echo json_encode($cursos, JSON_UNESCAPED_UNICODE); ?>;
  const asigsAll  = <?php echo json_encode($asigs,  JSON_UNESCAPED_UNICODE); ?>;

  const cursosByFam = {};
  cursosAll.forEach(c => { (cursosByFam[c.familia_id] ||= []).push({id:c.id, nombre:c.nombre}); });
  const asigByCurso = {};
  asigsAll.forEach(a => { (asigByCurso[a.curso_id] ||= []).push({id:a.id, nombre:a.nombre}); });

  function opt(val, label) { const o = document.createElement('option'); o.value = val; o.textContent = label; return o; }

  function renderCursosSelect(sel, familiaId, selectedId = null) {
    sel.innerHTML = '';
    sel.appendChild(opt('', '— Curso —'));
    (cursosByFam[familiaId] || []).forEach(c => {
      const o = opt(c.id, c.nombre);
      if (selectedId && String(selectedId) === String(c.id)) o.selected = true;
      sel.appendChild(o);
    });
  }

  function renderAsigSelect(sel, cursoId, selectedId = null) {
    sel.innerHTML = '';
    sel.appendChild(opt('', '— Asignatura —'));
    (asigByCurso[cursoId] || []).forEach(a => {
      const o = opt(a.id, a.nombre);
      if (selectedId && String(selectedId) === String(a.id)) o.selected = true;
      sel.appendChild(o);
    });
  }

  // Inicializa filas existentes (PHP las pintó, pero hay que cargar cursos/asig correctos)
  document.querySelectorAll('#rowsBody tr').forEach((tr) => {
    const selFam   = tr.querySelector('.famSel');
    const selCurso = tr.querySelector('.cursoSel');
    const selAsig  = tr.querySelector('.asigSel');

    // Lee valores actuales desde atributos selected del HTML (ya puestos por PHP)
    const famVal = selFam.value || '';
    const cursoVal = '<?= json_encode(array_column($asigRows, "curso_id")) ?>'; // no lo usamos directo
    // Mejor, usamos dataset a partir de inputs ocultos generados:
    // Como no tenemos dataset, pedimos a PHP que nos inyecte valores por celda:
  });

  // Para inyectar los valores seleccionados por fila sin datasets, rehacemos con PHP un arreglo JS:
  const existingRows = <?php
    $arr = [];
    foreach ($asigRows as $r) {
      $arr[] = [
        'familia_id' => (int)$r['familia_id'],
        'curso_id' => (int)$r['curso_id'],
        'asignatura_id' => (int)$r['asignatura_id'],
      ];
    }
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  ?>;

  // Aplica selects dependientes en las filas existentes, manteniendo selección
  document.querySelectorAll('#rowsBody tr').forEach((tr, idx) => {
    const selFam   = tr.querySelector('.famSel');
    const selCurso = tr.querySelector('.cursoSel');
    const selAsig  = tr.querySelector('.asigSel');

    const preset = existingRows[idx] || {};
    const famId = parseInt(selFam.value || preset.familia_id || '0', 10);
    renderCursosSelect(selCurso, famId, preset.curso_id);
    renderAsigSelect(selAsig, preset.curso_id, preset.asignatura_id);

    selFam.addEventListener('change', () => {
      const fid = parseInt(selFam.value || '0', 10);
      renderCursosSelect(selCurso, fid, null);
      renderAsigSelect(selAsig, 0, null);
    }, { passive: true });

    selCurso.addEventListener('change', () => {
      const cid = parseInt(selCurso.value || '0', 10);
      renderAsigSelect(selAsig, cid, null);
    }, { passive: true });
  });

  // Añadir nuevas filas
  const rowsBody = document.getElementById('rowsBody');
  const btnAddRow = document.getElementById('btnAddRow');

  function addRow() {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td class="px-3 py-2">
        <input type="hidden" name="pa_id[]" value="">
        <select name="asig_familia_id[]" class="famSel w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
          <option value="">— Familia/Grado —</option>
          ${familias.map(f => `<option value="${f.id}">${f.nombre}</option>`).join('')}
        </select>
      </td>
      <td class="px-3 py-2">
        <select name="asig_curso_id[]" class="cursoSel w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
          <option value="">— Curso —</option>
        </select>
      </td>
      <td class="px-3 py-2">
        <select name="asig_asignatura_id[]" class="asigSel w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
          <option value="">— Asignatura —</option>
        </select>
      </td>
      <td class="px-3 py-2">
        <input name="asig_anio[]" type="text" placeholder="2025-2026"
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
        <button type="button" class="inline-flex items-center rounded-lg bg-rose-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-rose-500 btnDelNew">Quitar</button>
      </td>
    `;
    const selFam   = tr.querySelector('.famSel');
    const selCurso = tr.querySelector('.cursoSel');
    const selAsig  = tr.querySelector('.asigSel');

    selFam.addEventListener('change', () => {
      const fid = parseInt(selFam.value || '0', 10);
      renderCursosSelect(selCurso, fid, null);
      renderAsigSelect(selAsig, 0, null);
    }, { passive: true });

    selCurso.addEventListener('change', () => {
      const cid = parseInt(selCurso.value || '0', 10);
      renderAsigSelect(selAsig, cid, null);
    }, { passive: true });

    tr.querySelector('.btnDelNew').addEventListener('click', () => tr.remove());

    rowsBody.appendChild(tr);
  }

  btnAddRow.addEventListener('click', addRow);
</script>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>
