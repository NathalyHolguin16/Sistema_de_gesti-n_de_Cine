<?php
header('Content-Type: application/json');
require_once("conexion.php");

try {
    $id_funcion = $_GET['id_funcion'];
    $query = "SELECT asientos FROM Entradas WHERE id_funcion = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$id_funcion]);
    $result = $stmt->fetchAll();
    
    $ocupados = [];
    foreach ($result as $row) {
        $ocupados = array_merge($ocupados, explode(',', $row['asientos']));
    }
    echo json_encode($ocupados);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>