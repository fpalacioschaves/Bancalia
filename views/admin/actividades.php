<section id="tab-actividades" class="tab <?= ($activeTab==='actividades'?'':'hidden') ?>">
  <div class="card p-4">
    <div class="mb-1">
      <h2 class="text-base font-semibold">Panel: Actividades</h2>
      <p class="text-sm text-gray-600">Listado de actividades subidas por profesores. El administrador puede eliminarlas.</p>
    </div>

    <!-- Filtros + Acciones (Mostrando… arriba dcha) -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div class="flex flex-wrap items-center gap-2">
        <input id="actQ" type="text" placeholder="Buscar título" class="ui-input w-64" />
        <select id="actTipo" class="ui-select"><option value="">Todos los tipos</option></select>
        <select id="actVis" class="ui-select">
          <option value="">Todas las visibilidades</option>
          <option value="privada">Privada</option>
          <option value="compartida">Compartida</option>
        </select>
        <select id="actAutor" class="ui-select"><option value="">Todos los autores</option></select>
        <button id="actBuscar" class="ui-btn btn-blue">Buscar</button>
      </div>
      <div id="actMeta" class="text-sm text-gray-500"></div>
    </div>

    <!-- Tabla (sin columna de "Creado") -->
    <div class="mt-4 table-wrap">
      <table class="w-full text-sm">
        <thead class="border-b">
          <tr class="text-left">
            <th class="sticky-th py-2 pr-3">ID</th>
            <th class="sticky-th py-2 pr-3">Título</th>
            <th class="sticky-th py-2 pr-3">Tipo</th>
            <th class="sticky-th py-2 pr-3">Visib.</th>
            <th class="sticky-th py-2 pr-3">Estado</th>
            <th class="sticky-th py-2 pr-3">Autor</th>
            <th class="sticky-th py-2">Acciones</th>
          </tr>
        </thead>
        <tbody id="actsBody"></tbody>
      </table>
    </div>

    <!-- Paginación -->
    <div class="mt-3 flex items-center gap-2">
      <button id="actPrev" class="ui-btn border">Anterior</button>
      <button id="actNext" class="ui-btn border">Siguiente</button>
    </div>
  </div>
</section>
