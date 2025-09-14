(() => {
  document.addEventListener('DOMContentLoaded', () => {
    const usersBody = document.getElementById('usersBody');
    if (!usersBody) return; // pestaña no presente

    let page = 1, perPage = 10, q = '', role = '', lastPage = 1, total = 0;
    const usersMeta = document.getElementById('usersMeta');
    const btnPrev = document.getElementById('prev');
    const btnNext = document.getElementById('next');

    const renderEstadoTag = (estado) =>
      estado === 'activo'
        ? `<span class="tag tag-ok">activo</span>`
        : `<span class="tag tag-bad">inactivo</span>`;

    function actionsCell(u) {
      const isAdmin = Number(u.rol_id) === 1;
      const isMe = Number(u.id) === Number(window.CURRENT_USER_ID);
      if (isAdmin) return `<div class="text-gray-400">—</div>`;
      const toState = u.estado === 'activo' ? 'inactivo' : 'activo';
      const disableToggle = isMe && toState === 'inactivo';
      return `
        <div class="flex gap-2">
          <button class="ui-btn ${toState === 'activo' ? 'btn-green' : 'btn-gray'} act-toggle"
                  data-id="${u.id}" data-next="${toState}" ${disableToggle ? 'disabled' : ''}>
            ${toState === 'activo' ? 'Activar' : 'Desactivar'}
          </button>
          <button class="ui-btn btn-red act-delete" data-id="${u.id}" ${isMe ? 'disabled' : ''}>
            Eliminar
          </button>
        </div>`;
    }

    function updatePager() {
      $setBtnState(btnPrev, page <= 1);
      $setBtnState(btnNext, page >= lastPage);
    }

    function loadUsers() {
      const url = new URL('/Bancalia/api/admin/users_list.php', window.location.origin);
      url.searchParams.set('page', page);
      url.searchParams.set('per_page', perPage);
      if (q) url.searchParams.set('q', q);
      if (role) url.searchParams.set('rol', role);

      usersBody.innerHTML = `<tr><td colspan="6" class="py-6 text-center text-gray-500">Cargando…</td></tr>`;
      updatePager();

      $fetchJSON(url.toString()).then(j => {
        total = j.total || 0;
        lastPage = Math.max(1, Math.ceil(total / perPage));
        if (total > 0 && page > lastPage) { page = lastPage; return loadUsers(); }
        if (total === 0 && page !== 1) { page = 1; return loadUsers(); }

        const rows = j.data || [];
        usersBody.innerHTML = rows.length
          ? rows.map(u => `
              <tr class="border-b">
                <td class="py-2 pr-3">${u.id}</td>
                <td class="py-2 pr-3">${u.nombre}</td>
                <td class="py-2 pr-3">${u.email}</td>
                <td class="py-2 pr-3">${u.rol}</td>
                <td class="py-2 pr-3">${renderEstadoTag(u.estado)}</td>
                <td class="py-2">${actionsCell(u)}</td>
              </tr>`).join('')
          : `<tr><td colspan="6" class="py-6 text-center text-gray-500">Sin resultados.</td></tr>`;

        usersMeta.textContent = `Mostrando ${rows.length} de ${total} (página ${page} de ${lastPage})`;
        updatePager();
      }).catch(e => {
        usersBody.innerHTML = `<tr><td colspan="6" class="py-6 text-center text-red-600">${e.message}</td></tr>`;
        usersMeta.textContent = '';
        $setBtnState(btnPrev, true); $setBtnState(btnNext, true);
      });
    }

    document.getElementById('btnBuscar').addEventListener('click', () => {
      q = document.getElementById('q').value.trim();
      role = document.getElementById('role').value;
      page = 1;
      loadUsers();
    });
    btnPrev.addEventListener('click', () => { if (page > 1) { page--; loadUsers(); } });
    btnNext.addEventListener('click', () => { if (page < lastPage) { page++; loadUsers(); } });

    usersBody.addEventListener('click', async (ev) => {
      const t = ev.target.closest('button'); if (!t) return;
      const id = t.dataset.id;
      if (t.classList.contains('act-toggle')) {
        const next = t.dataset.next;
        try {
          t.disabled = true;
          const fd = new FormData(); fd.append('user_id', id); fd.append('estado', next);
          await $fetchJSON('/Bancalia/api/admin/user_set_status.php', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
          loadUsers();
        } catch (e) { alert(e.message); } finally { t.disabled = false; }
      }
      if (t.classList.contains('act-delete')) {
        if (!confirm('¿Eliminar este usuario? Esta acción no se puede deshacer.')) return;
        try {
          t.disabled = true;
          const fd = new FormData(); fd.append('user_id', id);
          await $fetchJSON('/Bancalia/api/admin/user_delete.php', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
          loadUsers();
        } catch (e) { alert(e.message); } finally { t.disabled = false; }
      }
    });

    // Primera carga
    loadUsers();
  });
})();
