<?php
header('Content-Type: application/json');
require_once("conexion.php");
require_once("client_info.php");

$data = json_decode(file_get_contents("php://input"), true);

$id_funcion = $data['id_funcion'];
$asientos = $data['asientos'];
$cantidad = $data['cantidad'];
$total_pagado = $data['total_pagado'];
$id_cliente = isset($data['id_cliente']) ? $data['id_cliente'] : null;

try {
    // Verificar que los asientos no estén ocupados
    $query = "SELECT asientos FROM Entradas WHERE id_funcion = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$id_funcion]);
    $result = $stmt->fetchAll();
    
    $ocupados = [];
    foreach ($result as $row) {
        $ocupados = array_merge($ocupados, explode(',', $row['asientos']));
    }
    
    foreach ($asientos as $asiento) {
        if (in_array($asiento, $ocupados)) {
            echo json_encode(['success' => false, 'error' => "El asiento $asiento ya está ocupado."]);
            exit;
        }
    }

    $asientos_str = implode(',', $asientos);

    // Iniciar transacción
    $conn->beginTransaction();

    // Verificar que la reserva no exista antes de guardar
    $queryCheck = "SELECT id_entrada FROM Entradas WHERE id_funcion = ? AND id_cliente = ? AND asientos = ?";
    $stmtCheck = $conn->prepare($queryCheck);
    $stmtCheck->execute([$id_funcion, $id_cliente, $asientos_str]);
    $resultCheck = $stmtCheck->fetch();

    if ($resultCheck) {
        echo json_encode(['success' => false, 'error' => 'La reserva ya existe.']);
        $conn->rollback();
        exit;
    }

    // Guardar la reserva
    $query = "INSERT INTO Entradas (id_funcion, id_cliente, cantidad, asientos, total_pagado) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->execute([$id_funcion, $id_cliente, $cantidad, $asientos_str, $total_pagado]);

    // Registrar en la bitácora con información del cliente
    if ($id_cliente) {
        $clientInfo = obtenerInfoCliente();
        $userAgentInfo = parsearUserAgent($clientInfo['user_agent']);
        
        $detalles = sprintf(
            'Reserva realizada: %d asientos (%s) para función %d. Total: $%.2f. IP: %s, Navegador: %s %s',
            $cantidad,
            $asientos_str,
            $id_funcion,
            $total_pagado,
            $clientInfo['ip'],
            $userAgentInfo['navegador'],
            $userAgentInfo['version']
        );
        
        registrarBitacoraCliente($conn, $id_cliente, 'Reserva realizada', $detalles);
    }

    // Confirmar transacción
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Reserva realizada exitosamente']);
} catch (Exception $e) {
    $conn->rollback();
    error_log("Error en reservas.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al procesar la reserva']);
}
?>