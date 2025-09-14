<section id="tab-usuarios" class="tab <?= ($activeTab==='usuarios'?'':'hidden') ?>">
  <div class="card p-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div class="flex flex-wrap items-center gap-2">
        <input id="q" type="text" placeholder="Buscar nombre o email" class="ui-input w-64" />
        <select id="role" class="ui-select">
          <option value="">Todos los roles</option>
          <option value="1">Admin</option>
          <option value="2">Profesor</option>
          <option value="3">Alumno</option>
        </select>
        <button id="btnBuscar" class="ui-btn bg-blue-600 text-white hover:bg-blue-700">Buscar</button>
      </div>
      <div id="usersMeta" class="text-sm text-gray-500"></div>
    </div>

    <div class="mt-4 table-wrap">
      <table class="w-full text-sm">
        <thead class="border-b">
          <tr class="text-left">
            <th class="sticky-th py-2 pr-3">ID</th>
            <th class="sticky-th py-2 pr-3">Nombre</th>
            <th class="sticky-th py-2 pr-3">Email</th>
            <th class="sticky-th py-2 pr-3">Rol</th>
            <th class="sticky-th py-2 pr-3">Estado</th>
            <th class="sticky-th py-2">Acciones</th>
          </tr>
        </thead>
        <tbody id="usersBody"></tbody>
      </table>
    </div>

    <div class="mt-3 flex items-center gap-2">
      <button id="prev" class="ui-btn border">Anterior</button>
      <button id="next" class="ui-btn border">Siguiente</button>
    </div>
  </div>
</section>
