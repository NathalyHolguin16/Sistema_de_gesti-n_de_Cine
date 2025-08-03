<?php
header('Content-Type: application/json');
require_once("conexion.php");

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 10;
        $offset = ($page - 1) * $limit;

        if (isset($_GET['id_empleado'])) {
            $query = "SELECT * FROM Empleados WHERE id_empleado = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$_GET['id_empleado']]);
            echo json_encode($stmt->fetch());
        } else {
            // Contar total de empleados
            $countQuery = "SELECT COUNT(*) as total FROM Empleados";
            $countStmt = $conn->prepare($countQuery);
            $countStmt->execute();
            $totalResult = $countStmt->fetch();
            $total = $totalResult['total'];
            
            // Calcular información de paginación
            $totalPages = ceil($total / $limit);
            $hasNextPage = $page < $totalPages;
            $hasPrevPage = $page > 1;

            // Obtener empleados de la página actual
            $query = "SELECT * FROM Empleados ORDER BY id_empleado ASC LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$limit, $offset]);
            $empleados = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true, 
                'data' => $empleados,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_items' => $total,
                    'items_per_page' => $limit,
                    'has_next_page' => $hasNextPage,
                    'has_prev_page' => $hasPrevPage,
                    'showing_from' => $offset + 1,
                    'showing_to' => min($offset + $limit, $total)
                ]
            ]);
        }
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);

        // La base de datos se encarga del hasheo automáticamente
        $hashedPassword = $data['contrasena'];

        $query = "INSERT INTO Empleados (nombre, cargo, usuario, contrasena, rol) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $success = $stmt->execute([$data['nombre'], $data['cargo'], $data['usuario'], $hashedPassword, $data['rol']]);
        
        if ($success) {
            // Registrar en la bitácora
            $id_empleado_admin = $data['id_empleado_admin'] ?? null; // ID del administrador que realiza la acción
            if ($id_empleado_admin) {
                $accion = 'Agregar empleado';
                $detalles = 'Se agregó el empleado "' . $data['nombre'] . '" con cargo "' . $data['cargo'] . '".';
                $queryBitacora = "INSERT INTO BitacoraEmpleados (id_empleado, accion, detalles) VALUES (?, ?, ?)";
                $stmtBitacora = $conn->prepare($queryBitacora);
                $stmtBitacora->execute([$id_empleado_admin, $accion, $detalles]);
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    } elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true);

        // La base de datos se encarga del hasheo automáticamente
        $hashedPassword = $data['contrasena'];

        $query = "UPDATE Empleados SET nombre = ?, cargo = ?, usuario = ?, contrasena = ?, rol = ? WHERE id_empleado = ?";
        $stmt = $conn->prepare($query);
        $success = $stmt->execute([$data['nombre'], $data['cargo'], $data['usuario'], $hashedPassword, $data['rol'], $data['id_empleado']]);
        
        if ($success) {
            // Registrar en la bitácora
            $id_empleado_admin = $data['id_empleado_admin'] ?? null; // ID del administrador que realiza la acción
            if ($id_empleado_admin) {
                $accion = 'Editar empleado';
                $detalles = 'Se editó el empleado con ID ' . $data['id_empleado'] . ' y nombre "' . $data['nombre'] . '".';
                $queryBitacora = "INSERT INTO BitacoraEmpleados (id_empleado, accion, detalles) VALUES (?, ?, ?)";
                $stmtBitacora = $conn->prepare($queryBitacora);
                $stmtBitacora->execute([$id_empleado_admin, $accion, $detalles]);
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    } elseif ($method === 'DELETE') {
        $query = "DELETE FROM Empleados WHERE id_empleado = ?";
        $stmt = $conn->prepare($query);
        $success = $stmt->execute([$_GET['id_empleado']]);
        
        if ($success) {
            // Registrar en la bitácora
            $id_empleado_admin = $_GET['id_empleado_admin'] ?? null; // ID del administrador que realiza la acción
            if ($id_empleado_admin) {
                $accion = 'Eliminar empleado';
                $detalles = 'Se eliminó el empleado con ID ' . $_GET['id_empleado'] . '.';
                $queryBitacora = "INSERT INTO BitacoraEmpleados (id_empleado, accion, detalles) VALUES (?, ?, ?)";
                $stmtBitacora = $conn->prepare($queryBitacora);
                $stmtBitacora->execute([$id_empleado_admin, $accion, $detalles]);
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>