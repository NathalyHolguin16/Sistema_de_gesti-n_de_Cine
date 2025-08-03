-- PostgreSQL Migration Script
-- Migrated from MySQL database: btcq1ilgyasmbpre8a0s
-- Date: August 2, 2025

-- Drop existing tables if they exist (in correct order due to foreign keys)
DROP TABLE IF EXISTS BitacoraClientes CASCADE;
DROP TABLE IF EXISTS BitacoraEmpleados CASCADE;
DROP TABLE IF EXISTS Entradas CASCADE;
DROP TABLE IF EXISTS Funciones CASCADE;
DROP TABLE IF EXISTS Peliculas CASCADE;
DROP TABLE IF EXISTS Clientes CASCADE;
DROP TABLE IF EXISTS Empleados CASCADE;

-- Create enum types for PostgreSQL
CREATE TYPE rol_enum AS ENUM ('Administrador', 'Empleado');

-- --------------------------------------------------------
--
-- Table structure for table "Clientes"
--

CREATE TABLE Clientes (
    id_cliente SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) UNIQUE,
    telefono VARCHAR(20),
    contrasena VARCHAR(255) NOT NULL
);

-- --------------------------------------------------------
--
-- Table structure for table "Empleados"
--

CREATE TABLE Empleados (
    id_empleado SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    cargo VARCHAR(50),
    rol rol_enum NOT NULL DEFAULT 'Empleado',
    usuario VARCHAR(50) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL
);

-- --------------------------------------------------------
--
-- Table structure for table "Peliculas"
--

CREATE TABLE Peliculas (
    id_pelicula SERIAL PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    duracion_minutos INTEGER NOT NULL,
    clasificacion VARCHAR(10),
    genero VARCHAR(50),
    sinopsis TEXT,
    estado BOOLEAN DEFAULT true,
    imagen VARCHAR(255) NOT NULL
);

-- --------------------------------------------------------
--
-- Table structure for table "Funciones"
--

CREATE TABLE Funciones (
    id_funcion SERIAL PRIMARY KEY,
    id_pelicula INTEGER NOT NULL,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    precio DECIMAL(6,2) NOT NULL,
    FOREIGN KEY (id_pelicula) REFERENCES Peliculas(id_pelicula)
);

-- --------------------------------------------------------
--
-- Table structure for table "Entradas"
--

CREATE TABLE Entradas (
    id_entrada SERIAL PRIMARY KEY,
    id_funcion INTEGER NOT NULL,
    id_cliente INTEGER,
    cantidad INTEGER NOT NULL,
    asientos VARCHAR(255) NOT NULL,
    fecha_compra TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_pagado DECIMAL(8,2) NOT NULL,
    FOREIGN KEY (id_funcion) REFERENCES Funciones(id_funcion),
    FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente)
);

-- --------------------------------------------------------
--
-- Table structure for table "BitacoraClientes"
--

CREATE TABLE BitacoraClientes (
    id_bitacora SERIAL PRIMARY KEY,
    id_cliente INTEGER NOT NULL,
    accion VARCHAR(255) NOT NULL,
    detalles TEXT,
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cliente) REFERENCES Clientes(id_cliente)
);

-- --------------------------------------------------------
--
-- Table structure for table "BitacoraEmpleados"
--

CREATE TABLE BitacoraEmpleados (
    id_bitacora SERIAL PRIMARY KEY,
    id_empleado INTEGER NOT NULL,
    accion VARCHAR(255) NOT NULL,
    detalles TEXT,
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_empleado) REFERENCES Empleados(id_empleado)
);

-- --------------------------------------------------------
-- Data insertion
-- --------------------------------------------------------

--
-- Data for table "Clientes"
--

