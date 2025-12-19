<h1 align="center">🎓 Bancalia</h1>

<p align="center">
  <strong>Plataforma inteligente de banco de actividades, exámenes y evaluación educativa</strong>
</p>

<p align="center">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8.x-blue" />
  <img alt="MySQL" src="https://img.shields.io/badge/MySQL-MariaDB-orange" />
  <img alt="Status" src="https://img.shields.io/badge/status-en%20desarrollo-yellow" />
  <img alt="License" src="https://img.shields.io/badge/license-MIT-green" />
</p>

<hr/>

<h2>📑 Índice</h2>
<ul>
  <li><a href="#descripcion">Descripción</a></li>
  <li><a href="#caracteristicas">Características</a></li>
  <li><a href="#demo--capturas">Demo / Capturas</a></li>
  <li><a href="#tecnologias">Tecnologías</a></li>
  <li><a href="#requisitos">Requisitos</a></li>
  <li><a href="#instalacion-rapida">Instalación rápida</a></li>
  <li><a href="#base-de-datos">Base de datos</a></li>
  <li><a href="#uso">Uso</a></li>
  <li><a href="#despliegue">Despliegue</a></li>
  <li><a href="#contribuir">Contribuir</a></li>
  <li><a href="#roadmap">Roadmap</a></li>
  <li><a href="#seguridad">Seguridad</a></li>
  <li><a href="#licencia">Licencia</a></li>
  <li><a href="#contacto">Contacto</a></li>
</ul>

<hr/>

<h2 id="descripcion">📘 Descripción</h2>
<p>
  <strong>Bancalia</strong> es un banco de actividades educativas que permite a profesores, alumnos y administradores
  crear, gestionar y utilizar actividades estructuradas (tests, respuestas cortas, rellenar huecos, emparejar, tareas, etc.).
  Diseñado para centros de Formación Profesional y entornos reglados, con soporte para generación de actividades con IA y opciones
  de monetización por suscripción.
</p>

<hr/>

<h2 id="caracteristicas">✨ Características</h2>

<ul>
  <li>📚 <strong>Banco centralizado de actividades</strong></li>
  <li>🧩 <strong>Tipos</strong>: Opción múltiple, V/F, Respuesta corta, Rellenar huecos, Emparejar, Tareas con rúbrica</li>
  <li>👥 <strong>Gestión de permisos</strong>: profesor, alumno, administrador</li>
  <li>🔄 <strong>Estados de actividad</strong>: borrador / publicada / entregada / corregida</li>
  <li>🧩 <strong>Integración LMS</strong> (iframe), acceso por QR o código</li>
  <li>🧠 <strong>Generación de actividades mediante IA</strong> (opcional)</li>
  <li>💳 <strong>Suscripciones y planes</strong> (Plan gratuito / Plan Pro)</li>
</ul>

<details>
  <summary><strong>📌 Ver detalle por apartados</strong></summary>

  <h3>🧩 Tipos de actividad</h3>
  <ul>
    <li>Opción múltiple</li>
    <li>Verdadero / Falso</li>
    <li>Respuesta corta (palabras clave o regex)</li>
    <li>Rellenar huecos</li>
    <li>Emparejar conceptos</li>
    <li>Tareas de entrega con rúbrica</li>
  </ul>

  <h3>👥 Gestión de roles</h3>
  <ul>
    <li>Profesor</li>
    <li>Alumno</li>
    <li>Administrador</li>
  </ul>

  <h3>🔄 Estados de actividad</h3>
  <ul>
    <li>Borrador</li>
    <li>Publicada</li>
    <li>Entregada</li>
    <li>Corregida</li>
  </ul>

  <h3>🏷️ Clasificación pedagógica completa</h3>
  <ul>
    <li>Familia profesional / Grado</li>
    <li>Curso</li>
    <li>Asignatura</li>
    <li>Tema</li>
    <li>Dificultad</li>
    <li>Etiquetas compartidas</li>
  </ul>

  <h3>📱 Acceso rápido</h3>
  <ul>
    <li>Código de acceso</li>
    <li>Código QR</li>
  </ul>

  <h3>🧩 Integración con LMS</h3>
  <ul>
    <li>Iframe embebible (Moodle, etc.)</li>
  </ul>

  <h3>💳 Suscripciones</h3>
  <ul>
    <li>Plan gratuito</li>
    <li>Plan Pro para profesorado</li>
  </ul>
</details>

<hr/>

<h2 id="demo--capturas">🖥️ Demo / Capturas</h2>
<ul>
  <li>🔗 <strong>Demo en vivo</strong>: Próximamente</li>
  <li>🖼️ <strong>Capturas de pantalla</strong>: Próximamente</li>
</ul>

<hr/>

<h2 id="tecnologias">🛠️ Tecnologías</h2>

