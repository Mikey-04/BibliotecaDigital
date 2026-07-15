-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 15-07-2026 a las 17:44:16
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
-- Base de datos: `biblioteca_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carreras`
--

CREATE TABLE `carreras` (
  `id` int(11) NOT NULL,
  `nombre_carrera` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE `compras` (
  `id` int(11) NOT NULL,
  `estudiante_id` int(11) NOT NULL,
  `libro_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `total` decimal(10,2) NOT NULL,
  `fecha_compra` datetime NOT NULL,
  `firma_digital` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE `estudiantes` (
  `id` int(11) NOT NULL,
  `cip_identificacion` varchar(30) NOT NULL,
  `primer_nombre` varchar(50) NOT NULL,
  `segundo_nombre` varchar(50) DEFAULT NULL,
  `primer_apellido` varchar(50) NOT NULL,
  `segundo_apellido` varchar(50) DEFAULT NULL,
  `fecha_nacimiento` date NOT NULL,
  `carrera_id` int(11) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estudiantes`
--

INSERT INTO `estudiantes` (`id`, `cip_identificacion`, `primer_nombre`, `segundo_nombre`, `primer_apellido`, `segundo_apellido`, `fecha_nacimiento`, `carrera_id`, `password`) VALUES
(1, '8-1017760', 'Austin', NULL, 'Bernal', NULL, '2026-07-16', 1, '$2y$10$Duuse6ieg8T9qFmLF3zEXOSIX1xAJfBX270kZXe1ovUSht2WUKbH2'),
(2, '8-1084-9034', 'Abraham', NULL, 'Alcedo', NULL, '2026-07-17', 3, '$2y$10$XvDHzfV6naF7oX.yuypfXOyIwIiHvNT9CMsADl8A4c00BZtD5c9NK'),
(3, '8-997-1171', 'Alejandro', NULL, 'Rodriguez', NULL, '2000-06-06', 1, '$2y$10$By5hKKdm70i70XWcvZ9TYeVzXADaIUGFnn9zugamhcMQQS4t8V3oK'),
(4, '8-997-1145', 'felipe', NULL, 'fernandez', NULL, '1999-07-20', 2, '$2y$10$l05mPchXHjg/Nb4wtGISYuLppQkkZnrlx22T5VjKwHmjSLL/gCF6O');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `libros`
--

CREATE TABLE `libros` (
  `id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `unidades_existentes` int(11) NOT NULL DEFAULT 0,
  `categoria_id` int(11) NOT NULL,
  `imagen_url` varchar(255) DEFAULT NULL,
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sede_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `libros`
--

INSERT INTO `libros` (`id`, `titulo`, `descripcion`, `unidades_existentes`, `categoria_id`, `imagen_url`, `thumbnail_url`, `precio`, `sede_id`) VALUES
(1, 'Algebra de Baldor', '', 8, 4, 'publico/archivos/libros/libro_6a565e0ca2f766.44443455.jpg', 'publico/archivos/miniaturas/libro_6a565e0ca2f766.44443455.jpg', 25.00, NULL),
(3, 'Matematica 1', '', 86, 4, 'publico/archivos/libros/libro_6a56601bc2e901.61203837.png', 'publico/archivos/miniaturas/libro_6a56601bc2e901.61203837.png', 0.00, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `fecha` timestamp NULL DEFAULT current_timestamp(),
  `intento_exitoso` tinyint(1) NOT NULL,
  `detalles` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE `profesores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `cip` varchar(30) NOT NULL,
  `correo` varchar(150) NOT NULL,
  `especialidad` varchar(100) NOT NULL,
  `fecha_registro` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `profesores`
--

INSERT INTO `profesores` (`id`, `nombre`, `apellido`, `cip`, `correo`, `especialidad`, `fecha_registro`) VALUES
(2, 'Jose', 'Chiru', '8-123-4321', 'jose.chiru@utp.ac.pa', 'Lic. en Desarrollo de Software', '2026-07-15 07:11:42'),
(4, 'Irina', 'Fong', '8-789-4163', 'irina.fong@utp.ac.pa', 'Lic. en Desarrollo de Software', '2026-07-15 14:41:35'),
(5, 'Carlos', 'Hernandez', '8-123-6547', 'carlos.hernandez@utp.ac.pa', 'Lic. en Desarrollo de Software', '2026-07-15 14:49:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas`
--

CREATE TABLE `reservas` (
  `id` int(11) NOT NULL,
  `estudiante_id` int(11) NOT NULL,
  `libro_id` int(11) NOT NULL,
  `fecha_reserva` timestamp NULL DEFAULT current_timestamp(),
  `fecha_devolucion` timestamp NULL DEFAULT NULL,
  `estado` enum('Prestado','Devuelto') DEFAULT 'Prestado',
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_historico` decimal(10,2) NOT NULL DEFAULT 0.00,
  `datos_firma` varchar(255) DEFAULT NULL,
  `firma_digital` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `reservas`
--

INSERT INTO `reservas` (`id`, `estudiante_id`, `libro_id`, `fecha_reserva`, `fecha_devolucion`, `estado`, `cantidad`, `precio_historico`, `datos_firma`, `firma_digital`) VALUES
(1, 1, 3, '2026-07-14 16:16:56', NULL, 'Prestado', 1, 0.00, NULL, NULL),
(3, 1, 3, '2026-07-14 16:23:04', NULL, '', 1, 0.00, NULL, NULL),
(6, 1, 3, '2026-07-14 16:51:32', NULL, '', 2, 0.00, NULL, NULL),
(7, 1, 3, '2026-07-14 16:51:38', NULL, 'Prestado', 1, 0.00, NULL, NULL),
(10, 1, 1, '2026-07-14 16:59:48', NULL, '', 1, 0.00, NULL, NULL),
(11, 1, 3, '2026-07-14 16:59:50', NULL, 'Prestado', 1, 0.00, NULL, NULL),
(12, 1, 3, '2026-07-14 17:00:32', NULL, 'Prestado', 1, 0.00, NULL, NULL),
(13, 1, 1, '2026-07-14 17:00:35', NULL, '', 1, 25.00, NULL, NULL),
(14, 1, 1, '2026-07-14 17:02:26', NULL, '', 1, 25.00, NULL, NULL),
(15, 1, 1, '2026-07-14 17:02:34', NULL, '', 1, 25.00, NULL, NULL),
(16, 1, 3, '2026-07-14 17:02:48', NULL, 'Prestado', 1, 0.00, NULL, NULL),
(18, 1, 1, '2026-07-14 17:10:29', NULL, 'Prestado', 1, 0.00, NULL, NULL),
(19, 1, 1, '2026-07-14 21:52:10', NULL, 'Prestado', 1, 0.00, NULL, NULL),
(20, 1, 3, '2026-07-14 21:52:52', NULL, 'Prestado', 1, 0.00, NULL, NULL),
(21, 1, 3, '2026-07-14 23:15:18', NULL, 'Prestado', 1, 0.00, NULL, NULL),
(22, 1, 1, '2026-07-15 00:06:32', NULL, 'Prestado', 1, 0.00, NULL, NULL),
(23, 1, 1, '2026-07-15 00:23:02', NULL, 'Prestado', 1, 0.00, NULL, NULL),
(24, 1, 3, '2026-07-15 05:58:40', NULL, 'Prestado', 1, 0.00, 'Estudiante:1|Libro:3|Fecha:2026-07-15 07:58:40', '2b871e2f57e95c35cd4fa9c13982eea7c163b38003637b1560c2820cec022030');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sedes`
--

CREATE TABLE `sedes` (
  `id` int(11) NOT NULL,
  `nombre_sede` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sedes`
--

INSERT INTO `sedes` (`id`, `nombre_sede`) VALUES
(1, 'Sede Ciudad de Panamá'),
(2, 'Sede Tocumen'),
(3, 'Sede Azuero');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_adquisicion`
--

CREATE TABLE `solicitudes_adquisicion` (
  `id` int(11) NOT NULL,
  `estudiante_id` int(11) NOT NULL,
  `nombre_libro` varchar(255) NOT NULL,
  `area` varchar(155) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `fecha_solicitud` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_libros`
--

CREATE TABLE `solicitudes_libros` (
  `id` int(11) NOT NULL,
  `estudiante_id` int(11) NOT NULL,
  `nombre_libro` varchar(150) NOT NULL,
  `area` enum('Matemáticas','Ciencias','Tecnologías','Deporte','Salud','Revistas Científicas') NOT NULL,
  `fecha_solicitud` timestamp NULL DEFAULT current_timestamp(),
  `notas` text DEFAULT NULL,
  `notas_adicionales` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `solicitudes_libros`
--

INSERT INTO `solicitudes_libros` (`id`, `estudiante_id`, `nombre_libro`, `area`, `fecha_solicitud`, `notas`, `notas_adicionales`) VALUES
(1, 1, 'algebra de baldor', 'Matemáticas', '2026-07-14 16:17:33', NULL, NULL),
(3, 1, 'fisica', '', '2026-07-14 16:40:59', NULL, NULL),
(4, 1, 'fisica', '', '2026-07-14 16:51:27', NULL, NULL),
(5, 1, 'fisica', '', '2026-07-14 21:38:40', NULL, NULL),
(6, 1, 'fisica', '', '2026-07-14 21:43:14', NULL, NULL),
(7, 1, 'fisica', '', '2026-07-14 21:43:17', NULL, NULL),
(8, 1, 'fisica', '', '2026-07-14 21:44:06', NULL, NULL),
(9, 1, 'fisica', '', '2026-07-14 21:52:06', NULL, NULL),
(11, 1, 'matematica 2', '', '2026-07-14 21:53:08', NULL, NULL),
(12, 1, 'matematica 2', '', '2026-07-14 21:58:16', NULL, NULL),
(13, 1, 'Ciencia 1', '', '2026-07-14 21:58:31', NULL, NULL),
(14, 1, 'Ciencia 1', '', '2026-07-14 22:11:00', NULL, NULL),
(15, 1, 'matematica 3', '', '2026-07-14 22:19:28', NULL, NULL),
(16, 1, 'Ciencia 1', '', '2026-07-14 22:39:43', NULL, NULL),
(17, 1, 'fisica 1', 'Tecnologías', '2026-07-14 22:39:57', NULL, NULL),
(18, 1, 'fisica 1', 'Tecnologías', '2026-07-14 22:42:31', NULL, NULL),
(19, 1, 'fisica 1', 'Tecnologías', '2026-07-14 23:13:00', NULL, NULL),
(20, 2, 'Patrones de PHP', '', '2026-07-14 23:50:46', 'Pedido urgente por la profesora Irina Phonk', NULL),
(21, 1, 'fisica', '', '2026-07-14 23:58:32', NULL, NULL),
(22, 1, 'fisica', '', '2026-07-15 00:05:46', NULL, NULL),
(28, 4, 'aprende ciencias', '', '2026-07-15 07:42:41', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `rol` varchar(50) DEFAULT 'bibliotecario'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `nombre`, `estado`, `created_at`, `rol`) VALUES
(1, 'admin', '$2y$10$ss3tHAQtlpAsMHpLOrZwX.oAw/RqT6qfQ6SrOUAbVOlEWmPuHXLzK', 'Administrador General', 1, '2026-07-09 13:21:16', 'admin'),
(5, 'admin7', '$2y$10$m1mgQKRmbuFgtmj69cpn3uXdHnoAqQ.Z5lfuoEqrgMWcaxNYWrunC', 'traly', 1, '2026-07-15 00:46:49', 'bibliotecario'),
(13, 'admin8', '$2y$10$Y5jLUmQS6IRXBrxhcfdbu.8ShxmL09KdH30043cojWEysw1.oNUDe', 'keray', 1, '2026-07-15 01:14:55', 'admin'),
(14, 'admin2026', '$2y$10$5Tonzuv5qAN4NNeu/Xz1Ee/JiDpPQ8Jgc6n1LBaNkT7irWY6IL6pu', 'Mikey', 1, '2026-07-15 06:09:38', 'admin'),
(17, 'admin9', '$2y$10$.QuKY.k0Dr0112VdllK4beVjxRAseQ0tNJwBoVFPgeCuxSDw9depy', 'Mario', 1, '2026-07-15 07:44:35', 'bibliotecario');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `carreras`
--
ALTER TABLE `carreras`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre_carrera` (`nombre_carrera`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `compras`
--
ALTER TABLE `compras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_compras_estudiantes` (`estudiante_id`),
  ADD KEY `fk_compras_libros` (`libro_id`);

--
-- Indices de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cip_identificacion` (`cip_identificacion`),
  ADD KEY `carrera_id` (`carrera_id`);

--
-- Indices de la tabla `libros`
--
ALTER TABLE `libros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categoria_id` (`categoria_id`),
  ADD KEY `fk_libro_sede` (`sede_id`);

--
-- Indices de la tabla `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `profesores`
--
ALTER TABLE `profesores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cip` (`cip`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- Indices de la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `estudiante_id` (`estudiante_id`),
  ADD KEY `fk_reservas_libros` (`libro_id`);

--
-- Indices de la tabla `sedes`
--
ALTER TABLE `sedes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `solicitudes_adquisicion`
--
ALTER TABLE `solicitudes_adquisicion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `estudiante_id` (`estudiante_id`);

--
-- Indices de la tabla `solicitudes_libros`
--
ALTER TABLE `solicitudes_libros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `estudiante_id` (`estudiante_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `carreras`
--
ALTER TABLE `carreras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `compras`
--
ALTER TABLE `compras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `libros`
--
ALTER TABLE `libros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `profesores`
--
ALTER TABLE `profesores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `reservas`
--
ALTER TABLE `reservas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `sedes`
--
ALTER TABLE `sedes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `solicitudes_adquisicion`
--
ALTER TABLE `solicitudes_adquisicion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `solicitudes_libros`
--
ALTER TABLE `solicitudes_libros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `compras`
--
ALTER TABLE `compras`
  ADD CONSTRAINT `fk_compras_estudiantes` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_compras_libros` FOREIGN KEY (`libro_id`) REFERENCES `libros` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD CONSTRAINT `estudiantes_ibfk_1` FOREIGN KEY (`carrera_id`) REFERENCES `carreras` (`id`);

--
-- Filtros para la tabla `libros`
--
ALTER TABLE `libros`
  ADD CONSTRAINT `fk_libro_sede` FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`),
  ADD CONSTRAINT `libros_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`);

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
