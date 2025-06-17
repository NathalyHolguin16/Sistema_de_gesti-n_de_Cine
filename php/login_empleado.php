<?php
header('Content-Type: application/json');
require_once("conexion.php");

$data = json_decode(file_get_contents("php://input"), true);

$usuario = $data['usuario'];
$contrasena = $data['contrasena'];

// Verificar usuario y contraseña
$query = "SELECT * FROM Empleados WHERE usuario = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $empleado = $result->fetch_assoc();

    if (password_verify($contrasena, $empleado['contrasena'])) {
        // Registrar en la bitácora
        $accion = 'Inicio de sesión';
        $detalles = 'El empleado ' . $empleado['nombre'] . ' inició sesión.';
        $queryBitacora = "INSERT INTO BitacoraEmpleados (id_empleado, accion, detalles) VALUES (?, ?, ?)";
        $stmtBitacora = $conn->prepare($queryBitacora);
        $stmtBitacora->bind_param("iss", $empleado['id_empleado'], $accion, $detalles);
        $stmtBitacora->execute();

        echo json_encode(['success' => true, 'id' => $empleado['id_empleado'], 'nombre' => $empleado['nombre'], 'rol' => $empleado['rol']]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Contraseña incorrecta.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Usuario no encontrado.']);
}
?>