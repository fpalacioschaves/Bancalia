<?php
// /admin/actividades/items/edit.php
declare(strict_types=1);
require_once __DIR__ . '/../../../middleware/require_auth.php';
require_once __DIR__ . '/../../../lib/auth.php';
require_once __DIR__ . '/../../../partials/header.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$id = (int)($_GET['id'] ?? 0);
if ($id<=0){ header('Location: '.PUBLIC_URL.'/admin/actividades/index.php'); exit; }

$st = pdo()->prepare('SELECT i.*, a.profesor_id, a.id AS actividad_id FROM actividad_items i JOIN actividades a ON a.id=i.actividad_id WHERE i.id=:id');
$st->execute([':id'=>$id]);
$it = $st->fetch();
if (!$it){ $_SESSION['flash']='Ítem no encontrado.'; header('Location: '.PUBLIC_URL.'/admin/actividades/index.php'); exit; }

$u = current_user();
if (($u['role'] ?? '')!=='admin' && (int)$it['profesor_id'] !== (int)($u['profesor_id'] ?? 0)) { $_SESSION['flash']='Sin permiso.'; header('Location: '.PUBLIC_URL.'/admin/actividades/index.php'); exit; }

$actividad_id = (int)$it['actividad_id'];

// Cargar opciones si procede
$opciones=[];
if ($it['tipo_item']==='opcion'){
  $stO = pdo()->prepare('SELECT * FROM actividad_item_opciones WHERE item_id=:id ORDER BY orden ASC, id ASC');
  $stO->execute([':id'=>$id]);
  $opciones = $stO->fetchAll();
}

