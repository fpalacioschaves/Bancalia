-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 18-10-2025 a las 13:35:53
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
  `id` bigint(20) UNSIGNED NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `enunciado` mediumtext NOT NULL,
  `autor_id` bigint(20) UNSIGNED NOT NULL,
  `tipo_id` tinyint(3) UNSIGNED DEFAULT NULL,
  `visibilidad` enum('privada','compartida','publicada') NOT NULL DEFAULT 'privada',
  `estado` enum('borrador','publicada') NOT NULL DEFAULT 'borrador',
  `dificultad` enum('facil','media','dificil') DEFAULT NULL,
  `tiempo_estimado_min` smallint(5) UNSIGNED DEFAULT NULL,
  `publico_slug` varchar(140) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `actividades`
--

INSERT INTO `actividades` (`id`, `titulo`, `enunciado`, `autor_id`, `tipo_id`, `visibilidad`, `estado`, `dificultad`, `tiempo_estimado_min`, `publico_slug`, `creado_en`, `actualizado_en`) VALUES
(1, 'VF: Una entidad débil nunca tiene clave primaria propia', 'Indica si la afirmación es verdadera o falsa.', 2, 1, 'compartida', 'publicada', 'media', 3, 'vf-entidad-debil', '2025-09-13 11:59:34', '2025-09-13 11:59:34'),
(2, 'VF: <main> solo puede aparecer una vez por página', 'Indica si la afirmación es verdadera o falsa.', 2, 1, 'compartida', 'publicada', 'facil', 2, 'vf-main-unico', '2025-09-13 11:59:34', '2025-09-13 11:59:34'),
(3, 'EM: Clave candidata en un esquema relacional', 'Selecciona la opción correcta.', 2, 2, 'compartida', 'borrador', 'media', 5, 'em-clave-candidata', '2025-09-13 11:59:34', '2025-09-13 11:59:34'),
(4, 'EM: XPath para seleccionar todos los <libro> con precio > 30', 'Selecciona la expresión XPath correcta.', 2, 2, 'compartida', 'borrador', 'media', 5, 'em-xpath-precio', '2025-09-13 11:59:34', '2025-09-13 11:59:34'),
(5, 'Desarrollo: Normaliza a 3FN una tabla de pedidos', 'Adjunta un PDF justificando las dependencias funcionales y el proceso de normalización hasta 3FN.', 2, 3, 'privada', 'borrador', 'dificil', 30, 'des-normalizacion-3fn', '2025-09-13 11:59:34', '2025-09-13 11:59:34'),
(6, 'Desarrollo: Flujo Git con ramas feature y pull requests', 'Entrega un PDF con un flujo de trabajo recomendado para un equipo pequeño.', 2, 3, 'compartida', 'borrador', 'media', 15, 'des-flujo-git', '2025-09-13 11:59:34', '2025-09-13 11:59:34');

--
-- Disparadores `actividades`
--
DELIMITER $$
CREATE TRIGGER `trg_em_check_correcta_before_publish` BEFORE UPDATE ON `actividades` FOR EACH ROW BEGIN
  DECLARE num_correctas INT DEFAULT 0;

  IF NEW.estado = 'publicada' AND NEW.tipo_id = 2 THEN
    SELECT COUNT(*) INTO num_correctas
      FROM actividad_opciones
     WHERE actividad_id = NEW.id
       AND es_correcta = TRUE;

    IF num_correctas = 0 THEN
      SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La actividad de elección múltiple debe tener al menos una opción correcta.';
    END IF;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividad_desarrollo`
--

CREATE TABLE `actividad_desarrollo` (
  `actividad_id` bigint(20) UNSIGNED NOT NULL,
  `requiere_archivo` tinyint(1) NOT NULL DEFAULT 1,
  `formatos_permitidos` varchar(200) DEFAULT NULL,
  `tamano_max_mb` smallint(5) UNSIGNED DEFAULT NULL,
  `max_palabras` int(10) UNSIGNED DEFAULT NULL,
  `criterios_rubrica` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`criterios_rubrica`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `actividad_desarrollo`
--

INSERT INTO `actividad_desarrollo` (`actividad_id`, `requiere_archivo`, `formatos_permitidos`, `tamano_max_mb`, `max_palabras`, `criterios_rubrica`) VALUES
(5, 1, 'pdf', 20, 800, '{\"criterios\": [{\"nombre\": \"Identificación de DF\", \"peso\": 0.35}, {\"nombre\": \"Proceso a 3FN\", \"peso\": 0.35}, {\"nombre\": \"Claridad y justificación\", \"peso\": 0.30}]}'),
(6, 1, 'pdf', 10, 600, '{\"criterios\": [{\"nombre\": \"Estrategia de ramas\", \"peso\": 0.4}, {\"nombre\": \"Gestión de PR y revisiones\", \"peso\": 0.4}, {\"nombre\": \"Buenas prácticas\", \"peso\": 0.2}]}');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividad_etiqueta`
--