INSERT INTO Clientes (id_cliente, nombre, correo, telefono, contrasena) VALUES
(3, 'juan ', 'juan@gmail.com', '0987654321', '$2y$10$IAI9QyZf4hcNDImwOoTONub0/1/YrC4eVg6DFIBKLKwrsg5iWz/qK'),
(9, 'Mario', 'mario@gmail.com', '0987654321', '$2y$10$4PWaVcrRhAKTHkvLmoTyBetvnL.HQ5dDpCYtXuWU0MmNjdvdmvhPW');

--
-- Data for table "Empleados"
--

INSERT INTO Empleados (id_empleado, nombre, cargo, rol, usuario, contrasena) VALUES
(1, 'lizzardi', 'Gerente', 'Administrador', 'lizzardi_admin', '$2y$10$3SDNQDSooDmzsYTaG7vNMe9HxbNgcH6BeLuIFV4s6TfITzEeBaHOG'),
(3, 'Maria', 'Empleada', 'Empleado', 'Maria@gmail.com', '$2y$10$egn2taaTzZkZMiRqTfO5CeQSvHBGF942RGw1pdUMe/8ijNQiMi/Gu'),
(5, 'luis', 'empleado', 'Empleado', 'luis', '$2y$10$/9kjA.O21a3JJWfH84USO./kIPtKIFK4jGw1QtWs.65nzKPK/T2CS');

--
-- Data for table "Peliculas"
--

INSERT INTO Peliculas (id_pelicula, titulo, duracion_minutos, clasificacion, genero, sinopsis, estado, imagen) VALUES
(2, 'Los tipos malos', 100, 'todo publi', 'animacion', 'El señor Lobo, el señor Serpiente, el señor Piraña, el señor Tiburón y la señorita Tarántula tienen que fingir que se han convertido en chicos buenos para evitar ir a la cárcel. Conseguirlo se convierte en el mayor reto de sus carreras delictivas.', true, 'peli_683293c9484d4.jpg'),
(3, 'Free Guy- tomando el control', 110, '+12 años', 'comedia', 'El cajero de un banco descubre que en realidad es un personaje secundario dentro de un videojuego violento.', true, 'peli_683295141a564.jpg'),
(4, 'Sueño de fuga', 140, '+12 años', 'Drama', 'Un hombre inocente es enviado a una corrupta penitenciaria de Maine en 1947 y sentenciado a dos cadenas perpetuas por un doble asesinato.', true, 'peli_6832963da7d2e.jpg'),
(5, 'El gran showman', 105, 'todo publi', 'musical', 'Inspirado en la imaginación de PT Barnum, The Greatest Showman es un musical original que celebra el nacimiento del mundo del espectáculo y cuenta la historia de un visionario que surgió de la nada para crear un espectáculo que se convirtió en una sensación mundial.', true, 'peli_683297cb5177e.jpg'),
(6, 'El Mono', 98, '+18 años', 'Terror', 'Dos hermanos gemelos encuentran un misterioso mono de cuerda y una serie de muertes atroces separan a su familia. Veinticinco años más tarde, el mono reanuda su macabra trayectoria, obligando a los hermanos separados a enfrentarse al juguete maldito.', true, 'peli_68329b0d71946.jpg'),
(7, 'Avatar', 162, '+ 13 años', 'Aventura', 'En un exuberante planeta llamado Pandora viven los Na''vi, seres que aparentan ser primitivos pero que en realidad son muy evolucionados. Debido a que el ambiente de Pandora es venenoso, los híbridos humanos/Na''vi, llamados Avatares, están relacionados con las mentes humanas, lo que les permite moverse libremente por Pandora. Jake Sully, un exinfante de marina paralítico se transforma a través de un Avatar, y se enamora de una mujer Na''vi.', true, 'peli_6834fd21b9b31.jpeg'),
(8, 'Blade Runner 2049', 163, '+16 años', 'Accion', 'En el año 2049 el oficial K, un nuevo replicante de la policía de Los Ángeles, emprende la búsqueda del replicante Rick Deckard, desaparecido 30 años antes. K piensa que en Deckard reside la clave que podría permitir salvar a la sociedad del caos en el que está inmersa.', true, 'peli_6834fdfabd3d2.jpg'),
(9, 'Tron Ares', 160, '+16 años', 'Ciencia Ficción', 'Tron: Ares es una próxima película estadounidense de ciencia ficción producida por Walt Disney Pictures y distribuida por Walt Disney Studios Motion Pictures. Sirve como secuela independiente de Tron y Tron: Legacy.', true, 'peli_6834fecb1d165.jpg'),
(10, 'Bastardos sin gloria', 150, '+18 años', 'Belico', 'Es el primer año de la ocupación alemana de Francia. El oficial aliado, teniente Aldo Raine, ensambla un equipo de soldados judíos para cometer actos violentos en contra de los nazis, incluyendo la toma de cabelleras. Él y sus hombres unen fuerzas con Bridget von Hammersmark, una actriz alemana y agente encubierto, para derrocar a los líderes del Tercer Reich. Sus destinos convergen con la dueña de teatro Shosanna Dreyfus, quien busca vengar la ejecución de su familia.', true, 'peli_6834ff57355d2.jpg'),
(12, 'Interestellar', 160, '+13 años', 'Ciencia Ficcion ', 'Gracias a un descubrimiento, un grupo de científicos y exploradores, encabezados por Cooper, se embarcan en un viaje espacial para encontrar un lugar con las condiciones necesarias para reemplazar a la Tierra y comenzar una nueva vida allí.', true, 'peli_68407076c5940.jpg'),
(13, 'Misión imposible: sentencia mortal', 163, '+13 años', 'Accion ', 'Ethan debe detener a una inteligencia artificial que todas las potencias mundiales codician, la cual se ha vuelto tan poderosa que se rebeló contra sus creadores y ahora es una amenaza en sí misma.', true, 'peli_684b8e7ba73da.jpg'),
(14, 'Lilo y Stitch', 108, 'todo publi', 'Ciencia ficcion', 'Lilo & Stitch es una película de ciencia ficción y comedia dramática estadounidense de 2025, dirigida por Dean Fleischer-Camp, escrita por Chris Kekaniokalani Bright y Mike Van Waes, y producida por Dan Lin y Jonathan Eirich.', true, 'peli_684b8fd5a18f9.jpg'),
(15, 'Pecadores', 137, '+16 años', 'Acción', 'Con el objetivo de dejar atrás sus turbulentas vidas, dos hermanos gemelos regresan a su ciudad natal para comenzar de nuevo. Una vez allí, descubrirán que un mal espera al acecho para recibirlos.', true, 'peli_6851c038a36b4.jpg');

