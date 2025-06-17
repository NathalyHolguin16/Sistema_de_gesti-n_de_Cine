<?php
header('Content-Type: application/json');
require_once("conexion.php");

$data = json_decode(file_get_contents("php://input"), true);

$id_empleado = $data['id_empleado'];
$accion = $data['accion'];
$detalles = $data['detalles'];

$query = "INSERT INTO BitacoraEmpleados (id_empleado, accion, detalles, fecha_hora) VALUES (?, ?, ?, NOW())";
$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $id_empleado, $accion, $detalles);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
?>
