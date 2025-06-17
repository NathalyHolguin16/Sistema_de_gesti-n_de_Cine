<?php
header('Content-Type: application/json');
require_once("conexion.php");

// Desactivar advertencias
error_reporting(0);

$data = json_decode(file_get_contents("php://input"), true);

// Validar que las claves esperadas estén presentes
if (!isset($data['correo'], $data['contrasena'])) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos.']);
    exit;
}

$correo = $data['correo'];
$contrasena = $data['contrasena'];

// Verificar que el correo exista
$query = "SELECT * FROM Clientes WHERE correo = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $cliente = $result->fetch_assoc();
    if (password_verify($contrasena, $cliente['contrasena'])) {
        // Registrar inicio de sesión en la bitácora
        $accion = 'Inicio de sesión';
        $detalles = 'El cliente con ID ' . $cliente['id_cliente'] . ' inició sesión.';
        $queryBitacora = "INSERT INTO BitacoraClientes (id_cliente, accion, detalles) VALUES (?, ?, ?)";
        $stmtBitacora = $conn->prepare($queryBitacora);
        $stmtBitacora->bind_param("iss", $cliente['id_cliente'], $accion, $detalles);
        $stmtBitacora->execute();

        echo json_encode(['success' => true, 'id' => $cliente['id_cliente'], 'nombre' => $cliente['nombre']]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Contraseña incorrecta.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Correo no registrado.']);
}
exit;
?>