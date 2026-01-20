-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 20-01-2026 a las 21:22:38
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `bancalia`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividades`
--

CREATE TABLE `actividades` (
  `id` int(11) NOT NULL,
  `profesor_id` int(11) NOT NULL,
  `familia_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `asignatura_id` int(11) NOT NULL,
  `tema_id` int(11) DEFAULT NULL,
  `tipo` enum('opcion_multiple','verdadero_falso','respuesta_corta','rellenar_huecos','emparejar','tarea') NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `dificultad` enum('baja','media','alta') NOT NULL DEFAULT 'media',
  `visibilidad` enum('privada','centro','publica') NOT NULL DEFAULT 'privada',
  `estado` enum('borrador','publicada') NOT NULL DEFAULT 'borrador',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `centro_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `actividades`
--

INSERT INTO `actividades` (`id`, `profesor_id`, `familia_id`, `curso_id`, `asignatura_id`, `tema_id`, `tipo`, `titulo`, `descripcion`, `dificultad`, `visibilidad`, `estado`, `created_at`, `updated_at`, `centro_id`) VALUES
(6, 2, 2, 3, 1, 1, 'verdadero_falso', 'Actividad de Verdadero / Falso', 'asdfasdgsadfgsdfg', 'baja', 'privada', 'borrador', '2025-10-19 18:45:20', '2025-11-23 12:51:56', 1),
(8, 2, 2, 3, 1, 1, 'tarea', 'Primera Actividad de Entrega', 'Descripción de la Primera Actividad de Entrega', 'baja', 'privada', 'borrador', '2025-10-20 12:53:41', '2025-11-23 12:51:59', 1),
(9, 2, 2, 3, 1, 1, 'respuesta_corta', 'Primera tarea Respuesta Corta', 'Eres tonto?', 'media', 'privada', 'borrador', '2025-10-20 13:38:39', '2025-11-23 12:52:12', 1),
(17, 2, 2, 3, 1, 1, 'rellenar_huecos', 'Actividad Rellenado de Huecos', 'Esta es una actividad de rellenado de huecos sobre SQL', 'baja', 'privada', 'borrador', '2025-11-01 16:42:29', '2025-11-23 12:52:02', 1),
(25, 2, 2, 3, 1, 1, 'emparejar', 'Actividad de Emparejar', 'Descripción de la tarea de emparejar', 'baja', 'privada', 'borrador', '2025-11-02 13:27:41', '2025-11-23 13:43:35', 1),
(34, 2, 2, 3, 1, NULL, 'opcion_multiple', 'Actividad de Opción Múltiple', 'Descripción de la tarea de opción múltiple', 'baja', 'centro', 'publicada', '2025-11-02 15:55:47', '2025-11-23 12:51:54', 1),
(35, 2, 2, 3, 1, NULL, 'opcion_multiple', 'Actividad de Opción Múltiple Duplicada', 'Descripción de la tarea de opción múltiple', 'baja', 'privada', 'borrador', '2025-11-23 11:20:58', '2025-11-23 12:52:07', 1),
(36, 2, 2, 3, 1, 1, 'verdadero_falso', 'uithyuityui', 'yuityuityuityui', 'baja', 'centro', 'publicada', '2025-11-23 12:35:49', '2025-11-23 13:43:14', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividades_emp`
--

