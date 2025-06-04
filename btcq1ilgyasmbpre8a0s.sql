-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: btcq1ilgyasmbpre8a0s-mysql.services.clever-cloud.com:3306
-- Tiempo de generación: 04-06-2025 a las 18:41:28
-- Versión del servidor: 8.4.2-2
-- Versión de PHP: 8.2.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `btcq1ilgyasmbpre8a0s`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Clientes`
--

CREATE TABLE `Clientes` (
  `id_cliente` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `correo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Empleados`
--

CREATE TABLE `Empleados` (
  `id_empleado` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cargo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contrasena` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Entradas`
--

CREATE TABLE `Entradas` (
  `id_entrada` int NOT NULL,
  `id_funcion` int NOT NULL,
  `id_cliente` int DEFAULT NULL,
  `cantidad` int NOT NULL,
  `asientos` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_compra` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `total_pagado` decimal(8,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Funciones`
--

CREATE TABLE `Funciones` (
  `id_funcion` int NOT NULL,
  `id_pelicula` int NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `precio` decimal(6,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `Funciones`
--

INSERT INTO `Funciones` (`id_funcion`, `id_pelicula`, `fecha`, `hora_inicio`, `precio`) VALUES
(1, 12, '2025-06-11', '12:00:00', 8.00),
(2, 12, '2025-06-11', '15:00:00', 8.00),
(3, 12, '2025-06-11', '18:00:00', 8.00),
(4, 12, '2025-06-12', '12:00:00', 8.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Peliculas`
--

CREATE TABLE `Peliculas` (
  `id_pelicula` int NOT NULL,
  `titulo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `duracion_minutos` int NOT NULL,
  `clasificacion` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `genero` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sinopsis` text COLLATE utf8mb4_unicode_ci,
  `estado` tinyint(1) DEFAULT '1',
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `Peliculas`
--

INSERT INTO `Peliculas` (`id_pelicula`, `titulo`, `duracion_minutos`, `clasificacion`, `genero`, `sinopsis`, `estado`, `imagen`) VALUES
(2, 'Los tipos malos', 100, 'todo publi', 'animacion', 'El señor Lobo, el señor Serpiente, el señor Piraña, el señor Tiburón y la señorita Tarántula tienen que fingir que se han convertido en chicos buenos para evitar ir a la cárcel. Conseguirlo se convierte en el mayor reto de sus carreras delictivas.', 1, 'peli_683293c9484d4.jpg'),
(3, 'Free Guy- tomando el control', 110, '+12 años', 'comedia', 'El cajero de un banco descubre que en realidad es un personaje secundario dentro de un videojuego violento.', 1, 'peli_683295141a564.jpg'),
(4, 'Sueño de fuga', 140, '+12 años', 'Drama', 'Un hombre inocente es enviado a una corrupta penitenciaria de Maine en 1947 y sentenciado a dos cadenas perpetuas por un doble asesinato.', 1, 'peli_6832963da7d2e.jpg'),
(5, 'El gran showman', 105, 'todo publi', 'musical', 'Inspirado en la imaginación de PT Barnum, The Greatest Showman es un musical original que celebra el nacimiento del mundo del espectáculo y cuenta la historia de un visionario que surgió de la nada para crear un espectáculo que se convirtió en una sensación mundial.', 1, 'peli_683297cb5177e.jpg'),
(6, 'El Mono', 98, '+18 años', 'Terror', 'Dos hermanos gemelos encuentran un misterioso mono de cuerda y una serie de muertes atroces separan a su familia. Veinticinco años más tarde, el mono reanuda su macabra trayectoria, obligando a los hermanos separados a enfrentarse al juguete maldito.', 1, 'peli_68329b0d71946.jpg'),
(7, 'Avatar', 162, '+ 13 años', 'Aventura', 'En un exuberante planeta llamado Pandora viven los Na\'vi, seres que aparentan ser primitivos pero que en realidad son muy evolucionados. Debido a que el ambiente de Pandora es venenoso, los híbridos humanos/Na\'vi, llamados Avatares, están relacionados con las mentes humanas, lo que les permite moverse libremente por Pandora. Jake Sully, un exinfante de marina paralítico se transforma a través de un Avatar, y se enamora de una mujer Na\'vi.', 1, 'peli_6834fd21b9b31.jpeg'),
(8, 'Blade Runner 2049', 163, '+16 años', 'Accion', 'En el año 2049 el oficial K, un nuevo replicante de la policía de Los Ángeles, emprende la búsqueda del replicante Rick Deckard, desaparecido 30 años antes. K piensa que en Deckard reside la clave que podría permitir salvar a la sociedad del caos en el que está inmersa.', 1, 'peli_6834fdfabd3d2.jpg'),
(9, 'Tron Ares', 160, '+16 años', 'Ciencia Ficción', 'Tron: Ares es una próxima película estadounidense de ciencia ficción producida por Walt Disney Pictures y distribuida por Walt Disney Studios Motion Pictures. Sirve como secuela independiente de Tron y Tron: Legacy.', 1, 'peli_6834fecb1d165.jpg'),
(10, 'Bastardos sin gloria', 150, '+18 años', 'Belico', 'Es el primer año de la ocupación alemana de Francia. El oficial aliado, teniente Aldo Raine, ensambla un equipo de soldados judíos para cometer actos violentos en contra de los nazis, incluyendo la toma de cabelleras. Él y sus hombres unen fuerzas con Bridget von Hammersmark, una actriz alemana y agente encubierto, para derrocar a los líderes del Tercer Reich. Sus destinos convergen con la dueña de teatro Shosanna Dreyfus, quien busca vengar la ejecución de su familia.', 1, 'peli_6834ff57355d2.jpg'),
(12, 'Interestellar ', 160, '+13 años', 'Ciencia Ficcion ', 'Gracias a un descubrimiento, un grupo de científicos y exploradores, encabezados por Cooper, se embarcan en un viaje espacial para encontrar un lugar con las condiciones necesarias para reemplazar a la Tierra y comenzar una nueva vida allí.', 1, 'peli_68407076c5940.jpg');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `Clientes`
--
ALTER TABLE `Clientes`
  ADD PRIMARY KEY (`id_cliente`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- Indices de la tabla `Empleados`
--
ALTER TABLE `Empleados`
  ADD PRIMARY KEY (`id_empleado`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- Indices de la tabla `Entradas`
--
ALTER TABLE `Entradas`
  ADD PRIMARY KEY (`id_entrada`),
  ADD KEY `id_funcion` (`id_funcion`),
  ADD KEY `id_cliente` (`id_cliente`);

--
-- Indices de la tabla `Funciones`
--
ALTER TABLE `Funciones`
  ADD PRIMARY KEY (`id_funcion`),
  ADD KEY `id_pelicula` (`id_pelicula`);

--
-- Indices de la tabla `Peliculas`
--
ALTER TABLE `Peliculas`
  ADD PRIMARY KEY (`id_pelicula`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `Clientes`
--
ALTER TABLE `Clientes`
  MODIFY `id_cliente` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Empleados`
--
ALTER TABLE `Empleados`
  MODIFY `id_empleado` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Entradas`
--
ALTER TABLE `Entradas`
  MODIFY `id_entrada` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `Funciones`
--
ALTER TABLE `Funciones`
  MODIFY `id_funcion` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `Peliculas`
--
ALTER TABLE `Peliculas`
  MODIFY `id_pelicula` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `Entradas`
--
ALTER TABLE `Entradas`
  ADD CONSTRAINT `Entradas_ibfk_1` FOREIGN KEY (`id_funcion`) REFERENCES `Funciones` (`id_funcion`),
  ADD CONSTRAINT `Entradas_ibfk_2` FOREIGN KEY (`id_cliente`) REFERENCES `Clientes` (`id_cliente`);

--
-- Filtros para la tabla `Funciones`
--
ALTER TABLE `Funciones`
  ADD CONSTRAINT `Funciones_ibfk_1` FOREIGN KEY (`id_pelicula`) REFERENCES `Peliculas` (`id_pelicula`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
