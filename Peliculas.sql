-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 26-05-2025 a las 21:39:33
-- Versión del servidor: 10.11.11-MariaDB
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u312507976_db87`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `Peliculas`
--

CREATE TABLE `Peliculas` (
  `id_pelicula` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `duracion_minutos` int(11) NOT NULL,
  `clasificacion` varchar(10) DEFAULT NULL,
  `genero` varchar(50) DEFAULT NULL,
  `sinopsis` text DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 1,
  `imagen` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `Peliculas`
--

INSERT INTO `Peliculas` (`id_pelicula`, `titulo`, `duracion_minutos`, `clasificacion`, `genero`, `sinopsis`, `estado`, `imagen`) VALUES
(2, 'Los tipos malos', 100, 'todo publi', 'animacion', 'El señor Lobo, el señor Serpiente, el señor Piraña, el señor Tiburón y la señorita Tarántula tienen que fingir que se han convertido en chicos buenos para evitar ir a la cárcel. Conseguirlo se convierte en el mayor reto de sus carreras delictivas.', 1, 'peli_683293c9484d4.jpg'),
(3, 'Free Guy- tomando el control', 110, '+12 años', 'comedia', 'El cajero de un banco descubre que en realidad es un personaje secundario dentro de un videojuego violento.', 1, 'peli_683295141a564.jpg'),
(4, 'Sueño de fuga', 140, '+12 años', 'Drama', 'Un hombre inocente es enviado a una corrupta penitenciaria de Maine en 1947 y sentenciado a dos cadenas perpetuas por un doble asesinato.', 1, 'peli_6832963da7d2e.jpg'),
(5, 'El gran showman', 105, 'todo publi', 'musical', 'Inspirado en la imaginación de PT Barnum, The Greatest Showman es un musical original que celebra el nacimiento del mundo del espectáculo y cuenta la historia de un visionario que surgió de la nada para crear un espectáculo que se convirtió en una sensación mundial.', 1, 'peli_683297cb5177e.jpg'),
(6, 'El mono', 98, '+18 años', 'Terror', 'Dos hermanos gemelos encuentran un misterioso mono de cuerda y una serie de muertes atroces separan a su familia. Veinticinco años más tarde, el mono reanuda su macabra trayectoria, obligando a los hermanos separados a enfrentarse al juguete maldito.', 1, 'peli_68329b0d71946.jpg');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `Peliculas`
--
ALTER TABLE `Peliculas`
  ADD PRIMARY KEY (`id_pelicula`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `Peliculas`
--
ALTER TABLE `Peliculas`
  MODIFY `id_pelicula` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
