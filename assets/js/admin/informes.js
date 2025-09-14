(() => {
  document.addEventListener('DOMContentLoaded', () => {
    const cont = document.getElementById('tab-informes');
    if (!cont) return;

    // Filtros
    const infFrom  = document.getElementById('infFrom');
    const infTo    = document.getElementById('infTo');
    const infTipo  = document.getElementById('infTipo');
    const infVis   = document.getElementById('infVis');
    const infAutor = document.getElementById('infAutor');
    const infBuscar= document.getElementById('infBuscar');
    const infHoy   = document.getElementById('infHoy');
    const inf30    = document.getElementById('inf30');
    const infTodo  = document.getElementById('infTodo');

    // KPIs
    const infKpis  = document.getElementById('infKpis');

    // Tabla tipo
    const tipoBody = document.getElementById('infTipoBody');
    const tipoPrev = document.getElementById('infTipoPrev');
    const tipoNext = document.getElementById('infTipoNext');
    const tipoMeta = document.getElementById('infTipoMeta');
    const tipoCsv  = document.getElementById('infTipoCsv');

    // Tabla autor
    const autBody  = document.getElementById('infAutorBody');
    const autPrev  = document.getElementById('infAutorPrev');
    const autNext  = document.getElementById('infAutorNext');
    const autMeta  = document.getElementById('infAutorMeta');
    const autCsv   = document.getElementById('infAutorCsv');

    // Estado
    let tPage=1, tPer=10, tLast=1, tTotal=0;
    let aPage=1, aPer=10, aLast=1, aTotal=0;

    // Utils
    function setBtn(btn, d){ if(!btn)return; btn.disabled=!!d; btn.classList.toggle('btn-disabled', !!d); }
    function fmt(n){ return (n||0).toLocaleString('es'); }
    function qsFilters(){
      const u = new URLSearchParams();
      if (infFrom.value) u.set('from', infFrom.value);
      if (infTo.value)   u.set('to', infTo.value);
      if (infTipo.value) u.set('tipo_id', infTipo.value);
      if (infVis.value)  u.set('visibilidad', infVis.value);
      if (infAutor.value)u.set('autor_id', infAutor.value);
      return u;
    }
    function setRange(days){
      const now = new Date();
      const to = new Date(now.getFullYear(), now.getMonth(), now.getDate());
      const from = new Date(to);
      from.setDate(to.getDate() - days + 1);
      infFrom.value = from.toISOString().slice(0,10);
      infTo.value   = to.toISOString().slice(0,10);
    }

    // Cargar selects (tipos, autores)
    function loadFilters(){
      $fetchJSON('/Bancalia/api/admin/actividades_filters.php').then(j=>{
        const tipos = j.tipos || [];
        infTipo.innerHTML = `<option value="">Todos</option>` + tipos.map(t=>`<option value="${t.id}">${t.nombre}</option>`).join('');
        const prof = j.profesores || [];
        infAutor.innerHTML = `<option value="">Todos</option>` + prof.map(p=>`<option value="${p.id}">${p.nombre} (${p.email})</option>`).join('');
      }).catch(()=>{});
    }

    // KPIs
    function loadKpis(){
      const qs = qsFilters();
      const url = '/Bancalia/api/admin/informes_kpis.php?' + qs.toString();
      infKpis.innerHTML = `<div class="card p-4">Cargando…</div>`;
      $fetchJSON(url).then(j=>{
        const k = (t,v)=>`<div class="card p-4"><div class="text-xs text-gray-500">${t}</div><div class="text-xl font-semibold mt-1">${fmt(v)}</div></div>`;
        const d = j.data || {};
        infKpis.innerHTML = [
          k('Actividades (periodo)', d.total),
          k('Compartidas', d.compartidas),
          k('Privadas', d.privadas),
          k('Profesores activos', d.autores_activos),
        ].join('');
      }).catch(e=>{
        infKpis.innerHTML = `<div class="card p-4 text-red-600">${e.message}</div>`;
      });
    }

    // Tabla por tipo
    function loadTipo(){
      const qs = qsFilters();
      qs.set('page', tPage); qs.set('per_page', tPer);
      const url = '/Bancalia/api/admin/informes_tipo.php?' + qs.toString();
      tipoBody.innerHTML = `<tr><td colspan="4" class="py-6 text-center text-gray-500">Cargando…</td></tr>`;
      setBtn(tipoPrev, tPage<=1); setBtn(tipoNext, true);
      $fetchJSON(url).then(j=>{
        tTotal = j.total || 0;
        tLast  = Math.max(1, Math.ceil(tTotal / tPer));
        if (tTotal>0 && tPage>tLast){ tPage=tLast; return loadTipo(); }
        if (tTotal===0 && tPage!==1){ tPage=1; return loadTipo(); }
        const rows = j.data || [];
        tipoBody.innerHTML = rows.length ? rows.map(r=>`
          <tr class="border-b">
            <td class="py-2 pr-3">${r.tipo ?? '—'}</td>
            <td class="py-2 pr-3">${fmt(r.total)}</td>
            <td class="py-2 pr-3">${fmt(r.compartidas)}</td>
            <td class="py-2 pr-3">${fmt(r.privadas)}</td>
          </tr>`).join('') : `<tr><td colspan="4" class="py-6 text-center text-gray-500">Sin resultados.</td></tr>`;
        tipoMeta.textContent = `Mostrando ${rows.length} de ${tTotal} (página ${tPage} de ${tLast})`;
        setBtn(tipoPrev, tPage<=1); setBtn(tipoNext, tPage>=tLast);
      }).catch(e=>{
        tipoBody.innerHTML = `<tr><td colspan="4" class="py-6 text-center text-red-600">${e.message}</td></tr>`;
        tipoMeta.textContent = '';
        setBtn(tipoPrev,true); setBtn(tipoNext,true);
      });
    }

    // Tabla por autor
    function loadAutor(){
      const qs = qsFilters();
      qs.set('page', aPage); qs.set('per_page', aPer);
      const url = '/Bancalia/api/admin/informes_autor.php?' + qs.toString();
      autBody.innerHTML = `<tr><td colspan="5" class="py-6 text-center text-gray-500">Cargando…</td></tr>`;
      setBtn(autPrev, aPage<=1); setBtn(autNext, true);
      $fetchJSON(url).then(j=>{
        aTotal = j.total || 0;
        aLast  = Math.max(1, Math.ceil(aTotal / aPer));
        if (aTotal>0 && aPage>aLast){ aPage=aLast; return loadAutor(); }
        if (aTotal===0 && aPage!==1){ aPage=1; return loadAutor(); }
        const rows = j.data || [];
        autBody.innerHTML = rows.length ? rows.map(r=>`
          <tr class="border-b">
            <td class="py-2 pr-3">${r.nombre}</td>
            <td class="py-2 pr-3">${r.email}</td>
            <td class="py-2 pr-3">${fmt(r.total)}</td>
            <td class="py-2 pr-3">${fmt(r.compartidas)}</td>
            <td class="py-2 pr-3">${fmt(r.privadas)}</td>
          </tr>`).join('') : `<tr><td colspan="5" class="py-6 text-center text-gray-500">Sin resultados.</td></tr>`;
        autMeta.textContent = `Mostrando ${rows.length} de ${aTotal} (página ${aPage} de ${aLast})`;
        setBtn(autPrev, aPage<=1); setBtn(autNext, aPage>=aLast);
      }).catch(e=>{
        autBody.innerHTML = `<tr><td colspan="5" class="py-6 text-center text-red-600">${e.message}</td></tr>`;
        autMeta.textContent = '';
        setBtn(autPrev,true); setBtn(autNext,true);
      });
    }

    // CSV links
    function refreshCsvLinks(){
      const qs = qsFilters();
      tipoCsv.href = '/Bancalia/api/admin/informes_export.php?group=tipo&' + qs.toString();
      autCsv.href  = '/Bancalia/api/admin/informes_export.php?group=autor&' + qs.toString();
    }

    // Eventos
    infBuscar.addEventListener('click', ()=>{ tPage=1; aPage=1; loadKpis(); loadTipo(); loadAutor(); refreshCsvLinks(); });
    infHoy.addEventListener('click', ()=>{ setRange(1); infBuscar.click(); });
    inf30.addEventListener('click', ()=>{ setRange(30); infBuscar.click(); });
    infTodo.addEventListener('click', ()=>{ infFrom.value=''; infTo.value=''; infBuscar.click(); });

    tipoPrev.addEventListener('click', ()=>{ if (tPage>1){ tPage--; loadTipo(); } });
    tipoNext.addEventListener('click', ()=>{ if (tPage<tLast){ tPage++; loadTipo(); } });
    autPrev.addEventListener('click',  ()=>{ if (aPage>1){ aPage--; loadAutor(); } });
    autNext.addEventListener('click',  ()=>{ if (aPage<aLast){ aPage++; loadAutor(); } });

    // Init
    loadFilters();
    setRange(30);
    infBuscar.click();
  });
})();
