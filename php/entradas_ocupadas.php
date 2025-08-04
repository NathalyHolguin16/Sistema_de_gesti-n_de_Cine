<?php
header('Content-Type: application/json');
require_once("conexion.php");

try {
    $id_funcion = $_GET['id_funcion'] ?? null;
    
    // Validar que el ID de función sea un número válido
    if (!$id_funcion || $id_funcion === 'null' || !is_numeric($id_funcion)) {
        echo json_encode([]);
        exit;
    }
    
    $id_funcion = (int)$id_funcion;
    
    $query = "SELECT asientos FROM entradas WHERE id_funcion = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$id_funcion]);
    $result = $stmt->fetchAll();
    
    $ocupados = [];
    foreach ($result as $row) {
        if (!empty($row['asientos'])) {
            $asientosArray = explode(',', $row['asientos']);
            // Limpiar espacios en blanco de cada asiento
            $asientosArray = array_map('trim', $asientosArray);
            $ocupados = array_merge($ocupados, $asientosArray);
        }
    }
    
    // Eliminar duplicados y reindexar
    $ocupados = array_values(array_unique($ocupados));
    
    echo json_encode($ocupados);
} catch (PDOException $e) {
    error_log("Error en entradas_ocupadas.php: " . $e->getMessage());
    // Devolver array vacío en caso de error para evitar problemas en el frontend
    echo json_encode([]);
}
?>