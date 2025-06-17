<?php
header('Content-Type: application/json');
require_once("conexion.php");

$query = "SELECT c.nombre AS cliente, p.titulo AS pelicula, f.fecha, f.hora_inicio AS hora, e.asientos, e.total_pagado
          FROM Entradas e
          JOIN Clientes c ON e.id_cliente = c.id_cliente
          JOIN Funciones f ON e.id_funcion = f.id_funcion
          JOIN Peliculas p ON f.id_pelicula = p.id_pelicula
          ORDER BY e.fecha_compra DESC";

$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

$reservas = [];
while ($row = $result->fetch_assoc()) {
    $reservas[] = $row;
}

echo json_encode(['success' => true, 'data' => $reservas]);
?>