<table>
  <thead>
    <tr>
      <th align="left">Capa</th>
      <th align="left">Tecnologías</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><strong>Frontend</strong></td>
      <td>HTML5, CSS3, jQuery</td>
    </tr>
    <tr>
      <td><strong>Backend</strong></td>
      <td>PHP 8+, PDO, arquitectura modular</td>
    </tr>
    <tr>
      <td><strong>Base de datos</strong></td>
      <td>MySQL / MariaDB</td>
    </tr>
  </tbody>
</table>

<hr/>

<h2 id="requisitos">⚙️ Requisitos</h2>
<ul>
  <li>PHP &gt;= 8.x</li>
  <li>MySQL / MariaDB</li>
  <li>Servidor web (Apache / Nginx)</li>
  <li>Composer (opcional)</li>
  <li>Docker / docker-compose (opcional)</li>
</ul>

<hr/>

<h2 id="instalacion-rapida">🚀 Instalación rápida</h2>

<h3>1) Clonar el repositorio</h3>
<pre><code>git clone https://github.com/fpalacioschaves/Bancalia.git
cd Bancalia
</code></pre>

<h3>2) Configurar entorno</h3>
<p>Edita el archivo <code>config.php</code> con tus credenciales de base de datos.</p>

<h3>3) Base de datos</h3>
<p>Importa el esquema o dump inicial:</p>
<pre><code>mysql -u root -p bancalia &lt; database/dump/bancalia.sql
</code></pre>

<h3>4) Arrancar servidor</h3>
<pre><code>php -S localhost -t public
</code></pre>

<p>
  Accede a: 👉 <strong><a href="http://localhost">http://localhost</a></strong>
</p>

<hr/>

<h2 id="base-de-datos">🗄️ Base de datos</h2>
<ul>
  <li><strong>Esquema relacional normalizado</strong></li>
  <li><strong>Soporte para</strong>:
    <ul>
      <li>actividades</li>
      <li>exámenes</li>
      <li>asignaciones</li>
      <li>entregas</li>
      <li>usuarios y roles</li>
      <li>suscripciones</li>
    </ul>
  </li>
</ul>

<p><strong>Restaurar base de datos de ejemplo:</strong></p>
<pre><code>mysql -u root -p bancalia &lt; database/dump/example.sql
</code></pre>

<hr/>

<h2 id="uso">▶️ Uso</h2>

<h3>👨‍🏫 Flujo típico (profesor)</h3>
<ol>
  <li>Crear actividad</li>
  <li>Clasificarla (curso, asignatura, tema, etiquetas)</li>
  <li>Marcar como publicada</li>
  <li>Compartir con el resto del profesorado o mantenerla privada</li>
  <li>Usarla como parte de un exámen</li>
</ol>

<h3>🎓 Flujo típico (alumno)</h3>
<ol>
  <li>Acceder a la actividad/exámen</li>
  <li>Resolverla</li>
  <li>Entregar</li>
  <li>Consultar feedback y estado</li>
</ol>

<hr/>

<h2 id="despliegue">☁️ Despliegue</h2>
<p><strong>Recomendaciones:</strong></p>
<ul>
  <li>PHP 8+</li>
  <li>HTTPS (TLS)</li>
  <li>Backups periódicos de la base de datos</li>
  <li>Separar almacenamiento de archivos si se escala</li>
</ul>
<p><em>Soporte para Docker previsto.</em></p>

<hr/>

<h2 id="contribuir">🤝 Contribuir</h2>
<p>¡Las contribuciones son bienvenidas!</p>
<ol>
  <li>Abre un issue antes de cambios grandes</li>
  <li>Haz fork del proyecto</li>
  <li>Crea una rama <code>feature/nombre</code></li>
  <li>Envía un Pull Request bien documentado</li>
</ol>

<hr/>

<h2 id="roadmap">🗺️ Roadmap</h2>
<ul>
  <li>✅ MVP: banco de actividades</li>
  <li>🔄 Asignaciones y entregas</li>
  <li>📝 Exámenes automáticos</li>
  <li>🧩 Integración LMS</li>
  <li>🤖 IA educativa (beta)</li>
  <li>📊 Analíticas y métricas</li>
  <li>💳 Monetización y planes avanzados</li>
</ul>

<hr/>

<h2 id="seguridad">🔐 Seguridad</h2>
<p>
  Si detectas una vulnerabilidad, por favor repórtala de forma responsable a:
  <br/>
  📧 <strong><a href="mailto:fpalacioschaves@gmail.com">fpalacioschaves@gmail.com</a></strong>
  <em>(placeholder)</em>
</p>

<hr/>

<h2 id="licencia">📄 Licencia</h2>
<p>Este proyecto está bajo licencia <strong>MIT</strong>.</p>

<hr/>

<h2 id="contacto">📬 Contacto</h2>
<ul>
  <li>👤 <strong>Mantenedor:</strong> Paco Palacios</li>
  <li>🐙 <strong>GitHub:</strong> <a href="https://github.com/fpalacioschaves">https://github.com/fpalacioschaves</a></li>
  <li>📧 <strong>Email:</strong> <a href="mailto:fpalacioschaves@gmail.com">fpalacioschaves@gmail.com</a></li>
</ul>


