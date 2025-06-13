-- Tabla para registrar las acciones de los clientes
CREATE TABLE `BitacoraClientes` (
  `id_bitacora` INT NOT NULL AUTO_INCREMENT,
  `id_cliente` INT NOT NULL,
  `accion` VARCHAR(255) NOT NULL,
  `detalles` TEXT,
  `fecha_hora` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_bitacora`),
  FOREIGN KEY (`id_cliente`) REFERENCES `Clientes`(`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para registrar las acciones de los empleados
CREATE TABLE `BitacoraEmpleados` (
  `id_bitacora` INT NOT NULL AUTO_INCREMENT,
  `id_empleado` INT NOT NULL,
  `accion` VARCHAR(255) NOT NULL,
  `detalles` TEXT,
  `fecha_hora` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_bitacora`),
  FOREIGN KEY (`id_empleado`) REFERENCES `Empleados`(`id_empleado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
