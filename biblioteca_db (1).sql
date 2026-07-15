-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 15-07-2026 a las 01:26:01
-- Versión del servidor: 8.4.7
-- Versión de PHP: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `biblioteca_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carreras`
--

DROP TABLE IF EXISTS `carreras`;
CREATE TABLE IF NOT EXISTS `carreras` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre_carrera` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre_carrera` (`nombre_carrera`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `carreras`
--

INSERT INTO `carreras` (`id`, `nombre_carrera`) VALUES
(3, 'Ing. Industrial'),
(2, 'Lic. en Ciberseguridad'),
(1, 'Lic. en Desarrollo de Software');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

DROP TABLE IF EXISTS `categorias`;
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`) VALUES
(5, 'Estadística'),
(3, 'Lógica'),
(4, 'Matemática'),
(1, 'Química'),
(2, 'Sistemas');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compras`
--

DROP TABLE IF EXISTS `compras`;
CREATE TABLE IF NOT EXISTS `compras` (
  `id` int NOT NULL AUTO_INCREMENT,
  `estudiante_id` int NOT NULL,
  `libro_id` int NOT NULL,
  `cantidad` int NOT NULL DEFAULT '1',
  `total` decimal(10,2) NOT NULL,
  `fecha_compra` datetime NOT NULL,
  `firma_digital` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_compras_estudiantes` (`estudiante_id`),
  KEY `fk_compras_libros` (`libro_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `compras`
--

INSERT INTO `compras` (`id`, `estudiante_id`, `libro_id`, `cantidad`, `total`, `fecha_compra`, `firma_digital`) VALUES
(1, 1, 1, 2, 50.00, '2026-07-14 12:13:21', NULL),
(2, 1, 1, 1, 25.00, '2026-07-14 12:16:29', NULL),
(3, 1, 1, 1, 25.00, '2026-07-14 12:16:40', NULL),
(4, 1, 1, 2, 50.00, '2026-07-14 12:22:39', NULL),
(5, 1, 1, 2, 50.00, '2026-07-14 12:32:55', NULL),
(6, 1, 1, 2, 50.00, '2026-07-14 12:33:10', NULL),
(7, 1, 1, 2, 50.00, '2026-07-14 12:44:42', NULL),
(8, 1, 1, 1, 25.00, '2026-07-14 12:44:56', NULL),
(9, 1, 1, 1, 25.00, '2026-07-14 16:38:26', NULL),
(10, 1, 1, 1, 25.00, '2026-07-14 16:43:22', NULL),
(11, 1, 1, 1, 25.00, '2026-07-14 17:11:12', NULL),
(12, 1, 3, 1, 0.00, '2026-07-14 18:38:56', NULL),
(13, 1, 3, 1, 0.00, '2026-07-14 18:40:27', NULL),
(14, 1, 1, 1, 25.00, '2026-07-14 19:05:51', NULL),
(15, 1, 3, 1, 0.00, '2026-07-14 20:18:06', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiantes`
--

DROP TABLE IF EXISTS `estudiantes`;
CREATE TABLE IF NOT EXISTS `estudiantes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cip_identificacion` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `primer_nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `segundo_nombre` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `primer_apellido` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `segundo_apellido` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_nacimiento` date NOT NULL,
  `carrera_id` int NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cip_identificacion` (`cip_identificacion`),
  KEY `carrera_id` (`carrera_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estudiantes`
--

INSERT INTO `estudiantes` (`id`, `cip_identificacion`, `primer_nombre`, `segundo_nombre`, `primer_apellido`, `segundo_apellido`, `fecha_nacimiento`, `carrera_id`, `password`) VALUES
(1, '8-1017760', 'Austin', NULL, 'Bernal', NULL, '2026-07-16', 1, '$2y$10$Duuse6ieg8T9qFmLF3zEXOSIX1xAJfBX270kZXe1ovUSht2WUKbH2'),
(2, '8-1084-9034', 'Abraham', NULL, 'Alcedo', NULL, '2026-07-17', 3, '$2y$10$XvDHzfV6naF7oX.yuypfXOyIwIiHvNT9CMsADl8A4c00BZtD5c9NK');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `libros`
--

DROP TABLE IF EXISTS `libros`;
CREATE TABLE IF NOT EXISTS `libros` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `unidades_existentes` int NOT NULL DEFAULT '0',
  `categoria_id` int NOT NULL,
  `imagen_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thumbnail_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `categoria_id` (`categoria_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `libros`
--

INSERT INTO `libros` (`id`, `titulo`, `descripcion`, `unidades_existentes`, `categoria_id`, `imagen_url`, `thumbnail_url`, `precio`) VALUES
(1, 'Algebra de Baldor', '', 8, 4, 'publico/archivos/libros/libro_6a565e0ca2f766.44443455.jpg', 'publico/archivos/miniaturas/libro_6a565e0ca2f766.44443455.jpg', 25.00),
(3, 'Matematica 1', '', 87, 4, 'publico/archivos/libros/libro_6a56601bc2e901.61203837.png', 'publico/archivos/miniaturas/libro_6a56601bc2e901.61203837.png', 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `login_logs`
--

DROP TABLE IF EXISTS `login_logs`;
CREATE TABLE IF NOT EXISTS `login_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `intento_exitoso` tinyint(1) NOT NULL,
  `detalles` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `login_logs`
--

INSERT INTO `login_logs` (`id`, `username`, `ip_address`, `fecha`, `intento_exitoso`, `detalles`) VALUES
(1, 'admin', '::1', '2026-07-09 13:36:01', 1, 'Inicio de sesión correcto.'),
(2, 'E-8215994', '::1', '2026-07-09 13:50:06', 0, 'Fallo de autenticación: Usuario no existe.'),
(3, 'admin', '::1', '2026-07-09 14:04:17', 1, 'Inicio de sesión correcto.'),
(4, 'admin', '::1', '2026-07-09 14:11:08', 1, 'Inicio de sesión correcto.'),
(5, 'admin', '::1', '2026-07-09 16:03:50', 0, 'Fallo de autenticación: Contraseña incorrecta.'),
(6, 'admin', '::1', '2026-07-09 16:04:08', 0, 'Fallo de autenticación: Contraseña incorrecta.'),
(7, 'admin', '::1', '2026-07-09 16:04:28', 1, 'Inicio de sesión correcto.'),
(8, 'admin', '::1', '2026-07-14 16:11:37', 0, 'Fallo de autenticación: Contraseña incorrecta.'),
(9, 'admin', '::1', '2026-07-14 16:11:49', 1, 'Inicio de sesión correcto.'),
(10, 'admin', '::1', '2026-07-14 16:24:38', 1, 'Inicio de sesión correcto.'),
(11, 'admin', '::1', '2026-07-14 17:00:05', 1, 'Inicio de sesión correcto.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesores`
--

DROP TABLE IF EXISTS `profesores`;
CREATE TABLE IF NOT EXISTS `profesores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apellido` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cip` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `correo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `especialidad` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cip` (`cip`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `profesores`
--

INSERT INTO `profesores` (`id`, `nombre`, `apellido`, `cip`, `correo`, `especialidad`, `fecha_registro`) VALUES
(1, 'Irina', 'Phonk', '000000', 'irina.phonk@utp.ac.pa', 'Lic. en Desarrollo de Software', '2026-07-14 23:34:45');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas`
--

DROP TABLE IF EXISTS `reservas`;
CREATE TABLE IF NOT EXISTS `reservas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `estudiante_id` int NOT NULL,
  `libro_id` int NOT NULL,
  `fecha_reserva` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_devolucion` timestamp NULL DEFAULT NULL,
  `estado` enum('Prestado','Devuelto') COLLATE utf8mb4_unicode_ci DEFAULT 'Prestado',
  `cantidad` int NOT NULL DEFAULT '1',
  `precio_historico` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `estudiante_id` (`estudiante_id`),
  KEY `fk_reservas_libros` (`libro_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `reservas`
--

INSERT INTO `reservas` (`id`, `estudiante_id`, `libro_id`, `fecha_reserva`, `fecha_devolucion`, `estado`, `cantidad`, `precio_historico`) VALUES
(1, 1, 3, '2026-07-14 16:16:56', NULL, 'Prestado', 1, 0.00),
(3, 1, 3, '2026-07-14 16:23:04', NULL, '', 1, 0.00),
(6, 1, 3, '2026-07-14 16:51:32', NULL, '', 2, 0.00),
(7, 1, 3, '2026-07-14 16:51:38', NULL, 'Prestado', 1, 0.00),
(10, 1, 1, '2026-07-14 16:59:48', NULL, '', 1, 0.00),
(11, 1, 3, '2026-07-14 16:59:50', NULL, 'Prestado', 1, 0.00),
(12, 1, 3, '2026-07-14 17:00:32', NULL, 'Prestado', 1, 0.00),
(13, 1, 1, '2026-07-14 17:00:35', NULL, '', 1, 25.00),
(14, 1, 1, '2026-07-14 17:02:26', NULL, '', 1, 25.00),
(15, 1, 1, '2026-07-14 17:02:34', NULL, '', 1, 25.00),
(16, 1, 3, '2026-07-14 17:02:48', NULL, 'Prestado', 1, 0.00),
(18, 1, 1, '2026-07-14 17:10:29', NULL, 'Prestado', 1, 0.00),
(19, 1, 1, '2026-07-14 21:52:10', NULL, 'Prestado', 1, 0.00),
(20, 1, 3, '2026-07-14 21:52:52', NULL, 'Prestado', 1, 0.00),
(21, 1, 3, '2026-07-14 23:15:18', NULL, 'Prestado', 1, 0.00),
(22, 1, 1, '2026-07-15 00:06:32', NULL, 'Prestado', 1, 0.00),
(23, 1, 1, '2026-07-15 00:23:02', NULL, 'Prestado', 1, 0.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_adquisicion`
--

DROP TABLE IF EXISTS `solicitudes_adquisicion`;
CREATE TABLE IF NOT EXISTS `solicitudes_adquisicion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `estudiante_id` int NOT NULL,
  `nombre_libro` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `area` varchar(155) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `fecha_solicitud` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `estudiante_id` (`estudiante_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_libros`
--

DROP TABLE IF EXISTS `solicitudes_libros`;
CREATE TABLE IF NOT EXISTS `solicitudes_libros` (
  `id` int NOT NULL AUTO_INCREMENT,
  `estudiante_id` int NOT NULL,
  `nombre_libro` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `area` enum('Matemáticas','Ciencias','Tecnologías','Deporte','Salud','Revistas Científicas') COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_solicitud` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `notas` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `estudiante_id` (`estudiante_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `solicitudes_libros`
--

INSERT INTO `solicitudes_libros` (`id`, `estudiante_id`, `nombre_libro`, `area`, `fecha_solicitud`, `notas`) VALUES
(1, 1, 'algebra de baldor', 'Matemáticas', '2026-07-14 16:17:33', NULL),
(3, 1, 'fisica', '', '2026-07-14 16:40:59', NULL),
(4, 1, 'fisica', '', '2026-07-14 16:51:27', NULL),
(5, 1, 'fisica', '', '2026-07-14 21:38:40', NULL),
(6, 1, 'fisica', '', '2026-07-14 21:43:14', NULL),
(7, 1, 'fisica', '', '2026-07-14 21:43:17', NULL),
(8, 1, 'fisica', '', '2026-07-14 21:44:06', NULL),
(9, 1, 'fisica', '', '2026-07-14 21:52:06', NULL),
(10, 1, 'fisica', '', '2026-07-14 21:52:28', NULL),
(11, 1, 'matematica 2', '', '2026-07-14 21:53:08', NULL),
(12, 1, 'matematica 2', '', '2026-07-14 21:58:16', NULL),
(13, 1, 'Ciencia 1', '', '2026-07-14 21:58:31', NULL),
(14, 1, 'Ciencia 1', '', '2026-07-14 22:11:00', NULL),
(15, 1, 'matematica 3', '', '2026-07-14 22:19:28', NULL),
(16, 1, 'Ciencia 1', '', '2026-07-14 22:39:43', NULL),
(17, 1, 'fisica 1', 'Tecnologías', '2026-07-14 22:39:57', NULL),
(18, 1, 'fisica 1', 'Tecnologías', '2026-07-14 22:42:31', NULL),
(19, 1, 'fisica 1', 'Tecnologías', '2026-07-14 23:13:00', NULL),
(20, 2, 'Patrones de PHP', '', '2026-07-14 23:50:46', 'Pedido urgente por la profesora Irina Phonk'),
(21, 1, 'fisica', '', '2026-07-14 23:58:32', NULL),
(22, 1, 'fisica', '', '2026-07-15 00:05:46', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `rol` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'bibliotecario',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `nombre`, `estado`, `created_at`, `rol`) VALUES
(1, 'admin', '$2y$10$ss3tHAQtlpAsMHpLOrZwX.oAw/RqT6qfQ6SrOUAbVOlEWmPuHXLzK', 'Administrador General', 1, '2026-07-09 13:21:16', 'admin'),
(5, 'admin7', '$2y$10$m1mgQKRmbuFgtmj69cpn3uXdHnoAqQ.Z5lfuoEqrgMWcaxNYWrunC', 'traly', 1, '2026-07-15 00:46:49', 'bibliotecario'),
(13, 'admin8', '$2y$10$Y5jLUmQS6IRXBrxhcfdbu.8ShxmL09KdH30043cojWEysw1.oNUDe', 'keray', 1, '2026-07-15 01:14:55', 'admin');

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `compras`
--
ALTER TABLE `compras`
  ADD CONSTRAINT `fk_compras_estudiantes` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_compras_libros` FOREIGN KEY (`libro_id`) REFERENCES `libros` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD CONSTRAINT `estudiantes_ibfk_1` FOREIGN KEY (`carrera_id`) REFERENCES `carreras` (`id`) ON DELETE RESTRICT;

--
-- Filtros para la tabla `libros`
--
ALTER TABLE `libros`
  ADD CONSTRAINT `libros_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE RESTRICT;

--
-- Filtros para la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `fk_reservas_libros` FOREIGN KEY (`libro_id`) REFERENCES `libros` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `reservas_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `solicitudes_libros`
--
ALTER TABLE `solicitudes_libros`
  ADD CONSTRAINT `solicitudes_libros_ibfk_1` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
