(() => {
  document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registroForm');
    if (!form) return;

    // ocultos que realmente se envían
    const gradoHidden = document.getElementById('gradoHidden');
    const cursoHidden = document.getElementById('cursoHidden');

    // Secciones
    const perfilRadios = document.querySelectorAll('input[name="perfil"]');
    const secAlumno = document.getElementById('secAlumno');
    const secProfe  = document.getElementById('secProfe');

    // Alumno
    const aGrado = document.getElementById('aGrado');
    const aCurso = document.getElementById('aCurso');

    // Profesor (UI)
    const pGrado   = document.getElementById('pGrado');
    const pCursos  = document.getElementById('pCursos');
    const pAsigs   = document.getElementById('pAsigs');
    const pAdd     = document.getElementById('pAdd');   // opcional
    const pList    = document.getElementById('pList');  // opcional
    const pClear   = document.getElementById('pClear'); // opcional

    const msg = document.getElementById('regError');

    // Lista interna (modo con botón)
    // Formato: "curso:asig|Curso nombre|Asig nombre"
    let paresLista = [];

    // ===== Utils =====
    const fetchJSON = (url) =>
      fetch(url, { headers: { 'Accept':'application/json' } })
        .then(r => r.json().catch(()=>({ok:false,error:'Respuesta no válida'})))
        .then(j => { if (j.ok === false) throw new Error(j.error || 'Error'); return j; });

    const getMultiValues = (sel) => {
      const out = [];
      if (!sel) return out;
      for (const o of sel.options) if (o.selected && o.value) out.push(o.value);
      return out;
    };

    const renderPares = () => {
      if (!pList) return;
      if (!paresLista.length){
        pList.innerHTML = `<tr><td colspan="3" style="text-align:center;color:#6b7280">Sin asignaciones.</td></tr>`;
        return;
      }
      pList.innerHTML = paresLista.map(p=>{
        const [key, cName, aName] = p.split('|');
        return `<tr>
          <td>${cName}</td>
          <td>${aName}</td>
          <td><button class="btn btn-danger p-del" data-key="${key}" type="button">Quitar</button></td>
        </tr>`;
      }).join('');
    };

    // ===== Cargas =====
    const loadGrados = (selects) =>
      fetchJSON('/Bancalia/api/grados.php').then(j=>{
        const list = j.data || [];
        selects.forEach(sel => {
          if (!sel) return;
          sel.innerHTML = list.map(g=>`<option value="${g.id}">${g.nombre}</option>`).join('');
        });
      });

    const loadCursos = (gradoId, sel) => {
      if (!sel) return Promise.resolve();
      if (!gradoId){
        sel.innerHTML = '';
        sel.disabled = true;
        return Promise.resolve();
      }
      const url = new URL('/Bancalia/api/cursos.php', window.location.origin);
      url.searchParams.set('grado_id', gradoId);
      return fetchJSON(url.toString()).then(j=>{
        const list = j.data || [];
        sel.disabled = false;
        sel.innerHTML = list.map(c=>`<option value="${c.id}">${c.nombre}</option>`).join('');
      });
    };

    const loadAsigs = (gradoId, cursoIds, sel) => {
      if (!sel) return Promise.resolve();
      if (!gradoId || !cursoIds || !cursoIds.length){
        sel.innerHTML = `<option value="">— Selecciona curso(s) —</option>`;
        return Promise.resolve();
      }
      const url = new URL('/Bancalia/api/asignaturas_by.php', window.location.origin);
      url.searchParams.set('grado_id', String(gradoId));
      url.searchParams.set('curso_ids', cursoIds.join(','));
      return fetchJSON(url.toString()).then(j=>{
        const list = j.data || [];
        sel.innerHTML = list.length
          ? list.map(a=>`<option value="${a.id}">${a.nombre}</option>`).join('')
          : `<option value="">— No hay asignaturas para ese curso —</option>`;
      });
    };

    const showPerfil = (perfil) => {
      const p = String(perfil || '').toLowerCase();
      secAlumno.classList.toggle('hidden', p !== 'alumno');
      secProfe .classList.toggle('hidden', p !== 'profesor');
    };

    // ===== Inicial =====
    loadGrados([aGrado, pGrado])
      .then(() => Promise.all([
        loadCursos(aGrado?.value, aCurso),
        loadCursos(pGrado?.value, pCursos)
      ]))
      .then(() => { pAsigs.innerHTML = `<option value="">— Selecciona curso(s) —</option>`; });

    showPerfil(document.querySelector('input[name="perfil"]:checked')?.value || 'alumno');
    perfilRadios.forEach(r=>r.addEventListener('change', e=>showPerfil(e.target.value)));

    // Alumno
    aGrado.addEventListener('change', ()=> loadCursos(aGrado.value, aCurso));

    // Profesor
    pGrado.addEventListener('change', async ()=>{
      await loadCursos(pGrado.value, pCursos);
      // limpiar selección al cambiar grado
      for (const o of pCursos.options) o.selected = false;
      pAsigs.innerHTML = `<option value="">— Selecciona curso(s) —</option>`;
    });

    const refreshAsigs = () => {
      const cursosSel = getMultiValues(pCursos);
      loadAsigs(pGrado.value, cursosSel, pAsigs);
    };
    pCursos.addEventListener('change', refreshAsigs);
    pCursos.addEventListener('input',  refreshAsigs);

    // Modo con botón (opcional)
    pAdd?.addEventListener('click', ()=>{
      msg.textContent='';
      const cursos = getMultiValues(pCursos);
      const asigs  = getMultiValues(pAsigs);
      if (!cursos.length || !asigs.length){
        msg.textContent = 'Selecciona al menos un curso y una asignatura.';
        return;
      }
      const cMap = Object.fromEntries([...pCursos.options].map(o=>[o.value,o.text]));
      const aMap = Object.fromEntries([...pAsigs.options].map(o=>[o.value,o.text]));
      cursos.forEach(c=>asigs.forEach(a=>{
        const key = `${c}:${a}`;
        if (!paresLista.some(x=>x.startsWith(key+'|'))) paresLista.push(`${key}|${cMap[c]}|${aMap[a]}`);
      }));
      renderPares();
    });

    pList?.addEventListener('click', (ev)=>{
      const btn = ev.target.closest('button'); if (!btn) return;
      if (btn.classList.contains('p-del')){
        const key = btn.dataset.key;
        paresLista = paresLista.filter(x=>!x.startsWith(key+'|'));
        renderPares();
      }
    });

    pClear?.addEventListener('click', ()=>{
      for (const o of pCursos.options) o.selected = false;
      for (const o of pAsigs.options)  o.selected = false;
    });

    // ===== Envío =====
    form.addEventListener('submit', async (ev)=>{
      ev.preventDefault();
      msg.textContent = '';

      const perfil = (form.querySelector('input[name="perfil"]:checked')?.value || '').toLowerCase();

      // Rellenar ocultos según perfil
      if (perfil === 'alumno') {
        gradoHidden.value = aGrado.value || '';
        cursoHidden.value = aCurso.value || '';
        if (!gradoHidden.value || !cursoHidden.value) {
          msg.textContent = 'Selecciona grado y curso.';
          return;
        }
      } else if (perfil === 'profesor') {
        // Para profesor no enviamos grado/curso globales: se calculan por cada par
        gradoHidden.value = '';
        cursoHidden.value = '';
      } else {
        msg.textContent = 'Perfil no válido.';
        return;
      }

      // Crear FormData DESPUÉS de setear los ocultos
      const fd = new FormData(form);

      // Para profesor: construir pares finales (aunque no uses la lista)
      if (perfil === 'profesor') {
        let finales = [...paresLista];
        const cursos = getMultiValues(pCursos);
        const asigs  = getMultiValues(pAsigs);
        if (cursos.length && asigs.length){
          const cMap = Object.fromEntries([...pCursos.options].map(o=>[o.value,o.text]));
          const aMap = Object.fromEntries([...pAsigs.options].map(o=>[o.value,o.text]));
          cursos.forEach(c=>asigs.forEach(a=>{
            const key = `${c}:${a}`;
            if (!finales.some(x=>x.startsWith(key+'|'))) finales.push(`${key}|${cMap[c]}|${aMap[a]}`);
          }));
        }
        const keys = finales.map(p => p.split('|')[0]).filter(Boolean);
        if (!keys.length){
          msg.textContent = 'Añade al menos una asignación (curso↔asignatura).';
          return;
        }
        // Enviar como pares[]
        keys.forEach(k => fd.append('pares[]', k));
      }

      try{
        const res = await fetch(form.action, { method:'POST', body:fd, headers:{ 'Accept':'application/json' }});
        const text = await res.text();
        let json = {}; try{ json = JSON.parse(text); }catch{ json = { success:false, error:text }; }
        if (json.success){
          window.location.href = json.redirect || '/Bancalia/panel.php';
        } else {
          msg.textContent = json.error || 'No se pudo completar el registro.';
        }
      }catch(err){
        msg.textContent = err.message;
      }
    });

    renderPares();
  });
})();
