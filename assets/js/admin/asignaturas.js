(() => {
  document.addEventListener('DOMContentLoaded', () => {
    const asigBody = document.getElementById('asigBody');
    if (!asigBody) return; // la sección no está en el DOM (otra pestaña activa)

    // Estado
    let aPage = 1, aPerPage = 10, aLastPage = 1, aTotal = 0, aQuery = '', aGrado = '';

    // Controles
    const asigQ = document.getElementById('asigQ');
    const asigBuscar = document.getElementById('asigBuscar');
    const asigNuevo = document.getElementById('asigNuevo');
    const asigPrev = document.getElementById('asigPrev');
    const asigNext = document.getElementById('asigNext');
    const asigMeta = document.getElementById('asigMeta');
    const asigGradoFiltro = document.getElementById('asigGradoFiltro');

    // Formulario
    const asigFormWrap = document.getElementById('asigFormWrap');
    const asigForm = document.getElementById('asigForm');
    const asigFormTitle = document.getElementById('asigFormTitle');
    const asigId = document.getElementById('asigId');
    const asigNombre = document.getElementById('asigNombre');
    const asigCodigo = document.getElementById('asigCodigo');
    const asigGrado = document.getElementById('asigGrado');
    const asigMsg = document.getElementById('asigMsg');

    // Helpers
    function updatePager() {
      $setBtnState(asigPrev, aPage <= 1);
      $setBtnState(asigNext, aPage >= aLastPage);
    }

    function loadGradosOptions() {
      $fetchJSON('/Bancalia/api/grados.php').then(j => {
        const list = j.data || [];
        if (asigGradoFiltro) {
          const v = asigGradoFiltro.value;
          asigGradoFiltro.innerHTML = `<option value="">Todos los grados</option>` +
            list.map(g => `<option value="${g.id}">${g.nombre}</option>`).join('');
          if ([...asigGradoFiltro.options].some(o => o.value === v)) asigGradoFiltro.value = v;
        }
        if (asigGrado) {
          const v2 = asigGrado.value;
          asigGrado.innerHTML = list.map(g => `<option value="${g.id}">${g.nombre}</option>`).join('');
          if ([...asigGrado.options].some(o => o.value === v2)) asigGrado.value = v2;
        }
      }).catch(()=>{});
    }

    function loadAsignaturas() {
      asigBody.innerHTML = `<tr><td colspan="6" class="py-6 text-center text-gray-500">Cargando…</td></tr>`;
      updatePager();
      const url = new URL('/Bancalia/api/admin/asignaturas_list.php', window.location.origin);
      url.searchParams.set('page', aPage);
      url.searchParams.set('per_page', aPerPage);
      if (aGrado) url.searchParams.set('grado_id', aGrado);
      if (aQuery) url.searchParams.set('q', aQuery);

      $fetchJSON(url.toString()).then(j => {
        aTotal = j.total || 0;
        aLastPage = Math.max(1, Math.ceil(aTotal / aPerPage));
        if (aTotal > 0 && aPage > aLastPage) { aPage = aLastPage; return loadAsignaturas(); }
        if (aTotal === 0 && aPage !== 1) { aPage = 1; return loadAsignaturas(); }

        const rows = j.data || [];
        asigBody.innerHTML = rows.length ? rows.map(a => `
          <tr class="border-b">
            <td class="py-2 pr-3">${a.id}</td>
            <td class="py-2 pr-3">${a.nombre}</td>
            <td class="py-2 pr-3">${a.codigo ?? ''}</td>
            <td class="py-2 pr-3">${a.grado}</td>
            <td class="py-2 pr-3">${a.temas}</td>
            <td class="py-2">
              <div class="flex gap-2">
                <button class="ui-btn btn-gray a-edit"
                        data-id="${a.id}"
                        data-nombre="${String(a.nombre).replace(/"/g,'&quot;')}"
                        data-codigo="${a.codigo ? String(a.codigo).replace(/"/g,'&quot;') : ''}"
                        data-grado="${a.grado_id}">
                  Editar
                </button>
                <button class="ui-btn btn-red a-del" data-id="${a.id}">Eliminar</button>
              </div>
            </td>
          </tr>`).join('') : `<tr><td colspan="6" class="py-6 text-center text-gray-500">Sin resultados.</td></tr>`;

        asigMeta.textContent = `Mostrando ${rows.length} de ${aTotal} (página ${aPage} de ${aLastPage})`;
        updatePager();
      }).catch(e => {
        asigBody.innerHTML = `<tr><td colspan="6" class="py-6 text-center text-red-600">${e.message}</td></tr>`;
        asigMeta.textContent = '';
        $setBtnState(asigPrev, true); $setBtnState(asigNext, true);
      });
    }

    // Eventos Filtro/paginación
    asigBuscar.addEventListener('click', () => {
      aQuery = asigQ.value.trim();
      aGrado = asigGradoFiltro.value;
      aPage = 1;
      loadAsignaturas();
    });
    asigPrev.addEventListener('click', () => { if (aPage > 1) { aPage--; loadAsignaturas(); } });
    asigNext.addEventListener('click', () => { if (aPage < aLastPage) { aPage++; loadAsignaturas(); } });

    // Nuevo
    asigNuevo.addEventListener('click', () => {
      asigFormTitle.textContent = 'Nueva asignatura';
      asigId.value = '';
      asigNombre.value = '';
      asigCodigo.value = '';
      asigGrado.value = asigGradoFiltro.value || (asigGrado.options[0]?.value || '');
      asigMsg.textContent = '';
      asigFormWrap.classList.remove('hidden');
      asigNombre.focus();
    });

    // Delegación tabla (editar/eliminar)
    asigBody.addEventListener('click', (ev) => {
      const btn = ev.target.closest('button'); if (!btn) return;
      if (btn.classList.contains('a-edit')) {
        asigFormTitle.textContent = 'Editar asignatura';
        asigId.value     = btn.dataset.id;
        asigNombre.value = btn.dataset.nombre || '';
        asigCodigo.value = btn.dataset.codigo || '';
        asigGrado.value  = btn.dataset.grado || '';
        asigMsg.textContent = '';
        asigFormWrap.classList.remove('hidden');
        asigNombre.focus();
      }
      if (btn.classList.contains('a-del')) {
        const id = btn.dataset.id;
        if (!confirm('¿Eliminar esta asignatura? Si tiene temas/RA/profesores asociados, no se podrá eliminar.')) return;
        const fd = new FormData(); fd.append('id', id);
        $fetchJSON('/Bancalia/api/admin/asignaturas_delete.php', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
          .then(() => loadAsignaturas())
          .catch(e => alert(e.message));
      }
    });

    // Guardar create/update
    asigForm.addEventListener('submit', (ev) => {
      ev.preventDefault();
      asigMsg.textContent = '';

      const fd = new FormData();
      const id = (asigId.value || '').trim();
      const nombre = (asigNombre.value || '').trim();
      const codigo = (asigCodigo.value || '').trim();
      const grado_id = (asigGrado.value || '').trim();

      if (id) {
        fd.append('id', id);
        fd.append('nombre', nombre);
        fd.append('codigo', codigo);
        if (grado_id !== '') fd.append('grado_id', grado_id);
        $fetchJSON('/Bancalia/api/admin/asignaturas_update.php', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
          .then(() => { asigFormWrap.classList.add('hidden'); loadAsignaturas(); })
          .catch(e => asigMsg.textContent = e.message);
      } else {
        fd.append('grado_id', grado_id);
        fd.append('nombre', nombre);
        if (codigo) fd.append('codigo', codigo);
        $fetchJSON('/Bancalia/api/admin/asignaturas_create.php', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
          .then(() => { asigFormWrap.classList.add('hidden'); aPage = 1; loadAsignaturas(); })
          .catch(e => asigMsg.textContent = e.message);
      }
    });
    document.getElementById('asigCancelar')?.addEventListener('click', () => {
      asigFormWrap.classList.add('hidden');
    });

    // Inicial
    loadGradosOptions();
    loadAsignaturas();
  });
})();