--
-- Data for table "Funciones"
--

INSERT INTO Funciones (id_funcion, id_pelicula, fecha, hora_inicio, precio) VALUES
(1, 12, '2025-06-11', '12:00:00', 8.00),
(2, 12, '2025-06-11', '15:00:00', 8.00),
(3, 12, '2025-06-11', '18:00:00', 8.00),
(4, 12, '2025-06-12', '12:00:00', 8.00),
(5, 9, '2025-06-20', '12:00:00', 8.00),
(6, 9, '2025-06-20', '16:00:00', 8.00),
(7, 14, '2025-06-16', '13:00:00', 9.00),
(8, 13, '2025-06-18', '14:00:00', 8.00),
(9, 10, '2025-06-17', '16:00:00', 7.00),
(10, 9, '2025-06-25', '14:00:00', 10.00),
(11, 9, '2025-06-26', '16:00:00', 8.00),
(12, 6, '2025-06-18', '20:00:00', 5.00),
(13, 14, '2025-06-19', '19:00:00', 9.00);

--
-- Data for table "Entradas"
--

INSERT INTO Entradas (id_entrada, id_funcion, id_cliente, cantidad, asientos, fecha_compra, total_pagado) VALUES
(53, 9, 9, 3, 'E10,E11,E12', '2025-06-17 15:20:40', 21.00),
(54, 9, NULL, 3, ',,', '2025-06-17 15:20:41', 21.00),
(55, 9, 9, 3, 'D12,D13,D14', '2025-06-17 15:29:25', 21.00),
(56, 10, 9, 3, 'D12,D13,D14', '2025-06-17 15:34:19', 30.00),
(57, 10, NULL, 3, ',,', '2025-06-17 15:34:20', 30.00),
(58, 10, 9, 3, 'E13,E14,E15', '2025-06-17 15:36:52', 30.00),
(59, 9, 9, 3, 'D16,D17,D18', '2025-06-17 19:13:06', 21.00),
(60, 9, 9, 3, 'C12,C13,C14', '2025-06-17 19:26:53', 21.00);

