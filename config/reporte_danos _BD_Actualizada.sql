-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 08-06-2026 a las 18:50:55
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
-- Base de datos: `reporte_danos`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `activo`) VALUES
(1, 'Plomería', 1),
(2, 'Electricidad', 1),
(3, 'Estructural', 1),
(4, 'Otros', 1),
(5, 'Jardineria', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estados`
--

CREATE TABLE `estados` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estados`
--

INSERT INTO `estados` (`id`, `nombre`) VALUES
(2, 'activo'),
(5, 'cerrado'),
(1, 'creado'),
(3, 'proceso'),
(4, 'resuelto');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `ticket_uuid` varchar(36) NOT NULL,
  `mensaje` text NOT NULL,
  `leido` tinyint(1) DEFAULT 0,
  `fecha_envio` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`id`, `usuario_id`, `ticket_uuid`, `mensaje`, `leido`, `fecha_envio`) VALUES
(1, 1, '3b18956808c12c7979f6c804eae47007', 'Nuevo ticket radicado (#3b189568) en la ubicación: salon social', 0, '2026-05-30 05:32:57'),
(3, 1, '8ecc0623b5d9c8c8f3e78a432aad0f10', 'Nuevo ticket radicado (#8ecc0623) en la ubicación: apto 182', 0, '2026-05-30 05:36:21'),
(4, 2, '8ecc0623b5d9c8c8f3e78a432aad0f10', 'Nuevo ticket radicado (#8ecc0623) en la ubicación: apto 182', 0, '2026-05-30 05:36:21'),
(5, 3, '8ecc0623b5d9c8c8f3e78a432aad0f10', 'Tu ticket #8ecc0623 en apto 182 ha cambiado al estado: CREADO', 0, '2026-05-30 19:27:10'),
(6, 1, '3b18956808c12c7979f6c804eae47007', 'Tu ticket #3b189568 en salon social ha cambiado al estado: CREADO', 0, '2026-05-30 20:11:07'),
(7, 3, '8ecc0623b5d9c8c8f3e78a432aad0f10', 'Tu ticket #8ecc0623 en apto 182 ha cambiado al estado: PROCESO', 0, '2026-05-30 20:11:13'),
(8, 1, '3b18956808c12c7979f6c804eae47007', 'Tu ticket #3b189568 en salon social ha cambiado al estado: PROCESO', 0, '2026-05-30 20:13:06'),
(9, 1, '81e0f8d47c16b561c9d50cf829934c1a', 'Nuevo ticket radicado (#81e0f8d4) en la ubicación: parqueadero 25', 0, '2026-05-30 20:16:04'),
(10, 2, '81e0f8d47c16b561c9d50cf829934c1a', 'Nuevo ticket radicado (#81e0f8d4) en la ubicación: parqueadero 25', 0, '2026-05-30 20:16:04'),
(11, 3, '81e0f8d47c16b561c9d50cf829934c1a', 'Tu ticket #81e0f8d4 en parqueadero 25 ha cambiado al estado: CREADO', 0, '2026-05-30 20:18:18'),
(12, 1, 'd5dd3a0c36dc4b9dc8cad2ef9c8dd4af', 'Nuevo ticket radicado (#d5dd3a0c) en la ubicación: apto 418', 0, '2026-06-08 02:51:05'),
(13, 2, 'd5dd3a0c36dc4b9dc8cad2ef9c8dd4af', 'Nuevo ticket radicado (#d5dd3a0c) en la ubicación: apto 418', 0, '2026-06-08 02:51:05'),
(14, 1, 'd5dd3a0c36dc4b9dc8cad2ef9c8dd4af', 'Tu ticket #d5dd3a0c en apto 418 ha cambiado al estado: CREADO', 0, '2026-06-08 02:56:09'),
(15, 1, 'e5fd7fcc83de4139ccaeb918d8d6c402', 'Nuevo ticket radicado (#e5fd7fcc) en la ubicación: apto 505', 0, '2026-06-08 03:34:39'),
(16, 2, 'e5fd7fcc83de4139ccaeb918d8d6c402', 'Nuevo ticket radicado (#e5fd7fcc) en la ubicación: apto 505', 0, '2026-06-08 03:34:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportes`
--

CREATE TABLE `reportes` (
  `id` int(11) NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `ticket_uuid` varchar(36) NOT NULL,
  `categoria` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `ubicacion` varchar(255) NOT NULL,
  `prioridad` enum('Baja','Media','Alta','Crítica') DEFAULT 'Media',
  `usuario_reporta_id` int(11) NOT NULL,
  `usuario_asignado_id` int(11) DEFAULT NULL,
  `estado_id` int(11) DEFAULT 1,
  `imagen_url` varchar(255) DEFAULT NULL,
  `fecha_reporte` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `reportes`
--

INSERT INTO `reportes` (`id`, `categoria_id`, `ticket_uuid`, `categoria`, `descripcion`, `ubicacion`, `prioridad`, `usuario_reporta_id`, `usuario_asignado_id`, `estado_id`, `imagen_url`, `fecha_reporte`, `fecha_actualizacion`) VALUES
(1, 2, '3b18956808c12c7979f6c804eae47007', 'Electricidad', 'switche malo', 'salon social', 'Media', 1, 2, 4, NULL, '2026-05-30 05:32:57', '2026-06-08 14:37:44'),
(2, 1, '8ecc0623b5d9c8c8f3e78a432aad0f10', 'Fontanería / Plomería', 'gotera en techo de baño, proviene de tuberia apto 282', 'apto 182', 'Media', 3, 2, 4, NULL, '2026-05-30 05:36:21', '2026-06-08 14:58:23'),
(3, 2, '81e0f8d47c16b561c9d50cf829934c1a', 'Electricidad', 'Lampara quemada', 'parqueadero 25', 'Media', 3, 2, 3, 'uploads/1780172164_WhatsApp Image 2026-05-20 at 3.31.44 PM.jpeg', '2026-05-30 20:16:04', '2026-06-08 14:46:11'),
(4, 4, 'd5dd3a0c36dc4b9dc8cad2ef9c8dd4af', 'Ascensores / Áreas Comunes', 'daño en luz de corredor', 'apto 418', 'Media', 1, 2, 3, NULL, '2026-06-08 02:51:05', '2026-06-08 15:03:08'),
(5, NULL, 'e5fd7fcc83de4139ccaeb918d8d6c402', 'Estructural (Paredes/Techos)', 'chapa de puesta mala', 'apto 505', 'Media', 1, NULL, 3, NULL, '2026-06-08 03:34:39', '2026-06-08 14:57:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`) VALUES
(1, 'administracion'),
(2, 'mantenimiento'),
(3, 'propietarios');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol_id` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `correo`, `password`, `rol_id`, `fecha_creacion`) VALUES
(1, 'Administrador Central', 'admin@residencial.com', 'Password123', 1, '2026-05-30 00:50:38'),
(2, 'Técnico Especialista', 'tecnico@residencial.com', 'Password123', 2, '2026-05-30 00:50:38'),
(3, 'Propietario Residente', 'propietario@residencial.com', 'Password123', 3, '2026-05-30 00:50:38');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `estados`
--
ALTER TABLE `estados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `reportes`
--
ALTER TABLE `reportes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_uuid` (`ticket_uuid`),
  ADD KEY `usuario_reporta_id` (`usuario_reporta_id`),
  ADD KEY `usuario_asignado_id` (`usuario_asignado_id`),
  ADD KEY `estado_id` (`estado_id`),
  ADD KEY `fk_reportes_categorias` (`categoria_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `rol_id` (`rol_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `estados`
--
ALTER TABLE `estados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `reportes`
--
ALTER TABLE `reportes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reportes`
--
ALTER TABLE `reportes`
  ADD CONSTRAINT `fk_reportes_categorias` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reportes_ibfk_1` FOREIGN KEY (`usuario_reporta_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reportes_ibfk_2` FOREIGN KEY (`usuario_asignado_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reportes_ibfk_3` FOREIGN KEY (`estado_id`) REFERENCES `estados` (`id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
