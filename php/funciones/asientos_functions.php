<?php
require_once("../conexion.php");

/**
 * Calcula y formatea los asientos asociados a una entrada
 * @param int $entrada_id ID de la entrada
 * @return string Cadena formateada con los asientos (ej: "A1,A2,B1")
 */
function calculate_asientos_from_asiento($entrada_id) {
    global $conn;
    
    try {
        $query = "SELECT fila, numero 
                  FROM public.asiento 
                  WHERE id_entrada = ? 
                  ORDER BY fila, numero";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$entrada_id]);
        $asientos = $stmt->fetchAll();
        
        if (empty($asientos)) {
            return '';
        }
        
        $asientos_formateados = array_map(function($asiento) {
            return $asiento['fila'] . $asiento['numero'];
        }, $asientos);
        
        return implode(',', $asientos_formateados);
    } catch (PDOException $e) {
        error_log("Error al calcular asientos: " . $e->getMessage());
        return '';
    }
}

/**
 * Verifica si los asientos están disponibles para una función
 * @param int $funcion_id ID de la función
 * @param array $asientos Array de asientos a verificar (ej: ['A1', 'A2'])
 * @return bool true si los asientos están disponibles
 */
function verificar_disponibilidad_asientos($funcion_id, $asientos) {
    global $conn;
    
    try {
        $query = "SELECT COUNT(*) as ocupados
                  FROM public.asiento a
                  JOIN public.entradas e ON a.id_entrada = e.id_entrada
                  WHERE e.id_funcion = ? 
                  AND CONCAT(a.fila, a.numero::text) = ANY(?)";
                  
        $stmt = $conn->prepare($query);
        $stmt->execute([$funcion_id, $asientos]);
        $result = $stmt->fetch();
        
        return $result['ocupados'] == 0;
    } catch (PDOException $e) {
        error_log("Error al verificar disponibilidad: " . $e->getMessage());
        return false;
    }
}

/**
 * Sincroniza los asientos en la base de datos
 * @return bool true si la sincronización fue exitosa
 */
function sync_asientos($funcion_id = null) {
    global $conn;
    
    try {
        // Llamar al procedimiento almacenado si se proporciona una función específica
        if ($funcion_id) {
            $stmt = $conn->prepare("CALL actualizar_disponibilidad_sala(?)");
            $stmt->execute([$funcion_id]);
        }
        
        // Llamar a la función general de sincronización
        $query = "SELECT public.sync_asientos()";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        error_log("Error al sincronizar asientos: " . $e->getMessage());
        return false;
    }
}
?>
