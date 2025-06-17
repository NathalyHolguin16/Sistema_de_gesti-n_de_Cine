<?php
header('Content-Type: application/json');
require_once("conexion.php");

// Desactivar advertencias
error_reporting(0);

$data = json_decode(file_get_contents("php://input"), true);

// Validar que las claves esperadas estén presentes
if (!isset($data['nombre'], $data['correo'], $data['telefono'], $data['contrasena'])) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos.']);
    exit;
}

$nombre = $data['nombre'];
$correo = $data['correo'];
$telefono = $data['telefono'];
$contrasena = password_hash($data['contrasena'], PASSWORD_BCRYPT);

// Verificar que el correo sea único
$query = "SELECT * FROM Clientes WHERE correo = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'El correo ya está registrado.']);
    exit;
}

// Insertar el cliente
$query = "INSERT INTO Clientes (nombre, correo, telefono, contrasena) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssss", $nombre, $correo, $telefono, $contrasena);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
exit;
?>