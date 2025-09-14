<?php
declare(strict_types=1);
/**
 * Vista de registro con:
 *  - Alumno: grado -> curso (SSR + mejora con JS).
 *  - Profesor: añadir múltiples combinaciones (grado, curso, asignatura) dinámicamente.
 */
require __DIR__.'/inc/db.php';

// Catálogo SSR para que siempre se vean opciones aunque falle JS
$grados = $pdo->query("SELECT id, nombre FROM grados ORDER BY nombre")->fetchAll();
$gradoDefaultId = $grados[0]['id'] ?? null;

$cursos = [];
$asignaturas = [];
if ($gradoDefaultId) {
  $st = $pdo->prepare("SELECT id, nombre FROM cursos WHERE grado_id=? ORDER BY nombre");
  $st->execute([$gradoDefaultId]);
  $cursos = $st->fetchAll();

  $st = $pdo->prepare("SELECT id, nombre FROM asignaturas WHERE grado_id=? ORDER BY nombre");
  $st->execute([$gradoDefaultId]);
  $asignaturas = $st->fetchAll();
}
?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Bancalia - Registro</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Tailwind opcional; mantenemos tu app.css -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/Bancalia/assets/css/app.css">
  <style>
    .row-card { border:1px solid #e5e7eb; border-radius:0.75rem; padding:1rem; }
    .row-del { cursor:pointer; }
    .hidden-nojs { display:block; }
  </style>
</head>
<body class="bg-gray-100 min-h-screen">

  <div class="max-w-3xl mx-auto px-4 py-8">
    <div class="bg-white rounded-xl shadow p-6">
      <div class="flex items-center gap-4 mb-6">
        <img src="/Bancalia/assets/images/logo.png" alt="Bancalia" class="w-14 h-14" />
        <h1 class="text-2xl font-bold text-blue-600">Crear cuenta</h1>
      </div>

      <form id="registroForm" action="/Bancalia/api/registro.php" method="post" class="space-y-6">
        <!-- Datos comunes -->
        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Nombre</label>
            <input type="text" name="nombre" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Email</label>
            <input type="email" name="email" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm font-medium mb-1">Contraseña</label>
            <input type="password" name="clave" minlength="6" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
          </div>
        </div>

        <!-- Rol -->
        <div>
          <span class="block text-sm font-medium mb-2">Rol</span>
          <div class="flex items-center gap-6">
            <label class="inline-flex items-center gap-2">
              <input type="radio" name="rol" value="alumno" class="accent-blue-600">
              <span>Alumno</span>
            </label>
            <label class="inline-flex items-center gap-2">
              <input type="radio" name="rol" value="profesor" class="accent-blue-600" checked>
              <span>Profesor</span>
            </label>
          </div>
        </div>

        <!-- ALUMNO -->
        <fieldset id="secAlumno" class="border rounded-lg p-4 hidden-nojs" style="display:none;">
          <legend class="px-2 text-sm font-semibold text-gray-700">Datos académicos (Alumno)</legend>
          <div class="grid md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-1">Grado</label>
              <select id="a_grado" name="a_grado" class="w-full px-3 py-2 border rounded-lg">
                <option value="">Selecciona…</option>
                <?php foreach ($grados as $g): ?>
                  <option value="<?= htmlspecialchars((string)$g['id']) ?>"><?= htmlspecialchars($g['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Curso</label>
              <select id="a_curso" name="a_curso" class="w-full px-3 py-2 border rounded-lg">
                <option value="">Selecciona…</option>
              </select>
            </div>
          </div>
        </fieldset>

        <!-- PROFESOR: múltiples combinaciones -->
        <fieldset id="secProfesor" class="border rounded-lg p-4">
          <legend class="px-2 text-sm font-semibold text-gray-700">Ámbito docente (Profesor)</legend>

          <div id="comboList" class="space-y-4">
            <!-- Fila 0 SSR por defecto -->
            <div class="row-card combo-row" data-index="0">
              <div class="grid md:grid-cols-3 gap-4">
                <div>
                  <label class="block text-sm font-medium mb-1">Grado</label>
                  <select name="combo_grado_0" class="grado w-full px-3 py-2 border rounded-lg">
                    <option value="">Selecciona…</option>
                    <?php foreach ($grados as $g): ?>
                      <option value="<?= htmlspecialchars((string)$g['id']) ?>" <?= $g['id']===$gradoDefaultId?'selected':'' ?>>
                        <?= htmlspecialchars($g['nombre']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Curso</label>
                  <select name="combo_curso_0" class="curso w-full px-3 py-2 border rounded-lg">
                    <option value="">Selecciona…</option>
                    <?php foreach ($cursos as $c): ?>
                      <option value="<?= htmlspecialchars((string)$c['id']) ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Asignatura</label>
                  <select name="combo_asignatura_0" class="asignatura w-full px-3 py-2 border rounded-lg">
                    <option value="">Selecciona…</option>
                    <?php foreach ($asignaturas as $a): ?>
                      <option value="<?= htmlspecialchars((string)$a['id']) ?>"><?= htmlspecialchars($a['nombre']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="mt-3 text-right">
                <button type="button" class="row-del text-sm text-red-600 hover:underline" data-action="del">Eliminar</button>
              </div>
            </div>
          </div>

          <div class="mt-4">
            <button type="button" id="addCombo" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
              Añadir combinación
            </button>
          </div>
        </fieldset>

        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">
          Registrarme
        </button>

        <p id="registroError" class="text-red-500 text-sm"></p>
        <p id="registroOk" class="text-green-600 text-sm"></p>

        <div class="text-center text-gray-600">
          ¿Ya tienes cuenta? <a href="/Bancalia/index.php" class="text-blue-600 hover:underline">Inicia sesión</a>
        </div>
      </form>
    </div>
  </div>

  <!-- JS inline -->
  <script>
  (function(){
    const API = {
      grados: '/Bancalia/api/grados.php',
      cursos: g => `/Bancalia/api/cursos.php?grado_id=${encodeURIComponent(g)}`,
      asignaturas: g => `/Bancalia/api/asignaturas.php?grado_id=${encodeURIComponent(g)}`,
      registro: '/Bancalia/api/registro.php'
    };

    const form = document.getElementById('registroForm');
    const secAlumno = document.getElementById('secAlumno');
    const secProfesor = document.getElementById('secProfesor');
    const a_grado = document.getElementById('a_grado');
    const a_curso = document.getElementById('a_curso');
    const comboList = document.getElementById('comboList');
    const addBtn = document.getElementById('addCombo');
    const msgError = document.getElementById('registroError');
    const msgOk = document.getElementById('registroOk');

    // Mostrar secciones por rol
    function refreshRole() {
      const rol = (document.querySelector('input[name="rol"]:checked')||{}).value;
      if (rol === 'alumno') {
        secAlumno.style.display = 'block';
        secProfesor.style.display = 'none';
      } else {
        secAlumno.style.display = 'none';
        secProfesor.style.display = 'block';
      }
    }
    document.querySelectorAll('input[name="rol"]').forEach(r => r.addEventListener('change', refreshRole));
    refreshRole();

    async function getJSON(url){
      const r = await fetch(url, { headers: { 'Accept':'application/json' }});
      if(!r.ok) throw new Error(`HTTP ${r.status}`);
      const j = await r.json();
      if(j.ok === false) throw new Error(j.error || 'Error de API');
      return j;
    }
    function fillSelect(sel, options, {placeholder='Selecciona…', valueKey='id', labelKey='nombre'}={}) {
      const keepMultiple = sel.multiple;
      sel.innerHTML = '';
      if (!keepMultiple) {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = placeholder;
        sel.appendChild(opt);
      }
      (options||[]).forEach(o=>{
        const opt = document.createElement('option');
        opt.value = o[valueKey];
        opt.textContent = o[labelKey];
        sel.appendChild(opt);
      });
    }

    // Dependencias ALUMNO
    a_grado && a_grado.addEventListener('change', async ()=>{
      msgError.textContent = '';
      a_curso.innerHTML = '<option value="">Cargando…</option>';
      const g = a_grado.value; if(!g){ a_curso.innerHTML = '<option value="">Selecciona…</option>'; return; }
      try {
        const jc = await getJSON(API.cursos(g));
        fillSelect(a_curso, jc.data);
      } catch(e){ msgError.textContent = 'No se pudieron cargar los cursos.'; }
    });

    // Manejadores por fila (grado -> cursos/asignaturas)
    async function onRowGradoChange(row){
      const gradoSel = row.querySelector('select.grado');
      const cursoSel = row.querySelector('select.curso');
      const asigSel  = row.querySelector('select.asignatura');
      const g = gradoSel.value;

      cursoSel.innerHTML = '<option value="">Cargando…</option>';
      asigSel.innerHTML  = '<option value="">Cargando…</option>';
      if (!g) { cursoSel.innerHTML = '<option value="">Selecciona…</option>'; asigSel.innerHTML='<option value="">Selecciona…</option>'; return; }

      try {
        const [jc, ja] = await Promise.all([ getJSON(API.cursos(g)), getJSON(API.asignaturas(g)) ]);
        fillSelect(cursoSel, jc.data);
        fillSelect(asigSel,  ja.data, {placeholder:'Selecciona…'});
      } catch(e){
        msgError.textContent = 'No se pudieron cargar cursos/asignaturas.';
      }
    }

    // Añadir/eliminar filas
    let rowIndex = 1; // ya existe la 0 SSR
    addBtn.addEventListener('click', ()=>{
      const tpl = document.querySelector('.combo-row[data-index="0"]');
      const clone = tpl.cloneNode(true);
      clone.dataset.index = String(rowIndex);

      // Actualizar names
      clone.querySelector('.grado').setAttribute('name', `combo_grado_${rowIndex}`);
      clone.querySelector('.curso').setAttribute('name', `combo_curso_${rowIndex}`);
      clone.querySelector('.asignatura').setAttribute('name', `combo_asignatura_${rowIndex}`);

      // Limpiar valores
      clone.querySelectorAll('select').forEach(s=>{ s.selectedIndex = 0; });

      comboList.appendChild(clone);
      rowIndex++;
    });

    // Delegación: eliminar fila + cambio de grado por fila
    comboList.addEventListener('click', (ev)=>{
      const btn = ev.target.closest('[data-action="del"]');
      if (!btn) return;
      const row = btn.closest('.combo-row');
      if (!row) return;
      // Evitar borrar la última fila: si solo queda una, limpiamos
      if (comboList.querySelectorAll('.combo-row').length === 1) {
        row.querySelectorAll('select').forEach(s=> s.selectedIndex = 0);
      } else {
        row.remove();
      }
    });
    comboList.addEventListener('change', (ev)=>{
      const sel = ev.target;
      if (!sel || !sel.classList.contains('grado')) return;
      const row = sel.closest('.combo-row');
      if (row) onRowGradoChange(row);
    });

    // Submit: serializar combos como arrays para la API
    form.addEventListener('submit', async (ev)=>{
      ev.preventDefault();
      msgError.textContent = ''; msgOk.textContent = '';

      const data = new FormData(form);

      // Construir combos[n][grado_id|curso_id|asignatura_id]
      const rows = Array.from(comboList.querySelectorAll('.combo-row'));
      let anyCombo = false;
      rows.forEach((row, i)=>{
        const g = row.querySelector('select.grado')?.value || '';
        const c = row.querySelector('select.curso')?.value || '';
        const a = row.querySelector('select.asignatura')?.value || '';
        if (g || c || a) {
          // Solo añadimos si hay algo seleccionado (la API validará que estén completos)
          data.append(`combos[${i}][grado_id]`, g);
          data.append(`combos[${i}][curso_id]`, c);
          data.append(`combos[${i}][asignatura_id]`, a);
          anyCombo = true;
        }
      });

      // Si rol=profesor, exigimos al menos una fila con datos
      const rol = (document.querySelector('input[name="rol"]:checked')||{}).value;
      if (rol === 'profesor' && !anyCombo) {
        msgError.textContent = 'Añade al menos una combinación de grado, curso y asignatura.';
        return;
      }

      try {
        const r = await fetch(API.registro, { method:'POST', body:data, headers:{'Accept':'application/json'} });
        const j = await r.json().catch(()=> ({}));
        if (!r.ok || j.success === false) throw new Error(j.error || `Error ${r.status}`);
        msgOk.textContent = 'Registro completado. Redirigiendo…';
        window.location.href = j.redirect || '/Bancalia/panel.php';
      } catch(e) {
        msgError.textContent = e.message || 'No se pudo completar el registro.';
      }
    });
  })();
  </script>
</body>
</html>