--
-- Data for table "BitacoraClientes"
--

INSERT INTO BitacoraClientes (id_bitacora, id_cliente, accion, detalles, fecha_hora) VALUES
(7, 9, 'Reserva realizada', 'El cliente con ID 9 reservó los asientos: D10,D11,D12 para la función 7.', '2025-06-16 23:41:28'),
(8, 9, 'Inicio de sesión', 'El cliente con ID 9 inició sesión.', '2025-06-16 23:46:46'),
(9, 9, 'Reserva realizada', 'El cliente con ID 9 reservó los asientos: D13,D14,D15,D16,D17,D18 para la función 4.', '2025-06-16 23:46:58'),
(10, 9, 'Inicio de sesión', 'El cliente con ID 9 inició sesión.', '2025-06-16 23:54:47'),
(11, 9, 'Inicio de sesión', 'El cliente con ID 9 inició sesión.', '2025-06-17 00:03:43'),
(12, 9, 'Cierre de sesión', 'El cliente con ID 9 cerró sesión.', '2025-06-17 00:03:45'),
(13, 9, 'Reserva realizada', 'El cliente con ID 9 reservó los asientos: C12,C13,C14,C15 para la función 7.', '2025-06-17 00:03:53'),
(14, 9, 'Inicio de sesión', 'El cliente con ID 9 inició sesión.', '2025-06-17 00:36:03'),
(15, 9, 'Reserva realizada', 'El cliente con ID 9 reservó los asientos: C13,C14,C15 para la función 2.', '2025-06-17 00:36:12'),
(16, 9, 'Inicio de sesión', 'El cliente con ID 9 inició sesión.', '2025-06-17 01:07:07'),
(17, 9, 'Cierre de sesión', 'El cliente con ID 9 cerró sesión.', '2025-06-17 01:07:09'),
(18, 9, 'Inicio de sesión', 'El cliente con ID 9 inició sesión.', '2025-06-17 02:44:43'),
(19, 9, 'Cierre de sesión', 'El cliente con ID 9 cerró sesión.', '2025-06-17 02:44:45'),
(20, 9, 'Reserva realizada', 'El cliente con ID 9 reservó los asientos: C13,C14,C15,C16,C17,C18 para la función 4.', '2025-06-17 02:45:01'),
(21, 9, 'Reserva realizada', 'El cliente con ID 9 reservó los asientos: C11,C12,D11,D12 para la función 4.', '2025-06-17 02:45:32'),
(22, 9, 'Reserva realizada', 'El cliente con ID 9 reservó los asientos: E11,E12,E13,E14 para la función 4.', '2025-06-17 02:48:33'),
(23, 9, 'Inicio de sesión', 'El cliente con ID 9 inició sesión.', '2025-06-17 02:51:33'),
(24, 9, 'Cierre de sesión', 'El cliente con ID 9 cerró sesión.', '2025-06-17 02:51:34'),
(25, 9, 'Reserva realizada', 'El cliente con ID 9 reservó los asientos: C12,C13,D12,D13 para la función 4.', '2025-06-17 02:51:43'),
(26, 9, 'Inicio de sesión', 'El cliente con ID 9 inició sesión.', '2025-06-17 03:14:15'),
(27, 9, 'Cierre de sesión', 'El cliente con ID 9 cerró sesión.', '2025-06-17 03:14:17'),
(28, 9, 'Inicio de sesión', 'El cliente con ID 9 inició sesión.', '2025-06-17 03:22:38'),
(29, 9, 'Cierre de sesión', 'El cliente con ID 9 cerró sesión.', '2025-06-17 03:22:40'),
(30, 9, 'Inicio de sesión', 'El cliente con ID 9 inició sesión.', '2025-06-17 03:29:30'),
(31, 9, 'Cierre de sesión', 'El cliente con ID 9 cerró sesión.', '2025-06-17 03:29:49'),
(32, 9, 'Reserva realizada', 'El cliente con ID 9 reservó los asientos: E11,E12,E13,E14 para la función 3.', '2025-06-17 04:06:35'),
(33, 9, 'Reserva realizada', 'El cliente con ID 9 reservó los asientos: D11,D12,D13,D14 para la función 3.', '2025-06-17 04:07:48'),
(34, 9, 'Inicio de sesión', 'El cliente con ID 9 inició sesión.', '2025-06-17 15:20:30'),
(35, 9, 'Cierre de sesión', 'El cliente con ID 9 cerró sesión.', '2025-06-17 15:20:32'),
(36, 9, 'Reserva realizada', 'El cliente con ID 9 reservó los asientos: E10,E11,E12 para la función 9.', '2025-06-17 15:20:40'),
(37, 9, 'Inicio de sesión', 'El cliente con ID 9 inició sesión.', '2025-06-17 15:29:11'),
(38, 9, 'Cierre de sesión', 'El cliente con ID 9 cerró sesión.', '2025-06-17 15:29:13'),
(39, 9, 'Reserva realizada', 'El cliente con ID 9 reservó los asientos: D12,D13,D14 para la función 9.', '2025-06-17 15:29:25'),
(40, 9, 'Reserva realizada', 'El cliente con ID 9 reservó los asientos: D12,D13,D14 para la función 10.', '2025-06-17 15:34:19'),
(41, 9, 'Reserva realizada', 'El cliente con ID 9 reservó los asientos: E13,E14,E15 para la función 10.', '2025-06-17 15:36:52'),
(42, 9, 'Inicio de sesión', 'El cliente con ID 9 inició sesión.', '2025-06-17 19:12:19'),
(43, 9, 'Cierre de sesión', 'El cliente con ID 9 cerró sesión.', '2025-06-17 19:12:23'),
(44, 9, 'Reserva realizada', 'El cliente con ID 9 reservó los asientos: D16,D17,D18 para la función 9.', '2025-06-17 19:13:07'),
(45, 9, 'Inicio de sesión', 'El cliente con ID 9 inició sesión.', '2025-06-17 19:26:29'),
(46, 9, 'Cierre de sesión', 'El cliente con ID 9 cerró sesión.', '2025-06-17 19:26:34'),
(47, 9, 'Reserva realizada', 'El cliente con ID 9 reservó los asientos: C12,C13,C14 para la función 9.', '2025-06-17 19:26:54');

