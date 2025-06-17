<?php
header('Content-Type: application/json');
require_once("conexion.php");

$data = json_decode(file_get_contents("php://input"), true);

$id_funcion = $data['id_funcion'];
$asientos = $data['asientos'];
$cantidad = $data['cantidad'];
$total_pagado = $data['total_pagado'];
$id_cliente = isset($data['id_cliente']) ? $data['id_cliente'] : null;

// Verificar que los asientos no estén ocupados
$query = "SELECT asientos FROM Entradas WHERE id_funcion = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_funcion);
$stmt->execute();
$result = $stmt->get_result();
$ocupados = [];
while ($row = $result->fetch_assoc()) {
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
$conn->begin_transaction();

try {
    // Verificar que la reserva no exista antes de guardar
    $queryCheck = "SELECT id_entrada FROM Entradas WHERE id_funcion = ? AND id_cliente = ? AND asientos = ?";
    $stmtCheck = $conn->prepare($queryCheck);
    $stmtCheck->bind_param("iis", $id_funcion, $id_cliente, $asientos_str);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'La reserva ya existe.']);
        $conn->rollback();
        exit;
    }

    // Guardar la reserva
    $query = "INSERT INTO Entradas (id_funcion, id_cliente, cantidad, asientos, total_pagado) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiisd", $id_funcion, $id_cliente, $cantidad, $asientos_str, $total_pagado);
    $stmt->execute();

    // Registrar en la bitácora
    if ($id_cliente) {
        $accion = 'Reserva realizada';
        $detalles = 'El cliente con ID ' . $id_cliente . ' reservó los asientos: ' . $asientos_str . ' para la función ' . $id_funcion . '.';
        $queryBitacora = "INSERT INTO BitacoraClientes (id_cliente, accion, detalles) VALUES (?, ?, ?)";
        $stmtBitacora = $conn->prepare($queryBitacora);
        $stmtBitacora->bind_param("iss", $id_cliente, $accion, $detalles);
        $stmtBitacora->execute();
    }

    // Confirmar transacción
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>