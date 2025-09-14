<section id="tab-usuarios" class="tab <?= ($activeTab==='usuarios'?'':'hidden') ?>">
  <div class="card p-4">
    <div class="mb-2">
      <h2 class="text-base font-semibold">Usuarios / Roles</h2>
      <p class="text-sm text-gray-600">Buscar, activar/desactivar y eliminar usuarios. Gestiona “Imparte” en profesores.</p>
    </div>

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div class="flex flex-wrap items-center gap-2">
        <input id="usrQ" type="text" class="ui-input w-64" placeholder="Buscar nombre o email" />
        <select id="usrRol" class="ui-select">
          <option value="">Todos los roles</option>
          <option value="1">Admin</option>
          <option value="2">Profesor</option>
          <option value="3">Alumno</option>
        </select>
        <button id="usrBuscar" class="ui-btn btn-blue">Buscar</button>
      </div>
      <div id="usrMeta" class="text-sm text-gray-500"></div>
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
        <tbody id="usrBody"></tbody>
      </table>
    </div>

    <div class="mt-3 flex items-center gap-2">
      <button id="usrPrev" class="ui-btn border">Anterior</button>
      <button id="usrNext" class="ui-btn border">Siguiente</button>
    </div>
  </div>

  <!-- GESTIÓN IMPARTE -->
  <div id="imparteWrap" class="card p-4 mt-4 hidden">
    <h3 class="text-base font-semibold mb-3">Gestionar “Imparte” — <span id="impNombre"></span> <span class="text-gray-500" id="impEmail"></span></h3>
    <input type="hidden" id="impProfesorId" />

    <div class="grid md:grid-cols-3 gap-4">
      <div>
        <label class="block text-sm font-medium mb-1">Grado</label>
        <select id="impGrado" class="ui-select w-full"></select>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Cursos (del grado)</label>
        <select id="impCursos" class="ui-multi w-full" multiple size="8"></select>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Asignaturas (filtradas por curso)</label>
        <select id="impAsigs" class="ui-multi w-full" multiple size="8"></select>
      </div>
    </div>

    <div class="mt-3 flex items-center gap-2">
      <button id="impAdd" class="ui-btn btn-blue">Añadir combinación</button>
      <button id="impClearSel" class="ui-btn border">Limpiar selección</button>
      <span id="impMsg" class="text-sm ml-2"></span>
    </div>

    <div class="mt-4">
      <h4 class="font-medium mb-2">Combinaciones actuales</h4>
      <div class="table-wrap">
        <table class="w-full text-sm">
          <thead class="border-b">
            <tr class="text-left">
              <th class="sticky-th py-2 pr-3">Curso</th>
              <th class="sticky-th py-2 pr-3">Asignatura</th>
              <th class="sticky-th py-2">Acción</th>
            </tr>
          </thead>
          <tbody id="impBody"></tbody>
        </table>
      </div>
      <div class="mt-3 flex items-center gap-2">
        <button id="impGuardar" class="ui-btn btn-green">Guardar cambios</button>
        <button id="impCancelar" class="ui-btn btn-gray">Cerrar</button>
      </div>
    </div>
  </div>
</section>
