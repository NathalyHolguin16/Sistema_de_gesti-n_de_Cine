<?php
require_once("conexion.php");
require_once("funciones/auth_functions.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Obtener fechas del rango, por defecto último mes
    $fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');
    $fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-d', strtotime('-1 month'));

    try {
        // Llamar al procedimiento almacenado
        $stmt = $conn->prepare("CALL generar_reporte_ventas(?, ?)");
        $stmt->execute([$fecha_inicio, $fecha_fin]);

        // Obtener resultados de la tabla temporal
        $query = "SELECT * FROM reporte_ventas";
        $stmt = $conn->query($query);
        $reporte = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => $reporte,
            'rango' => [
                'inicio' => $fecha_inicio,
                'fin' => $fecha_fin
            ]
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
