(() => {
  document.addEventListener('DOMContentLoaded', () => {
    const asigBody = document.getElementById('asigBody');
    if (!asigBody) return;

    // Estado
    let aPage = 1, aPerPage = 10, aLastPage = 1, aTotal = 0, aQuery = '', aGrado = '', aCurso = '';

    // Controles filtro
    const asigQ = document.getElementById('asigQ');
    const asigBuscar = document.getElementById('asigBuscar');
    const asigNuevo = document.getElementById('asigNuevo');
    const asigPrev = document.getElementById('asigPrev');
    const asigNext = document.getElementById('asigNext');
    const asigMeta = document.getElementById('asigMeta');
    const asigGradoFiltro = document.getElementById('asigGradoFiltro');
    const asigCursoFiltro = document.getElementById('asigCursoFiltro');

    // Formulario
    const asigFormWrap = document.getElementById('asigFormWrap');
    const asigForm = document.getElementById('asigForm');
    const asigFormTitle = document.getElementById('asigFormTitle');
    const asigId = document.getElementById('asigId');
    const asigNombre = document.getElementById('asigNombre');
    const asigCodigo = document.getElementById('asigCodigo');
    const asigGrado = document.getElementById('asigGrado');
    const asigCursos = document.getElementById('asigCursos');
    const asigMsg = document.getElementById('asigMsg');

    // Utils
    function updatePager() {
      $setBtnState(asigPrev, aPage <= 1);
      $setBtnState(asigNext, aPage >= aLastPage);
    }
    function setSelectOptions(sel, list, firstLabel) {
      const keep = sel.value;
      sel.innerHTML = (firstLabel !== undefined ? `<option value="">${firstLabel}</option>` : '') +
        list.map(o => `<option value="${o.id}">${o.nombre}</option>`).join('');
      if ([...sel.options].some(o => o.value === keep)) sel.value = keep;
    }
    function setMultiSelect(sel, values) {
      const set = new Set(values.map(String));
      [...sel.options].forEach(o => { o.selected = set.has(o.value); });
    }
    function getMultiValues(sel) {
      return [...sel.selectedOptions].map(o => o.value).filter(Boolean);
    }

    // Cargar grados
    function loadGrados() {
      return $fetchJSON('/Bancalia/api/grados.php').then(j => {
        const list = j.data || [];
        setSelectOptions(asigGradoFiltro, list, 'Todos los grados');
        setSelectOptions(asigGrado, list);
      });
    }

    // Cargar cursos por grado en filtro y en form
    function loadCursosFor(gradoId, targetSelect, withAllLabel) {
      if (!gradoId) {
        targetSelect.innerHTML = withAllLabel ? `<option value="">Todos los cursos</option>` : '';
        targetSelect.disabled = !!withAllLabel;
        return Promise.resolve();
      }
      const url = new URL('/Bancalia/api/cursos.php', window.location.origin);
      url.searchParams.set('grado_id', gradoId);
      return $fetchJSON(url.toString()).then(j => {
        const list = (j.data || []).map(c => ({ id: String(c.id), nombre: c.nombre }));
        targetSelect.disabled = false;
        if (withAllLabel) {
          setSelectOptions(targetSelect, list, 'Todos los cursos');
        } else {
          targetSelect.innerHTML = list.map(c => `<option value="${c.id}">${c.nombre}</option>`).join('');
        }
      });
    }

    // Lista
    function loadAsignaturas() {
      asigBody.innerHTML = `<tr><td colspan="7" class="py-6 text-center text-gray-500">Cargando…</td></tr>`;
      updatePager();
      const url = new URL('/Bancalia/api/admin/asignaturas_list.php', window.location.origin);
      url.searchParams.set('page', aPage);
      url.searchParams.set('per_page', aPerPage);
      if (aGrado) url.searchParams.set('grado_id', aGrado);
      if (aCurso) url.searchParams.set('curso_id', aCurso);
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
            <td class="py-2 pr-3">${a.cursos_nombres ?? '—'}</td>
            <td class="py-2 pr-3">${a.temas}</td>
            <td class="py-2">
              <div class="flex gap-2">
                <button class="ui-btn btn-gray a-edit"
                        data-id="${a.id}"
                        data-nombre="${String(a.nombre).replace(/"/g,'&quot;')}"
                        data-codigo="${a.codigo ? String(a.codigo).replace(/"/g,'&quot;') : ''}"
                        data-grado="${a.grado_id}"
                        data-cursos="${a.cursos_ids || ''}">
                  Editar
                </button>
                <button class="ui-btn btn-red a-del" data-id="${a.id}">Eliminar</button>
              </div>
            </td>
          </tr>`).join('') : `<tr><td colspan="7" class="py-6 text-center text-gray-500">Sin resultados.</td></tr>`;

        asigMeta.textContent = `Mostrando ${rows.length} de ${aTotal} (página ${aPage} de ${aLastPage})`;
        updatePager();
      }).catch(e => {
        asigBody.innerHTML = `<tr><td colspan="7" class="py-6 text-center text-red-600">${e.message}</td></tr>`;
        asigMeta.textContent = '';
        $setBtnState(asigPrev, true); $setBtnState(asigNext, true);
      });
    }

    // Filtros
    asigBuscar.addEventListener('click', () => {
      aQuery = asigQ.value.trim();
      aGrado = asigGradoFiltro.value;
      aCurso = asigCursoFiltro.value;
      aPage = 1;
      loadAsignaturas();
    });

    asigPrev.addEventListener('click', () => { if (aPage > 1) { aPage--; loadAsignaturas(); } });
    asigNext.addEventListener('click', () => { if (aPage < aLastPage) { aPage++; loadAsignaturas(); } });

    asigGradoFiltro.addEventListener('change', async () => {
      const g = asigGradoFiltro.value;
      aCurso = '';
      await loadCursosFor(g, asigCursoFiltro, true);
      if (!g) { asigCursoFiltro.disabled = true; }
    });

    // Nuevo
    asigNuevo.addEventListener('click', async () => {
      asigFormTitle.textContent = 'Nueva asignatura';
      asigId.value = '';
      asigNombre.value = '';
      asigCodigo.value = '';
      asigMsg.textContent = '';
      asigFormWrap.classList.remove('hidden');

      // grado por filtro o primero
      const g = asigGradoFiltro.value || asigGrado.options[0]?.value || '';
      asigGrado.value = g;
      await loadCursosFor(g, asigCursos, false);
      setMultiSelect(asigCursos, []);
      asigNombre.focus();
    });

    // Editar / Eliminar
    asigBody.addEventListener('click', async (ev) => {
      const btn = ev.target.closest('button'); if (!btn) return;
      if (btn.classList.contains('a-edit')) {
        asigFormTitle.textContent = 'Editar asignatura';
        asigId.value     = btn.dataset.id;
        asigNombre.value = btn.dataset.nombre || '';
        asigCodigo.value = btn.dataset.codigo || '';
        asigGrado.value  = btn.dataset.grado || '';
        asigMsg.textContent = '';
        asigFormWrap.classList.remove('hidden');

        await loadCursosFor(asigGrado.value, asigCursos, false);
        const ids = (btn.dataset.cursos || '').split(',').map(s=>s.trim()).filter(Boolean);
        setMultiSelect(asigCursos, ids);
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

    // Cambio de grado en el formulario → recargar cursos
    asigGrado.addEventListener('change', async () => {
      const g = asigGrado.value;
      await loadCursosFor(g, asigCursos, false);
      setMultiSelect(asigCursos, []);
    });

    // Guardar
    asigForm.addEventListener('submit', (ev) => {
      ev.preventDefault();
      asigMsg.textContent = '';

      const id = (asigId.value || '').trim();
      const nombre = (asigNombre.value || '').trim();
      const codigo = (asigCodigo.value || '').trim();
      const grado_id = (asigGrado.value || '').trim();
      const cursos = getMultiValues(asigCursos);

      const fd = new FormData();
      if (id) fd.append('id', id);
      fd.append('nombre', nombre);
      fd.append('codigo', codigo);
      fd.append('grado_id', grado_id);
      cursos.forEach(cid => fd.append('curso_ids[]', cid));

      const url = id ? '/Bancalia/api/admin/asignaturas_update.php'
                     : '/Bancalia/api/admin/asignaturas_create.php';

      $fetchJSON(url, { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
        .then(() => { asigFormWrap.classList.add('hidden'); aPage = 1; loadAsignaturas(); })
        .catch(e => asigMsg.textContent = e.message);
    });

    document.getElementById('asigCancelar')?.addEventListener('click', () => {
      asigFormWrap.classList.add('hidden');
    });

    // Inicial
    loadGrados().then(() => {
      const g = asigGradoFiltro.value;
      if (g) loadCursosFor(g, asigCursoFiltro, true);
    });
    loadAsignaturas();
  });
})();
