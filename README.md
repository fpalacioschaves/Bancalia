<h1 align="center">🎓 Bancalia</h1>

<p align="center">
  <strong>Banco de actividades educativas para crear, clasificar, reutilizar y evaluar recursos formativos</strong>
</p>

<p align="center">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8.x-blue" />
  <img alt="MySQL" src="https://img.shields.io/badge/MySQL-MariaDB-orange" />
  <img alt="Frontend" src="https://img.shields.io/badge/Frontend-HTML%20%7C%20CSS%20%7C%20jQuery-61dafb" />
  <img alt="Estado" src="https://img.shields.io/badge/estado-en%20desarrollo-yellow" />
</p>

---

## Descripción

**Bancalia** es una aplicación web orientada a la gestión de actividades educativas. Su objetivo es ayudar al profesorado a crear, clasificar, compartir y reutilizar actividades estructuradas en entornos de Formación Profesional y formación técnica.

El proyecto nace de una necesidad habitual en el aula: evitar que las actividades, pruebas, rúbricas y recursos queden dispersos en documentos sueltos, carpetas personales o plataformas poco conectadas entre sí.

La idea central es convertir ese material didáctico en un **banco organizado de actividades**, consultable por módulo, curso, tema, dificultad, tipo de actividad y etiquetas.

---

## Qué problema resuelve

En muchos contextos formativos, el profesorado genera una gran cantidad de materiales: cuestionarios, tareas, actividades de refuerzo, ejercicios de evaluación, prácticas, rúbricas o pruebas rápidas. El problema no es solo crearlos, sino mantenerlos ordenados, reutilizables y adaptables.

Bancalia propone una solución para:

- centralizar actividades y recursos didácticos,
- clasificar materiales por criterios pedagógicos,
- reutilizar actividades entre cursos o grupos,
- preparar pruebas y ejercicios de forma más rápida,
- facilitar la revisión, mejora y evolución de los materiales,
- conectar mejor el trabajo docente con la evaluación.

---

## Funcionalidades previstas

- Banco centralizado de actividades educativas.
- Gestión de usuarios con perfiles diferenciados.
- Clasificación por familia, ciclo, curso, módulo, tema, dificultad y etiquetas.
- Actividades de opción múltiple, verdadero/falso, respuesta corta, huecos, emparejamiento y tareas con rúbrica.
- Estados de actividad: borrador, publicada, entregada y corregida.
- Posibilidad de compartir actividades o mantenerlas privadas.
- Preparación de pruebas a partir de actividades existentes.
- Acceso rápido mediante código o enlace.
- Base para integración futura con LMS o plataformas de aula.
- Posible apoyo de IA para generación o adaptación de actividades.

---

## Valor educativo

Bancalia no está planteado como un ejercicio aislado, sino como una herramienta educativa real. Encaja especialmente en ciclos de FP, certificados de profesionalidad y entornos donde se necesita trabajar con actividades prácticas, evaluación continua y materiales reutilizables.

El proyecto conecta varias líneas de trabajo:

- **Docencia técnica:** actividades pensadas para programación, bases de datos, desarrollo web y módulos TIC.
- **Gestión educativa:** organización de recursos, clasificación, seguimiento y evaluación.
- **Desarrollo web:** aplicación CRUD con backend PHP, base de datos relacional y estructura modular.
- **Mejora docente:** reutilización de materiales y construcción progresiva de un repositorio didáctico.

---

## Stack técnico

| Capa | Tecnología |
|---|---|
| Backend | PHP 8+, PDO, arquitectura modular |
| Frontend | HTML5, CSS3, JavaScript, jQuery |
| Base de datos | MySQL / MariaDB |
| Servidor | Apache / Nginx / servidor PHP integrado para desarrollo |
| Enfoque | CRUD, roles, clasificación pedagógica, gestión educativa |

---

## Arquitectura general

El proyecto está planteado como una aplicación web clásica con separación entre:

```txt
Bancalia/
├── public/          # Punto de entrada público de la aplicación
├── config/          # Configuración de entorno y conexión
├── database/        # Esquemas o volcados de base de datos
├── src/             # Lógica principal de aplicación
├── views/           # Plantillas o vistas
└── assets/          # CSS, JS e imágenes
```

> La estructura exacta puede evolucionar durante el desarrollo.

---

## Flujo de uso previsto

### Profesorado

1. Crear una actividad.
2. Clasificarla por curso, módulo, tema y dificultad.
3. Añadir preguntas, enunciados, soluciones o rúbricas.
4. Guardarla como borrador o publicarla.
5. Reutilizarla en una prueba, tarea o actividad de aula.
6. Revisar resultados y mejorar el recurso.

### Alumnado

1. Acceder a una actividad o prueba.
2. Resolver las preguntas o entregar la tarea.
3. Recibir feedback o consultar el estado.
4. Repetir o mejorar la actividad cuando proceda.

---

## Instalación local orientativa

```bash
git clone https://github.com/fpalacioschaves/Bancalia.git
cd Bancalia
```

Configura la conexión a base de datos en el archivo de configuración correspondiente y crea una base de datos MySQL/MariaDB para el proyecto.

Ejemplo de arranque con servidor PHP integrado:

```bash
php -S localhost:8000 -t public
```

Después accede desde el navegador a:

```txt
http://localhost:8000
```

---

## Estado del proyecto

Proyecto en desarrollo y evolución. El repositorio funciona como prototipo técnico y como base para una posible herramienta educativa más completa.

Líneas de mejora previstas:

- completar flujos de asignación y entrega,
- mejorar la generación de pruebas,
- añadir analíticas de uso y resultados,
- reforzar permisos y roles,
- preparar integración con LMS,
- explorar generación asistida de actividades mediante IA.

---

## Relación con mi perfil profesional

Bancalia forma parte de una línea de proyectos centrados en tecnología educativa, Formación Profesional y desarrollo de herramientas reales para el aula.

Se relaciona directamente con otros proyectos como:

- [SAFA Twin](https://github.com/fpalacioschaves/safa-twin), gemelo digital académico para FP.
- [CV React](https://github.com/fpalacioschaves/cv-react), portfolio profesional desarrollado con React y Vite.
- [Practicalia](https://github.com/fpalacioschaves/practicalia), herramienta para gestión de FP Dual y prácticas.

---

## Contacto

**Francisco Palacios Chaves**  
Docente FP TIC · Desarrollador web full stack · Tecnología educativa

- GitHub: [https://github.com/fpalacioschaves](https://github.com/fpalacioschaves)
- CV online: [https://fpalacioschaves.github.io/cv-react/](https://fpalacioschaves.github.io/cv-react/)
- Email: [fpalacioschaves@gmail.com](mailto:fpalacioschaves@gmail.com)
