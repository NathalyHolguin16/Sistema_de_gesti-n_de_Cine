<?php
header('Content-Type: application/json');
require_once("conexion.php");

$data = json_decode(file_get_contents("php://input"), true);

$id_cliente = $data['id_cliente'];
$accion = $data['accion'];
$detalles = $data['detalles'];

$query = "INSERT INTO BitacoraClientes (id_cliente, accion, detalles, fecha_hora) VALUES (?, ?, ?, NOW())";
$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $id_cliente, $accion, $detalles);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
?>
