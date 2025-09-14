<section id="tab-resumen" class="tab <?= ($activeTab==='resumen'?'':'hidden') ?>">
  <div id="kpis" class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3"></div>

  <div class="card mt-5 p-4">
    <h2 class="text-base font-semibold mb-3">Actividad reciente</h2>
    <div class="table-wrap">
      <table class="w-full text-sm">
        <thead class="border-b">
          <tr class="text-left">
            <th class="sticky-th py-2 pr-4">Usuario</th>
            <th class="sticky-th py-2 pr-4">Email</th>
            <th class="sticky-th py-2 pr-4">Rol</th>
            <th class="sticky-th py-2">Creado</th>
          </tr>
        </thead>
        <tbody id="recentUsersBody"></tbody>
      </table>
    </div>
  </div>
</section>
