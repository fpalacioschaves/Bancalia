(() => {
  document.addEventListener('DOMContentLoaded', () => {
    const kpis = document.getElementById('kpis');
    const tb = document.getElementById('recentUsersBody');
    if (!kpis || !tb) return;

    $fetchJSON('/Bancalia/api/admin/overview.php', { headers: { 'Accept': 'application/json' } })
      .then(j => {
        const toCard = (k) =>
          `<div class="card p-4">
            <div class="text-xs text-gray-500">${k.t}</div>
            <div class="text-xl font-semibold mt-1">${k.v}</div>
          </div>`;
        const data = [
          { t: 'Usuarios totales', v: j.kpis.usuarios_total },
          { t: 'Admins', v: j.kpis.admins },
          { t: 'Profesores', v: j.kpis.profesores },
          { t: 'Alumnos', v: j.kpis.alumnos },
          { t: 'Actividades', v: j.kpis.actividades_total },
          { t: 'Publicadas', v: j.kpis.actividades_publicadas },
          { t: 'Compartidas', v: j.kpis.actividades_compartidas },
          { t: 'Privadas', v: j.kpis.actividades_privadas },
        ];
        kpis.innerHTML = data.map(toCard).join('');

        tb.innerHTML = (j.recentes || []).map(u => `
          <tr class="border-b">
            <td class="py-2 pr-3">${u.nombre}</td>
            <td class="py-2 pr-3">${u.email}</td>
            <td class="py-2 pr-3">${u.rol}</td>
            <td class="py-2">${u.creado_en}</td>
          </tr>`).join('');
      })
      .catch(() => { /* silencioso */ });
  });
})();
