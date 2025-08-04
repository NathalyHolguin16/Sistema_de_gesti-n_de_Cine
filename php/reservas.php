<?php
header('Content-Type: application/json');
require_once("conexion.php");
require_once("client_info.php");

$data = json_decode(file_get_contents("php://input"), true);

// Log para detectar solicitudes duplicadas
error_log("Solicitud de reserva recibida: " . json_encode($data));

$id_funcion = $data['id_funcion'];
$asientos = $data['asientos'];
$cantidad = $data['cantidad'];
$total_pagado = $data['total_pagado'];
$id_cliente = isset($data['id_cliente']) ? $data['id_cliente'] : null;

try {
    // Verificar que los asientos no estén ocupados
    $query = "SELECT asientos FROM entradas WHERE id_funcion = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$id_funcion]);
    $result = $stmt->fetchAll();
    
    $ocupados = [];
    foreach ($result as $row) {
        if (!empty($row['asientos'])) {
            $asientosArray = explode(',', $row['asientos']);
            $asientosArray = array_map('trim', $asientosArray);
            $ocupados = array_merge($ocupados, $asientosArray);
        }
    }
    
    foreach ($asientos as $asiento) {
        if (in_array(trim($asiento), $ocupados)) {
            echo json_encode(['success' => false, 'error' => "El asiento $asiento ya está ocupado."]);
            exit;
        }
    }

    $asientos_str = implode(',', $asientos);

    // Iniciar transacción
    $conn->beginTransaction();

    // Verificar que la reserva no exista antes de guardar
    $queryCheck = "SELECT id_entrada FROM entradas WHERE id_funcion = ? AND id_cliente = ? AND asientos = ?";
    $stmtCheck = $conn->prepare($queryCheck);
    $stmtCheck->execute([$id_funcion, $id_cliente, $asientos_str]);
    $resultCheck = $stmtCheck->fetch();

    if ($resultCheck) {
        echo json_encode(['success' => false, 'error' => 'La reserva ya existe.']);
        $conn->rollback();
        exit;
    }

    // Guardar la reserva
    $query = "INSERT INTO entradas (id_funcion, id_cliente, cantidad, asientos, total_pagado) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->execute([$id_funcion, $id_cliente, $cantidad, $asientos_str, $total_pagado]);

    // Obtener información detallada de la función y película para la bitácora
    $queryDetalle = "
        SELECT 
            f.id_funcion,
            f.fecha,
            f.hora_inicio as hora,
            f.id_sala,
            p.id_pelicula,
            p.titulo as nombre_pelicula
        FROM funciones f
        INNER JOIN peliculas p ON f.id_pelicula = p.id_pelicula
        WHERE f.id_funcion = ?
    ";
    $stmtDetalle = $conn->prepare($queryDetalle);
    $stmtDetalle->execute([$id_funcion]);
    $funcionDetalle = $stmtDetalle->fetch(PDO::FETCH_ASSOC);

    // Registrar en la bitácora con información detallada de la reserva
    if ($id_cliente && $funcionDetalle) {
        $clientInfo = obtenerInfoCliente();
        $userAgentInfo = parsearUserAgent($clientInfo['user_agent']);
        
        $detalles = sprintf(
            'Reserva realizada exitosamente para la película "%s". Función del %s a las %s en Sala %d. %d asientos reservados: %s. Total pagado: $%.2f',
            $funcionDetalle['nombre_pelicula'],
            $funcionDetalle['fecha'],
            $funcionDetalle['hora'],
            $funcionDetalle['id_sala'],
            $cantidad,
            $asientos_str,
            $total_pagado
        );
        
        // Datos específicos de la reserva para la bitácora
        $datosReserva = [
            'id_funcion' => $funcionDetalle['id_funcion'],
            'id_pelicula' => $funcionDetalle['id_pelicula'],
            'nombre_pelicula' => $funcionDetalle['nombre_pelicula'],
            'fecha_funcion' => $funcionDetalle['fecha'],
            'hora_funcion' => $funcionDetalle['hora'],
            'sala' => $funcionDetalle['id_sala'],
            'asientos_reservados' => $asientos_str,
            'cantidad_asientos' => $cantidad,
            'total_pagado' => $total_pagado
        ];
        
        registrarBitacoraCliente($conn, $id_cliente, 'Reserva realizada', $detalles, $datosReserva);
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