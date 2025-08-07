<?php
// Incluir los archivos de funciones
require_once("funciones/asientos_functions.php");
require_once("funciones/auth_functions.php");
require_once("funciones/trigger_functions.php");

header('Content-Type: application/json');

// Inicializar el manejador de triggers
$triggerManager = new TriggerManager($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Verificar datos necesarios
    if (!isset($data['funcion_id']) || !isset($data['asientos']) || !isset($data['id_cliente'])) {
        echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
        exit;
    }

    $funcion_id = (int)$data['funcion_id'];
    $asientos = $data['asientos']; // Array de asientos (ej: ['A1', 'A2'])
    $id_cliente = (int)$data['id_cliente'];

    // Verificar si el cliente existe y obtener su información
    $cliente = get_client_info($id_cliente);
    if (!$cliente) {
        echo json_encode(['success' => false, 'error' => 'Cliente no encontrado']);
        exit;
    }

    // Verificar disponibilidad de asientos
    if (!verificar_disponibilidad_asientos($funcion_id, $asientos)) {
        echo json_encode(['success' => false, 'error' => 'Algunos asientos no están disponibles']);
        exit;
    }

    try {

        // Después de modificar asientos
        $triggerManager->afterAsientoChange('UPDATE', [
            'id_asiento' => $asiento_id,
            'estado' => 'ocupado' 
        ]);

        // Después de crear la entrada, obtener su ID y los asientos asociados
        $entrada_id = $conn->lastInsertId();
        
        // Obtener los asientos formateados
        $asientos_str = calculate_asientos_from_asiento($entrada_id);

        // Sincronizar asientos
        sync_asientos();

        echo json_encode([
            'success' => true,
            'message' => 'Reserva realizada con éxito', 
            'data' => [
                'entrada_id' => $entrada_id,
                'asientos' => $asientos_str,
                'cliente' => $cliente['nombre']
            ]
        ]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error al procesar la reserva']);
        error_log($e->getMessage());
    }
}
?>