--
-- Data for table "BitacoraEmpleados"
--

INSERT INTO BitacoraEmpleados (id_bitacora, id_empleado, accion, detalles, fecha_hora) VALUES
(21, 1, 'Inicio de sesión', 'El empleado lizzardi inició sesión.', '2025-06-16 23:21:37'),
(22, 1, 'Agregar empleado', 'Se agregó el empleado "Maria" con cargo "Empleada".', '2025-06-16 23:22:18'),
(23, 1, 'Editar empleado', 'Se editó el empleado con ID 1 y nombre "lizzardi".', '2025-06-16 23:26:41'),
(24, 1, 'Editar empleado', 'Se editó el empleado con ID 3 y nombre "Maria".', '2025-06-16 23:27:15'),
(25, 1, 'Inicio de sesión', 'El empleado lizzardi inició sesión.', '2025-06-16 23:55:10'),
(26, 1, 'Inicio de sesión', 'El empleado lizzardi inició sesión.', '2025-06-17 00:14:42'),
(27, 1, 'Cierre de sesión', 'El empleado con ID 1 cerró sesión.', '2025-06-17 00:14:44'),
(28, 1, 'Editar empleado', 'Se editó el empleado con ID 3 y nombre "Marta".', '2025-06-17 00:15:13'),
(29, 1, 'Editar Película', 'ID: 9, Título: Tron 4, Duración: 160', '2025-06-17 00:15:34'),
(30, 1, 'Agregar Funcion', 'ID Pelicula: 9, Fecha: 2025-06-26, Hora: 16:00', '2025-06-17 00:15:59'),
(31, 1, 'Inicio de sesión', 'El empleado lizzardi inició sesión.', '2025-06-17 01:53:14'),
(32, 1, 'Cierre de sesión', 'El empleado con ID 1 cerró sesión.', '2025-06-17 01:53:16'),
(33, 1, 'Inicio de sesión', 'El empleado lizzardi inició sesión.', '2025-06-17 02:03:18'),
(34, 1, 'Cierre de sesión', 'El empleado con ID 1 cerró sesión.', '2025-06-17 02:03:21'),
(35, 1, 'Cierre de sesión', 'El empleado con ID 1 cerró sesión.', '2025-06-17 02:44:45'),
(36, 1, 'Inicio de sesión', 'El empleado lizzardi inició sesión.', '2025-06-17 15:21:25'),
(37, 1, 'Cierre de sesión', 'El empleado con ID 1 cerró sesión.', '2025-06-17 15:21:27'),
(38, 1, 'Editar Película', 'ID: 9, Título: Tron Ares, Duración: 160', '2025-06-17 15:26:28'),
(39, 1, 'Agregar Funcion', 'ID Pelicula: 6, Fecha: 2025-06-18, Hora: 20:00', '2025-06-17 15:26:50'),
(40, 1, 'Editar empleado', 'Se editó el empleado con ID 3 y nombre "Maria".', '2025-06-17 15:27:23'),
(41, 1, 'Inicio de sesión', 'El empleado lizzardi inició sesión.', '2025-06-17 15:38:27'),
(42, 1, 'Cierre de sesión', 'El empleado con ID 1 cerró sesión.', '2025-06-17 15:38:29'),
(43, 1, 'Agregar Película', 'Título: Pecadores, Duración: 137, Clasificación: +16 años', '2025-06-17 15:43:17'),
(44, 1, 'Inicio de sesión', 'El empleado lizzardi inició sesión.', '2025-06-17 16:11:01'),
(45, 1, 'Cierre de sesión', 'El empleado con ID 1 cerró sesión.', '2025-06-17 16:11:03'),
(46, 1, 'Inicio de sesión', 'El empleado lizzardi inició sesión.', '2025-06-17 19:15:59'),
(47, 1, 'Cierre de sesión', 'El empleado con ID 1 cerró sesión.', '2025-06-17 19:16:02'),
(48, 1, 'Agregar Funcion', 'ID Pelicula: 14, Fecha: 2025-06-19, Hora: 19:00', '2025-06-17 19:17:58'),
(49, 1, 'Inicio de sesión', 'El empleado lizzardi inició sesión.', '2025-06-17 19:20:19'),
(50, 1, 'Cierre de sesión', 'El empleado con ID 1 cerró sesión.', '2025-06-17 19:20:23'),
(51, 1, 'Editar Película', 'ID: 15, Título: Pecadores, Duración: 137', '2025-06-17 19:20:49'),
(52, 1, 'Editar Película', 'ID: 15, Título: Pecadores, Duración: 137', '2025-06-17 19:21:28'),
(53, 1, 'Inicio de sesión', 'El empleado lizzardi inició sesión.', '2025-06-17 19:27:20'),
(54, 1, 'Cierre de sesión', 'El empleado con ID 1 cerró sesión.', '2025-06-17 19:27:25'),
(55, 1, 'Agregar Película', 'Título: gestion de base de datos , Duración: 45, Clasificación: todo publi', '2025-06-17 19:29:25'),
(56, 1, 'Editar Película', 'ID: 16, Título: gestion de base de datos , Duración: 60', '2025-06-17 19:29:52'),
(57, 1, 'Agregar empleado', 'Se agregó el empleado "juan" con cargo "ayudante".', '2025-06-17 19:33:08'),
(58, 1, 'Editar empleado', 'Se editó el empleado con ID 4 y nombre "enrique".', '2025-06-17 19:33:34'),
(59, 1, 'Inicio de sesión', 'El empleado lizzardi inició sesión.', '2025-06-17 19:43:11'),
(60, 1, 'Cierre de sesión', 'El empleado con ID 1 cerró sesión.', '2025-06-17 19:43:14'),
(61, 1, 'Agregar empleado', 'Se agregó el empleado "luis" con cargo "empleado".', '2025-06-17 19:44:47'),
(62, 5, 'Inicio de sesión', 'El empleado luis inició sesión.', '2025-06-17 19:46:28'),
(63, 5, 'Cierre de sesión', 'El empleado con ID 5 cerró sesión.', '2025-06-17 19:46:33');

