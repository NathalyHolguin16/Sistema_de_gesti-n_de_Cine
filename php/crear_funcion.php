<?php
require_once("conexion.php");
require_once("funciones/auth_functions.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['id_pelicula']) || !isset($data['fecha']) || 
        !isset($data['hora_inicio']) || !isset($data['precio'])) {
        echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
        exit;
    }

    try {
        // Llamar al procedimiento almacenado
        $stmt = $conn->prepare("CALL crear_funcion(?, ?, ?, ?)");
        $stmt->execute([
            $data['id_pelicula'],
            $data['fecha'],
            $data['hora_inicio'],
            $data['precio']
        ]);

        echo json_encode(['success' => true, 'message' => 'Función creada exitosamente']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
