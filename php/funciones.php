<?php
header('Content-Type: application/json');
require_once("conexion.php");

// Listar funciones de una película
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id_pelicula'])) {
    $id_pelicula = $_GET['id_pelicula'];
    $query = "SELECT * FROM Funciones WHERE id_pelicula = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_pelicula);
    $stmt->execute();
    $result = $stmt->get_result();
    $funciones = [];
    while ($row = $result->fetch_assoc()) {
        $funciones[] = $row;
    }
    echo json_encode($funciones);
    exit;
}

// Obtener función por ID
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id_funcion'])) {
    $id_funcion = $_GET['id_funcion'];
    $query = "SELECT * FROM Funciones WHERE id_funcion = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_funcion);
    $stmt->execute();
    $result = $stmt->get_result();
    $funcion = $result->fetch_assoc();
    echo json_encode($funcion);
    exit;
}

// Agregar función
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id_pelicula = $data['id_pelicula'] ?? null;
    $fecha = $data['fecha'] ?? null;
    $hora = $data['hora'] ?? null;
    $precio = $data['precio'] ?? null;
    $id_empleado = $data['id_empleado'] ?? null; // ID del empleado

    if ($id_pelicula && $fecha && $hora && $precio !== null) {
        $query = "INSERT INTO Funciones (id_pelicula, fecha, hora_inicio, precio) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issd", $id_pelicula, $fecha, $hora, $precio);
        $success = $stmt->execute();

        if ($success && $id_empleado) {
            $bitacora_query = "INSERT INTO BitacoraEmpleados (id_empleado, accion, detalles) VALUES (?, 'Agregar Funcion', ?)";
            $bitacora_stmt = $conn->prepare($bitacora_query);
            $detalles = "ID Pelicula: $id_pelicula, Fecha: $fecha, Hora: $hora";
            $bitacora_stmt->bind_param("is", $id_empleado, $detalles);
            $bitacora_stmt->execute();
        }

        echo json_encode(['success' => $success]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    }
    exit;
}

// Si no coincide ningún método, responde vacío
echo json_encode([]);
?>