CREATE TABLE `actividades_emp` (
  `actividad_id` int(11) NOT NULL,
  `barajar` tinyint(1) NOT NULL DEFAULT 0,
  `instrucciones_html` mediumtext NOT NULL,
  `puntuacion_max` int(11) DEFAULT NULL,
  `modo_puntuacion` enum('por_par','todo_nada') NOT NULL DEFAULT 'por_par',
  `barajar_izquierda` tinyint(1) NOT NULL DEFAULT 1,
  `barajar_derecha` tinyint(1) NOT NULL DEFAULT 1,
  `feedback_correcta` text DEFAULT NULL,
  `feedback_incorrecta` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividades_emp_pares`
--

CREATE TABLE `actividades_emp_pares` (
  `id` int(11) NOT NULL,
  `actividad_id` int(11) NOT NULL,
  `izquierda_html` text NOT NULL,
  `derecha_html` text NOT NULL,
  `alternativas_derecha_json` text DEFAULT NULL,
  `grupo` varchar(64) DEFAULT NULL,
  `orden_izq` int(11) NOT NULL DEFAULT 1,
  `orden_der` int(11) NOT NULL DEFAULT 1,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `actividades_emp_pares`
--

INSERT INTO `actividades_emp_pares` (`id`, `actividad_id`, `izquierda_html`, `derecha_html`, `alternativas_derecha_json`, `grupo`, `orden_izq`, `orden_der`, `activo`, `created_at`, `updated_at`) VALUES
(28, 25, 'srgsfgsfg', 'sdfgsdfgsdfgsdfg', NULL, NULL, 1, 2, 1, '2025-11-23 13:43:35', '2025-11-23 13:43:35'),
(29, 25, 'dfgsdfgsdf', 'gsdfgsdfgsdfg', NULL, NULL, 2, 3, 1, '2025-11-23 13:43:35', '2025-11-23 13:43:35'),
(30, 25, 'dfgsdfgsdfgsdfg', 'sdfgsdfgsdfgsfdg', NULL, NULL, 3, 1, 1, '2025-11-23 13:43:35', '2025-11-23 13:43:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividades_om`
--

CREATE TABLE `actividades_om` (
  `actividad_id` int(11) NOT NULL,
  `barajar` tinyint(1) NOT NULL DEFAULT 0,
  `enunciado_html` mediumtext NOT NULL,
  `puntuacion_max` int(11) DEFAULT NULL,
  `feedback_correcta` text DEFAULT NULL,
  `feedback_incorrecta` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `actividades_om`
--

INSERT INTO `actividades_om` (`actividad_id`, `barajar`, `enunciado_html`, `puntuacion_max`, `feedback_correcta`, `feedback_incorrecta`, `created_at`, `updated_at`) VALUES
(34, 0, 'dfgsdfgsdfg', NULL, 'sdfgsdfgsfg', 'sdfgsdfgsdfg', '2025-11-02 15:55:47', '2025-11-23 12:51:54'),
(35, 0, 'dfgsdfgsdfg', NULL, 'sdfgsdfgsfg', 'sdfgsdfgsdfg', '2025-11-23 11:20:58', '2025-11-23 12:52:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividades_om_opciones`
--

CREATE TABLE `actividades_om_opciones` (
  `id` int(11) NOT NULL,
  `actividad_id` int(11) NOT NULL,
  `opcion_html` text NOT NULL,
  `es_correcta` tinyint(1) NOT NULL DEFAULT 0,
  `orden` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Volcado de datos para la tabla `actividades_om_opciones`
--

INSERT INTO `actividades_om_opciones` (`id`, `actividad_id`, `opcion_html`, `es_correcta`, `orden`, `created_at`, `updated_at`) VALUES
(87, 34, 'ftyufyhurtyur', 1, 1, '2025-11-23 12:51:54', '2025-11-23 12:51:54'),
(88, 34, 'rtyurtyurtyurtyutyurty', 0, 2, '2025-11-23 12:51:54', '2025-11-23 12:51:54'),
(89, 34, 'rtyurtyurtyuurtyurtyu', 0, 3, '2025-11-23 12:51:54', '2025-11-23 12:51:54'),
(90, 34, 'rtyurtyurtyurtyrtyurtyurtyurtyurtyu', 0, 4, '2025-11-23 12:51:54', '2025-11-23 12:51:54'),
(91, 35, 'ftyufyhurtyur', 1, 1, '2025-11-23 12:52:07', '2025-11-23 12:52:07'),
(92, 35, 'rtyurtyurtyurtyutyurty', 0, 2, '2025-11-23 12:52:07', '2025-11-23 12:52:07'),
(93, 35, 'rtyurtyurtyuurtyurtyu', 0, 3, '2025-11-23 12:52:07', '2025-11-23 12:52:07'),
(94, 35, 'rtyurtyurtyurtyrtyurtyurtyurtyurtyu', 0, 4, '2025-11-23 12:52:07', '2025-11-23 12:52:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividades_rc`
--

CREATE TABLE `actividades_rc` (
  `id` int(11) NOT NULL,
  `actividad_id` int(11) NOT NULL,
  `modo` enum('palabras_clave','regex') NOT NULL,
  `case_sensitive` tinyint(1) NOT NULL DEFAULT 0,
  `normalizar_acentos` tinyint(1) NOT NULL DEFAULT 1,
  `trim_espacios` tinyint(1) NOT NULL DEFAULT 1,
  `palabras_clave_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `coincidencia_minima` tinyint(3) UNSIGNED DEFAULT NULL,
  `puntuacion_max` int(11) DEFAULT NULL,
  `regex_pattern` text DEFAULT NULL,
  `regex_flags` varchar(16) DEFAULT NULL,
  `respuesta_muestra` text DEFAULT NULL,
  `feedback_correcta` text DEFAULT NULL,
  `feedback_incorrecta` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `actividades_rc`
--

INSERT INTO `actividades_rc` (`id`, `actividad_id`, `modo`, `case_sensitive`, `normalizar_acentos`, `trim_espacios`, `palabras_clave_json`, `coincidencia_minima`, `puntuacion_max`, `regex_pattern`, `regex_flags`, `respuesta_muestra`, `feedback_correcta`, `feedback_incorrecta`, `created_at`, `updated_at`) VALUES
(1, 9, 'palabras_clave', 0, 0, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-01 14:16:03', '2025-11-23 12:52:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividades_rh`
--

CREATE TABLE `actividades_rh` (
  `actividad_id` int(11) NOT NULL,
  `enunciado_html` mediumtext NOT NULL,
  `huecos_json` longtext DEFAULT NULL,
  `case_sensitive` tinyint(1) NOT NULL DEFAULT 0,
  `normalizar_acentos` tinyint(1) NOT NULL DEFAULT 1,
  `trim_espacios` tinyint(1) NOT NULL DEFAULT 1,
  `puntuacion_max` int(11) DEFAULT NULL,
  `feedback_correcta` text DEFAULT NULL,
  `feedback_incorrecta` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `actividades_rh`
--

INSERT INTO `actividades_rh` (`actividad_id`, `enunciado_html`, `huecos_json`, `case_sensitive`, `normalizar_acentos`, `trim_espacios`, `puntuacion_max`, `feedback_correcta`, `feedback_incorrecta`, `created_at`, `updated_at`) VALUES
(17, 'El lenguaje {{1}} es para bases de datos {{2}}', '[\"SQL\",\"relacionales\"]', 0, 0, 1, NULL, NULL, NULL, '2025-11-01 19:54:44', '2025-11-23 12:52:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividades_tarea`
--

CREATE TABLE `actividades_tarea` (
  `id` int(11) NOT NULL,
  `actividad_id` int(11) NOT NULL,
  `instrucciones` text DEFAULT NULL,
  `perm_texto` tinyint(1) NOT NULL DEFAULT 0,
  `perm_archivo` tinyint(1) NOT NULL DEFAULT 0,
  `perm_enlace` tinyint(1) NOT NULL DEFAULT 0,
  `max_archivos` int(11) DEFAULT NULL,
  `max_peso_mb` int(11) DEFAULT NULL,
  `evaluacion_modo` enum('puntos','rubrica') DEFAULT NULL,
  `puntuacion_max` int(11) DEFAULT NULL,
  `rubrica_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`rubrica_json`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `actividades_tarea`
--

INSERT INTO `actividades_tarea` (`id`, `actividad_id`, `instrucciones`, `perm_texto`, `perm_archivo`, `perm_enlace`, `max_archivos`, `max_peso_mb`, `evaluacion_modo`, `puntuacion_max`, `rubrica_json`, `created_at`, `updated_at`) VALUES
(2, 8, 'Instrucciones para el alumno para Primera Actividad de Entrega', 1, 1, 1, 10, 100, 'puntos', 10, NULL, '2025-10-20 12:53:41', '2025-11-23 12:51:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividades_valoraciones`
--

CREATE TABLE `actividades_valoraciones` (
  `id` int(11) NOT NULL,
  `actividad_id` int(11) NOT NULL,
  `profesor_id` int(11) NOT NULL,
  `valoracion` tinyint(1) NOT NULL COMMENT '1–5 estrellas',
  `comentario` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `actividades_valoraciones`
--

INSERT INTO `actividades_valoraciones` (`id`, `actividad_id`, `profesor_id`, `valoracion`, `comentario`, `created_at`, `updated_at`) VALUES
(1, 36, 2, 5, NULL, '2025-11-23 13:43:14', '2025-11-23 13:43:14'),
(2, 25, 2, 5, NULL, '2025-11-23 13:43:35', '2025-11-23 13:43:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividades_vf`
--

CREATE TABLE `actividades_vf` (
  `id` int(11) NOT NULL,
  `actividad_id` int(11) NOT NULL,
  `respuesta_correcta` enum('verdadero','falso') NOT NULL,
  `feedback_correcta` text DEFAULT NULL,
  `feedback_incorrecta` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `actividades_vf`
--

INSERT INTO `actividades_vf` (`id`, `actividad_id`, `respuesta_correcta`, `feedback_correcta`, `feedback_incorrecta`, `created_at`, `updated_at`) VALUES
(1, 9, 'verdadero', 'Po claro que eres tonto', 'Mal. Eres tonto', '2025-10-20 13:38:39', '2025-10-20 13:38:39'),
(2, 6, 'verdadero', NULL, NULL, '2025-11-01 14:16:38', '2025-11-23 12:51:56'),
(3, 36, 'verdadero', 'yuityuityui', 'yuiyuityuityu', '2025-11-23 12:35:49', '2025-11-23 13:43:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaturas`
--

CREATE TABLE `asignaturas` (
  `id` int(11) NOT NULL,
  `familia_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `slug` varchar(160) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `horas` int(11) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `asignaturas`
--

INSERT INTO `asignaturas` (`id`, `familia_id`, `curso_id`, `nombre`, `slug`, `codigo`, `horas`, `descripcion`, `orden`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 2, 3, 'Bases de Datos', 'bases-de-datos', NULL, NULL, NULL, 1, 1, '2025-10-18 16:03:02', '2025-10-18 16:03:02'),
(2, 2, 3, 'Programación', 'programaci-on', NULL, NULL, NULL, 1, 1, '2025-10-18 16:03:19', '2025-10-18 16:03:19'),
(3, 2, 3, 'Lenguajes de Marcas', 'lenguajes-de-marcas', NULL, NULL, NULL, 1, 1, '2025-10-18 16:03:52', '2025-10-18 16:03:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaturas_old`
--

CREATE TABLE `asignaturas_old` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `grado_id` smallint(5) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `asignaturas_old`
--

INSERT INTO `asignaturas_old` (`id`, `grado_id`, `nombre`, `codigo`) VALUES
(1, 1, 'Bases de Datos', 'BD'),
(2, 2, 'Lenguajes de Marcas', 'LM'),
(3, 2, 'Programación', 'PRO'),
(4, 1, 'Entornos de Desarrollo', 'ED');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignatura_curso`
--

CREATE TABLE `asignatura_curso` (
  `asignatura_id` smallint(5) UNSIGNED NOT NULL,
  `curso_id` smallint(5) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `asignatura_curso`
--

INSERT INTO `asignatura_curso` (`asignatura_id`, `curso_id`) VALUES
(1, 1),
(2, 3),
(3, 4),
(4, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `centros`
--

CREATE TABLE `centros` (
  `id` int(11) NOT NULL,
  `nombre` varchar(180) NOT NULL,
  `slug` varchar(190) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `tipo` enum('publico','privado','concertado') NOT NULL DEFAULT 'publico',
  `comunidad` varchar(100) DEFAULT NULL,
  `provincia` varchar(100) DEFAULT NULL,
  `localidad` varchar(120) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `cp` varchar(10) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `web` varchar(200) DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `centros`
--

INSERT INTO `centros` (`id`, `nombre`, `slug`, `codigo`, `tipo`, `comunidad`, `provincia`, `localidad`, `direccion`, `cp`, `email`, `telefono`, `web`, `lat`, `lng`, `descripcion`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'ICET SAFA Málaga', 'icet-safa-m-alaga', NULL, 'publico', 'Andalucía', 'Málaga', 'Málaga', 'C. Banda del Mar, 1', '29017', NULL, NULL, NULL, 36.7189700, -4.3585840, NULL, 1, '2025-10-18 19:36:33', '2025-10-18 19:59:17'),
(2, 'Otro Centro Distinto', 'o', NULL, 'publico', 'Andalucía', 'Málaga', 'Malaga', 'Tejares 13', '29011', NULL, NULL, NULL, 36.7284040, -4.4360520, NULL, 1, '2025-10-18 22:08:30', '2025-10-18 22:08:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id` int(11) NOT NULL,
  `familia_id` int(11) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cursos`
--

INSERT INTO `cursos` (`id`, `familia_id`, `nombre`, `slug`, `descripcion`, `orden`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, '1º', '1o', NULL, 1, 1, '2025-10-18 15:47:25', '2025-10-18 15:47:25'),
(2, 1, '2º', '2o', NULL, 2, 1, '2025-10-18 15:47:39', '2025-10-18 15:47:39'),
(3, 2, '1º', '1o', NULL, 1, 1, '2025-10-18 15:48:04', '2025-10-18 15:48:04'),
(4, 2, '2º', '2o', NULL, 2, 1, '2025-10-18 15:48:14', '2025-10-18 15:48:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos_old`
--

CREATE TABLE `cursos_old` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `grado_id` smallint(5) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `orden` tinyint(3) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cursos_old`
--

INSERT INTO `cursos_old` (`id`, `grado_id`, `nombre`, `orden`) VALUES
(1, 1, '1º', 1),
(2, 1, '2º', 2),
(3, 2, '1º', 1),
(4, 2, '2º', 2),
(5, 1, 'Máster', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `examenes`
--

CREATE TABLE `examenes` (
  `id` int(11) NOT NULL,
  `profesor_id` int(11) NOT NULL,
  `familia_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `asignatura_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado` enum('borrador','publicado') NOT NULL DEFAULT 'borrador',
  `tipo` enum('examen','practica') NOT NULL DEFAULT 'examen',
  `fecha` date DEFAULT NULL,
  `hora` time DEFAULT NULL,
  `duracion_minutos` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `examenes`
--

INSERT INTO `examenes` (`id`, `profesor_id`, `familia_id`, `curso_id`, `asignatura_id`, `titulo`, `descripcion`, `estado`, `tipo`, `fecha`, `hora`, `duracion_minutos`, `created_at`, `updated_at`) VALUES
(1, 2, 2, 3, 1, 'Primer Parcial de Bases de Datos', 'Primer parcial de Bases de Datos', 'publicado', 'examen', '2025-11-16', '18:36:00', 180, '2025-11-15 12:36:26', '2025-11-16 18:47:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `examenes_actividades`
--

CREATE TABLE `examenes_actividades` (
  `id` int(11) NOT NULL,
  `examen_id` int(11) NOT NULL,
  `actividad_id` int(11) NOT NULL,
  `orden` int(11) NOT NULL DEFAULT 1,
  `puntuacion` decimal(5,2) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `examenes_actividades`
--

INSERT INTO `examenes_actividades` (`id`, `examen_id`, `actividad_id`, `orden`, `puntuacion`, `created_at`, `updated_at`) VALUES
(4, 1, 34, 1, NULL, '2025-11-15 13:54:02', '2025-11-15 13:54:02'),
(5, 1, 25, 2, NULL, '2025-11-15 13:54:02', '2025-11-15 13:54:02'),
(6, 1, 17, 3, NULL, '2025-11-15 13:54:02', '2025-11-15 13:54:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `examen_intentos`
--

CREATE TABLE `examen_intentos` (
  `id` int(11) NOT NULL,
  `examen_id` int(11) NOT NULL,
  `nombre_alumno` varchar(150) DEFAULT NULL,
  `email_alumno` varchar(190) DEFAULT NULL,
  `token` varchar(64) NOT NULL,
  `nota` decimal(5,2) DEFAULT NULL,
  `corregido` tinyint(1) NOT NULL DEFAULT 0,
  `alumno_id` int(11) DEFAULT NULL,
  `inicio_at` datetime NOT NULL DEFAULT current_timestamp(),
  `fin_at` datetime DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `nota_total` decimal(5,2) DEFAULT NULL,
  `estado` enum('en_progreso','enviado','corregido') NOT NULL DEFAULT 'en_progreso',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `examen_intentos`
--

INSERT INTO `examen_intentos` (`id`, `examen_id`, `nombre_alumno`, `email_alumno`, `token`, `nota`, `corregido`, `alumno_id`, `inicio_at`, `fin_at`, `ip`, `nota_total`, `estado`, `created_at`, `updated_at`) VALUES
(1, 1, 'Alumno de Prueba', 'alumno@alumno.com', 'b003054a8f9175e60fc51ce6ae158832', 9.00, 1, NULL, '2025-11-15 20:26:50', NULL, NULL, NULL, 'en_progreso', '2025-11-15 20:26:50', '2025-11-16 17:03:41'),
(2, 1, 'Otro alumno', 'alumno2@alumno.com', '0a2f9712cb35edd49ce63d56228f844e', 4.00, 1, NULL, '2025-11-16 18:48:19', NULL, NULL, NULL, 'en_progreso', '2025-11-16 18:48:19', '2025-11-16 19:09:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `examen_respuestas`
--

CREATE TABLE `examen_respuestas` (
  `id` int(11) NOT NULL,
  `intento_id` int(11) NOT NULL,
  `actividad_id` int(11) NOT NULL,
  `respuesta_json` longtext DEFAULT NULL,
  `puntuacion` decimal(5,2) DEFAULT NULL,
  `corregida` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `examen_respuestas`
--

INSERT INTO `examen_respuestas` (`id`, `intento_id`, `actividad_id`, `respuesta_json`, `puntuacion`, `corregida`, `created_at`, `updated_at`) VALUES
(1, 1, 34, '{\"resp_34\":\"48\"}', 3.00, 1, '2025-11-15 20:35:34', '2025-11-16 17:03:41'),
(2, 1, 25, '{\"resp_25_13\":\"sdfgsdfgsdfgsdfg\",\"resp_25_14\":\"gsdfgsdfgsdfg\",\"resp_25_15\":\"gsdfgsdfgsdfg\"}', 3.00, 1, '2025-11-15 20:35:34', '2025-11-16 17:03:41'),
(3, 1, 17, '{\"resp_17_1\":\"SQL\",\"resp_17_2\":\"relacionales\"}', 3.00, 1, '2025-11-15 20:35:34', '2025-11-16 17:03:41'),
(4, 2, 34, '{\"resp_34\":\"48\"}', 3.00, 1, '2025-11-16 18:48:28', '2025-11-16 19:09:00'),
(5, 2, 25, '{\"resp_25_13\":\"sdfgsdfgsdfgsdfg\",\"resp_25_14\":\"sdfgsdfgsdfgsdfg\",\"resp_25_15\":\"gsdfgsdfgsdfg\"}', 1.00, 1, '2025-11-16 18:48:28', '2025-11-16 18:48:59'),
(6, 2, 17, '{\"resp_17_1\":\"sfsdgfsdfg\",\"resp_17_2\":\"sdfgsdfgsdf\"}', 0.00, 1, '2025-11-16 18:48:28', '2025-11-16 18:48:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `familias_profesionales`
--

CREATE TABLE `familias_profesionales` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `slug` varchar(160) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `familias_profesionales`
--

INSERT INTO `familias_profesionales` (`id`, `nombre`, `slug`, `descripcion`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Desarrollo de Aplicaciones Multiplataforma', 'desarrollo-de-aplicaciones-multiplataforma', NULL, 1, '2025-10-18 15:33:38', '2025-10-18 15:33:38'),
(2, 'Desarrollo de Aplicaciones Web', 'desarrollo-de-aplicaciones-web', NULL, 1, '2025-10-18 15:47:51', '2025-10-18 15:47:51');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grados`
--

CREATE TABLE `grados` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `grados`
--

INSERT INTO `grados` (`id`, `nombre`) VALUES
(4, 'ASIR'),
(1, 'Desarrollo de Aplicaciones Multiplataforma'),
(2, 'Desarrollo de Aplicaciones Web');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `perfiles_alumno`
--

CREATE TABLE `perfiles_alumno` (
  `usuario_id` bigint(20) UNSIGNED NOT NULL,
  `curso_id` smallint(5) UNSIGNED NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `perfiles_alumno`
--

INSERT INTO `perfiles_alumno` (`usuario_id`, `curso_id`, `creado_en`, `actualizado_en`) VALUES
(7, 1, '2025-09-13 13:40:16', '2025-09-13 13:40:16'),
(12, 1, '2025-09-14 16:55:27', '2025-09-14 16:55:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `perfiles_profesor`
--

CREATE TABLE `perfiles_profesor` (
  `usuario_id` bigint(20) UNSIGNED NOT NULL,
  `curso_id` smallint(5) UNSIGNED NOT NULL,
  `asignatura_id` smallint(5) UNSIGNED NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `perfiles_profesor`
--

INSERT INTO `perfiles_profesor` (`usuario_id`, `curso_id`, `asignatura_id`, `creado_en`, `actualizado_en`) VALUES
(6, 4, 3, '2025-09-13 13:38:46', '2025-09-13 13:38:46'),
(13, 4, 2, '2025-09-14 17:05:12', '2025-09-14 17:05:12'),
(14, 1, 1, '2025-09-14 17:07:54', '2025-09-14 17:07:54');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planes`
--

CREATE TABLE `planes` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `precio_mensual` decimal(7,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `planes`
--

INSERT INTO `planes` (`id`, `nombre`, `precio_mensual`) VALUES
(1, 'Gratis', 0.00),
(2, 'Pro', 9.99);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesores`
--

CREATE TABLE `profesores` (
  `id` int(11) NOT NULL,
  `centro_id` int(11) DEFAULT NULL,
  `nombre` varchar(120) NOT NULL,
  `apellidos` varchar(160) NOT NULL,
  `email` varchar(160) NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `profesores`
--

INSERT INTO `profesores` (`id`, `centro_id`, `nombre`, `apellidos`, `email`, `telefono`, `notas`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 1, 'Francisco', 'Palacios Chaves', 'fpalacioschaves@gmail.com', '655925498', 'Usuario Profesor para Bancalia', 1, '2025-10-18 21:45:18', '2026-01-20 21:14:10'),
(3, 1, 'Alberto', 'Ruiz Rodriguez', 'albertoruizprofesor@gmail.com', '666666666', NULL, 1, '2025-10-19 19:14:36', '2025-10-19 19:58:01'),
(5, 1, 'cvghgvjvghjfghj', 'ghjfghjfghjfgh', 'joseluis@dadisa.net', '+34952360412', 'ghjfghjfghjfghj', 1, '2026-01-20 21:20:06', '2026-01-20 21:20:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesor_asignacion`
--

CREATE TABLE `profesor_asignacion` (
  `id` int(11) NOT NULL,
  `profesor_id` int(11) NOT NULL,
  `centro_id` int(11) DEFAULT NULL,
  `familia_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `asignatura_id` int(11) NOT NULL,
  `anio_academico` varchar(9) NOT NULL,
  `horas` int(11) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `profesor_asignacion`
--

INSERT INTO `profesor_asignacion` (`id`, `profesor_id`, `centro_id`, `familia_id`, `curso_id`, `asignatura_id`, `anio_academico`, `horas`, `observaciones`, `is_active`, `created_at`, `updated_at`) VALUES
(3, 2, 1, 2, 3, 1, '2025-2026', NULL, NULL, 1, '2025-10-18 21:48:28', '2026-01-20 21:14:10'),
(4, 2, 1, 2, 3, 3, '2025-2026', NULL, NULL, 1, '2025-10-18 21:48:28', '2026-01-20 21:14:10'),
(5, 3, 1, 2, 3, 2, '2025-2026', NULL, NULL, 1, '2025-10-19 19:15:09', '2025-10-19 19:58:01'),
(6, 2, 1, 2, 3, 2, '2025-2026', NULL, NULL, 1, '2025-11-23 10:41:53', '2026-01-20 21:14:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `nombre` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`) VALUES
(1, 'admin'),
(3, 'alumno'),
(2, 'profesor');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `suscripciones`
--

CREATE TABLE `suscripciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `usuario_id` bigint(20) UNSIGNED NOT NULL,
  `plan_id` smallint(5) UNSIGNED NOT NULL,
  `estado` enum('activa','cancelada','expirada') NOT NULL DEFAULT 'activa',
  `inicio` date NOT NULL,
  `fin` date DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `suscripciones`
--

INSERT INTO `suscripciones` (`id`, `usuario_id`, `plan_id`, `estado`, `inicio`, `fin`, `creado_en`) VALUES
(1, 2, 2, 'activa', '2025-09-01', NULL, '2025-09-13 11:59:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `temas`
--

CREATE TABLE `temas` (
  `id` int(11) NOT NULL,
  `asignatura_id` int(11) NOT NULL,
  `nombre` varchar(160) NOT NULL,
  `slug` varchar(180) NOT NULL,
  `numero` int(11) NOT NULL DEFAULT 1,
  `descripcion` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `temas`
--

INSERT INTO `temas` (`id`, `asignatura_id`, `nombre`, `slug`, `numero`, `descripcion`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Introducción al SQL', 'introduccion-al-sql', 1, NULL, 1, '2025-10-18 20:38:49', '2025-10-18 20:38:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','profesor') NOT NULL DEFAULT 'profesor',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `email_verified_at` datetime DEFAULT NULL,
  `verify_token` char(64) DEFAULT NULL,
  `reset_token` char(64) DEFAULT NULL,
  `reset_expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `nombre`, `email`, `password_hash`, `role`, `is_active`, `email_verified_at`, `verify_token`, `reset_token`, `reset_expires_at`, `created_at`, `updated_at`) VALUES
(2, 'Admin', 'admin@bancalia.local', '$2y$10$FhjKsOldC1VlF2FYcQz1LOBANTQyLH2vX9436CttOnp1/DBjqA.0S', 'admin', 1, '2025-10-18 14:00:29', NULL, NULL, NULL, '2025-10-18 13:58:10', '2025-10-18 14:00:29'),
(5, 'fpalacioschaves', 'fpalacioschaves@gmail.com', '$2y$10$Oq8MhmKGKuw/KliAHFdIBuImSjqAJqBqi8mSKACi2hXYHDeyFgvxq', 'profesor', 1, NULL, NULL, NULL, NULL, '2025-10-18 21:45:18', '2025-10-18 21:45:18'),
(6, 'Alberto', 'albertoruizprofesor@gmail.com', '$2y$10$hrRku8L6FP1f4gZBYPo5L.FViGkT93qhDdK3tRrCOhB7PROyRHWyy', 'profesor', 1, NULL, NULL, NULL, NULL, '2025-10-19 19:14:36', '2025-10-19 19:14:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_old`
--

CREATE TABLE `usuarios_old` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol_id` tinyint(3) UNSIGNED NOT NULL,
  `estado` enum('activo','suspendido') NOT NULL DEFAULT 'activo',
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios_old`
--

INSERT INTO `usuarios_old` (`id`, `nombre`, `email`, `password_hash`, `rol_id`, `estado`, `creado_en`, `actualizado_en`) VALUES
(1, 'Admin', 'admin@bancalia.local', '$2y$10$lB1mKxDTSaDRKmKiJuOSJeglCC0pfHTkPrT9up0oUFz.vX0nLpBVS', 1, 'activo', '2025-09-13 11:59:34', '2025-09-13 20:41:30'),
(2, 'Paco Profesor', 'paco@bancalia.local', '$2y$10$hash_bcrypt_de_ejemplo_prof', 2, 'activo', '2025-09-13 11:59:34', '2025-09-13 11:59:34'),
(3, 'Ana Alumna', 'ana@bancalia.local', '$2y$10$hash_bcrypt_de_ejemplo_alum', 3, '', '2025-09-13 11:59:34', '2025-09-14 17:34:01'),
(4, 'Luis Alumno', 'luis@bancalia.local', '$2y$10$hash_bcrypt_de_ejemplo_alum', 3, 'activo', '2025-09-13 11:59:34', '2025-09-13 11:59:34'),
(5, 'Fco Palacios Alumno', 'fpalaciosalumno@gmail.com', '$2y$10$cSJ3BTVdAQ3sYUDz9E/gGOI4S.Sg.3bE23HCwGt7kODbX7nTnUz4i', 3, 'activo', '2025-09-13 13:14:26', '2025-09-13 13:14:26'),
(6, 'Francisco Palacios Profesor', 'fpalaciosprofesor@gmail.com', '$2y$10$wcH6Yh3xnK2ayAVwghKgCOJsXlT5kq3LACoL/V7NeSyzCtGjYJu22', 2, '', '2025-09-13 13:38:46', '2025-09-14 17:33:59'),
(7, 'Francisco Palacios Alumno', 'fpalaciosalumno2@gmail.com', '$2y$10$f/mCMtr8suVWDei5UNQOJu83ozeyKgliySPRLtdZcW82mCgJ5HXfu', 3, 'activo', '2025-09-13 13:40:16', '2025-09-13 22:13:16'),
(8, 'Un nuevo profesor', 'nuevoprofesor@gmail.com', '$2y$10$C44GxbxRbKyDkNs5cYt6kuiVx5Ba97KBTd05Z1AVP103LMCs0Yu8q', 2, '', '2025-09-14 12:26:14', '2025-09-14 17:33:58'),
(12, 'Nuevo Alumno', 'nuevoalumno@gmail.com', '$2y$10$233BZOF5Ly3wFT/XuBp8EeDaDqhX8Hz5bxfIzdebFSN9c5CHAyYgG', 3, 'activo', '2025-09-14 16:55:27', '2025-09-14 16:55:27'),
(13, 'Nuevo Profesor', 'nuevoprofesor2@gmail.com', '$2y$10$TWQNgnWchDGiPaKwPdL0ueXPoiyt93pprdvoNXLiZne7ee66I1wCy', 2, 'activo', '2025-09-14 17:05:12', '2025-09-14 17:48:03'),
(14, 'Profe A', 'profea@gmail.com', '$2y$10$WDZWl69n3/E4lPpgZD4OMuBeeWqQE.URI.HyEMGk/HsYQ7PNaNB8W', 2, '', '2025-09-14 17:07:54', '2025-09-14 18:15:09'),
(29, 'sdfasdfasd', 'fdsgadfgsdfg@bancalia.local', '$2y$10$upZdkNCxx4Q0XIdN45FTg.Vks0hh9hTZeCOmoMBpoeIBciSWAcaeC', 2, 'activo', '2025-09-14 20:59:27', '2025-09-14 20:59:27'),
(31, 'sdfasdgfasdg', 'sdgsdfg@bancalia.local', '$2y$10$BKgXotAvy81WuJoUk67TNercpRer5le5/5D61PKyIDmYLFs6oGvPG', 2, 'activo', '2025-09-14 21:07:35', '2025-09-14 21:07:35');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `actividades`
--
ALTER TABLE `actividades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profesor_id` (`profesor_id`),
  ADD KEY `familia_id` (`familia_id`),
  ADD KEY `curso_id` (`curso_id`),
  ADD KEY `asignatura_id` (`asignatura_id`),
  ADD KEY `tema_id` (`tema_id`),
  ADD KEY `tipo` (`tipo`),
  ADD KEY `visibilidad` (`visibilidad`),
  ADD KEY `estado` (`estado`),
  ADD KEY `fk_actividades_centro` (`centro_id`);

--
-- Indices de la tabla `actividades_emp`
--
ALTER TABLE `actividades_emp`
  ADD PRIMARY KEY (`actividad_id`),
  ADD KEY `idx_emp_modo` (`modo_puntuacion`);

--
-- Indices de la tabla `actividades_emp_pares`
--
ALTER TABLE `actividades_emp_pares`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_emp_pares_act` (`actividad_id`),
  ADD KEY `idx_emp_pares_orden` (`actividad_id`,`orden_izq`,`orden_der`),
  ADD KEY `idx_emp_pares_activo` (`actividad_id`,`activo`);

--
-- Indices de la tabla `actividades_om`
--
ALTER TABLE `actividades_om`
  ADD PRIMARY KEY (`actividad_id`);

--
-- Indices de la tabla `actividades_om_opciones`
--
ALTER TABLE `actividades_om_opciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_om_opciones_actividad` (`actividad_id`);

--
-- Indices de la tabla `actividades_rc`
--
ALTER TABLE `actividades_rc`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_actividades_rc_actividad` (`actividad_id`);

--
-- Indices de la tabla `actividades_rh`
--
ALTER TABLE `actividades_rh`
  ADD PRIMARY KEY (`actividad_id`);

--
-- Indices de la tabla `actividades_tarea`
--
ALTER TABLE `actividades_tarea`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `actividad_id` (`actividad_id`);

--
-- Indices de la tabla `actividades_valoraciones`
--
ALTER TABLE `actividades_valoraciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_actividad_profesor` (`actividad_id`,`profesor_id`),
  ADD KEY `idx_av_profesor` (`profesor_id`);

--
-- Indices de la tabla `actividades_vf`
--
ALTER TABLE `actividades_vf`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_actividades_vf_actividad` (`actividad_id`);

--
-- Indices de la tabla `asignaturas`
--
ALTER TABLE `asignaturas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_asig_curso_slug` (`curso_id`,`slug`),
  ADD UNIQUE KEY `uq_asig_curso_codigo` (`curso_id`,`codigo`),
  ADD KEY `fk_asig_familia` (`familia_id`);

--
-- Indices de la tabla `asignaturas_old`
--
ALTER TABLE `asignaturas_old`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `grado_id` (`grado_id`,`nombre`);

--
-- Indices de la tabla `asignatura_curso`
--
ALTER TABLE `asignatura_curso`
  ADD PRIMARY KEY (`asignatura_id`,`curso_id`),
  ADD KEY `idx_ac_curso` (`curso_id`);

--
-- Indices de la tabla `centros`
--
ALTER TABLE `centros`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `uq_centros_codigo` (`codigo`);

--
-- Indices de la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cursos_familia_slug` (`familia_id`,`slug`);

--
-- Indices de la tabla `cursos_old`
--
ALTER TABLE `cursos_old`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `examenes`
--
ALTER TABLE `examenes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ex_profesor` (`profesor_id`),
  ADD KEY `idx_ex_familia` (`familia_id`),
  ADD KEY `idx_ex_curso` (`curso_id`),
  ADD KEY `idx_ex_asignatura` (`asignatura_id`),
  ADD KEY `idx_ex_estado_fecha` (`estado`,`fecha`);

--
-- Indices de la tabla `examenes_actividades`
--
ALTER TABLE `examenes_actividades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_examen_actividad` (`examen_id`,`actividad_id`),
  ADD KEY `idx_ea_examen` (`examen_id`,`orden`),
  ADD KEY `idx_ea_actividad` (`actividad_id`);

--
-- Indices de la tabla `examen_intentos`
--
ALTER TABLE `examen_intentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ei_examen` (`examen_id`),
  ADD KEY `idx_ei_alumno` (`alumno_id`);

--
-- Indices de la tabla `examen_respuestas`
--
ALTER TABLE `examen_respuestas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_intento_actividad` (`intento_id`,`actividad_id`),
  ADD KEY `idx_er_intento` (`intento_id`),
  ADD KEY `idx_er_actividad` (`actividad_id`);

--
-- Indices de la tabla `familias_profesionales`
--
ALTER TABLE `familias_profesionales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indices de la tabla `grados`
--
ALTER TABLE `grados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `perfiles_alumno`
--
ALTER TABLE `perfiles_alumno`
  ADD PRIMARY KEY (`usuario_id`),
  ADD KEY `fk_alum_curso` (`curso_id`);

--
-- Indices de la tabla `perfiles_profesor`
--
ALTER TABLE `perfiles_profesor`
  ADD PRIMARY KEY (`usuario_id`),
  ADD KEY `fk_prof_curso` (`curso_id`),
  ADD KEY `fk_prof_asig` (`asignatura_id`);

--
-- Indices de la tabla `planes`
--
ALTER TABLE `planes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `profesores`
--
ALTER TABLE `profesores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_prof_email` (`email`),
  ADD KEY `idx_prof_centro` (`centro_id`),
  ADD KEY `idx_prof_nombre` (`apellidos`,`nombre`);

--
-- Indices de la tabla `profesor_asignacion`
--
ALTER TABLE `profesor_asignacion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pa` (`profesor_id`,`centro_id`,`familia_id`,`curso_id`,`asignatura_id`,`anio_academico`),
  ADD KEY `fk_pa_centro` (`centro_id`),
  ADD KEY `fk_pa_familia` (`familia_id`),
  ADD KEY `fk_pa_curso` (`curso_id`),
  ADD KEY `fk_pa_asig` (`asignatura_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `suscripciones`
--
ALTER TABLE `suscripciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sub_user` (`usuario_id`),
  ADD KEY `fk_sub_plan` (`plan_id`);

--
-- Indices de la tabla `temas`
--
ALTER TABLE `temas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tema_asig_slug` (`asignatura_id`,`slug`),
  ADD UNIQUE KEY `uq_tema_asig_numero` (`asignatura_id`,`numero`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `usuarios_old`
--
ALTER TABLE `usuarios_old`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `actividades`
--
ALTER TABLE `actividades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de la tabla `actividades_emp_pares`
--
ALTER TABLE `actividades_emp_pares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `actividades_om_opciones`
--
ALTER TABLE `actividades_om_opciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `actividades_rc`
--
ALTER TABLE `actividades_rc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `actividades_tarea`
--
ALTER TABLE `actividades_tarea`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `actividades_valoraciones`
--
ALTER TABLE `actividades_valoraciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `actividades_vf`
--
ALTER TABLE `actividades_vf`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `asignaturas`
--
ALTER TABLE `asignaturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `asignaturas_old`
--
ALTER TABLE `asignaturas_old`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `centros`
--
ALTER TABLE `centros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `cursos_old`
--
ALTER TABLE `cursos_old`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `examenes`
--
ALTER TABLE `examenes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `examenes_actividades`
--
ALTER TABLE `examenes_actividades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `examen_intentos`
--
ALTER TABLE `examen_intentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `examen_respuestas`
--
ALTER TABLE `examen_respuestas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `familias_profesionales`
--
ALTER TABLE `familias_profesionales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `grados`
--
ALTER TABLE `grados`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `planes`
--
ALTER TABLE `planes`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `profesores`
--
ALTER TABLE `profesores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `profesor_asignacion`
--
ALTER TABLE `profesor_asignacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `suscripciones`
--
ALTER TABLE `suscripciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `temas`
--
ALTER TABLE `temas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `usuarios_old`
--
ALTER TABLE `usuarios_old`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `actividades`
--
ALTER TABLE `actividades`
  ADD CONSTRAINT `fk_actividades_centro` FOREIGN KEY (`centro_id`) REFERENCES `centros` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `actividades_emp`
--
ALTER TABLE `actividades_emp`
  ADD CONSTRAINT `fk_emp_actividad` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `actividades_emp_pares`
--
ALTER TABLE `actividades_emp_pares`
  ADD CONSTRAINT `fk_emp_pares_act` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `actividades_om`
--
ALTER TABLE `actividades_om`
  ADD CONSTRAINT `fk_om_actividad` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `actividades_om_opciones`
--
ALTER TABLE `actividades_om_opciones`
  ADD CONSTRAINT `fk_om_opciones_actividad` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `actividades_rc`
--
ALTER TABLE `actividades_rc`
  ADD CONSTRAINT `fk_actividades_rc_actividad` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `actividades_rh`
--
ALTER TABLE `actividades_rh`
  ADD CONSTRAINT `fk_actividades_rh_actividad` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `actividades_tarea`
--
ALTER TABLE `actividades_tarea`
  ADD CONSTRAINT `fk_act_tarea` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `actividades_valoraciones`
--
ALTER TABLE `actividades_valoraciones`
  ADD CONSTRAINT `fk_av_actividad` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_av_profesor` FOREIGN KEY (`profesor_id`) REFERENCES `profesores` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `actividades_vf`
--
ALTER TABLE `actividades_vf`
  ADD CONSTRAINT `fk_actividades_vf_actividad` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `asignaturas`
--
ALTER TABLE `asignaturas`
  ADD CONSTRAINT `fk_asig_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_asig_familia` FOREIGN KEY (`familia_id`) REFERENCES `familias_profesionales` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `asignatura_curso`
--
ALTER TABLE `asignatura_curso`
  ADD CONSTRAINT `fk_ac_asignatura` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas_old` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ac_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos_old` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD CONSTRAINT `fk_cursos_familia` FOREIGN KEY (`familia_id`) REFERENCES `familias_profesionales` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `examenes`
--
ALTER TABLE `examenes`
  ADD CONSTRAINT `fk_ex_asignatura` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ex_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ex_familia` FOREIGN KEY (`familia_id`) REFERENCES `familias_profesionales` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ex_profesor` FOREIGN KEY (`profesor_id`) REFERENCES `profesores` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `examenes_actividades`
--
ALTER TABLE `examenes_actividades`
  ADD CONSTRAINT `fk_ea_actividad` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ea_examen` FOREIGN KEY (`examen_id`) REFERENCES `examenes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `examen_intentos`
--
ALTER TABLE `examen_intentos`
  ADD CONSTRAINT `fk_ei_examen` FOREIGN KEY (`examen_id`) REFERENCES `examenes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `examen_respuestas`
--
ALTER TABLE `examen_respuestas`
  ADD CONSTRAINT `fk_er_actividad` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_er_intento` FOREIGN KEY (`intento_id`) REFERENCES `examen_intentos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `perfiles_alumno`
--
ALTER TABLE `perfiles_alumno`
  ADD CONSTRAINT `fk_alum_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos_old` (`id`),
  ADD CONSTRAINT `fk_alum_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios_old` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `perfiles_profesor`
--
ALTER TABLE `perfiles_profesor`
  ADD CONSTRAINT `fk_prof_asig` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas_old` (`id`),
  ADD CONSTRAINT `fk_prof_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos_old` (`id`),
  ADD CONSTRAINT `fk_prof_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios_old` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `profesores`
--
ALTER TABLE `profesores`
  ADD CONSTRAINT `fk_prof_centro` FOREIGN KEY (`centro_id`) REFERENCES `centros` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `profesor_asignacion`
--
ALTER TABLE `profesor_asignacion`
  ADD CONSTRAINT `fk_pa_asig` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pa_centro` FOREIGN KEY (`centro_id`) REFERENCES `centros` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pa_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pa_familia` FOREIGN KEY (`familia_id`) REFERENCES `familias_profesionales` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pa_profesor` FOREIGN KEY (`profesor_id`) REFERENCES `profesores` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `suscripciones`
--
ALTER TABLE `suscripciones`
  ADD CONSTRAINT `fk_sub_plan` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`),
  ADD CONSTRAINT `fk_sub_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios_old` (`id`);

--
-- Filtros para la tabla `temas`
--
ALTER TABLE `temas`
  ADD CONSTRAINT `fk_tema_asignatura` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios_old`
--
ALTER TABLE `usuarios_old`
  ADD CONSTRAINT `fk_usuarios_roles` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
