<section id="tab-catalogo" class="tab <?= ($activeTab==='catalogo'?'':'hidden') ?>">

  <!-- GRADOS -->
  <div class="card p-4">
    <div class="mb-4">
      <h2 class="text-base font-semibold">Catálogo: Grados</h2>
      <p class="text-sm text-gray-600">Crear, editar y eliminar grados. No se puede eliminar un grado con cursos/asignaturas o profesores asociados.</p>
    </div>

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div class="flex flex-wrap items-center gap-2">
        <input id="gradoQ" type="text" placeholder="Buscar grado" class="ui-input w-64" />
        <button id="gradoBuscar" class="ui-btn btn-blue">Buscar</button>
        <button id="gradoNuevo" class="ui-btn btn-green ml-2">Nuevo grado</button>
      </div>
      <div id="gradoMeta" class="text-sm text-gray-500"></div>
    </div>

    <div class="mt-4 table-wrap">
      <table class="w-full text-sm">
        <thead class="border-b">
          <tr class="text-left">
            <th class="sticky-th py-2 pr-3">ID</th>
            <th class="sticky-th py-2 pr-3">Nombre</th>
            <th class="sticky-th py-2 pr-3">Cursos</th>
            <th class="sticky-th py-2">Acciones</th>
          </tr>
        </thead>
        <tbody id="gradosBody"></tbody>
      </table>
    </div>

    <div class="mt-3 flex items-center gap-2">
      <button id="gradoPrev" class="ui-btn border">Anterior</button>
      <button id="gradoNext" class="ui-btn border">Siguiente</button>
    </div>
  </div>

  <div id="gradoFormWrap" class="card p-4 mt-4 hidden">
    <h3 id="gradoFormTitle" class="text-base font-semibold mb-3">Nuevo grado</h3>
    <form id="gradoForm" class="grid md:grid-cols-3 gap-4">
      <input type="hidden" id="gradoId" />
      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Nombre</label>
        <input id="gradoNombre" type="text" class="ui-input w-full" />
      </div>
      <div class="md:col-span-3 flex items-center gap-2">
        <button id="gradoGuardar" type="submit" class="ui-btn btn-green">Guardar</button>
        <button id="gradoCancelar" type="button" class="ui-btn btn-gray">Cancelar</button>
        <span id="gradoMsg" class="text-sm ml-2"></span>
      </div>
    </form>
  </div>

  <!-- CURSOS -->
  <div class="card p-4 mt-6">
    <div class="mb-1">
      <h2 class="text-base font-semibold">Catálogo: Cursos</h2>
      <p class="text-sm text-gray-600">Crear, editar y eliminar cursos. Si un curso tiene dependencias, no podrá cambiar de grado ni eliminarse.</p>
    </div>

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div class="flex flex-wrap items-center gap-2">
        <select id="cursGrado" class="ui-select"><option value="">Todos los grados</option></select>
        <input id="cursQ" type="text" placeholder="Buscar curso" class="ui-input w-64" />
        <button id="cursBuscar" class="ui-btn btn-blue">Buscar</button>
        <button id="cursNuevo" class="ui-btn btn-green ml-2">Nuevo curso</button>
      </div>
      <div id="cursMeta" class="text-sm text-gray-500"></div>
    </div>

    <div class="mt-4 table-wrap">
      <table class="w-full text-sm">
        <thead class="border-b">
          <tr class="text-left">
            <th class="sticky-th py-2 pr-3">ID</th>
            <th class="sticky-th py-2 pr-3">Nombre</th>
            <th class="sticky-th py-2 pr-3">Grado</th>
            <th class="sticky-th py-2 pr-3">Orden</th>
            <th class="sticky-th py-2">Acciones</th>
          </tr>
        </thead>
        <tbody id="cursosBody"></tbody>
      </table>
    </div>

    <div class="mt-3 flex items-center gap-2">
      <button id="cursPrev" class="ui-btn border">Anterior</button>
      <button id="cursNext" class="ui-btn border">Siguiente</button>
    </div>
  </div>

  <div id="cursoFormWrap" class="card p-4 mt-4 hidden">
    <h3 id="cursoFormTitle" class="text-base font-semibold mb-3">Nuevo curso</h3>
    <form id="cursoForm" class="grid md:grid-cols-3 gap-4">
      <input type="hidden" id="cursoId" />
      <div>
        <label class="block text-sm font-medium mb-1">Grado</label>
        <select id="cursoGrado" class="ui-select w-full"></select>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Nombre</label>
        <input id="cursoNombre" type="text" class="ui-input w-full" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Orden</label>
        <input id="cursoOrden" type="number" min="0" class="ui-input w-full" />
      </div>
      <div class="md:col-span-3 flex items-center gap-2">
        <button id="cursoGuardar" type="submit" class="ui-btn btn-green">Guardar</button>
        <button id="cursoCancelar" type="button" class="ui-btn btn-gray">Cancelar</button>
        <span id="cursoMsg" class="text-sm ml-2"></span>
      </div>
    </form>
  </div>

  <!-- ASIGNATURAS -->
  <div class="card p-4 mt-6">
    <div class="mb-1">
      <h2 class="text-base font-semibold">Catálogo: Asignaturas</h2>
      <p class="text-sm text-gray-600">Las asignaturas pertenecen a un grado y pueden asociarse a uno o varios cursos de ese grado.</p>
    </div>

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div class="flex flex-wrap items-center gap-2">
        <select id="asigGradoFiltro" class="ui-select"><option value="">Todos los grados</option></select>
        <select id="asigCursoFiltro" class="ui-select" disabled><option value="">Todos los cursos</option></select>
        <input id="asigQ" type="text" placeholder="Buscar asignatura" class="ui-input w-64" />
        <button id="asigBuscar" class="ui-btn btn-blue">Buscar</button>
        <button id="asigNuevo" class="ui-btn btn-green ml-2">Nueva asignatura</button>
      </div>
      <div id="asigMeta" class="text-sm text-gray-500"></div>
    </div>

    <div class="mt-4 table-wrap">
      <table class="w-full text-sm">
        <thead class="border-b">
          <tr class="text-left">
            <th class="sticky-th py-2 pr-3">ID</th>
            <th class="sticky-th py-2 pr-3">Nombre</th>
            <th class="sticky-th py-2 pr-3">Código</th>
            <th class="sticky-th py-2 pr-3">Grado</th>
            <th class="sticky-th py-2 pr-3">Cursos</th>
            <th class="sticky-th py-2 pr-3">Temas</th>
            <th class="sticky-th py-2">Acciones</th>
          </tr>
        </thead>
        <tbody id="asigBody"></tbody>
      </table>
    </div>

    <div class="mt-3 flex items-center gap-2">
      <button id="asigPrev" class="ui-btn border">Anterior</button>
      <button id="asigNext" class="ui-btn border">Siguiente</button>
    </div>
  </div>

  <div id="asigFormWrap" class="card p-4 mt-4 hidden">
    <h3 id="asigFormTitle" class="text-base font-semibold mb-3">Nueva asignatura</h3>
    <form id="asigForm" class="grid md:grid-cols-3 gap-4">
      <input type="hidden" id="asigId" />
      <div>
        <label class="block text-sm font-medium mb-1">Grado</label>
        <select id="asigGrado" class="ui-select w-full"></select>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Nombre</label>
        <input id="asigNombre" type="text" class="ui-input w-full" />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Código (opcional)</label>
        <input id="asigCodigo" type="text" class="ui-input w-full" />
      </div>
      <div class="md:col-span-3">
        <label class="block text-sm font-medium mb-1">Cursos (del grado)</label>
        <select id="asigCursos" class="ui-multi w-full" multiple size="8"></select>
        <p class="help mt-1">Selecciona uno o varios cursos (Ctrl/⌘ + clic).</p>
      </div>
      <div class="md:col-span-3 flex items-center gap-2">
        <button id="asigGuardar" type="submit" class="ui-btn btn-green">Guardar</button>
        <button id="asigCancelar" type="button" class="ui-btn btn-gray">Cancelar</button>
        <span id="asigMsg" class="text-sm ml-2"></span>
      </div>
    </form>
  </div>

</section>
