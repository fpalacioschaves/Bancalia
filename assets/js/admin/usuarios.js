(() => {
  document.addEventListener('DOMContentLoaded', () => {
    const body = document.getElementById('usrBody');
    if (!body) return;

    let page=1, per=10, last=1, total=0, q='', rol='';

    const usrQ = document.getElementById('usrQ');
    const usrRol = document.getElementById('usrRol');
    const usrBuscar = document.getElementById('usrBuscar');
    const usrPrev = document.getElementById('usrPrev');
    const usrNext = document.getElementById('usrNext');
    const usrMeta = document.getElementById('usrMeta');

    function setBtn(b,d){ b.disabled=!!d; b.classList.toggle('btn-disabled', !!d); }

    function loadUsers(){
      body.innerHTML = `<tr><td colspan="6" class="py-6 text-center text-gray-500">Cargando…</td></tr>`;
      const url = new URL('/Bancalia/api/admin/usuarios_list.php', window.location.origin);
      url.searchParams.set('page', page);
      url.searchParams.set('per_page', per);
      if (q) url.searchParams.set('q', q);
      if (rol) url.searchParams.set('rol_id', rol);

      $fetchJSON(url.toString()).then(j=>{
        total = j.total || 0; last = Math.max(1, Math.ceil(total/per));
        if (total>0 && page>last){ page=last; return loadUsers(); }
        const rows = j.data || [];
        body.innerHTML = rows.length ? rows.map(u=>`
          <tr class="border-b">
            <td class="py-2 pr-3">${u.id}</td>
            <td class="py-2 pr-3">${u.nombre}</td>
            <td class="py-2 pr-3">${u.email}</td>
            <td class="py-2 pr-3">${u.rol}</td>
            <td class="py-2 pr-3"><span class="tag ${u.estado==='activo'?'tag-ok':'tag-bad'}">${u.estado}</span></td>
            <td class="py-2">
              <div class="flex gap-2">
                <button class="ui-btn border u-toggle" data-id="${u.id}" data-estado="${u.estado}">${u.estado==='activo'?'Desactivar':'Activar'}</button>
                <button class="ui-btn btn-red u-del" data-id="${u.id}">Eliminar</button>
                ${u.rol_id==2 ? `<button class="ui-btn btn-blue u-imp" data-id="${u.id}" data-nombre="${String(u.nombre).replace(/"/g,'&quot;')}" data-email="${u.email}">Imparte</button>` : ''}
              </div>
            </td>
          </tr>`).join('') : `<tr><td colspan="6" class="py-6 text-center text-gray-500">Sin resultados.</td></tr>`;
        usrMeta.textContent = `Mostrando ${rows.length} de ${total} (página ${page} de ${last})`;
        setBtn(usrPrev, page<=1); setBtn(usrNext, page>=last);
      }).catch(e=>{
        body.innerHTML = `<tr><td colspan="6" class="py-6 text-center text-red-600">${e.message}</td></tr>`;
        usrMeta.textContent = '';
        setBtn(usrPrev,true); setBtn(usrNext,true);
      });
    }

    usrBuscar.addEventListener('click', ()=>{ q=usrQ.value.trim(); rol=usrRol.value; page=1; loadUsers(); });
    usrPrev.addEventListener('click', ()=>{ if(page>1){ page--; loadUsers(); } });
    usrNext.addEventListener('click', ()=>{ if(page<last){ page++; loadUsers(); } });

    body.addEventListener('click', (ev)=>{
      const btn = ev.target.closest('button'); if (!btn) return;

      if (btn.classList.contains('u-toggle')){
        const id = btn.dataset.id, estado = btn.dataset.estado;
        const fd = new FormData(); fd.append('id', id); fd.append('estado', estado==='activo'?'inactivo':'activo');
        $fetchJSON('/Bancalia/api/admin/usuarios_estado.php', { method:'POST', body:fd, headers:{'Accept':'application/json'} })
          .then(loadUsers).catch(e=>alert(e.message));
      }

      if (btn.classList.contains('u-del')){
        const id = btn.dataset.id;
        if (!confirm('¿Eliminar este usuario?')) return;
        const fd = new FormData(); fd.append('id', id);
        $fetchJSON('/Bancalia/api/admin/usuarios_delete.php', { method:'POST', body:fd, headers:{'Accept':'application/json'} })
          .then(loadUsers).catch(e=>alert(e.message));
      }

      if (btn.classList.contains('u-imp')){
        openImparte(btn.dataset.id, btn.dataset.nombre, btn.dataset.email);
      }
    });

    // ====== Gestión IMPARTE ======
    const wrap = document.getElementById('imparteWrap');
    const impNombre = document.getElementById('impNombre');
    const impEmail  = document.getElementById('impEmail');
    const impProfesorId = document.getElementById('impProfesorId');
    const impGrado = document.getElementById('impGrado');
    const impCursos = document.getElementById('impCursos');
    const impAsigs  = document.getElementById('impAsigs');
    const impAdd    = document.getElementById('impAdd');
    const impClearSel = document.getElementById('impClearSel');
    const impBody   = document.getElementById('impBody');
    const impGuardar= document.getElementById('impGuardar');
    const impCancelar = document.getElementById('impCancelar');
    const impMsg    = document.getElementById('impMsg');

    let list = []; // pares actuales "curso:asig|Curso|Asig"

    function loadGrados(){
      return $fetchJSON('/Bancalia/api/grados.php').then(j=>{
        const arr = j.data||[];
        impGrado.innerHTML = arr.map(g=>`<option value="${g.id}">${g.nombre}</option>`).join('');
      });
    }
    function loadCursos(gid){
      if (!gid){ impCursos.innerHTML=''; return Promise.resolve(); }
      const url = new URL('/Bancalia/api/cursos.php', window.location.origin);
      url.searchParams.set('grado_id', gid);
      return $fetchJSON(url.toString()).then(j=>{
        const arr = j.data||[];
        impCursos.innerHTML = arr.map(c=>`<option value="${c.id}">${c.nombre}</option>`).join('');
      });
    }
    function loadAsigs(gid, cids){
      if (!gid){ impAsigs.innerHTML=''; return Promise.resolve(); }
      const url = new URL('/Bancalia/api/asignaturas_by.php', window.location.origin);
      url.searchParams.set('grado_id', gid);
      if (cids.length) url.searchParams.set('curso_ids', cids.join(','));
      return $fetchJSON(url.toString()).then(j=>{
        const arr = j.data||[];
        impAsigs.innerHTML = arr.map(a=>`<option value="${a.id}">${a.nombre}</option>`).join('');
      });
    }
    function getMulti(sel){ return [...sel.selectedOptions].map(o=>o.value).filter(Boolean); }
    function renderList(){
      if (!list.length){ impBody.innerHTML = `<tr><td colspan="3" class="py-3 text-center text-gray-500">Sin combinaciones.</td></tr>`; return; }
      impBody.innerHTML = list.map(p=>{
        const [ids, cn, an] = p.split('|'); // "c:a|Curso|Asig"
        return `<tr class="border-b">
          <td class="py-1 pr-3">${cn}</td>
          <td class="py-1 pr-3">${an}</td>
          <td class="py-1"><button class="ui-btn btn-red imp-del" data-key="${ids}">Quitar</button></td>
        </tr>`;
      }).join('');
    }

    function openImparte(id, nombre, email){
      impProfesorId.value = id;
      impNombre.textContent = nombre;
      impEmail.textContent  = `(${email})`;
      impMsg.textContent = '';
      list = [];

      // cargar selects + lista actual
      Promise.all([
        loadGrados().then(()=> loadCursos(impGrado.value)).then(()=> loadAsigs(impGrado.value, getMulti(impCursos))),
        $fetchJSON('/Bancalia/api/admin/profes_imparte_list.php?profesor_id='+id).then(j=>{
          const arr = j.data||[];
          arr.forEach(r=>{
            list.push(`${r.curso_id}:${r.asignatura_id}|${r.curso}|${r.asignatura}`);
          });
          renderList();
        })
      ]).then(()=>{
        wrap.classList.remove('hidden');
      }).catch(e=>{
        alert(e.message);
      });
    }

    impGrado.addEventListener('change', async ()=>{
      await loadCursos(impGrado.value);
      await loadAsigs(impGrado.value, getMulti(impCursos));
    });
    impCursos.addEventListener('change', ()=> loadAsigs(impGrado.value, getMulti(impCursos)));

    impAdd.addEventListener('click', ()=>{
      impMsg.textContent='';
      const cids = getMulti(impCursos), aids = getMulti(impAsigs);
      if (!impGrado.value || !cids.length || !aids.length){ impMsg.textContent='Selecciona grado, curso(s) y asignatura(s).'; return; }
      const cMap = Object.fromEntries([...impCursos.options].map(o=>[o.value,o.text]));
      const aMap = Object.fromEntries([...impAsigs.options].map(o=>[o.value,o.text]));
      cids.forEach(c=>aids.forEach(a=>{
        const key = `${c}:${a}`;
        if (!list.some(x=>x.startsWith(key+'|'))) list.push(`${key}|${cMap[c]}|${aMap[a]}`);
      }));
      renderList();
    });

    impBody.addEventListener('click', (ev)=>{
      const btn = ev.target.closest('button'); if(!btn) return;
      if (btn.classList.contains('imp-del')){
        const key = btn.dataset.key; list = list.filter(x=>!x.startsWith(key+'|')); renderList();
      }
    });

    impClearSel.addEventListener('click', ()=>{
      [...impCursos.options].forEach(o=>o.selected=false);
      [...impAsigs.options].forEach(o=>o.selected=false);
    });

    impGuardar.addEventListener('click', ()=>{
      if (!impProfesorId.value) return;
      const fd = new FormData();
      fd.append('profesor_id', impProfesorId.value);
      list.forEach(p=>fd.append('pairs[]', p.split('|')[0])); // "c:a"
      $fetchJSON('/Bancalia/api/admin/profes_imparte_save.php', { method:'POST', body:fd, headers:{'Accept':'application/json'} })
        .then(()=>{ impMsg.textContent='Guardado.'; setTimeout(()=>wrap.classList.add('hidden'), 600); })
        .catch(e=> impMsg.textContent = e.message);
    });

    impCancelar.addEventListener('click', ()=> wrap.classList.add('hidden'));

    // init
    loadUsers();
  });
})();
