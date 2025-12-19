✨ Características

📚 Banco centralizado de actividades

🧩 Tipos de actividad:

Opción múltiple

Verdadero / Falso

Respuesta corta (palabras clave o regex)

Rellenar huecos

Emparejar conceptos

Tareas de entrega con rúbrica

👥 Gestión de roles:

Profesor

Alumno

Administrador

🔄 Estados de actividad:

Borrador

Publicada

Entregada

Corregida

🏷️ Clasificación pedagógica completa:

Familia profesional / Grado

Curso

Asignatura

Tema

Dificultad

Etiquetas compartidas

🧠 Generación de actividades mediante IA (opcional)

📱 Acceso rápido a actividades:

Código de acceso

Código QR

🧩 Integración con LMS mediante iframe (Moodle, etc.)

💳 Sistema de suscripciones:

Plan gratuito

Plan Pro para profesorado

🖥️ Demo / Capturas

🔗 Demo en vivo:
Próximamente

🖼️ Capturas de pantalla:
Próximamente

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
Clonar el repositorio
git clone https://github.com/fpalacioschaves/Bancalia.git
cd Bancalia

Configurar entorno
Edita el archivo config.php con tus credenciales de base de datos.

Base de datos
Importa el esquema o dump inicial:

mysql -u root -p bancalia < database/dump/bancalia.sql

Arrancar servidor
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
Flujo típico (profesor)

Crear actividad

Clasificarla (curso, asignatura, tema, etiquetas)

Marcar como publicada

Compartir con el resto del profesorado o mantenerla privada

Usarla como parte de un exámen

Flujo típico (alumno)

Acceder a la actividad/exámen

Resolverla

Entregar

Consultar feedback y estado


☁️ Despliegue

Recomendaciones:

PHP 8+

HTTPS (TLS)

Backups periódicos de la base de datos

Separar almacenamiento de archivos si se escala

Soporte para Docker previsto.

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

Si detectas una vulnerabilidad, por favor repórtala de forma responsable a:
📧 fpalacioschaves@gmail.com
 (placeholder)

📄 Licencia

Este proyecto está bajo licencia MIT.

📬 Contacto

👤 Mantenedor: Paco Palacios

🐙 GitHub: https://github.com/fpalacioschaves

📧 Email: fpalacioschaves@gmail.com
