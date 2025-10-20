<?php
// /admin/actividades/items/create.php
declare(strict_types=1);

require_once __DIR__ . '/../../../partials/header.php';



$u = current_user();
$role = $u['role'] ?? '';
$profesorId = (int)($u['profesor_id'] ?? 0);

$actividad_id = (int)($_GET['actividad_id'] ?? 0);
if ($actividad_id<=0) { $_SESSION['flash']='Actividad requerida.'; header('Location: '.PUBLIC_URL.'/admin/actividades/index.php'); exit; }

$act = pdo()->prepare('SELECT * FROM actividades WHERE id=:id');
$act->execute([':id'=>$actividad_id]);
$a = $act->fetch();
if (!$a || $a['tipo']!=='quiz') { $_SESSION['flash']='Actividad inválida.'; header('Location: '.PUBLIC_URL.'/admin/actividades/index.php'); exit; }
if ($role!=='admin' && (int)$a['profesor_id'] !== $profesorId) { $_SESSION['flash']='Sin permiso.'; header('Location: '.PUBLIC_URL.'/admin/actividades/index.php'); exit; }

if ($_SERVER['REQUEST_METHOD']==='POST') {
  try{
    csrf_check($_POST['csrf'] ?? null);

    $tipo_item = $_POST['tipo_item'];
    $enunciado = trim((string)($_POST['enunciado'] ?? ''));
    $puntos    = (float)($_POST['puntos'] ?? 1);

    if (!in_array($tipo_item, ['opcion','vf','corta','huecos','emparejar'], true))
      throw new RuntimeException('Tipo de ítem no soportado.');
    if ($enunciado==='') throw new RuntimeException('El enunciado es obligatorio.');

    $contenido = null;

    if ($tipo_item === 'vf') {
      $correcta = ($_POST['vf_correcta'] ?? 'verdadero') === 'verdadero';
      $contenido = json_encode(['correcta'=> $correcta], JSON_UNESCAPED_UNICODE);
    }
    if ($tipo_item === 'corta') {
      $claves = array_filter(array_map('trim', explode("\n", (string)($_POST['corta_claves'] ?? ''))));
      if (!$claves) throw new RuntimeException('Añade palabras clave (una por línea).');
      $contenido = json_encode(['claves'=>$claves,'match'=>'icontains'], JSON_UNESCAPED_UNICODE);
    }
    if ($tipo_item === 'huecos') {
      // Texto con {{slot}}
      $texto = trim((string)($_POST['huecos_texto'] ?? ''));
      $solJSON = trim((string)($_POST['huecos_sol'] ?? '')); // JSON: { "slot1":["Ohm"], "slot2":["R","resistencia"] }
      if ($texto==='') throw new RuntimeException('Añade el texto con huecos.');
      $sol = json_decode($solJSON, true);
      if (!is_array($sol)) throw new RuntimeException('Soluciones inválidas (JSON).');
      $contenido = json_encode(['texto'=>$texto, 'soluciones'=>$sol], JSON_UNESCAPED_UNICODE);
    }
    if ($tipo_item === 'emparejar') {
      $pairsText = trim((string)($_POST['pairs'] ?? ''));
      $pairs = [];
      foreach (explode("\n", $pairsText) as $line) {
        $line = trim($line);
        if ($line==='') continue;
        $parts = explode('|',$line,2);
        if (count($parts)<2) throw new RuntimeException('Formato de pares: termino|definicion');
        $pairs[] = ['t'=>trim($parts[0]), 'd'=>trim($parts[1])];
      }
      if (!$pairs) throw new RuntimeException('Añade al menos un par.');
      $contenido = json_encode(['pares'=>$pairs], JSON_UNESCAPED_UNICODE);
    }

    // Insert item
    $ins = pdo()->prepare('INSERT INTO actividad_items
      (actividad_id, tipo_item, enunciado, puntos, contenido, created_at, updated_at)
      VALUES (:a,:t,:e,:p,:c, NOW(), NOW())');
    $ins->execute([':a'=>$actividad_id, ':t'=>$tipo_item, ':e'=>$enunciado, ':p'=>$puntos, ':c'=>$contenido]);

    $item_id = (int)pdo()->lastInsertId();

    // Opciones si es 'opcion'
    if ($tipo_item==='opcion') {
      $opts = $_POST['op_texto'] ?? [];
      $oks  = $_POST['op_ok'] ?? [];
      $ret  = $_POST['op_retro'] ?? [];
      $insOp = pdo()->prepare('INSERT INTO actividad_item_opciones (item_id, texto, es_correcta, retro, orden, created_at, updated_at)
                               VALUES (:i,:tx,:ok,:re,:o, NOW(), NOW())');
      foreach ($opts as $i => $txt) {
        $txt = trim((string)$txt);
        if ($txt==='') continue;
        $ok = in_array((string)$i, $oks ?? [], true) ? 1 : 0;
        $insOp->execute([':i'=>$item_id, ':tx'=>$txt, ':ok'=>$ok, ':re'=>trim((string)($ret[$i] ?? '')), ':o'=>$i]);
      }
    }

    $_SESSION['flash']='Ítem creado.';
    header('Location: '.PUBLIC_URL.'/admin/actividades/edit.php?id='.$actividad_id); exit;

  }catch(Throwable $e){
    $_SESSION['flash']=$e->getMessage();
    header('Location: '.PUBLIC_URL.'/admin/actividades/items/create.php?actividad_id='.$actividad_id); exit;
  }
}
?>
<div class="mb-6">
  <h1 class="text-2xl font-semibold tracking-tight">Nuevo ítem</h1>
  <p class="text-sm text-slate-600">Actividad #<?= (int)$actividad_id ?> (quiz)</p>
</div>

<form method="post" action="" class="grid gap-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm" id="itemForm">
  <?= csrf_field() ?>

  <div class="grid gap-4 sm:grid-cols-2">
    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700">Tipo de ítem</label>
      <select name="tipo_item" id="tipo_item" class="w-full rounded-lg border px-3 py-2 text-sm">
        <option value="opcion">Opción múltiple</option>
        <option value="vf">Verdadero / Falso</option>
        <option value="corta">Respuesta corta</option>
        <option value="huecos">Rellenar huecos</option>
        <option value="emparejar">Emparejar</option>
      </select>
    </div>
    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700">Puntos</label>
      <input name="puntos" type="number" step="0.25" value="1" class="w-full rounded-lg border px-3 py-2 text-sm">
    </div>
  </div>

  <div>
    <label class="mb-1 block text-sm font-medium text-slate-700">Enunciado</label>
    <textarea name="enunciado" rows="3" class="w-full rounded-lg border px-3 py-2 text-sm" required></textarea>
  </div>

  <!-- OPCION MÚLTIPLE -->
  <div id="blk_opcion" class="hidden">
    <div class="mb-2 text-sm font-semibold">Opciones</div>
    <div id="opts" class="space-y-2"></div>
    <button type="button" id="addOpt" class="mt-2 rounded-lg border px-3 py-1.5 text-sm">+ Añadir opción</button>
  </div>

  <!-- VF -->
  <div id="blk_vf" class="hidden">
    <label class="mb-1 block text-sm font-medium text-slate-700">Respuesta correcta</label>
    <select name="vf_correcta" class="w-40 rounded-lg border px-3 py-2 text-sm">
      <option value="verdadero">Verdadero</option>
      <option value="falso">Falso</option>
    </select>
  </div>

  <!-- CORTA -->
  <div id="blk_corta" class="hidden">
    <label class="mb-1 block text-sm font-medium text-slate-700">Palabras clave (una por línea)</label>
    <textarea name="corta_claves" rows="4" class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="osciloscopio&#10;multímetro"></textarea>
  </div>

  <!-- HUECOS -->
  <div id="blk_huecos" class="hidden">
    <label class="mb-1 block text-sm font-medium text-slate-700">Texto con huecos (usa {{slot1}}, {{slot2}}…)</label>
    <textarea name="huecos_texto" rows="4" class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="La ley de {{slot1}} relaciona V = I * {{slot2}}"></textarea>
    <label class="mt-2 mb-1 block text-sm font-medium text-slate-700">Soluciones (JSON)</label>
    <textarea name="huecos_sol" rows="4" class="w-full rounded-lg border px-3 py-2 text-sm" placeholder='{"slot1":["Ohm"], "slot2":["R","resistencia"]}'></textarea>
  </div>

  <!-- EMPAREJAR -->
  <div id="blk_emparejar" class="hidden">
    <label class="mb-1 block text-sm font-medium text-slate-700">Pares término|definición (uno por línea)</label>
    <textarea name="pairs" rows="5" class="w-full rounded-lg border px-3 py-2 text-sm" placeholder="Voltaje|Diferencia de potencial&#10;Corriente|Flujo de electrones"></textarea>
  </div>

  <div class="flex justify-end">
    <a href="<?= PUBLIC_URL ?>/admin/actividades/edit.php?id=<?= (int)$actividad_id ?>" class="rounded-lg border px-3 py-2 text-sm">Cancelar</a>
    <button class="ml-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white">Crear ítem</button>
  </div>
</form>

<script>
const $tipo=document.getElementById('tipo_item');
const blocks={opcion:'blk_opcion', vf:'blk_vf', corta:'blk_corta', huecos:'blk_huecos', emparejar:'blk_emparejar'};
function showBlock(){
  Object.values(blocks).forEach(id=>document.getElementById(id).classList.add('hidden'));
  document.getElementById(blocks[$tipo.value]).classList.remove('hidden');
}
$tipo.addEventListener('change', showBlock, {passive:true}); showBlock();

// opción múltiple UI
const $opts=document.getElementById('opts'); const $add=document.getElementById('addOpt');
function addOpt(){
  const i=$opts.children.length;
  const div=document.createElement('div');
  div.className='flex items-start gap-2';
  div.innerHTML=`
    <input type="checkbox" name="op_ok[]" value="${i}" class="mt-2">
    <input name="op_texto[]" class="flex-1 rounded-lg border px-3 py-2 text-sm" placeholder="Texto de opción">
    <input name="op_retro[]" class="w-64 rounded-lg border px-3 py-2 text-sm" placeholder="Retro (opcional)">
    <button type="button" class="rm rounded-lg border px-2 py-1 text-sm">Quitar</button>
  `;
  div.querySelector('.rm').addEventListener('click',()=>div.remove());
  $opts.appendChild(div);
}
$add.addEventListener('click', addOpt); addOpt(); addOpt();
</script>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>
