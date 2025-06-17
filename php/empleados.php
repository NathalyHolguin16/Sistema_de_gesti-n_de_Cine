<?php
header('Content-Type: application/json');
require_once("conexion.php");

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (isset($_GET['id_empleado'])) {
        $query = "SELECT * FROM Empleados WHERE id_empleado = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $_GET['id_empleado']);
        $stmt->execute();
        $result = $stmt->get_result();
        echo json_encode($result->fetch_assoc());
    } else {
        $query = "SELECT * FROM Empleados";
        $result = $conn->query($query);
        $empleados = [];
        while ($row = $result->fetch_assoc()) {
            $empleados[] = $row;
        }
        echo json_encode($empleados);
    }
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    // Hashear la contraseña
    $hashedPassword = password_hash($data['contrasena'], PASSWORD_BCRYPT);

    $query = "INSERT INTO Empleados (nombre, cargo, usuario, contrasena, rol) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssss", $data['nombre'], $data['cargo'], $data['usuario'], $hashedPassword, $data['rol']);
    if ($stmt->execute()) {
        // Registrar en la bitácora
        $id_empleado_admin = $data['id_empleado_admin'] ?? null; // ID del administrador que realiza la acción
        if ($id_empleado_admin) {
            $accion = 'Agregar empleado';
            $detalles = 'Se agregó el empleado "' . $data['nombre'] . '" con cargo "' . $data['cargo'] . '".';
            $queryBitacora = "INSERT INTO BitacoraEmpleados (id_empleado, accion, detalles) VALUES (?, ?, ?)";
            $stmtBitacora = $conn->prepare($queryBitacora);
            $stmtBitacora->bind_param("iss", $id_empleado_admin, $accion, $detalles);
            $stmtBitacora->execute();
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);

    // Hashear la contraseña
    $hashedPassword = password_hash($data['contrasena'], PASSWORD_BCRYPT);

    $query = "UPDATE Empleados SET nombre = ?, cargo = ?, usuario = ?, contrasena = ?, rol = ? WHERE id_empleado = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssi", $data['nombre'], $data['cargo'], $data['usuario'], $hashedPassword, $data['rol'], $data['id_empleado']);
    if ($stmt->execute()) {
        // Registrar en la bitácora
        $id_empleado_admin = $data['id_empleado_admin'] ?? null; // ID del administrador que realiza la acción
        if ($id_empleado_admin) {
            $accion = 'Editar empleado';
            $detalles = 'Se editó el empleado con ID ' . $data['id_empleado'] . ' y nombre "' . $data['nombre'] . '".';
            $queryBitacora = "INSERT INTO BitacoraEmpleados (id_empleado, accion, detalles) VALUES (?, ?, ?)";
            $stmtBitacora = $conn->prepare($queryBitacora);
            $stmtBitacora->bind_param("iss", $id_empleado_admin, $accion, $detalles);
            $stmtBitacora->execute();
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} elseif ($method === 'DELETE') {
    $query = "DELETE FROM Empleados WHERE id_empleado = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_GET['id_empleado']);
    if ($stmt->execute()) {
        // Registrar en la bitácora
        $id_empleado_admin = $_GET['id_empleado_admin'] ?? null; // ID del administrador que realiza la acción
        if ($id_empleado_admin) {
            $accion = 'Eliminar empleado';
            $detalles = 'Se eliminó el empleado con ID ' . $_GET['id_empleado'] . '.';
            $queryBitacora = "INSERT INTO BitacoraEmpleados (id_empleado, accion, detalles) VALUES (?, ?, ?)";
            $stmtBitacora = $conn->prepare($queryBitacora);
            $stmtBitacora->bind_param("iss", $id_empleado_admin, $accion, $detalles);
            $stmtBitacora->execute();
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}
?>