<?php
// /public/ayuda.php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../partials/header.php';
?>
<div class="mb-6">
  <h1 class="text-2xl font-semibold tracking-tight">Ayuda para profesores</h1>
  <p class="mt-1 text-slate-600">Guía rápida: acceso, perfil, centro y creación de actividades.</p>
</div>

<div class="grid gap-6 lg:grid-cols-4">
  <!-- Índice -->
  <aside class="lg:col-span-1">
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <h3 class="text-sm font-semibold text-slate-700 mb-2">Contenido</h3>
      <nav class="text-sm space-y-2">
        <a class="block hover:underline" href="#login">1. Acceso y Mi perfil</a>
        <a class="block hover:underline" href="#centro">2. Crear y gestionar Centro</a>
        <a class="block hover:underline" href="#actividades">3. Crear actividades</a>
        <div class="ml-3 space-y-1">
          <a class="block hover:underline" href="#campos-comunes">3.1 Campos comunes</a>
          <a class="block hover:underline" href="#tarea">3.2 Tarea / Entrega</a>
          <a class="block hover:underline" href="#vf">3.3 Verdadero / Falso</a>
          <a class="block hover:underline" href="#rc">3.4 Respuesta corta</a>
        </div>
        <a class="block hover:underline" href="#faq">4. Preguntas frecuentes</a>
      </nav>
    </div>
  </aside>

  <!-- Contenido principal -->
  <main class="lg:col-span-3 space-y-10">

    <!-- Login / Perfil -->
    <section id="login" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      <h2 class="text-lg font-semibold">1. Acceso y Mi perfil</h2>
      <div class="mt-3 space-y-3 text-sm text-slate-700 leading-6">
        <p><strong>Acceso</strong>: entra con tu email y contraseña. Si no recuerdas la contraseña, usa “¿Olvidaste tu contraseña?” para solicitar un enlace de recuperación.</p>
        <p><strong>Mi perfil</strong>: arriba a la derecha encontrarás el acceso a tu perfil. Comprueba que tu <em>email</em> y tu <em>Rol</em> son correctos. Como profesor, es importante que tu ficha tenga asociado un <em>Profesor ID</em>; si no aparece, contacta con el administrador del centro.</p>
        <p><strong>Foto/Nombre</strong>: puedes actualizar tu nombre visible y otros datos básicos. Guarda los cambios antes de salir.</p>
      </div>
    </section>

    <!-- Centro -->
    <section id="centro" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      <h2 class="text-lg font-semibold">2. Crear y gestionar Centro</h2>
      <div class="mt-3 space-y-3 text-sm text-slate-700 leading-6">
        <p>Si tu instalación usa la noción de <strong>Centro</strong> (institución/colegio), primero debe existir el centro para poder asociar familias, cursos, asignaturas y profesores.</p>
        <ol class="list-decimal pl-5 space-y-2">
          <li>Ve a <em>Administración &rarr; Centros</em> y pulsa <strong>Nuevo centro</strong>.</li>
          <li>Completa <em>Nombre</em>, <em>Código</em> (si aplica), <em>Dirección</em> y <em>Contacto</em>. Marca <em>Activo</em>.</li>
          <li>Guarda. Después podrás vincular <em>Familias profesionales</em>, <em>Cursos</em> y <em>Asignaturas</em> a tu centro.</li>
        </ol>
        <p class="text-xs text-slate-500">Nota: según tu despliegue, la creación/edición de centros puede estar restringida a usuarios con rol administrador.</p>
      </div>
    </section>

    <!-- Actividades -->
    <section id="actividades" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      <h2 class="text-lg font-semibold">3. Crear actividades</h2>
      <p class="mt-3 text-sm text-slate-700 leading-6">
        Desde <strong>Admin &rarr; Actividades</strong> pulsa <em>Nueva actividad</em>. Rellena los campos comunes y, según el <em>Tipo</em>, aparecerán opciones específicas.
      </p>

      <!-- Campos comunes -->
      <div id="campos-comunes" class="mt-5">
        <h3 class="text-base font-semibold text-slate-800">3.1 Campos comunes</h3>
        <dl class="mt-2 grid gap-4 sm:grid-cols-2 text-sm">
          <div>
            <dt class="font-medium">Título *</dt>
            <dd class="text-slate-700">Nombre claro y breve. Ej.: “Célula y orgánulos (repaso)”.</dd>
          </div>
          <div>
            <dt class="font-medium">Descripción</dt>
            <dd class="text-slate-700">Contexto o instrucciones generales. Opcional.</dd>
          </div>
          <div>
            <dt class="font-medium">Familia / Grado *</dt>
            <dd class="text-slate-700">Selecciona la familia profesional o etapa a la que pertenece.</dd>
          </div>
          <div>
            <dt class="font-medium">Curso *</dt>
            <dd class="text-slate-700">Se filtra por la familia elegida para evitar errores.</dd>
          </div>
          <div>
            <dt class="font-medium">Asignatura *</dt>
            <dd class="text-slate-700">Depende del curso. Debe ser coherente (el sistema lo valida).</dd>
          </div>
          <div>
            <dt class="font-medium">Tema (opcional)</dt>
            <dd class="text-slate-700">Puedes asociar un tema concreto para ordenar el material.</dd>
          </div>
          <div>
            <dt class="font-medium">Tipo *</dt>
            <dd class="text-slate-700">Define el formato de la actividad: Opción múltiple, Verdadero/Falso, Respuesta corta, Rellenar huecos, Emparejar o Tarea.</dd>
          </div>
          <div>
            <dt class="font-medium">Dificultad</dt>
            <dd class="text-slate-700">Baja / Media / Alta. Referencia para ti y el alumno.</dd>
          </div>
          <div>
            <dt class="font-medium">Visibilidad</dt>
            <dd class="text-slate-700"><em>Privada</em> (solo tú) o <em>Pública</em> (visible para el centro/a quien corresponda).</dd>
          </div>
          <div>
            <dt class="font-medium">Estado</dt>
            <dd class="text-slate-700"><em>Borrador</em> (en edición) o <em>Publicada</em> (lista para usar).</dd>
          </div>
        </dl>
      </div>

      <!-- Tarea -->
      <div id="tarea" class="mt-8">
        <h3 class="text-base font-semibold text-slate-800">3.2 Tarea / Entrega (campos específicos)</h3>
        <ul class="mt-2 list-disc pl-6 text-sm text-slate-700 space-y-1">
          <li><strong>Instrucciones</strong>: qué debe entregar el alumno.</li>
          <li><strong>Permitir texto / archivos / enlaces</strong>: tipos de entrega aceptados.</li>
          <li><strong>Máx. archivos</strong> y <strong>Máx. tamaño (MB)</strong>: límites por alumno.</li>
          <li><strong>Evaluación</strong>: sin evaluación, <em>Puntuación</em> (define <em>Puntuación máxima</em>) o <em>Rúbrica</em> (JSON).</li>
        </ul>
        <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs">
          <strong>Ejemplo</strong> (rúbrica JSON):
          <pre class="mt-2 overflow-x-auto text-[11px] leading-5">[
  {"criterio":"Presentación","max":2},
  {"criterio":"Contenido","max":8}
]</pre>
        </div>
      </div>

      <!-- Verdadero / Falso -->
      <div id="vf" class="mt-8">
        <h3 class="text-base font-semibold text-slate-800">3.3 Verdadero / Falso</h3>
        <ul class="mt-2 list-disc pl-6 text-sm text-slate-700 space-y-1">
          <li><strong>Respuesta correcta</strong>: selecciona <em>Verdadero</em> o <em>Falso</em>.</li>
          <li><strong>Feedback</strong> (acierto / error): mensajes opcionales que verá el alumno tras responder.</li>
        </ul>
        <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs">
          <strong>Ejemplo</strong>: “La mitosis ocurre en células somáticas.” → <em>Respuesta correcta:</em> Verdadero.<br>
          <em>Feedback si acierta:</em> “Correcto: la mitosis se da en células somáticas.”<br>
          <em>Feedback si falla:</em> “Revisa: la meiosis es la división para células sexuales.”
        </div>
      </div>

      <!-- Respuesta corta -->
      <div id="rc" class="mt-8">
        <h3 class="text-base font-semibold text-slate-800">3.4 Respuesta corta</h3>
        <p class="mt-2 text-sm text-slate-700">El sistema puede autocorregir por <em>Palabras clave</em> o por <em>Regex</em>.</p>

        <div class="mt-3 grid gap-4 sm:grid-cols-2">
          <div class="rounded-lg border border-slate-200 bg-white p-4">
            <h4 class="text-sm font-semibold">Modo: Palabras clave</h4>
            <ul class="mt-2 list-disc pl-6 text-sm text-slate-700 space-y-1">
              <li><strong>Palabras clave (JSON)</strong>: lista con “palabra” y “peso”.</li>
              <li><strong>% Coincidencia mínima</strong>: umbral de acierto.</li>
              <li><strong>Puntuación máxima</strong>: si vas a puntuar automáticamente.</li>
              <li>Opciones: sensible a mayúsculas, normalizar acentos, ignorar espacios.</li>
            </ul>
            <div class="mt-3 rounded border border-slate-200 bg-slate-50 p-3 text-xs">
              <strong>Ejemplo de JSON</strong>:
              <pre class="mt-2 overflow-x-auto text-[11px] leading-5">[
  {"palabra":"ósmosis","peso":1},
  {"palabra":"membrana","peso":1},
  {"palabra":"gradiente","peso":1}
]</pre>
              <div class="mt-2">Umbral: 60%. Puntuación máx.: 10. Acierto si aparece al menos el 60% del peso total.</div>
            </div>
          </div>

          <div class="rounded-lg border border-slate-200 bg-white p-4">
            <h4 class="text-sm font-semibold">Modo: Regex</h4>
            <ul class="mt-2 list-disc pl-6 text-sm text-slate-700 space-y-1">
              <li><strong>Patrón regex</strong> y <strong>flags</strong> (p. ej. <code>i</code> para no distinguir mayúsculas).</li>
              <li>Útil cuando la respuesta válida sigue un formato claro.</li>
            </ul>
            <div class="mt-3 rounded border border-slate-200 bg-slate-50 p-3 text-xs">
              <strong>Ejemplo</strong>: aceptar “ADN” o “ácido desoxirribonucleico”.<br>
              Patrón: <code>^(ADN|acido\\s+desoxirribonucleico)$</code> &nbsp;&nbsp;Flags: <code>i</code>
            </div>
          </div>
        </div>

        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs">
          <strong>Sugerencia</strong>: usa “Respuesta de ejemplo” como guía para el alumno; no se corrige, solo orienta.
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section id="faq" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      <h2 class="text-lg font-semibold">4. Preguntas frecuentes</h2>
      <div class="mt-3 divide-y">
        <details class="py-3">
          <summary class="cursor-pointer text-sm font-medium">¿Puedo cambiar el tipo de una actividad una vez creada?</summary>
          <p class="mt-2 text-sm text-slate-700">Sí. Al editar, cambia el <em>Tipo</em> y completa sus opciones. No borramos configuraciones anteriores (quedan guardadas por si vuelves a ese tipo).</p>
        </details>
        <details class="py-3">
          <summary class="cursor-pointer text-sm font-medium">¿Qué significan “Privada” y “Publicada”?</summary>
          <p class="mt-2 text-sm text-slate-700"><em>Privada</em> limita la visibilidad; <em>Publicada</em> la deja lista para su uso y visibilidad según la configuración del centro.</p>
        </details>
        <details class="py-3">
          <summary class="cursor-pointer text-sm font-medium">Me da error con el JSON de palabras clave</summary>
          <p class="mt-2 text-sm text-slate-700">Comprueba comas y comillas. Pruébalo con un validador JSON. Estructura mínima: <code>[{"palabra":"texto","peso":1}]</code>.</p>
        </details>
      </div>
    </section>

  </main>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
