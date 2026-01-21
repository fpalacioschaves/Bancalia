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
    <!-- sticky en pantallas grandes, con límite de altura y scroll interno -->
    <div
      class="lg:sticky lg:top-24 lg:max-h-[calc(100vh-6rem)] overflow-auto rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <h3 class="text-sm font-semibold text-slate-700 mb-2">Contenido</h3>
      <nav class="text-sm space-y-2">
        <a class="block hover:underline" href="#login">1. Acceso y Mi perfil</a>
        <a class="block hover:underline" href="#organizacion">2. Organización del curso</a>
        <div class="ml-3 space-y-1">
          <a class="block hover:underline" href="#centro">2.1 Centros</a>
          <a class="block hover:underline" href="#temas">2.2 Temas</a>
        </div>
        <a class="block hover:underline" href="#actividades">3. Crear actividades</a>
        <div class="ml-3 space-y-1">
          <a class="block hover:underline" href="#campos-comunes">3.1 Campos comunes</a>
          <a class="block hover:underline" href="#act-tipos">3.2 Otros tipos (Huecos, Pares)</a>
        </div>
        <a class="block hover:underline" href="#examenes">4. Gestión de Exámenes</a>
        <div class="ml-3 space-y-1">
          <a class="block hover:underline" href="#iframe">4.3 Integración (Iframe)</a>
        </div>
        <a class="block hover:underline" href="#evaluacion">5. Evaluación y Notas</a>
        <a class="block hover:underline" href="#faq">6. Preguntas frecuentes</a>
      </nav>
    </div>
  </aside>

  <!-- Contenido principal -->
  <main class="lg:col-span-3 space-y-10">

    <!-- Login / Perfil -->
    <section id="login" class="scroll-mt-24 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      <h2 class="text-lg font-semibold">1. Acceso y Mi perfil</h2>
      <div class="mt-3 space-y-3 text-sm text-slate-700 leading-6">
        <p><strong>Acceso</strong>: entra con tu email y contraseña. Si no recuerdas la contraseña, usa “¿Olvidaste tu
          contraseña?” para solicitar un enlace de recuperación.</p>
        <p><strong>Mi perfil</strong>: arriba a la derecha encontrarás el acceso a tu perfil. Comprueba que tu
          <em>email</em> y tu <em>Rol</em> son correctos. Como profesor, es importante que tu ficha tenga asociado un
          <em>Profesor ID</em>; si no aparece, contacta con el administrador del centro.
        </p>
        <p><strong>Foto/Nombre</strong>: puedes actualizar tu nombre visible y otros datos básicos. Guarda los cambios
          antes de salir.</p>
      </div>
    </section>

    <!-- Organización -->
    <section id="organizacion" class="scroll-mt-24 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      <h2 class="text-lg font-semibold">2. Organización del curso</h2>

      <!-- Centro -->
      <div id="centro" class="mt-4 scroll-mt-24">
        <h3 class="text-base font-semibold text-slate-800">2.1 Centros</h3>
        <div class="mt-2 space-y-3 text-sm text-slate-700 leading-6">
          <p>Si tu instalación usa la noción de <strong>Centro</strong> (institución/colegio), primero debe existir el
            centro para poder asociar familias, cursos, asignaturas y profesores.</p>
          <ol class="list-decimal pl-5 space-y-2">
            <li>Ve a <em>Administración &rarr; Centros</em> y pulsa <strong>Nuevo centro</strong>.</li>
            <li>Guarda. Después podrás vincular <em>Familias profesionales</em>, <em>Cursos</em> y <em>Asignaturas</em>.
            </li>
          </ol>
        </div>
      </div>

      <!-- Temas -->
      <div id="temas" class="mt-8 scroll-mt-24">
        <h3 class="text-base font-semibold text-slate-800">2.2 Temas</h3>
        <div class="mt-2 text-sm text-slate-700 leading-6">
          <p>Los <strong>Temas</strong> permiten agrupar las actividades de forma lógica dentro de una asignatura.</p>
          <ul class="list-disc pl-5 mt-2 space-y-1">
            <li>Asocia cada tema a una asignatura específica.</li>
            <li>Usa el campo <em>Número</em> para definir el orden (T1, T2...).</li>
            <li>Al crear una actividad, selecciona el tema correspondiente para que el alumno vea el material
              organizado.</li>
          </ul>
        </div>
      </div>
    </section>

    <!-- Actividades -->
    <section id="actividades" class="scroll-mt-24 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      <h2 class="text-lg font-semibold">3. Crear actividades</h2>
      <p class="mt-3 text-sm text-slate-700 leading-6">
        Desde <strong>Admin &rarr; Actividades</strong> pulsa <em>Nueva actividad</em>. Rellena los campos comunes y,
        según el <em>Tipo</em>, aparecerán opciones específicas.
      </p>

      <!-- Campos comunes -->
      <div id="campos-comunes" class="mt-5 scroll-mt-24">
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
            <dd class="text-slate-700">Define el formato de la actividad: Opción múltiple, Verdadero/Falso, Respuesta
              corta, Rellenar huecos, Emparejar o Tarea.</dd>
          </div>
          <div>
            <dt class="font-medium">Dificultad</dt>
            <dd class="text-slate-700">Baja / Media / Alta. Referencia para ti y el alumno.</dd>
          </div>
          <div>
            <dt class="font-medium">Visibilidad</dt>
            <dd class="text-slate-700"><em>Privada</em> (solo tú) o <em>Pública</em> (visible para el centro/a quien
              corresponda).</dd>
          </div>
          <div>
            <dt class="font-medium">Estado</dt>
            <dd class="text-slate-700"><em>Borrador</em> (en edición) o <em>Publicada</em> (lista para usar).</dd>
          </div>
        </dl>
      </div>

      <!-- Tarea -->
      <div id="tarea" class="mt-8 scroll-mt-24">
        <h3 class="text-base font-semibold text-slate-800">3.2 Tarea / Entrega (campos específicos)</h3>
        <ul class="mt-2 list-disc pl-6 text-sm text-slate-700 space-y-1">
          <li><strong>Instrucciones</strong>: qué debe entregar el alumno.</li>
          <li><strong>Permitir texto / archivos / enlaces</strong>: tipos de entrega aceptados.</li>
          <li><strong>Máx. archivos</strong> y <strong>Máx. tamaño (MB)</strong>: límites por alumno.</li>
          <li><strong>Evaluación</strong>: sin evaluación, <em>Puntuación</em> (define <em>Puntuación máxima</em>) o
            <em>Rúbrica</em> (JSON).
          </li>
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
      <div id="vf" class="mt-8 scroll-mt-24">
        <h3 class="text-base font-semibold text-slate-800">3.3 Verdadero / Falso</h3>
        <ul class="mt-2 list-disc pl-6 text-sm text-slate-700 space-y-1">
          <li><strong>Respuesta correcta</strong>: selecciona <em>Verdadero</em> o <em>Falso</em>.</li>
          <li><strong>Feedback</strong> (acierto / error): mensajes opcionales que verá el alumno tras responder.</li>
        </ul>
        <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs">
          <strong>Ejemplo</strong>: “La mitosis ocurre en células somáticas.” → <em>Respuesta correcta:</em>
          Verdadero.<br>
          <em>Feedback si acierta:</em> “Correcto: la mitosis se da en células somáticas.”<br>
          <em>Feedback si falla:</em> “Revisa: la meiosis es la división para células sexuales.”
        </div>
      </div>

      <!-- Respuesta corta -->
      <div id="rc" class="mt-8 scroll-mt-24">
        <h3 class="text-base font-semibold text-slate-800">3.4 Respuesta corta</h3>
        <p class="mt-2 text-sm text-slate-700">El sistema puede autocorregir por <em>Palabras clave</em> o por
          <em>Regex</em>.
        </p>

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
              <div class="mt-2">Umbral: 60%. Puntuación máx.: 10. Acierto si aparece al menos el 60% del peso total.
              </div>
            </div>
          </div>

          <div class="rounded-lg border border-slate-200 bg-white p-4">
            <h4 class="text-sm font-semibold">Modo: Regex</h4>
            <ul class="mt-2 list-disc pl-6 text-sm text-slate-700 space-y-1">
              <li><strong>Patrón regex</strong> y <strong>flags</strong> (p. ej. <code>i</code> para no distinguir
                mayúsculas).</li>
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

      <!-- Otros tipos -->
      <div id="act-tipos" class="mt-8 scroll-mt-24 border-t border-slate-100 pt-5">
        <h3 class="text-base font-semibold text-slate-800">3.5 Otros tipos de actividad</h3>
        <div class="mt-3 space-y-4 text-sm text-slate-700">
          <div>
            <h4 class="font-semibold text-slate-800">Rellenar huecos</h4>
            <p>Escribe el texto y usa doble corchete para los huecos: <code>La capital de Francia es [[París]]</code>.
              El sistema detectará automáticamente "París" como la respuesta válida.</p>
          </div>
          <div>
            <h4 class="font-semibold text-slate-800">Emparejar</h4>
            <p>Define parejas de conceptos (Izquierda - Derecha). Al alumno se le presentará la columna derecha
              desordenada para que busque las parejas correctas.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Exámenes -->
    <section id="examenes" class="scroll-mt-24 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      <h2 class="text-lg font-semibold">4. Gestión de Exámenes</h2>
      <div class="mt-3 space-y-4 text-sm text-slate-700 leading-6">
        <p>Un <strong>Examen</strong> o <strong>Hoja de actividades</strong> es la agrupación final que el alumno debe
          completar.</p>
        <ol class="list-decimal pl-5 space-y-2">
          <li>Crea el examen en <em>Admin &rarr; Exámenes</em> asignándolo a un curso y asignatura.</li>
          <li>Usa el icono de "Actividades" (&plusmn;) en la tabla para añadir preguntas desde tu banco de actividades.
          </li>
          <li>Configura los <strong>parámetros técnicos</strong>:
            <ul class="list-disc pl-5 mt-2 space-y-1">
              <li><em>Borrador / Publicado</em>: solo los publicados son visibles para el alumno.</li>
              <li><em>Fecha y Hora</em>: definen cuándo se abre la prueba.</li>
              <li><em>Duración</em>: tiempo máximo que tendrá el alumno una vez iniciado.</li>
            </ul>
          </li>
        </ol>
      </div>

      <div id="iframe" class="mt-8 scroll-mt-24 border-t border-slate-100 pt-5">
        <h3 class="text-base font-semibold text-slate-800">4.3 Integración externa (Iframe)</h3>
        <p class="mt-3 text-sm text-slate-700">
          Si usas una plataforma externa como <strong>Moodle, Canvas o WordPress</strong>, puedes incrustar directamente
          el examen sin que tus alumnos tengan que salir de tu entorno.
        </p>
        <div class="mt-4 rounded-lg border border-teal-200 bg-teal-50 p-4">
          <h4 class="text-sm font-semibold text-teal-800">Cómo obtener el código:</h4>
          <ol class="mt-2 list-decimal pl-5 text-sm text-teal-700 space-y-1">
            <li>Ve a <em>Admin &rarr; Exámenes</em>.</li>
            <li>En la fila del examen deseado, haz clic en el botón <strong>"Código Iframe"</strong>.</li>
            <li>Copia el fragmento de código HTML que aparece y pégalo en el editor de tu plataforma (en modo HTML).
            </li>
          </ol>
        </div>
        <p class="mt-3 text-xs text-slate-500">
          <em>Nota:</em> Este modo oculta automáticamente las cabeceras de Bancalia para que el examen se integre de
          forma natural en el diseño de tu sitio.
        </p>
      </div>
    </section>

    <!-- Evaluación -->
    <section id="evaluacion" class="scroll-mt-24 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      <h2 class="text-lg font-semibold">5. Evaluación y Notas</h2>
      <div class="mt-3 space-y-4 text-sm text-slate-700 leading-6">
        <p>El sistema corrige automáticamente la mayoría de tipos, pero el profesor tiene el control final.</p>

        <h3 class="font-semibold text-slate-800 mt-4 text-base">Ver Resultados</h3>
        <p>En el listado de exámenes, pulsa <strong>Intentos</strong> para ver quién ha participado, su fecha de entrega
          y su nota provisional.</p>

        <h3 class="font-semibold text-slate-800 mt-4 text-base">Calificación Manual (Tareas)</h3>
        <p>Las actividades de tipo <em>Tarea</em> requieren tu intervención:</p>
        <ul class="list-disc pl-5 space-y-1">
          <li>Entra en <strong>Ver/Calificar</strong> dentro de un intento.</li>
          <li>Lee la respuesta o revisa el enlace enviado por el alumno.</li>
          <li>Asigna la puntuación en el recuadro de esa actividad y pulsa <strong>Guardar calificaciones</strong>. La
            nota total se recalculará automáticamente.</li>
        </ul>
      </div>
    </section>

    <!-- FAQ -->
    <section id="faq" class="scroll-mt-24 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      <h2 class="text-lg font-semibold">6. Preguntas frecuentes</h2>
      <div class="mt-3 divide-y">
        <details class="py-3">
          <summary class="cursor-pointer text-sm font-medium">¿Puedo cambiar el tipo de una actividad una vez creada?
          </summary>
          <p class="mt-2 text-sm text-slate-700">Sí. Al editar, cambia el <em>Tipo</em> y completa sus opciones. No
            borramos configuraciones anteriores (quedan guardadas por si vuelves a ese tipo).</p>
        </details>
        <details class="py-3">
          <summary class="cursor-pointer text-sm font-medium">¿Por qué mis alumnos no ven el examen?</summary>
          <p class="mt-2 text-sm text-slate-700">Asegúrate de que el estado es <strong>Publicado</strong> y que la
            fecha/hora de inicio ya ha pasado. También debe tener al menos una actividad asociada.</p>
        </details>
        <details class="py-3">
          <summary class="cursor-pointer text-sm font-medium">¿Qué significa el estado “Pendiente” en un intento?
          </summary>
          <p class="mt-2 text-sm text-slate-700">Significa que el intento contiene preguntas que requieren corrección
            manual (como Tareas) o que el profesor aún no ha validado la nota.</p>
        </details>
        <details class="py-3">
          <summary class="cursor-pointer text-sm font-medium">Me da error con el JSON de palabras clave</summary>
          <p class="mt-2 text-sm text-slate-700">Comprueba comas y comillas. Pruébalo con un validador JSON. Estructura
            mínima: <code>[{"palabra":"texto","peso":1}]</code>.</p>
        </details>
      </div>
    </section>

  </main>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>