<section id="tab-informes" class="tab <?= ($activeTab==='informes'?'':'hidden') ?>">
  <div class="card p-4">
    <div class="mb-2">
      <h2 class="text-base font-semibold">Informes</h2>
      <p class="text-sm text-gray-600">Pulso del sistema: actividades por periodo, tipo, visibilidad y autor.</p>
    </div>

    <!-- Filtros + KPIs -->
    <div class="grid md:grid-cols-5 gap-3">
      <div>
        <label class="block text-sm text-gray-600 mb-1">Desde</label>
        <input id="infFrom" type="date" class="ui-input w-full" />
      </div>
      <div>
        <label class="block text-sm text-gray-600 mb-1">Hasta</label>
        <input id="infTo" type="date" class="ui-input w-full" />
      </div>
      <div>
        <label class="block text-sm text-gray-600 mb-1">Tipo</label>
        <select id="infTipo" class="ui-select w-full"><option value="">Todos</option></select>
      </div>
      <div>
        <label class="block text-sm text-gray-600 mb-1">Visibilidad</label>
        <select id="infVis" class="ui-select w-full">
          <option value="">Todas</option>
          <option value="privada">Privada</option>
          <option value="compartida">Compartida</option>
        </select>
      </div>
      <div>
        <label class="block text-sm text-gray-600 mb-1">Autor (profesor)</label>
        <select id="infAutor" class="ui-select w-full"><option value="">Todos</option></select>
      </div>
    </div>

    <div class="mt-3 flex items-center gap-2">
      <button id="infBuscar" class="ui-btn btn-blue">Aplicar filtros</button>
      <button id="infHoy" class="ui-btn border">Hoy</button>
      <button id="inf30" class="ui-btn border">Últimos 30 días</button>
      <button id="infTodo" class="ui-btn border">Todo</button>
    </div>

    <!-- KPIs -->
    <div id="infKpis" class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-4"></div>
  </div>

  <!-- Tabla: Actividades por tipo -->
  <div class="card p-4 mt-6">
    <div class="flex items-start md:items-center justify-between gap-2">
      <div>
        <h3 class="text-base font-semibold">Actividades por tipo</h3>
        <p class="text-sm text-gray-600">Distribución por tipo y visibilidad.</p>
      </div>
      <div class="flex items-center gap-2">
        <a id="infTipoCsv" href="#" class="ui-btn border">Exportar CSV</a>
        <div id="infTipoMeta" class="text-sm text-gray-500"></div>
      </div>
    </div>

    <div class="mt-3 table-wrap">
      <table class="w-full text-sm">
        <thead class="border-b">
          <tr class="text-left">
            <th class="sticky-th py-2 pr-3">Tipo</th>
            <th class="sticky-th py-2 pr-3">Total</th>
            <th class="sticky-th py-2 pr-3">Compartidas</th>
            <th class="sticky-th py-2 pr-3">Privadas</th>
          </tr>
        </thead>
        <tbody id="infTipoBody"></tbody>
      </table>
    </div>

    <div class="mt-3 flex items-center gap-2">
      <button id="infTipoPrev" class="ui-btn border">Anterior</button>
      <button id="infTipoNext" class="ui-btn border">Siguiente</button>
    </div>
  </div>

  <!-- Tabla: Actividades por profesor -->
  <div class="card p-4 mt-6">
    <div class="flex items-start md:items-center justify-between gap-2">
      <div>
        <h3 class="text-base font-semibold">Actividades por profesor</h3>
        <p class="text-sm text-gray-600">Autores (rol profesor) y su actividad en el periodo.</p>
      </div>
      <div class="flex items-center gap-2">
        <a id="infAutorCsv" href="#" class="ui-btn border">Exportar CSV</a>
        <div id="infAutorMeta" class="text-sm text-gray-500"></div>
      </div>
    </div>

    <div class="mt-3 table-wrap">
      <table class="w-full text-sm">
        <thead class="border-b">
          <tr class="text-left">
            <th class="sticky-th py-2 pr-3">Profesor</th>
            <th class="sticky-th py-2 pr-3">Email</th>
            <th class="sticky-th py-2 pr-3">Total</th>
            <th class="sticky-th py-2 pr-3">Compartidas</th>
            <th class="sticky-th py-2 pr-3">Privadas</th>
          </tr>
        </thead>
        <tbody id="infAutorBody"></tbody>
      </table>
    </div>

    <div class="mt-3 flex items-center gap-2">
      <button id="infAutorPrev" class="ui-btn border">Anterior</button>
      <button id="infAutorNext" class="ui-btn border">Siguiente</button>
    </div>
  </div>
</section>