CREATE TABLE `actividad_etiqueta` (
  `actividad_id` bigint(20) UNSIGNED NOT NULL,
  `etiqueta_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `actividad_etiqueta`
--

INSERT INTO `actividad_etiqueta` (`actividad_id`, `etiqueta_id`) VALUES
(1, 1),
(2, 4),
(3, 1),
(4, 3),
(5, 2),
(6, 6);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividad_opciones`
--

CREATE TABLE `actividad_opciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `actividad_id` bigint(20) UNSIGNED NOT NULL,
  `texto_opcion` text NOT NULL,
  `es_correcta` tinyint(1) NOT NULL DEFAULT 0,
  `retroalimentacion` varchar(255) DEFAULT NULL,
  `orden` smallint(5) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `actividad_opciones`
--

INSERT INTO `actividad_opciones` (`id`, `actividad_id`, `texto_opcion`, `es_correcta`, `retroalimentacion`, `orden`) VALUES
(1, 3, 'Cualquier atributo que identifique de forma única una tupla y sea minimal.', 1, NULL, 1),
(2, 3, 'Un atributo que siempre es foráneo.', 0, NULL, 2),
(3, 3, 'La concatenación de todas las columnas de la tabla.', 0, NULL, 3),
(4, 3, 'Un índice no cluster obligatorio.', 0, NULL, 4),
(5, 4, '//libro[precio>30]', 1, NULL, 1),
(6, 4, '//libro/@precio>30', 0, NULL, 2),
(7, 4, 'libro/precio>30', 0, NULL, 3),
(8, 4, '//precio[text()>30]/libro', 0, NULL, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividad_ra`
--

CREATE TABLE `actividad_ra` (
  `actividad_id` bigint(20) UNSIGNED NOT NULL,
  `ra_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `actividad_ra`
--

INSERT INTO `actividad_ra` (`actividad_id`, `ra_id`) VALUES
(1, 1),
(2, 4),
(3, 2),
(4, 5),
(5, 3),
(6, 8);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividad_tema`
--

CREATE TABLE `actividad_tema` (
  `actividad_id` bigint(20) UNSIGNED NOT NULL,
  `tema_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `actividad_tema`
--

INSERT INTO `actividad_tema` (`actividad_id`, `tema_id`) VALUES
(1, 1),
(2, 4),
(3, 2),
(4, 5),
(5, 3),
(6, 8);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividad_tipos`
--

CREATE TABLE `actividad_tipos` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `clave` varchar(40) NOT NULL,
  `nombre` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `actividad_tipos`
--

INSERT INTO `actividad_tipos` (`id`, `clave`, `nombre`) VALUES
(1, 'vf', 'Verdadero/Falso'),
(2, 'em', 'Elección múltiple'),
(3, 'des', 'Desarrollo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `actividad_vf`
--

CREATE TABLE `actividad_vf` (
  `actividad_id` bigint(20) UNSIGNED NOT NULL,
  `respuesta_correcta` tinyint(1) NOT NULL,
  `feedback_correcto` varchar(255) DEFAULT NULL,
  `feedback_incorrecto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `actividad_vf`
--

INSERT INTO `actividad_vf` (`actividad_id`, `respuesta_correcta`, `feedback_correcto`, `feedback_incorrecto`) VALUES
(1, 1, 'Correcto: las entidades débiles dependen de una fuerte para su identificación.', 'Revisa el concepto de entidad débil.'),
(2, 1, 'Correcto: según HTML Living Standard, solo un <main> por documento.', 'Revisa accesibilidad y semántica HTML.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaciones`
--

CREATE TABLE `asignaciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `actividad_id` bigint(20) UNSIGNED NOT NULL,
  `alumno_id` bigint(20) UNSIGNED NOT NULL,
  `estado` enum('sin_enviar','enviado','resuelto') NOT NULL DEFAULT 'sin_enviar',
  `fecha_limite` date DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `asignaciones`
--

INSERT INTO `asignaciones` (`id`, `actividad_id`, `alumno_id`, `estado`, `fecha_limite`, `creado_en`, `actualizado_en`) VALUES
(1, 1, 3, 'enviado', '2025-10-20', '2025-09-13 11:59:34', '2025-09-13 11:59:34'),
(2, 1, 4, 'sin_enviar', '2025-10-20', '2025-09-13 11:59:34', '2025-09-13 11:59:34'),
(3, 3, 3, 'enviado', '2025-10-22', '2025-09-13 11:59:34', '2025-09-13 11:59:34'),
(4, 6, 4, 'sin_enviar', '2025-10-30', '2025-09-13 11:59:34', '2025-09-13 11:59:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaturas`
--

CREATE TABLE `asignaturas` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `grado_id` smallint(5) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `asignaturas`
--

INSERT INTO `asignaturas` (`id`, `grado_id`, `nombre`, `codigo`) VALUES
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
-- Estructura de tabla para la tabla `auditoria`
--

CREATE TABLE `auditoria` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `accion` varchar(120) NOT NULL,
  `entidad` varchar(60) NOT NULL,
  `entidad_id` bigint(20) UNSIGNED DEFAULT NULL,
  `detalles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`detalles`)),
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `auditoria`
--

INSERT INTO `auditoria` (`id`, `usuario_id`, `accion`, `entidad`, `entidad_id`, `detalles`, `creado_en`) VALUES
(1, 2, 'crear_actividad', 'actividades', 1, '{\"titulo\": \"VF entidad débil\"}', '2025-09-13 11:59:34'),
(2, 2, 'crear_actividad', 'actividades', 3, '{\"titulo\": \"EM clave candidata\"}', '2025-09-13 11:59:34'),
(3, 2, 'asignar_actividad', 'asignaciones', 1, '{\"alumno\": 3, \"actividad\": 1}', '2025-09-13 11:59:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `grado_id` smallint(5) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `orden` tinyint(3) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cursos`
--

INSERT INTO `cursos` (`id`, `grado_id`, `nombre`, `orden`) VALUES
(1, 1, '1º', 1),
(2, 1, '2º', 2),
(3, 2, '1º', 1),
(4, 2, '2º', 2),
(5, 1, 'Máster', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `enlaces`
--

CREATE TABLE `enlaces` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `actividad_id` bigint(20) UNSIGNED NOT NULL,
  `tipo` enum('qr','codigo','iframe') NOT NULL,
  `token` varchar(140) NOT NULL,
  `expiracion` datetime DEFAULT NULL,
  `usos_max` int(10) UNSIGNED DEFAULT NULL,
  `usos_hechos` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `enlaces`
--

INSERT INTO `enlaces` (`id`, `actividad_id`, `tipo`, `token`, `expiracion`, `usos_max`, `usos_hechos`, `creado_en`) VALUES
(1, 1, 'codigo', 'COD-VF-ENTDEBIL', '2025-12-31 23:59:59', 100, 2, '2025-09-13 11:59:34'),
(2, 3, 'iframe', 'IFR-CLAVE-CAND', NULL, NULL, 0, '2025-09-13 11:59:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entregas`
--

CREATE TABLE `entregas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `asignacion_id` bigint(20) UNSIGNED NOT NULL,
  `alumno_id` bigint(20) UNSIGNED NOT NULL,
  `url` text DEFAULT NULL,
  `archivo_path` varchar(255) DEFAULT NULL,
  `texto_respuesta` mediumtext DEFAULT NULL,
  `nota` decimal(5,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ;

--
-- Volcado de datos para la tabla `entregas`
--

INSERT INTO `entregas` (`id`, `asignacion_id`, `alumno_id`, `url`, `archivo_path`, `texto_respuesta`, `nota`, `feedback`, `creado_en`) VALUES
(1, 1, 3, NULL, NULL, 'Verdadero', 1.00, 'Bien.', '2025-09-13 11:59:34'),
(2, 3, 3, NULL, NULL, 'Una clave candidata es un superconjunto mínimo que identifica de forma única', NULL, NULL, '2025-09-13 11:59:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `etiquetas`
--

CREATE TABLE `etiquetas` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `color` varchar(9) DEFAULT NULL,
  `creador_id` bigint(20) UNSIGNED DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `etiquetas`
--

INSERT INTO `etiquetas` (`id`, `nombre`, `color`, `creador_id`, `creado_en`) VALUES
(1, 'SQL', '#2b6cb0', 2, '2025-09-13 11:59:34'),
(2, 'Normalización', '#805ad5', 2, '2025-09-13 11:59:34'),
(3, 'XML', '#38a169', 2, '2025-09-13 11:59:34'),
(4, 'HTML', '#dd6b20', 2, '2025-09-13 11:59:34'),
(5, 'JS', '#718096', 2, '2025-09-13 11:59:34'),
(6, 'Git', '#1a202c', 2, '2025-09-13 11:59:34'),
(7, 'XPath', '#ccc', 3, '2025-09-13 20:18:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `examenes`
--

CREATE TABLE `examenes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `autor_id` bigint(20) UNSIGNED NOT NULL,
  `estado` enum('borrador','publicado') NOT NULL DEFAULT 'borrador',
  `fecha` date DEFAULT NULL,
  `hora` time DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `examenes`
--

INSERT INTO `examenes` (`id`, `titulo`, `autor_id`, `estado`, `fecha`, `hora`, `creado_en`, `actualizado_en`) VALUES
(1, 'Examen Parcial BD — ER y Relacional', 2, 'borrador', '2025-10-15', '10:00:00', '2025-09-13 11:59:34', '2025-09-13 11:59:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `examenes_actividades`
--

CREATE TABLE `examenes_actividades` (
  `examen_id` bigint(20) UNSIGNED NOT NULL,
  `actividad_id` bigint(20) UNSIGNED NOT NULL,
  `orden` smallint(5) UNSIGNED DEFAULT NULL,
  `puntos` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `examenes_actividades`
--

INSERT INTO `examenes_actividades` (`examen_id`, `actividad_id`, `orden`, `puntos`) VALUES
(1, 1, 1, 1.00),
(1, 3, 2, 2.00),
(1, 5, 3, 3.00);

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
-- Estructura de tabla para la tabla `profesor_imparte`
--

CREATE TABLE `profesor_imparte` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `profesor_id` bigint(20) UNSIGNED NOT NULL,
  `grado_id` smallint(5) UNSIGNED NOT NULL,
  `curso_id` smallint(5) UNSIGNED NOT NULL,
  `asignatura_id` smallint(5) UNSIGNED NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `profesor_imparte`
--

INSERT INTO `profesor_imparte` (`id`, `profesor_id`, `grado_id`, `curso_id`, `asignatura_id`, `creado_en`) VALUES
(1, 6, 2, 4, 3, '2025-09-14 11:56:13'),
(2, 6, 2, 3, 2, '2025-09-14 12:13:40'),
(3, 6, 1, 1, 1, '2025-09-14 12:13:57'),
(4, 8, 1, 1, 1, '2025-09-14 12:26:14'),
(5, 6, 2, 3, 4, '2025-09-14 12:49:09'),
(6, 13, 2, 4, 2, '2025-09-14 17:05:12'),
(7, 14, 1, 1, 1, '2025-09-14 17:07:54'),
(8, 14, 2, 3, 4, '2025-09-14 17:07:54'),
(9, 14, 2, 3, 2, '2025-09-14 17:07:54'),
(10, 29, 1, 1, 1, '2025-09-14 20:59:27'),
(11, 29, 1, 2, 4, '2025-09-14 20:59:27'),
(12, 31, 1, 1, 1, '2025-09-14 21:07:35'),
(13, 31, 2, 3, 2, '2025-09-14 21:07:35');

--
-- Disparadores `profesor_imparte`
--
DELIMITER $$
CREATE TRIGGER `trg_pi_check_grado` BEFORE INSERT ON `profesor_imparte` FOR EACH ROW BEGIN
  DECLARE g_curso SMALLINT UNSIGNED;
  DECLARE g_asig  SMALLINT UNSIGNED;
  SELECT grado_id INTO g_curso FROM cursos       WHERE id = NEW.curso_id;
  SELECT grado_id INTO g_asig  FROM asignaturas  WHERE id = NEW.asignatura_id;
  IF g_curso IS NULL OR g_asig IS NULL OR g_curso <> g_asig OR g_curso <> NEW.grado_id THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'grado_id no coincide con curso/asignatura';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ra`
--

CREATE TABLE `ra` (
  `id` int(10) UNSIGNED NOT NULL,
  `asignatura_id` smallint(5) UNSIGNED NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `descripcion` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ra`
--

INSERT INTO `ra` (`id`, `asignatura_id`, `codigo`, `descripcion`) VALUES
(1, 1, 'RA1', 'Diseña modelos entidad-relación para problemas dados.'),
(2, 1, 'RA2', 'Transforma modelos al esquema relacional aplicando claves y restricciones.'),
(3, 1, 'RA3', 'Aplica formas normales para mejorar el diseño.'),
(4, 2, 'RA1', 'Estructura documentos con HTML semántico y accesible.'),
(5, 2, 'RA2', 'Valida y transforma XML con XSD y XPath/XSLT.'),
(6, 3, 'RA1', 'Aplica POO en JavaScript con clases y módulos.'),
(7, 3, 'RA2', 'Implementa estructuras de datos básicas.'),
(8, 4, 'RA1', 'Gestiona proyectos con Git y buenas prácticas.');

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
  `id` int(10) UNSIGNED NOT NULL,
  `asignatura_id` smallint(5) UNSIGNED NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `orden` smallint(5) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `temas`
--

INSERT INTO `temas` (`id`, `asignatura_id`, `nombre`, `codigo`, `orden`) VALUES
(1, 1, 'Modelo Entidad-Relación', 'T1-ER', 3),
(2, 1, 'Modelo Relacional y Álgebra', 'T2-REL', 2),
(3, 1, 'Normalización', 'T3-NORM', 3),
(4, 2, 'HTML y Accesibilidad', 'T1-HTML', 1),
(5, 2, 'XML/XSD/XPath', 'T2-XML', 2),
(6, 3, 'POO en JavaScript', 'T1-POOJS', 1),
(7, 3, 'Estructuras de Datos', 'T2-ED', 2),
(8, 4, 'Control de versiones con Git', 'T1-GIT', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `temas_ra`
--

CREATE TABLE `temas_ra` (
  `tema_id` int(10) UNSIGNED NOT NULL,
  `ra_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `temas_ra`
--

INSERT INTO `temas_ra` (`tema_id`, `ra_id`) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(5, 5),
(6, 6),
(7, 7),
(8, 8);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
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
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password_hash`, `rol_id`, `estado`, `creado_en`, `actualizado_en`) VALUES
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
  ADD UNIQUE KEY `publico_slug` (`publico_slug`),
  ADD KEY `fk_act_autor` (`autor_id`),
  ADD KEY `fk_actividad_tipo` (`tipo_id`),
  ADD KEY `ix_act_vis_estado` (`visibilidad`,`estado`);
ALTER TABLE `actividades` ADD FULLTEXT KEY `ft_actividades_titulo_enunciado` (`titulo`,`enunciado`);

--
-- Indices de la tabla `actividad_desarrollo`
--
ALTER TABLE `actividad_desarrollo`
  ADD PRIMARY KEY (`actividad_id`);

--
-- Indices de la tabla `actividad_etiqueta`
--
ALTER TABLE `actividad_etiqueta`
  ADD PRIMARY KEY (`actividad_id`,`etiqueta_id`),
  ADD KEY `ix_ae_tag` (`etiqueta_id`,`actividad_id`);

--
-- Indices de la tabla `actividad_opciones`
--
ALTER TABLE `actividad_opciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `actividad_id` (`actividad_id`,`orden`);

--
-- Indices de la tabla `actividad_ra`
--
ALTER TABLE `actividad_ra`
  ADD PRIMARY KEY (`actividad_id`,`ra_id`),
  ADD KEY `ix_ar_ra` (`ra_id`,`actividad_id`);

--
-- Indices de la tabla `actividad_tema`
--
ALTER TABLE `actividad_tema`
  ADD PRIMARY KEY (`actividad_id`,`tema_id`),
  ADD KEY `ix_at_tema` (`tema_id`,`actividad_id`);

--
-- Indices de la tabla `actividad_tipos`
--
ALTER TABLE `actividad_tipos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `actividad_vf`
--
ALTER TABLE `actividad_vf`
  ADD PRIMARY KEY (`actividad_id`);

--
-- Indices de la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `actividad_id` (`actividad_id`,`alumno_id`),
  ADD KEY `ix_asignaciones_alumno` (`alumno_id`,`estado`);

--
-- Indices de la tabla `asignaturas`
--
ALTER TABLE `asignaturas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `grado_id` (`grado_id`,`nombre`);

--
-- Indices de la tabla `asignatura_curso`
--
ALTER TABLE `asignatura_curso`
  ADD PRIMARY KEY (`asignatura_id`,`curso_id`),
  ADD KEY `idx_ac_curso` (`curso_id`);

--
-- Indices de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `entidad` (`entidad`,`entidad_id`),
  ADD KEY `fk_aud_user` (`usuario_id`);

--
-- Indices de la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `grado_id` (`grado_id`,`nombre`);

--
-- Indices de la tabla `enlaces`
--
ALTER TABLE `enlaces`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `fk_link_act` (`actividad_id`);

--
-- Indices de la tabla `entregas`
--
ALTER TABLE `entregas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ent_asig` (`asignacion_id`),
  ADD KEY `fk_ent_alum` (`alumno_id`);

--
-- Indices de la tabla `etiquetas`
--
ALTER TABLE `etiquetas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`),
  ADD KEY `fk_tag_user` (`creador_id`);

--
-- Indices de la tabla `examenes`
--
ALTER TABLE `examenes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ex_autor` (`autor_id`);

--
-- Indices de la tabla `examenes_actividades`
--
ALTER TABLE `examenes_actividades`
  ADD PRIMARY KEY (`examen_id`,`actividad_id`),
  ADD KEY `fk_ea_act` (`actividad_id`);

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
-- Indices de la tabla `profesor_imparte`
--
ALTER TABLE `profesor_imparte`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_prof_curso_asig` (`profesor_id`,`curso_id`,`asignatura_id`),
  ADD KEY `fk_pi_grado` (`grado_id`),
  ADD KEY `fk_pi_curso` (`curso_id`),
  ADD KEY `fk_pi_asig` (`asignatura_id`);

--
-- Indices de la tabla `ra`
--
ALTER TABLE `ra`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asignatura_id` (`asignatura_id`,`codigo`);

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
  ADD UNIQUE KEY `asignatura_id` (`asignatura_id`,`nombre`);

--
-- Indices de la tabla `temas_ra`
--
ALTER TABLE `temas_ra`
  ADD PRIMARY KEY (`tema_id`,`ra_id`),
  ADD KEY `fk_tr_ra` (`ra_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_usuarios_roles` (`rol_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `actividades`
--
ALTER TABLE `actividades`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `actividad_opciones`
--
ALTER TABLE `actividad_opciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `actividad_tipos`
--
ALTER TABLE `actividad_tipos`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `asignaturas`
--
ALTER TABLE `asignaturas`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `enlaces`
--
ALTER TABLE `enlaces`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `entregas`
--
ALTER TABLE `entregas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `etiquetas`
--
ALTER TABLE `etiquetas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `examenes`
--
ALTER TABLE `examenes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
-- AUTO_INCREMENT de la tabla `profesor_imparte`
--
ALTER TABLE `profesor_imparte`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `ra`
--
ALTER TABLE `ra`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `actividades`
--
ALTER TABLE `actividades`
  ADD CONSTRAINT `fk_act_autor` FOREIGN KEY (`autor_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_actividad_tipo` FOREIGN KEY (`tipo_id`) REFERENCES `actividad_tipos` (`id`);

--
-- Filtros para la tabla `actividad_desarrollo`
--
ALTER TABLE `actividad_desarrollo`
  ADD CONSTRAINT `fk_des_act` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `actividad_etiqueta`
--
ALTER TABLE `actividad_etiqueta`
  ADD CONSTRAINT `fk_ae_act` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ae_tag` FOREIGN KEY (`etiqueta_id`) REFERENCES `etiquetas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `actividad_opciones`
--
ALTER TABLE `actividad_opciones`
  ADD CONSTRAINT `fk_opt_act` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `actividad_ra`
--
ALTER TABLE `actividad_ra`
  ADD CONSTRAINT `fk_ar_actividad` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ar_ra` FOREIGN KEY (`ra_id`) REFERENCES `ra` (`id`);

--
-- Filtros para la tabla `actividad_tema`
--
ALTER TABLE `actividad_tema`
  ADD CONSTRAINT `fk_at_actividad` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_at_tema` FOREIGN KEY (`tema_id`) REFERENCES `temas` (`id`);

--
-- Filtros para la tabla `actividad_vf`
--
ALTER TABLE `actividad_vf`
  ADD CONSTRAINT `fk_vf_act` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `asignaciones`
--
ALTER TABLE `asignaciones`
  ADD CONSTRAINT `fk_asig_act` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_asig_alum` FOREIGN KEY (`alumno_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `asignaturas`
--
ALTER TABLE `asignaturas`
  ADD CONSTRAINT `fk_asig_grados` FOREIGN KEY (`grado_id`) REFERENCES `grados` (`id`);

--
-- Filtros para la tabla `asignatura_curso`
--
ALTER TABLE `asignatura_curso`
  ADD CONSTRAINT `fk_ac_asignatura` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ac_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD CONSTRAINT `fk_aud_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD CONSTRAINT `fk_cursos_grados` FOREIGN KEY (`grado_id`) REFERENCES `grados` (`id`);

--
-- Filtros para la tabla `enlaces`
--
ALTER TABLE `enlaces`
  ADD CONSTRAINT `fk_link_act` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `entregas`
--
ALTER TABLE `entregas`
  ADD CONSTRAINT `fk_ent_alum` FOREIGN KEY (`alumno_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_ent_asig` FOREIGN KEY (`asignacion_id`) REFERENCES `asignaciones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `etiquetas`
--
ALTER TABLE `etiquetas`
  ADD CONSTRAINT `fk_tag_user` FOREIGN KEY (`creador_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `examenes`
--
ALTER TABLE `examenes`
  ADD CONSTRAINT `fk_ex_autor` FOREIGN KEY (`autor_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `examenes_actividades`
--
ALTER TABLE `examenes_actividades`
  ADD CONSTRAINT `fk_ea_act` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`),
  ADD CONSTRAINT `fk_ea_ex` FOREIGN KEY (`examen_id`) REFERENCES `examenes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `perfiles_alumno`
--
ALTER TABLE `perfiles_alumno`
  ADD CONSTRAINT `fk_alum_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`),
  ADD CONSTRAINT `fk_alum_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `perfiles_profesor`
--
ALTER TABLE `perfiles_profesor`
  ADD CONSTRAINT `fk_prof_asig` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`id`),
  ADD CONSTRAINT `fk_prof_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`),
  ADD CONSTRAINT `fk_prof_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `profesor_imparte`
--
ALTER TABLE `profesor_imparte`
  ADD CONSTRAINT `fk_pi_asig` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`id`),
  ADD CONSTRAINT `fk_pi_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`),
  ADD CONSTRAINT `fk_pi_grado` FOREIGN KEY (`grado_id`) REFERENCES `grados` (`id`),
  ADD CONSTRAINT `fk_pi_prof` FOREIGN KEY (`profesor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ra`
--
ALTER TABLE `ra`
  ADD CONSTRAINT `fk_ra_asig` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`id`);

--
-- Filtros para la tabla `suscripciones`
--
ALTER TABLE `suscripciones`
  ADD CONSTRAINT `fk_sub_plan` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`),
  ADD CONSTRAINT `fk_sub_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `temas`
--
ALTER TABLE `temas`
  ADD CONSTRAINT `fk_temas_asig` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`id`);

--
-- Filtros para la tabla `temas_ra`
--
ALTER TABLE `temas_ra`
  ADD CONSTRAINT `fk_tr_ra` FOREIGN KEY (`ra_id`) REFERENCES `ra` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tr_tema` FOREIGN KEY (`tema_id`) REFERENCES `temas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_roles` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