if ($_SERVER['REQUEST_METHOD']==='POST'){
  try{
    csrf_check($_POST['csrf'] ?? null);
    $enunciado = trim((string)($_POST['enunciado'] ?? ''));
    $puntos    = (float)($_POST['puntos'] ?? 1);
    if ($enunciado==='') throw new RuntimeException('El enunciado es obligatorio.');

    // actualizar contenido según tipo
    $contenido = null; $tipo = $it['tipo_item'];

    if ($tipo==='vf'){
      $contenido = json_encode(['correcta'=>(($_POST['vf_correcta'] ?? 'verdadero')==='verdadero')], JSON_UNESCAPED_UNICODE);
    }
    if ($tipo==='corta'){
      $claves = array_filter(array_map('trim', explode("\n", (string)($_POST['corta_claves'] ?? ''))));
      if (!$claves) throw new RuntimeException('Añade palabras clave.');
      $contenido = json_encode(['claves'=>$claves,'match'=>'icontains'], JSON_UNESCAPED_UNICODE);
    }
    if ($tipo==='huecos'){
      $texto = trim((string)($_POST['huecos_texto'] ?? ''));
      $sol   = json_decode((string)($_POST['huecos_sol'] ?? ''), true);
      if ($texto==='') throw new RuntimeException('Añade el texto.');
      if (!is_array($sol)) throw new RuntimeException('Soluciones inválidas.');
      $contenido = json_encode(['texto'=>$texto,'soluciones'=>$sol], JSON_UNESCAPED_UNICODE);
    }
    if ($tipo==='emparejar'){
      $pairsText = trim((string)($_POST['pairs'] ?? ''));
      $pairs = [];
      foreach (explode("\n", $pairsText) as $line) {
        $line = trim($line); if ($line==='') continue;
        $parts = explode('|',$line,2); if (count($parts)<2) throw new RuntimeException('Formato termino|definicion');
        $pairs[] = ['t'=>trim($parts[0]), 'd'=>trim($parts[1])];
      }
      if (!$pairs) throw new RuntimeException('Añade pares.');
      $contenido = json_encode(['pares'=>$pairs], JSON_UNESCAPED_UNICODE);
    }

    $up = pdo()->prepare('UPDATE actividad_items SET enunciado=:e, puntos=:p, contenido=:c, updated_at=NOW() WHERE id=:id');
    $up->execute([':e'=>$enunciado, ':p'=>$puntos, ':c'=>$contenido, ':id'=>$id]);

    if ($tipo==='opcion'){
      // Reemplazar opciones
      $del = pdo()->prepare('DELETE FROM actividad_item_opciones WHERE item_id=:i');
      $del->execute([':i'=>$id]);

      $opts = $_POST['op_texto'] ?? [];
      $oks  = $_POST['op_ok'] ?? [];
      $ret  = $_POST['op_retro'] ?? [];
      $insOp = pdo()->prepare('INSERT INTO actividad_item_opciones (item_id, texto, es_correcta, retro, orden, created_at, updated_at)
                               VALUES (:i,:tx,:ok,:re,:o, NOW(), NOW())');
      foreach ($opts as $i => $txt) {
        $txt = trim((string)$txt);
        if ($txt==='') continue;
        $ok = in_array((string)$i, $oks ?? [], true) ? 1 : 0;
        $insOp->execute([':i'=>$id, ':tx'=>$txt, ':ok'=>$ok, ':re'=>trim((string)($ret[$i] ?? '')), ':o'=>$i]);
      }
    }

    $_SESSION['flash']='Ítem actualizado.';
    header('Location: '.PUBLIC_URL.'/admin/actividades/edit.php?id='.$actividad_id); exit;

  }catch(Throwable $e){
    $_SESSION['flash']=$e->getMessage();
    header('Location: '.PUBLIC_URL.'/admin/actividades/items/edit.php?id='.$id); exit;
  }
}
?>
<div class="mb-6">
  <h1 class="text-2xl font-semibold tracking-tight">Editar ítem</h1>
  <p class="text-sm text-slate-600">Actividad #<?= (int)$actividad_id ?> · Tipo: <?= h($it['tipo_item']) ?></p>
</div>

<form method="post" action="" class="grid gap-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
  <?= csrf_field() ?>

  <div class="grid gap-4 sm:grid-cols-2">
    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700">Puntos</label>
      <input name="puntos" type="number" step="0.25" value="<?= h($it['puntos']) ?>" class="w-full rounded-lg border px-3 py-2 text-sm">
    </div>
  </div>

  <div>
    <label class="mb-1 block text-sm font-medium text-slate-700">Enunciado</label>
    <textarea name="enunciado" rows="3" class="w-full rounded-lg border px-3 py-2 text-sm" required><?= h($it['enunciado']) ?></textarea>
  </div>

  <?php if ($it['tipo_item']==='opcion'): ?>
    <div class="mb-2 text-sm font-semibold">Opciones</div>
    <div id="opts" class="space-y-2">
      <?php foreach ($opciones as $i=>$o): ?>
        <div class="flex items-start gap-2">
          <input type="checkbox" name="op_ok[]" value="<?= $i ?>" class="mt-2" <?= ((int)$o['es_correcta']===1?'checked':'') ?>>
          <input name="op_texto[]" class="flex-1 rounded-lg border px-3 py-2 text-sm" value="<?= h($o['texto']) ?>">
          <input name="op_retro[]" class="w-64 rounded-lg border px-3 py-2 text-sm" value="<?= h((string)($o['retro'] ?? '')) ?>" placeholder="Retro (opcional)">
        </div>
      <?php endforeach; ?>
    </div>
  <?php elseif ($it['tipo_item']==='vf'):
      $vf = json_decode((string)$it['contenido'], true); $corr = !empty($vf['correcta']);
  ?>
    <label class="mb-1 block text-sm font-medium text-slate-700">Respuesta correcta</label>
    <select name="vf_correcta" class="w-40 rounded-lg border px-3 py-2 text-sm">
      <option value="verdadero" <?= $corr?'selected':'' ?>>Verdadero</option>
      <option value="falso" <?= !$corr?'selected':'' ?>>Falso</option>
    </select>
  <?php elseif ($it['tipo_item']==='corta'):
      $cj = json_decode((string)$it['contenido'], true);
      $claves = isset($cj['claves']) ? implode("\n", (array)$cj['claves']) : '';
  ?>
    <label class="mb-1 block text-sm font-medium text-slate-700">Palabras clave (una por línea)</label>
    <textarea name="corta_claves" rows="4" class="w-full rounded-lg border px-3 py-2 text-sm"><?= h($claves) ?></textarea>
  <?php elseif ($it['tipo_item']==='huecos'):
      $hj = json_decode((string)$it['contenido'], true);
  ?>
    <label class="mb-1 block text-sm font-medium text-slate-700">Texto con huecos</label>
    <textarea name="huecos_texto" rows="4" class="w-full rounded-lg border px-3 py-2 text-sm"><?= h((string)($hj['texto'] ?? '')) ?></textarea>
    <label class="mt-2 mb-1 block text-sm font-medium text-slate-700">Soluciones (JSON)</label>
    <textarea name="huecos_sol" rows="4" class="w-full rounded-lg border px-3 py-2 text-sm"><?= h(json_encode($hj['soluciones'] ?? new stdClass(), JSON_UNESCAPED_UNICODE)) ?></textarea>
  <?php elseif ($it['tipo_item']==='emparejar'):
      $pj = json_decode((string)$it['contenido'], true);
      $lines = '';
      foreach ((array)($pj['pares'] ?? []) as $p) { $lines .= ($p['t'] ?? '').'|'.($p['d'] ?? '')."\n"; }
  ?>
    <label class="mb-1 block text-sm font-medium text-slate-700">Pares término|definición</label>
    <textarea name="pairs" rows="5" class="w-full rounded-lg border px-3 py-2 text-sm"><?= h(trim($lines)) ?></textarea>
  <?php endif; ?>

  <div class="flex justify-end">
    <a href="<?= PUBLIC_URL ?>/admin/actividades/edit.php?id=<?= (int)$actividad_id ?>" class="rounded-lg border px-3 py-2 text-sm">Volver</a>
    <button class="ml-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white">Guardar</button>
  </div>
</form>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>
