✨ Características
📚 Banco centralizado de actividades

Repositorio único de actividades educativas reutilizables, organizadas y accesibles según permisos.

🧩 Tipos de actividad soportados

Opción múltiple

Verdadero / Falso

Respuesta corta

Palabras clave

Expresiones regulares (regex)

Rellenar huecos

Emparejar conceptos

Tareas de entrega

Texto

Archivos

Enlaces

Evaluación mediante rúbrica

💡 Permite combinar corrección automática y evaluación manual según el tipo de actividad.

👥 Gestión de roles

👨‍🏫 Profesor

🎓 Alumno

🛠️ Administrador

Cada rol dispone de permisos específicos y vistas adaptadas.

🔄 Estados de actividad

📝 Borrador

📢 Publicada

📥 Entregada

✅ Corregida

Estos estados permiten controlar todo el ciclo de vida de una actividad o examen.

🏷️ Clasificación pedagógica completa

Las actividades se organizan por:

Familia profesional / Grado

Curso

Asignatura

Tema

Dificultad

Etiquetas compartidas entre profesores

Esto facilita búsquedas avanzadas y reutilización de contenidos.

🧠 Generación de actividades mediante IA (opcional)

Creación asistida de actividades educativas

Enfoque controlado y revisable por el profesorado

Pensado como ayuda, no como sustitución del docente

📱 Acceso rápido a actividades

🔑 Código de acceso

📷 Código QR

Ideal para compartir actividades puntuales o evaluaciones rápidas.

🧩 Integración con LMS

Generación de iframe embebible

Compatible con plataformas como Moodle

Integración sin duplicar contenidos

💳 Sistema de suscripciones

🆓 Plan gratuito

⭐ Plan Pro para profesorado

Más actividades

IA

Funcionalidades avanzadas

🖥️ Demo / Capturas
🔗 Demo en vivo

Próximamente

🖼️ Capturas de pantalla

Próximamente

![Panel del profesor](docs/screenshots/panel-profesor.png)
![Creación de actividad](docs/screenshots/crear-actividad.png)
![Vista del alumno](docs/screenshots/panel-alumno.png)

🛠️ Tecnologías
Frontend

HTML5

CSS3

jQuery

Backend

PHP 8+

PDO

Arquitectura modular

Base de datos

MySQL / MariaDB

⚙️ Requisitos

PHP >= 8.x

MySQL / MariaDB

Servidor web (Apache / Nginx)

Composer (opcional)

Docker / docker-compose (opcional)

🚀 Instalación rápida
1️⃣ Clonar el repositorio
git clone https://github.com/fpalacioschaves/Bancalia.git
cd Bancalia

2️⃣ Configurar entorno

Edita el archivo config.php con tus credenciales de base de datos.

3️⃣ Base de datos

Importa el esquema o dump inicial:

mysql -u root -p bancalia < database/dump/bancalia.sql

4️⃣ Arrancar servidor
php -S localhost -t public


Accede a:
👉 http://localhost

🗄️ Base de datos

Esquema relacional normalizado

Soporte para:

actividades

exámenes

asignaciones

entregas

usuarios y roles

suscripciones

Restaurar base de datos de ejemplo:

mysql -u root -p bancalia < database/dump/example.sql

▶️ Uso
👨‍🏫 Flujo típico (profesor)

Crear actividad

Clasificarla (curso, asignatura, tema, etiquetas)

Marcarla como publicada

Compartirla o mantenerla privada

Usarla como parte de un examen

🎓 Flujo típico (alumno)

Acceder a la actividad o examen

Resolverla

Entregar

Consultar feedback y estado

☁️ Despliegue

Recomendaciones:

PHP 8+

HTTPS (TLS)

Backups periódicos de la base de datos

Separar almacenamiento de archivos si se escala

🐳 Soporte para Docker previsto.

🤝 Contribuir

¡Las contribuciones son bienvenidas!

Abre un issue antes de cambios grandes

Haz fork del proyecto

Crea una rama feature/nombre

Envía un Pull Request bien documentado

🗺️ Roadmap

✅ MVP: banco de actividades

🔄 Asignaciones y entregas

📝 Exámenes automáticos

🧩 Integración LMS

🤖 IA educativa (beta)

📊 Analíticas y métricas

💳 Monetización y planes avanzados

🔐 Seguridad

Si detectas una vulnerabilidad, repórtala de forma responsable a:
📧 fpalacioschaves@gmail.com
 (placeholder)

📄 Licencia

Este proyecto está bajo licencia MIT.

📬 Contacto

👤 Mantenedor: Paco Palacios

🐙 GitHub: https://github.com/fpalacioschaves

📧 Email: fpalacioschaves@gmail.com
