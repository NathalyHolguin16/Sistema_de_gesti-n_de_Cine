<?php
header('Content-Type: application/json');
require_once("conexion.php");

$data = json_decode(file_get_contents("php://input"), true);

$correo = $data['correo'];

// Verificar que el correo exista
$query = "SELECT * FROM Clientes WHERE correo = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $cliente = $result->fetch_assoc();

    // Registrar en la bitácora
    $accion = 'Inicio de sesión';
    $detalles = 'El cliente ' . $cliente['nombre'] . ' inició sesión.';
    $queryBitacora = "INSERT INTO BitacoraClientes (id_cliente, accion, detalles) VALUES (?, ?, ?)";
    $stmtBitacora = $conn->prepare($queryBitacora);
    $stmtBitacora->bind_param("iss", $cliente['id_cliente'], $accion, $detalles);
    $stmtBitacora->execute();

    echo json_encode(['success' => true, 'id' => $cliente['id_cliente'], 'nombre' => $cliente['nombre']]);
} else {
    echo json_encode(['success' => false, 'error' => 'Correo no registrado.']);
}
?>