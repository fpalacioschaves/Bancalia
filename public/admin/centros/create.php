<?php
// /public/admin/centros/create.php
declare(strict_types=1);

require_once __DIR__ . '/../../../config.php';
require_login_or_redirect();
$u = current_user();

/*if (($u['role'] ?? '') !== 'admin') {
  flash('error', 'Acceso restringido a administradores.');
  header('Location: ' . PUBLIC_URL . '/dashboard.php');
  exit;
}

if (($u['role'] ?? '') !== 'admin') {
  flash('error', 'Acceso restringido a administradores.');
  header('Location: ' . PUBLIC_URL . '/dashboard.php');
  exit;
}*/
if (!$u || !in_array(($u['role'] ?? ''), ['admin','profesor'], true)) {
  $_SESSION['flash'] = 'Acceso restringido.';
  header('Location: ' . PUBLIC_URL . '/auth/login.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_check($_POST['csrf'] ?? null);

    $nombre    = trim((string)($_POST['nombre'] ?? ''));
    $slug      = trim((string)($_POST['slug'] ?? ''));
    $direccion = trim((string)($_POST['direccion'] ?? ''));
    $cp        = trim((string)($_POST['cp'] ?? ''));
    $localidad = trim((string)($_POST['localidad'] ?? ''));
    $provincia = trim((string)($_POST['provincia'] ?? ''));
    $comunidad = trim((string)($_POST['comunidad'] ?? ''));
    $telefono  = trim((string)($_POST['telefono'] ?? ''));
    $email     = trim((string)($_POST['email'] ?? ''));
    $web       = trim((string)($_POST['web'] ?? ''));
    $lat       = trim((string)($_POST['lat'] ?? ''));
    $lng       = trim((string)($_POST['lng'] ?? ''));
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($nombre === '') throw new RuntimeException('El nombre es obligatorio.');

    if ($slug === '') {
      $slug = strtolower(trim(preg_replace('/[^a-z0-9\-]+/i', '-', $nombre) ?? ''));
      $slug = trim($slug, '-');
    }
    if ($slug === '') throw new RuntimeException('No se pudo generar un slug válido.');

    $chk = pdo()->prepare('SELECT 1 FROM centros WHERE slug = :s LIMIT 1');
    $chk->execute([':s' => $slug]);
    if ($chk->fetch()) throw new RuntimeException('Ya existe un centro con ese slug.');

    $telefono = ($telefono !== '') ? $telefono : null;
    $email    = ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) ? $email : null;
    $web      = ($web !== '') ? $web : null;
    $latVal   = ($lat !== '' ? (float)$lat : null);
    $lngVal   = ($lng !== '' ? (float)$lng : null);

    $ins = pdo()->prepare('
      INSERT INTO centros
        (nombre, slug, direccion, cp, localidad, provincia, comunidad, telefono, email, web, lat, lng, is_active)
      VALUES
        (:n, :s, :d, :cp, :loc, :pr, :co, :t, :e, :w, :lat, :lng, :a)
    ');
    $ins->execute([
      ':n'=>$nombre, ':s'=>$slug, ':d'=>($direccion!==''?$direccion:null),
      ':cp'=>($cp!==''?$cp:null), ':loc'=>($localidad!==''?$localidad:null),
      ':pr'=>($provincia!==''?$provincia:null), ':co'=>($comunidad!==''?$comunidad:null),
      ':t'=>$telefono, ':e'=>$email, ':w'=>$web, ':lat'=>$latVal, ':lng'=>$lngVal, ':a'=>$is_active
    ]);

    $_SESSION['flash'] = 'Centro creado correctamente.';
    if (($u['role'] ?? '') === 'profesor') {
      header('Location: ' . PUBLIC_URL . '/mi-perfil.php'); exit;
    }
    header('Location: ' . PUBLIC_URL . '/admin/centros/index.php'); exit;

  } catch (Throwable $e) {
    $_SESSION['flash'] = $e->getMessage();
    header('Location: ' . PUBLIC_URL . '/admin/centros/create.php'); exit;
  }
}

