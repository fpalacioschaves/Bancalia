<?php
// /public/mi-perfil.php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/require_auth.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../partials/header.php';

$u = current_user();

/**
 * --- AJUSTE CLAVE ---
 * No redirigimos a dashboard si falta profesor_id:
 * - Si no es admin y no hay profesor_id en sesión, intentamos resolverlo por email.
 * - Si no existe el profesor, lo creamos mínimo y guardamos profesor_id en sesión.
 * - Solo si no hay manera de identificarlo, mostramos un flash y seguimos en la misma página (sin redirect).
 */
if (($u['role'] ?? '') !== 'admin') {
  $profesorId = (int)($u['profesor_id'] ?? 0);

  if ($profesorId <= 0) {
    // 1) Intentar por email
    $email = trim((string)($u['email'] ?? ''));
    if ($email !== '') {
      $stFind = pdo()->prepare('SELECT id FROM profesores WHERE email = :e LIMIT 1');
      $stFind->execute([':e' => $email]);
      if ($row = $stFind->fetch()) {
        $profesorId = (int)$row['id'];
        $_SESSION['user']['profesor_id'] = $profesorId; // persistimos en sesión
      }
    }

    // 2) Si aún no existe, lo creamos mínimo
    if ($profesorId <= 0) {
      $ins = pdo()->prepare('
        INSERT INTO profesores (nombre, apellidos, email, is_active, created_at, updated_at)
        VALUES (:n, :a, :e, 1, NOW(), NOW())
      ');
      $nombre    = trim((string)($u['nombre'] ?? $u['username'] ?? 'Profesor'));
      $apellidos = '';
      $emailDb   = ($email !== '' ? $email : null);
      $ins->execute([':n'=>$nombre, ':a'=>$apellidos, ':e'=>$emailDb]);

      $profesorId = (int)pdo()->lastInsertId();
      $_SESSION['user']['profesor_id'] = $profesorId;
      // Info suave; no redirigimos.
      if (function_exists('flash')) {
        flash('success', 'Se ha inicializado tu ficha de profesor.');
      }
    }
  }
} else {
  // Admin puede editar cualquier perfil, pero aquí usamos su propio profesor_id si lo tiene
  $profesorId = (int)($u['profesor_id'] ?? 0);
}

// Si aún no hay profesor_id a estas alturas, mostramos aviso (sin redirigir)
if ($profesorId <= 0 && function_exists('flash')) {
  flash('error', 'No ha sido posible identificar tu perfil de profesor.');
}

// Datos de referencia
$centros = pdo()->query('SELECT id, nombre FROM centros WHERE is_active=1 ORDER BY nombre ASC')->fetchAll();
$fams    = pdo()->query('SELECT id, nombre FROM familias_profesionales WHERE is_active=1 ORDER BY nombre ASC')->fetchAll();
$cursos  = pdo()->query('SELECT id, nombre, familia_id, orden FROM cursos WHERE is_active=1 ORDER BY familia_id ASC, orden ASC, nombre ASC')->fetchAll();
$asigs   = pdo()->query('SELECT id, nombre, curso_id, familia_id, orden FROM asignaturas WHERE is_active=1 ORDER BY familia_id ASC, curso_id ASC, orden ASC, nombre ASC')->fetchAll();

// Profesor
$prof = null;
if ($profesorId > 0) {
  $st = pdo()->prepare('SELECT * FROM profesores WHERE id=:id LIMIT 1');
  $st->execute([':id'=>$profesorId]);
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
  $asignaciones->execute([':p'=>$profesorId]);
  $asigRows = $asignaciones->fetchAll();
}

// POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $centro_id = ($_POST['centro_id'] ?? '') !== '' ? (int)$_POST['centro_id'] : null;
    $nombre    = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $notas     = trim($_POST['notas'] ?? '');
    $activo    = isset($_POST['is_active']) ? 1 : 0;

    if ($profesorId <= 0) {
      throw new RuntimeException('Tu ficha de profesor no está inicializada.');
    }
    if ($nombre === '' || $apellidos === '') throw new RuntimeException('Nombre y apellidos son obligatorios.');
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email no válido.');

    // Centro válido si viene
    if ($centro_id !== null) {
      $chkC = pdo()->prepare('SELECT 1 FROM centros WHERE id=:id LIMIT 1');
      $chkC->execute([':id'=>$centro_id]);
      if (!$chkC->fetch()) throw new RuntimeException('El centro seleccionado no existe.');
    }

    // Update profesor
    $up = pdo()->prepare('
      UPDATE profesores
      SET centro_id=:c, nombre=:n, apellidos=:a, email=:e, telefono=:t, notas=:no, is_active=:ac, updated_at=NOW()
      WHERE id=:id
    ');
    $up->execute([
      ':c'=>$centro_id, ':n'=>$nombre, ':a'=>$apellidos, ':e'=>($email!==''?$email:null),
      ':t'=>($telefono !== '' ? $telefono : null),
      ':no'=>($notas !== '' ? $notas : null),
      ':ac'=>$activo, ':id'=>$profesorId
    ]);

    // Coherencias para asignaciones
    $cursoToFamilia = [];
    foreach ($cursos as $c) $cursoToFamilia[(int)$c['id']] = (int)$c['familia_id'];
    $asigToCurso = [];
    foreach ($asigs as $a) $asigToCurso[(int)$a['id']] = (int)$a['curso_id'];

    $paIds   = $_POST['pa_id'] ?? [];
    $famArr  = $_POST['asig_familia_id'] ?? [];
    $curArr  = $_POST['asig_curso_id'] ?? [];
    $asiArr  = $_POST['asig_asignatura_id'] ?? [];
    $anioArr = $_POST['asig_anio'] ?? [];
    $hrsArr  = $_POST['asig_horas'] ?? [];
    $obsArr  = $_POST['asig_obs'] ?? [];
    $delArr  = $_POST['asig_delete'] ?? [];

    $maxLen = max(count($famArr),count($curArr),count($asiArr),count($paIds));

    $insAsig = pdo()->prepare('
      INSERT INTO profesor_asignacion (profesor_id, centro_id, familia_id, curso_id, asignatura_id, anio_academico, horas, observaciones, is_active, created_at, updated_at)
      VALUES (:p, :centro, :fam, :curso, :asig, :anio, :hrs, :obs, 1, NOW(), NOW())
    ');
    $upAsig = pdo()->prepare('
      UPDATE profesor_asignacion
      SET familia_id=:fam, curso_id=:curso, asignatura_id=:asig, anio_academico=:anio, horas=:hrs, observaciones=:obs, updated_at=NOW()
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

      if ($paId > 0 && $deleteThis) {
        $delAsig->execute([':id'=>$paId, ':p'=>$profesorId]);
        continue;
      }

      $allEmpty = ($fam===0 && $cur===0 && $asi===0 && $anio==='' && $hrs==='' && $obs==='');
      if ($allEmpty) continue;

      if ($fam<=0 || $cur<=0 || $asi<=0 || $anio==='') {
        throw new RuntimeException('Completa familia, curso, asignatura y año en cada asignación.');
      }
      if (($cursoToFamilia[$cur] ?? 0) !== $fam) {
        throw new RuntimeException('El curso no pertenece a la familia seleccionada.');
      }
      if (($asigToCurso[$asi] ?? 0) !== $cur) {
        throw new RuntimeException('La asignatura no pertenece al curso seleccionado.');
      }

      $hrsVal = ($hrs !== '' ? max(0,(int)$hrs) : null);

      if ($paId > 0) {
        $upAsig->execute([
          ':fam'=>$fam, ':curso'=>$cur, ':asig'=>$asi, ':anio'=>$anio,
          ':hrs'=>$hrsVal, ':obs'=>($obs!==''?$obs:null),
          ':id'=>$paId, ':p'=>$profesorId
        ]);
      } else {
        $insAsig->execute([
          ':p'=>$profesorId, ':centro'=>$centro_id, ':fam'=>$fam, ':curso'=>$cur, ':asig'=>$asi,
          ':anio'=>$anio, ':hrs'=>$hrsVal, ':obs'=>($obs!==''?$obs:null)
        ]);
      }
    }

    if (function_exists('flash')) flash('success','Perfil actualizado.');
    header('Location: ' . PUBLIC_URL . '/mi-perfil.php'); exit;

  } catch (Throwable $e) {
    $msg = $e->getMessage();
    if (stripos($msg, 'uq_pa') !== false) $msg = 'Asignación duplicada en el mismo año.';
    if (function_exists('flash')) flash('error',$msg);
    header('Location: ' . PUBLIC_URL . '/mi-perfil.php'); exit;
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
    <?= csrf_field() ?>

    <div class="grid gap-4 sm:grid-cols-3">
      <div class="sm:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Nombre</label>
        <input name="nombre" type="text" required value="<?= htmlspecialchars($prof['nombre'] ?? '') ?>"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Apellidos</label>
        <input name="apellidos" type="text" required value="<?= htmlspecialchars($prof['apellidos'] ?? '') ?>"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
        <input name="email" type="email" value="<?= htmlspecialchars((string)($prof['email'] ?? '')) ?>"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Teléfono</label>
        <input name="telefono" type="text" value="<?= htmlspecialchars((string)($prof['telefono'] ?? '')) ?>"
               class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Centro</label>
        <select name="centro_id" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
          <option value="">— Selecciona centro —</option>
          <?php foreach ($centros as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ((int)($prof['centro_id'] ?? 0)===(int)$c['id'])?'selected':'' ?>>
              <?= htmlspecialchars($c['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="mt-1 text-xs text-slate-500">Se aplicará a las nuevas asignaciones.</p>
      </div>
    </div>

    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700">Notas</label>
      <textarea name="notas" rows="3"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400"><?= htmlspecialchars((string)($prof['notas'] ?? '')) ?></textarea>
    </div>

    <div class="flex items-center gap-2">
      <input id="is_active" name="is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400"
             <?= ((int)($prof['is_active'] ?? 1)===1 ? 'checked' : '') ?>>
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
                  <select name="asig_familia_id[]" class="famSel w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:ring-2 focus:ring-slate-400">
                    <option value="">— Familia/Grado —</option>
                    <?php foreach ($fams as $f): ?>
                      <option value="<?= (int)$f['id'] ?>" <?= (int)$r['familia_id']===(int)$f['id']?'selected':'' ?>>
                        <?= htmlspecialchars($f['nombre']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td class="px-3 py-2">
                  <select name="asig_curso_id[]" class="cursoSel w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:ring-2 focus:ring-slate-400"></select>
                </td>
                <td class="px-3 py-2">
                  <select name="asig_asignatura_id[]" class="asigSel w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:ring-2 focus:ring-slate-400"></select>
                </td>
                <td class="px-3 py-2">
                  <input name="asig_anio[]" type="text" value="<?= htmlspecialchars($r['anio_academico']) ?>" placeholder="2025-2026"
                         class="w-28 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:ring-2 focus:ring-slate-400">
                </td>
                <td class="px-3 py-2">
                  <input name="asig_horas[]" type="number" min="0" value="<?= htmlspecialchars((string)$r['horas']) ?>"
                         class="w-20 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:ring-2 focus:ring-slate-400">
                </td>
                <td class="px-3 py-2">
                  <input name="asig_obs[]" type="text" value="<?= htmlspecialchars((string)$r['observaciones']) ?>"
                         class="w-48 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:ring-2 focus:ring-slate-400" placeholder="Notas">
                </td>
                <td class="px-3 py-2 text-right">
                  <label class="inline-flex items-center gap-1 text-xs text-rose-700">
                    <input type="checkbox" name="asig_delete[]" value="<?= $idx ?>" class="h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-400">
                    Quitar
                  </label>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <p class="mt-2 text-xs text-slate-500">Los select se filtran en cascada. Marca “Quitar” para eliminar una asignación existente.</p>
    </div>

    <div class="flex justify-end">
      <button type="submit"
              class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
        Guardar cambios
      </button>
    </div>
  </form>
</div>

<script>
  const familias = <?php echo json_encode($fams, JSON_UNESCAPED_UNICODE); ?>;
  const cursosAll = <?php echo json_encode($cursos, JSON_UNESCAPED_UNICODE); ?>;
  const asigsAll  = <?php echo json_encode($asigs,  JSON_UNESCAPED_UNICODE); ?>;

  const cursosByFam = {};
  cursosAll.forEach(c => { (cursosByFam[c.familia_id] ||= []).push({id:c.id, nombre:c.nombre}); });
  const asigByCurso = {};
  asigsAll.forEach(a => { (asigByCurso[a.curso_id] ||= []).push({id:a.id, nombre:a.nombre}); });

  function opt(v,t){const o=document.createElement('option');o.value=v;o.textContent=t;return o;}

  function renderCursos(sel, fid, selected=null){
    sel.innerHTML=''; sel.appendChild(opt('','— Curso —'));
    (cursosByFam[fid]||[]).forEach(c=>{const o=opt(c.id,c.nombre); if(selected&&String(selected)===String(c.id)) o.selected=true; sel.appendChild(o);});
  }
  function renderAsigs(sel, cid, selected=null){
    sel.innerHTML=''; sel.appendChild(opt('','— Asignatura —'));
    (asigByCurso[cid]||[]).forEach(a=>{const o=opt(a.id,a.nombre); if(selected&&String(selected)===String(a.id)) o.selected=true; sel.appendChild(o);});
  }

  // Inicializa filas existentes
  const existing = <?php
    $arr=[];
    foreach($asigRows as $r){ $arr[]=['familia_id'=>(int)$r['familia_id'],'curso_id'=>(int)$r['curso_id'],'asignatura_id'=>(int)$r['asignatura_id']]; }
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  ?>;

  document.querySelectorAll('#rowsBody tr').forEach((tr,idx)=>{
    const selFam=tr.querySelector('.famSel'); const selCur=tr.querySelector('.cursoSel'); const selAs=tr.querySelector('.asigSel');
    const preset=existing[idx]||{};
    const fid=parseInt(selFam.value||preset.familia_id||'0',10);
    renderCursos(selCur,fid,preset.curso_id);
    renderAsigs(selAs,preset.curso_id,preset.asignatura_id);

    selFam.addEventListener('change',()=>{const f=parseInt(selFam.value||'0',10); renderCursos(selCur,f,null); renderAsigs(selAs,0,null);},{passive:true});
    selCur.addEventListener('change',()=>{const c=parseInt(selCur.value||'0',10); renderAsigs(selAs,c,null);},{passive:true});
  });

  // Añadir nuevas filas
  const rowsBody=document.getElementById('rowsBody');
  document.getElementById('btnAddRow').addEventListener('click', ()=>{
    const tr=document.createElement('tr');
    tr.innerHTML=`
      <td class="px-3 py-2">
        <input type="hidden" name="pa_id[]" value="">
        <select name="asig_familia_id[]" class="famSel w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm focus:ring-2 focus:ring-slate-400">
          <option value="">— Familia/Grado —</option>
          ${familias.map(f=>`<option value="${f.id}">${f.nombre}</option>`).join('')}
        </select>
      </td>
      <td class="px-3 py-2"><select name="asig_curso_id[]" class="cursoSel w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm"><option value="">— Curso —</option></select></td>
      <td class="px-3 py-2"><select name="asig_asignatura_id[]" class="asigSel w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm"><option value="">— Asignatura —</option></select></td>
      <td class="px-3 py-2"><input name="asig_anio[]" type="text" placeholder="2025-2026" class="w-28 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm"></td>
      <td class="px-3 py-2"><input name="asig_horas[]" type="number" min="0" class="w-20 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm"></td>
      <td class="px-3 py-2"><input name="asig_obs[]" type="text" class="w-48 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm" placeholder="Notas"></td>
      <td class="px-3 py-2 text-right"><button type="button" class="btnDelNew inline-flex items-center rounded-lg bg-rose-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-rose-500">Quitar</button></td>
    `;
    const selFam=tr.querySelector('.famSel'); const selCur=tr.querySelector('.cursoSel'); const selAs=tr.querySelector('.asigSel');
    selFam.addEventListener('change',()=>{const f=parseInt(selFam.value||'0',10); renderCursos(selCur,f,null); renderAsigs(selAs,0,null);},{passive:true});
    selCur.addEventListener('change',()=>{const c=parseInt(selCur.value||'0',10); renderAsigs(selAs,c,null);},{passive:true});
    tr.querySelector('.btnDelNew').addEventListener('click',()=>tr.remove());
    rowsBody.appendChild(tr);
  });
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
