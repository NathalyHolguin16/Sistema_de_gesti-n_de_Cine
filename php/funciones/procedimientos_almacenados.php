<?php
require_once("../conexion.php");

/**
 * Clase para manejar los procedimientos almacenados del sistema
 */
class ProcedimientosAlmacenados {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Crea una nueva función de cine
     * @param int $id_pelicula ID de la película
     * @param string $fecha Fecha de la función (YYYY-MM-DD)
     * @param string $hora_inicio Hora de inicio (HH:MM:SS)
     * @param float $precio Precio de la función
     * @return bool true si se creó correctamente
     */
    public function crearFuncion($id_pelicula, $fecha, $hora_inicio, $precio) {
        try {
            $stmt = $this->conn->prepare("CALL crear_funcion(?, ?, ?, ?)");
            $stmt->execute([$id_pelicula, $fecha, $hora_inicio, $precio]);
            return true;
        } catch (PDOException $e) {
            error_log("Error al crear función: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Procesa una reserva completa
     * @param int $funcion_id ID de la función
     * @param int $cliente_id ID del cliente
     * @param array $asientos Array de asientos
     * @param int $cantidad Cantidad de asientos
     * @param float $total Total a pagar
     * @return array|bool Array con datos de la reserva o false si hay error
     */
    public function procesarReserva($funcion_id, $cliente_id, $asientos, $cantidad, $total) {
        try {
            $stmt = $this->conn->prepare("CALL procesar_reserva(?, ?, ?, ?, ?)");
            $stmt->execute([$funcion_id, $cliente_id, $asientos, $cantidad, $total]);
            
            // Obtener el ID de la última entrada creada
            $entrada_id = $this->conn->lastInsertId();
            
            return [
                'success' => true,
                'entrada_id' => $entrada_id,
                'mensaje' => 'Reserva procesada correctamente'
            ];
        } catch (PDOException $e) {
            error_log("Error al procesar reserva: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Genera reporte de ventas
     * @param string $fecha_inicio Fecha inicial (YYYY-MM-DD)
     * @param string $fecha_fin Fecha final (YYYY-MM-DD)
     * @return array|bool Datos del reporte o false si hay error
     */
    public function generarReporteVentas($fecha_inicio, $fecha_fin) {
        try {
            // Ejecutar el procedimiento
            $stmt = $this->conn->prepare("CALL generar_reporte_ventas(?, ?)");
            $stmt->execute([$fecha_inicio, $fecha_fin]);

            // Obtener resultados de la tabla temporal
            $query = "SELECT * FROM reporte_ventas";
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al generar reporte: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza la disponibilidad de asientos de una sala
     * @param int $funcion_id ID de la función
     * @return bool true si se actualizó correctamente
     */
    public function actualizarDisponibilidadSala($funcion_id) {
        try {
            $stmt = $this->conn->prepare("CALL actualizar_disponibilidad_sala(?)");
            $stmt->execute([$funcion_id]);
            return true;
        } catch (PDOException $e) {
            error_log("Error al actualizar disponibilidad: " . $e->getMessage());
            return false;
        }
    }
}

// Ejemplo de uso:
/*
$procedimientos = new ProcedimientosAlmacenados($conn);

// Crear una función
$procedimientos->crearFuncion(1, '2025-08-15', '15:00:00', 10.50);

// Procesar una reserva
$procedimientos->procesarReserva(1, 1, ['A1', 'A2'], 2, 21.00);

// Generar reporte
$reporte = $procedimientos->generarReporteVentas('2025-08-01', '2025-08-31');

// Actualizar disponibilidad
$procedimientos->actualizarDisponibilidadSala(1);
*/
?>
