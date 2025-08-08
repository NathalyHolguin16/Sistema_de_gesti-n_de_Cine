-- Procedimientos almacenados para el sistema de cine

-- 1. Procedimiento para crear una nueva función de cine
CREATE OR REPLACE PROCEDURE crear_funcion(
    p_id_pelicula INT,
    p_fecha DATE,
    p_hora_inicio TIME,
    p_precio DECIMAL
)
LANGUAGE plpgsql
AS $$
BEGIN
    -- Verificar que la película existe
    IF NOT EXISTS (SELECT 1 FROM Peliculas WHERE id_pelicula = p_id_pelicula) THEN
        RAISE EXCEPTION 'La película no existe';
    END IF;

    -- Insertar la nueva función
    INSERT INTO Funciones (id_pelicula, fecha, hora_inicio, precio)
    VALUES (p_id_pelicula, p_fecha, p_hora_inicio, p_precio);

    -- Registrar en bitácora
    INSERT INTO BitacoraEmpleados (id_empleado, accion, detalles)
    SELECT current_setting('app.user_id')::integer, 
           'Crear Función',
           format('Película: %s, Fecha: %s, Hora: %s', p_id_pelicula, p_fecha, p_hora_inicio);
END;
$$;

-- 2. Procedimiento para procesar una reserva completa
CREATE OR REPLACE PROCEDURE procesar_reserva(
    p_id_funcion INT,
    p_id_cliente INT,
    p_asientos TEXT[],
    p_cantidad INT,
    p_total DECIMAL
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_id_entrada INT;
BEGIN
    -- Verificar disponibilidad de asientos
    IF EXISTS (
        SELECT 1 FROM asiento a
        JOIN Entradas e ON a.id_entrada = e.id_entrada
        WHERE e.id_funcion = p_id_funcion
        AND CONCAT(a.fila, a.numero::text) = ANY(p_asientos)
    ) THEN
        RAISE EXCEPTION 'Algunos asientos ya están ocupados';
    END IF;

    -- Crear la entrada
    INSERT INTO Entradas (id_funcion, id_cliente, cantidad, total_pagado)
    VALUES (p_id_funcion, p_id_cliente, p_cantidad, p_total)
    RETURNING id_entrada INTO v_id_entrada;

    -- Registrar los asientos
    INSERT INTO asiento (id_entrada, fila, numero)
    SELECT v_id_entrada, 
           SUBSTRING(asiento, 1, 1), 
           CAST(SUBSTRING(asiento, 2) AS INTEGER)
    FROM unnest(p_asientos) AS asiento;

    -- Registrar en bitácora
    INSERT INTO BitacoraClientes (id_cliente, accion, detalles)
    VALUES (p_id_cliente, 'Reserva realizada', 
            format('Reserva de %s asientos para función %s', p_cantidad, p_id_funcion));
END;
$$;

-- 3. Procedimiento para generar reportes de ventas
CREATE OR REPLACE PROCEDURE generar_reporte_ventas(
    p_fecha_inicio DATE,
    p_fecha_fin DATE
)
LANGUAGE plpgsql
AS $$
BEGIN
    -- Crear tabla temporal para el reporte
    CREATE TEMP TABLE IF NOT EXISTS reporte_ventas AS
    SELECT 
        p.titulo,
        COUNT(e.id_entrada) as total_entradas,
        SUM(e.total_pagado) as ingresos_totales,
        AVG(e.total_pagado) as precio_promedio,
        COUNT(DISTINCT e.id_cliente) as total_clientes
    FROM Peliculas p
    JOIN Funciones f ON p.id_pelicula = f.id_pelicula
    JOIN Entradas e ON f.id_funcion = e.id_funcion
    WHERE f.fecha BETWEEN p_fecha_inicio AND p_fecha_fin
    GROUP BY p.titulo
    ORDER BY ingresos_totales DESC;

    -- Agregar estadísticas generales
    INSERT INTO reporte_ventas
    SELECT 
        'TOTAL',
        COUNT(*),
        SUM(total_pagado),
        AVG(total_pagado),
        COUNT(DISTINCT id_cliente)
    FROM Entradas e
    JOIN Funciones f ON e.id_funcion = f.id_funcion
    WHERE f.fecha BETWEEN p_fecha_inicio AND p_fecha_fin;
END;
$$;

-- 4. Procedimiento para gestionar disponibilidad de asientos
CREATE OR REPLACE PROCEDURE actualizar_disponibilidad_sala(
    p_id_funcion INT
)
LANGUAGE plpgsql
AS $$
DECLARE
    v_capacidad INT;
    v_ocupados INT;
BEGIN
    -- Obtener capacidad de la sala
    SELECT s.capacidad INTO v_capacidad
    FROM Funciones f
    JOIN Salas s ON f.id_sala = s.id_sala
    WHERE f.id_funcion = p_id_funcion;

    -- Contar asientos ocupados
    SELECT COUNT(*) INTO v_ocupados
    FROM Entradas e
    JOIN asiento a ON e.id_entrada = a.id_entrada
    WHERE e.id_funcion = p_id_funcion;

    -- Actualizar estado de asientos
    UPDATE asiento
    SET estado = 'ocupado'
    WHERE id_entrada IN (
        SELECT id_entrada 
        FROM Entradas 
        WHERE id_funcion = p_id_funcion
    );
    
    -- Registrar estado en bitácora
    INSERT INTO bitacora_sistema (accion, detalles)
    VALUES ('Actualización de asientos', 
            format('Función %s: %s de %s asientos ocupados', 
                   p_id_funcion, v_ocupados, v_capacidad));
END;
$$