require_once __DIR__ . '/../../../partials/header.php';
?>
<div class="mb-6 flex items-center justify-between">
  <div>
    <h1 class="text-xl font-semibold tracking-tight">Nuevo centro</h1>
    <p class="mt-1 text-sm text-slate-600">El mapa se calcula automáticamente desde la dirección.</p>
  </div>
  <a href="<?= PUBLIC_URL ?>/admin/centros/index.php"
     class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Volver al listado</a>
</div>

<div class="grid gap-6 lg:grid-cols-3">
  <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <form method="post" action="" class="space-y-5" id="centroForm">
      <?= csrf_field() ?>

      <div class="grid gap-4 sm:grid-cols-[2fr,1fr]">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Nombre</label>
          <input name="nombre" id="nombre" type="text" required
                 class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Slug</label>
          <input name="slug" id="slug" type="text" placeholder="se-deriva-del-nombre"
                 class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
          <p class="mt-1 text-xs text-slate-500">Si lo dejas vacío, se genera automáticamente.</p>
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Dirección</label>
          <input name="direccion" id="direccion" type="text" placeholder="Calle, número"
                 class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Código postal</label>
          <input name="cp" id="cp" type="text" placeholder="28001"
                 class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-3">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Localidad</label>
          <input name="localidad" id="localidad" type="text"
                 class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Provincia</label>
          <input name="provincia" id="provincia" type="text"
                 class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Comunidad</label>
          <input name="comunidad" id="comunidad" type="text"
                 class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-3">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Teléfono</label>
          <input name="telefono" type="text" placeholder="+34 600 000 000"
                 class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
          <input name="email" type="email" placeholder="centro@dominio.es"
                 class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Web</label>
          <input name="web" type="text" placeholder="https://…"
                 class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:ring-2 focus:ring-slate-400">
        </div>
      </div>

      <input type="hidden" name="lat" id="lat">
      <input type="hidden" name="lng" id="lng">

      <div class="flex items-center gap-2">
        <input id="is_active" name="is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400" checked>
        <label for="is_active" class="text-sm text-slate-700">Activo</label>
      </div>

      <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <a href="<?= PUBLIC_URL ?>/admin/centros/index.php"
           class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Cancelar</a>
        <button type="submit"
                class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Crear centro</button>
      </div>
    </form>
  </div>

  <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
    <h2 class="text-sm font-semibold text-slate-700 mb-3">Ubicación</h2>
    <div id="map" class="h-80 w-full rounded-lg border border-slate-200"></div>
    <p class="mt-2 text-xs text-slate-500">El marcador se calcula desde la dirección. Puedes arrastrarlo para afinar.</p>
  </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
  const nombreEl = document.getElementById('nombre');
  const slugEl = document.getElementById('slug');
  function slugify(t){return t.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');}
  nombreEl.addEventListener('input', ()=>{ if (slugEl.value.trim()==='') slugEl.value = slugify(nombreEl.value); });

  const latEl = document.getElementById('lat'), lngEl = document.getElementById('lng');
  const addrEls = ['direccion','cp','localidad','provincia','comunidad'].map(id=>document.getElementById(id));

  const map = L.map('map', { scrollWheelZoom:false }).setView([40.4168,-3.7038], 6);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom:19 }).addTo(map);
  const marker = L.marker([40.4168,-3.7038], { draggable:true }).addTo(map);
  marker.on('moveend', (e)=>{const p=e.target.getLatLng(); latEl.value=p.lat.toFixed(6); lngEl.value=p.lng.toFixed(6);});

  let t=null;
  function fullAddr(){return addrEls.map(el=>(el?.value||'').trim()).filter(Boolean).join(', ');}
  async function geocode(){
    const q=fullAddr(); if(!q) return;
    try{ const r=await fetch('https://nominatim.openstreetmap.org/search?format=json&q='+encodeURIComponent(q), {headers:{'Accept-Language':'es'}}); const d=await r.json();
      if(d && d[0]){ const lat=parseFloat(d[0].lat), lon=parseFloat(d[0].lon); marker.setLatLng([lat,lon]); map.setView([lat,lon],15); latEl.value=lat.toFixed(6); lngEl.value=lon.toFixed(6); }
    }catch(e){ console.warn('geocode falló', e); }
  }
  addrEls.forEach(el=>el.addEventListener('input', ()=>{clearTimeout(t); t=setTimeout(geocode,600);} ));
</script>

<?php require_once __DIR__ . '/../../../partials/footer.php'; ?>
