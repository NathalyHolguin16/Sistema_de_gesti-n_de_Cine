<?php
require_once("../conexion.php");

/**
 * Clase para manejar los triggers del sistema
 */
class TriggerManager {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Trigger para hashear contraseña de cliente antes de insertar
     * @param array $data Datos del cliente
     * @return array Datos modificados
     */
    public function beforeInsertCliente($data) {
        if (isset($data['contrasena'])) {
            $data['contrasena'] = hash_cliente_password($data['contrasena']);
        }
        return $data;
    }

    /**
     * Trigger para hashear contraseña de cliente antes de actualizar
     * @param array $data Datos a actualizar
     * @return array Datos modificados
     */
    public function beforeUpdateCliente($data) {
        if (isset($data['contrasena'])) {
            $data['contrasena'] = hash_cliente_password($data['contrasena']);
        }
        return $data;
    }

    /**
     * Trigger para hashear contraseña de empleado antes de insertar
     * @param array $data Datos del empleado
     * @return array Datos modificados
     */
    public function beforeInsertEmpleado($data) {
        if (isset($data['contrasena'])) {
            $data['contrasena'] = hash_empleado_password($data['contrasena']);
        }
        return $data;
    }

    /**
     * Trigger para hashear contraseña de empleado antes de actualizar
     * @param array $data Datos a actualizar
     * @return array Datos modificados
     */
    public function beforeUpdateEmpleado($data) {
        if (isset($data['contrasena'])) {
            $data['contrasena'] = hash_empleado_password($data['contrasena']);
        }
        return $data;
    }

    /**
     * Trigger para sincronizar asientos después de cambios
     * @param string $operation Tipo de operación (INSERT, UPDATE, DELETE)
     * @param array $data Datos del asiento
     */
    public function afterAsientoChange($operation, $data = null) {
        try {
            // Llamar a la función de sincronización
            sync_asientos();
            
            // Registrar la operación en el log
            $this->logAsientoOperation($operation, $data);
            
            return true;
        } catch (Exception $e) {
            error_log("Error en trigger de asientos: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra las operaciones de asientos en el log
     * @param string $operation Tipo de operación
     * @param array $data Datos involucrados
     */
    private function logAsientoOperation($operation, $data) {
        $log_query = "INSERT INTO bitacora_asientos (operacion, detalles, fecha) VALUES (?, ?, CURRENT_TIMESTAMP)";
        try {
            $stmt = $this->conn->prepare($log_query);
            $detalles = json_encode($data);
            $stmt->execute([$operation, $detalles]);
        } catch (Exception $e) {
            error_log("Error al registrar en bitácora: " . $e->getMessage());
        }
    }
}

// Ejemplo de uso:
/*
$triggerManager = new TriggerManager($conn);

// Antes de insertar un cliente
$datos_cliente = $triggerManager->beforeInsertCliente([
    'nombre' => 'Juan',
    'contrasena' => '123456'
]);

// Después de modificar un asiento
$triggerManager->afterAsientoChange('UPDATE', ['id_asiento' => 1, 'estado' => 'ocupado']);
*/
?>
