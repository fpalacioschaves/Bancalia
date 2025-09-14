(() => {
  document.addEventListener('DOMContentLoaded', () => {
    const actsBody = document.getElementById('actsBody');
    if (!actsBody) return;

    // Estado
    let aPage = 1, aPer = 10, aLast = 1, aTotal = 0;
    let q = '', tipo_id = '', vis = '', autor_id = '';

    // Controles
    const actQ = document.getElementById('actQ');
    const actTipo = document.getElementById('actTipo');
    const actVis = document.getElementById('actVis');
    const actAutor = document.getElementById('actAutor');
    const actBuscar = document.getElementById('actBuscar');
    const actPrev = document.getElementById('actPrev');
    const actNext = document.getElementById('actNext');
    const actMeta = document.getElementById('actMeta');

    function updatePager() {
      $setBtnState(actPrev, aPage <= 1);
      $setBtnState(actNext, aPage >= aLast);
    }

    function loadFilters() {
      $fetchJSON('/Bancalia/api/admin/actividades_filters.php').then(j => {
        const tipos = j.tipos || [];
        actTipo.innerHTML = `<option value="">Todos los tipos</option>` +
          tipos.map(t => `<option value="${t.id}">${t.nombre}</option>`).join('');

        const profes = j.profesores || [];
        actAutor.innerHTML = `<option value="">Todos los autores</option>` +
          profes.map(p => `<option value="${p.id}">${p.nombre} (${p.email})</option>`).join('');
      }).catch(()=>{});
    }

    function loadActividades() {
      actsBody.innerHTML = `<tr><td colspan="7" class="py-6 text-center text-gray-500">Cargando…</td></tr>`;
      updatePager();

      const url = new URL('/Bancalia/api/admin/actividades_list.php', window.location.origin);
      url.searchParams.set('page', aPage);
      url.searchParams.set('per_page', aPer);
      if (q) url.searchParams.set('q', q);
      if (tipo_id) url.searchParams.set('tipo_id', tipo_id);
      if (vis) url.searchParams.set('visibilidad', vis);
      if (autor_id) url.searchParams.set('autor_id', autor_id);

      $fetchJSON(url.toString()).then(j => {
        aTotal = j.total || 0;
        aLast = Math.max(1, Math.ceil(aTotal / aPer));
        if (aTotal > 0 && aPage > aLast) { aPage = aLast; return loadActividades(); }
        if (aTotal === 0 && aPage !== 1) { aPage = 1; return loadActividades(); }

        const rows = j.data || [];
        actsBody.innerHTML = rows.length ? rows.map(a => `
          <tr class="border-b">
            <td class="py-2 pr-3">${a.id}</td>
            <td class="py-2 pr-3">${a.titulo}</td>
            <td class="py-2 pr-3">${a.tipo_nombre ?? ''}</td>
            <td class="py-2 pr-3">${a.visibilidad}</td>
            <td class="py-2 pr-3">${a.estado ?? ''}</td>
            <td class="py-2 pr-3">${a.autor_nombre} <span class="text-gray-500">(${a.autor_email})</span></td>
            <td class="py-2">
              <div class="flex gap-2">
                <button class="ui-btn btn-red act-del" data-id="${a.id}">Eliminar</button>
              </div>
            </td>
          </tr>`).join('') : `<tr><td colspan="7" class="py-6 text-center text-gray-500">Sin resultados.</td></tr>`;

        actMeta.textContent = `Mostrando ${rows.length} de ${aTotal} (página ${aPage} de ${aLast})`;
        updatePager();
      }).catch(e => {
        actsBody.innerHTML = `<tr><td colspan="7" class="py-6 text-center text-red-600">${e.message}</td></tr>`;
        actMeta.textContent = '';
        $setBtnState(actPrev, true); $setBtnState(actNext, true);
      });
    }

    // Eventos
    actBuscar.addEventListener('click', () => {
      q = actQ.value.trim();
      tipo_id = actTipo.value;
      vis = actVis.value;             // solo 'privada' | 'compartida' (o vacío)
      autor_id = actAutor.value;
      aPage = 1;
      loadActividades();
    });
    actPrev.addEventListener('click', () => { if (aPage > 1) { aPage--; loadActividades(); } });
    actNext.addEventListener('click', () => { if (aPage < aLast) { aPage++; loadActividades(); } });

    actsBody.addEventListener('click', (ev) => {
      const btn = ev.target.closest('button'); if (!btn) return;
      if (btn.classList.contains('act-del')) {
        const id = btn.dataset.id;
        if (!confirm('¿Eliminar esta actividad? Esta acción no se puede deshacer.')) return;
        const fd = new FormData(); fd.append('id', id);
        $fetchJSON('/Bancalia/api/admin/actividades_delete.php', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
          .then(() => loadActividades())
          .catch(e => alert(e.message));
      }
    });

    // Inicial
    loadFilters();
    loadActividades();
  });
})();
