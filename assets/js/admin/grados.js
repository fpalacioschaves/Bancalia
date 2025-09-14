(() => {
  document.addEventListener('DOMContentLoaded', () => {
    const gradosBody = document.getElementById('gradosBody');
    if (!gradosBody) return; // si no está la pestaña de catálogos visible en DOM

    let gPage = 1, gPerPage = 10, gLastPage = 1, gTotal = 0, gQuery = '';

    const gradoQ = document.getElementById('gradoQ');
    const gradoBuscar = document.getElementById('gradoBuscar');
    const gradoNuevo = document.getElementById('gradoNuevo');
    const gradoPrev = document.getElementById('gradoPrev');
    const gradoNext = document.getElementById('gradoNext');
    const gradoMeta = document.getElementById('gradoMeta');

    const gradoFormWrap = document.getElementById('gradoFormWrap');
    const gradoForm = document.getElementById('gradoForm');
    const gradoFormTitle = document.getElementById('gradoFormTitle');
    const gradoId = document.getElementById('gradoId');
    const gradoNombre = document.getElementById('gradoNombre');
    const gradoMsg = document.getElementById('gradoMsg');

    function updatePager() {
      $setBtnState(gradoPrev, gPage <= 1);
      $setBtnState(gradoNext, gPage >= gLastPage);
    }

    function refreshGradosSelects() {
      // Rellena selects usados por Cursos si existen
      $fetchJSON('/Bancalia/api/grados.php').then(j => {
        const list = j.data || [];
        const cursGrado = document.getElementById('cursGrado');
        const cursoGrado = document.getElementById('cursoGrado');
        if (cursGrado) {
          const v = cursGrado.value;
          cursGrado.innerHTML = `<option value="">Todos los grados</option>` + list.map(g => `<option value="${g.id}">${g.nombre}</option>`).join('');
          if ([...cursGrado.options].some(o => o.value === v)) cursGrado.value = v;
        }
        if (cursoGrado) {
          const v2 = cursoGrado.value;
          cursoGrado.innerHTML = list.map(g => `<option value="${g.id}">${g.nombre}</option>`).join('');
          if ([...cursoGrado.options].some(o => o.value === v2)) cursoGrado.value = v2;
        }
      }).catch(()=>{});
    }

    function loadGrados() {
      gradosBody.innerHTML = `<tr><td colspan="4" class="py-6 text-center text-gray-500">Cargando…</td></tr>`;
      updatePager();
      const url = new URL('/Bancalia/api/admin/grados_list.php', window.location.origin);
      url.searchParams.set('page', gPage);
      url.searchParams.set('per_page', gPerPage);
      if (gQuery) url.searchParams.set('q', gQuery);

      $fetchJSON(url.toString()).then(j => {
        gTotal = j.total || 0;
        gLastPage = Math.max(1, Math.ceil(gTotal / gPerPage));
        if (gTotal > 0 && gPage > gLastPage) { gPage = gLastPage; return loadGrados(); }
        if (gTotal === 0 && gPage !== 1) { gPage = 1; return loadGrados(); }

        const rows = j.data || [];
        gradosBody.innerHTML = rows.length ? rows.map(g => `
          <tr class="border-b">
            <td class="py-2 pr-3">${g.id}</td>
            <td class="py-2 pr-3">${g.nombre}</td>
            <td class="py-2 pr-3">${g.cursos}</td>
            <td class="py-2">
              <div class="flex gap-2">
                <button class="ui-btn btn-gray g-edit" data-id="${g.id}" data-nombre="${String(g.nombre).replace(/"/g,'&quot;')}">Editar</button>
                <button class="ui-btn btn-red g-del" data-id="${g.id}">Eliminar</button>
              </div>
            </td>
          </tr>`).join('') : `<tr><td colspan="4" class="py-6 text-center text-gray-500">Sin resultados.</td></tr>`;

        gradoMeta.textContent = `Mostrando ${rows.length} de ${gTotal} (página ${gPage} de ${gLastPage})`;
        updatePager();
        refreshGradosSelects();
      }).catch(e => {
        gradosBody.innerHTML = `<tr><td colspan="4" class="py-6 text-center text-red-600">${e.message}</td></tr>`;
        gradoMeta.textContent = '';
        $setBtnState(gradoPrev, true); $setBtnState(gradoNext, true);
      });
    }

    gradoBuscar.addEventListener('click', () => { gQuery = gradoQ.value.trim(); gPage = 1; loadGrados(); });
    gradoPrev.addEventListener('click', () => { if (gPage > 1) { gPage--; loadGrados(); } });
    gradoNext.addEventListener('click', () => { if (gPage < gLastPage) { gPage++; loadGrados(); } });

    gradoNuevo.addEventListener('click', () => {
      gradoFormTitle.textContent = 'Nuevo grado';
      gradoId.value = ''; gradoNombre.value = ''; gradoMsg.textContent = '';
      gradoFormWrap.classList.remove('hidden'); gradoNombre.focus();
    });

    gradosBody.addEventListener('click', (ev) => {
      const btn = ev.target.closest('button'); if (!btn) return;
      if (btn.classList.contains('g-edit')) {
        gradoFormTitle.textContent = 'Editar grado';
        gradoId.value = btn.dataset.id;
        gradoNombre.value = btn.dataset.nombre || '';
        gradoMsg.textContent = '';
        gradoFormWrap.classList.remove('hidden'); gradoNombre.focus();
      }
      if (btn.classList.contains('g-del')) {
        const id = btn.dataset.id;
        if (!confirm('¿Eliminar este grado? Si tiene dependencias, no se eliminará.')) return;
        const fd = new FormData(); fd.append('id', id);
        $fetchJSON('/Bancalia/api/admin/grados_delete.php', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
          .then(() => loadGrados())
          .catch(e => alert(e.message));
      }
    });

    gradoForm.addEventListener('submit', (ev) => {
      ev.preventDefault();
      gradoMsg.textContent = '';
      const id = (gradoId.value || '').trim();
      const nombre = (gradoNombre.value || '').trim();
      const fd = new FormData();
      if (id) {
        fd.append('id', id); fd.append('nombre', nombre);
        $fetchJSON('/Bancalia/api/admin/grados_update.php', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
          .then(() => { gradoFormWrap.classList.add('hidden'); loadGrados(); })
          .catch(e => gradoMsg.textContent = e.message);
      } else {
        fd.append('nombre', nombre);
        $fetchJSON('/Bancalia/api/admin/grados_create.php', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
          .then(() => { gradoFormWrap.classList.add('hidden'); gPage = 1; loadGrados(); })
          .catch(e => gradoMsg.textContent = e.message);
      }
    });
    document.getElementById('gradoCancelar').addEventListener('click', () => {
      gradoFormWrap.classList.add('hidden');
    });

    // Carga inicial
    loadGrados();
  });
})();
