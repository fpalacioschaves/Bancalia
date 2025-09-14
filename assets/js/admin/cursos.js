(() => {
  document.addEventListener('DOMContentLoaded', () => {
    const cursosBody = document.getElementById('cursosBody');
    if (!cursosBody) return;

    let cPage = 1, cPerPage = 10, cLastPage = 1, cTotal = 0;
    let cGradoFilter = '', cQuery = '';

    const cursGrado = document.getElementById('cursGrado');
    const cursQ = document.getElementById('cursQ');
    const cursBuscar = document.getElementById('cursBuscar');
    const cursNuevo = document.getElementById('cursNuevo');
    const cursPrev = document.getElementById('cursPrev');
    const cursNext = document.getElementById('cursNext');
    const cursMeta = document.getElementById('cursMeta');

    const cursoFormWrap = document.getElementById('cursoFormWrap');
    const cursoForm = document.getElementById('cursoForm');
    const cursoFormTitle = document.getElementById('cursoFormTitle');
    const cursoId = document.getElementById('cursoId');
    const cursoGrado = document.getElementById('cursoGrado');
    const cursoNombre = document.getElementById('cursoNombre');
    const cursoOrden = document.getElementById('cursoOrden');
    const cursoMsg = document.getElementById('cursoMsg');

    function updatePager() {
      $setBtnState(cursPrev, cPage <= 1);
      $setBtnState(cursNext, cPage >= cLastPage);
    }

    function loadGradosOptions() {
      $fetchJSON('/Bancalia/api/grados.php').then(j => {
        const list = j.data || [];
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

    function loadCursos() {
      cursosBody.innerHTML = `<tr><td colspan="5" class="py-6 text-center text-gray-500">Cargando…</td></tr>`;
      updatePager();

      const url = new URL('/Bancalia/api/admin/cursos_list.php', window.location.origin);
      url.searchParams.set('page', cPage);
      url.searchParams.set('per_page', cPerPage);
      if (cGradoFilter) url.searchParams.set('grado_id', cGradoFilter);
      if (cQuery) url.searchParams.set('q', cQuery);

      $fetchJSON(url.toString()).then(j => {
        cTotal = j.total || 0;
        cLastPage = Math.max(1, Math.ceil(cTotal / cPerPage));
        if (cTotal > 0 && cPage > cLastPage) { cPage = cLastPage; return loadCursos(); }
        if (cTotal === 0 && cPage !== 1) { cPage = 1; return loadCursos(); }

        const rows = j.data || [];
        cursosBody.innerHTML = rows.length ? rows.map(c => `
          <tr class="border-b">
            <td class="py-2 pr-3">${c.id}</td>
            <td class="py-2 pr-3">${c.nombre}</td>
            <td class="py-2 pr-3">${c.grado}</td>
            <td class="py-2 pr-3">${c.orden}</td>
            <td class="py-2">
              <div class="flex gap-2">
                <button class="ui-btn btn-gray act-edit"
                        data-id="${c.id}" data-grado="${c.grado_id}"
                        data-nombre="${String(c.nombre).replace(/"/g,'&quot;')}"
                        data-orden="${c.orden}">Editar</button>
                <button class="ui-btn btn-red act-del" data-id="${c.id}">Eliminar</button>
              </div>
            </td>
          </tr>`).join('') : `<tr><td colspan="5" class="py-6 text-center text-gray-500">Sin resultados.</td></tr>`;

        cursMeta.textContent = `Mostrando ${rows.length} de ${cTotal} (página ${cPage} de ${cLastPage})`;
        updatePager();
      }).catch(e => {
        cursosBody.innerHTML = `<tr><td colspan="5" class="py-6 text-center text-red-600">${e.message}</td></tr>`;
        cursMeta.textContent = '';
        $setBtnState(cursPrev, true); $setBtnState(cursNext, true);
      });
    }

    // Eventos
    cursBuscar.addEventListener('click', () => {
      cGradoFilter = cursGrado.value; cQuery = cursQ.value.trim(); cPage = 1; loadCursos();
    });
    cursPrev.addEventListener('click', () => { if (cPage > 1) { cPage--; loadCursos(); } });
    cursNext.addEventListener('click', () => { if (cPage < cLastPage) { cPage++; loadCursos(); } });

    cursNuevo.addEventListener('click', () => {
      cursoFormTitle.textContent = 'Nuevo curso';
      cursoId.value = ''; cursoNombre.value = ''; cursoOrden.value = '';
      cursoGrado.value = cursGrado.value || (cursoGrado.options[0]?.value || '');
      cursoMsg.textContent = '';
      cursoFormWrap.classList.remove('hidden');
      cursoNombre.focus();
    });

    cursosBody.addEventListener('click', (ev) => {
      const btn = ev.target.closest('button'); if (!btn) return;
      if (btn.classList.contains('act-edit')) {
        cursoFormTitle.textContent = 'Editar curso';
        cursoId.value = btn.dataset.id;
        cursoNombre.value = btn.dataset.nombre || '';
        cursoOrden.value = btn.dataset.orden || '';
        cursoGrado.value = btn.dataset.grado || '';
        cursoMsg.textContent = '';
        cursoFormWrap.classList.remove('hidden');
        cursoNombre.focus();
      }
      if (btn.classList.contains('act-del')) {
        const id = btn.dataset.id;
        if (!confirm('¿Eliminar este curso? Si tiene dependencias, no se podrá eliminar.')) return;
        const fd = new FormData(); fd.append('id', id);
        $fetchJSON('/Bancalia/api/admin/cursos_delete.php', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
          .then(() => loadCursos())
          .catch(e => alert(e.message));
      }
    });

    cursoForm.addEventListener('submit', (ev) => {
      ev.preventDefault();
      cursoMsg.textContent = '';

      const fd = new FormData();
      const id = (cursoId.value || '').trim();
      const nombre = (cursoNombre.value || '').trim();
      const orden = (cursoOrden.value || '').trim();
      const grado_id = (cursoGrado.value || '').trim();

      if (id) {
        fd.append('id', id);
        if (grado_id !== '') fd.append('grado_id', grado_id);
        fd.append('nombre', nombre);
        if (orden !== '') fd.append('orden', orden);
        $fetchJSON('/Bancalia/api/admin/cursos_update.php', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
          .then(() => { cursoFormWrap.classList.add('hidden'); loadCursos(); })
          .catch(e => cursoMsg.textContent = e.message);
      } else {
        fd.append('grado_id', grado_id);
        fd.append('nombre', nombre);
        if (orden !== '') fd.append('orden', orden);
        $fetchJSON('/Bancalia/api/admin/cursos_create.php', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
          .then(() => { cursoFormWrap.classList.add('hidden'); cPage = 1; loadCursos(); })
          .catch(e => cursoMsg.textContent = e.message);
      }
    });
    document.getElementById('cursoCancelar')?.addEventListener('click', () => {
      cursoFormWrap.classList.add('hidden');
    });

    // Inicial
    loadGradosOptions();
    loadCursos();
  });
})();
