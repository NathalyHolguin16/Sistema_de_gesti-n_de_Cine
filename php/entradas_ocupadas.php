<?php
header('Content-Type: application/json');
require_once("conexion.php");

$id_funcion = $_GET['id_funcion'];
$query = "SELECT asientos FROM Entradas WHERE id_funcion = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_funcion);
$stmt->execute();
$result = $stmt->get_result();
$ocupados = [];
while ($row = $result->fetch_assoc()) {
    $ocupados = array_merge($ocupados, explode(',', $row['asientos']));
}
echo json_encode($ocupados);
?>