-- --------------------------------------------------------
-- Update sequences to match the current maximum IDs
-- --------------------------------------------------------

-- Update sequence for Clientes
SELECT setval('clientes_id_cliente_seq', (SELECT COALESCE(MAX(id_cliente), 1) FROM Clientes));

-- Update sequence for Empleados
SELECT setval('empleados_id_empleado_seq', (SELECT COALESCE(MAX(id_empleado), 1) FROM Empleados));

-- Update sequence for Peliculas
SELECT setval('peliculas_id_pelicula_seq', (SELECT COALESCE(MAX(id_pelicula), 1) FROM Peliculas));

-- Update sequence for Funciones
SELECT setval('funciones_id_funcion_seq', (SELECT COALESCE(MAX(id_funcion), 1) FROM Funciones));

-- Update sequence for Entradas
SELECT setval('entradas_id_entrada_seq', (SELECT COALESCE(MAX(id_entrada), 1) FROM Entradas));

-- Update sequence for BitacoraClientes
SELECT setval('bitacoraclientes_id_bitacora_seq', (SELECT COALESCE(MAX(id_bitacora), 1) FROM BitacoraClientes));

-- Update sequence for BitacoraEmpleados
SELECT setval('bitacoraempleados_id_bitacora_seq', (SELECT COALESCE(MAX(id_bitacora), 1) FROM BitacoraEmpleados));

-- --------------------------------------------------------
-- Create indexes for better performance (optional)
-- --------------------------------------------------------

CREATE INDEX idx_clientes_correo ON Clientes(correo);
CREATE INDEX idx_empleados_usuario ON Empleados(usuario);
CREATE INDEX idx_funciones_pelicula ON Funciones(id_pelicula);
CREATE INDEX idx_funciones_fecha ON Funciones(fecha);
CREATE INDEX idx_entradas_funcion ON Entradas(id_funcion);
CREATE INDEX idx_entradas_cliente ON Entradas(id_cliente);
CREATE INDEX idx_bitacora_clientes_cliente ON BitacoraClientes(id_cliente);
CREATE INDEX idx_bitacora_clientes_fecha ON BitacoraClientes(fecha_hora);
CREATE INDEX idx_bitacora_empleados_empleado ON BitacoraEmpleados(id_empleado);
CREATE INDEX idx_bitacora_empleados_fecha ON BitacoraEmpleados(fecha_hora);

-- Migration completed successfully!
