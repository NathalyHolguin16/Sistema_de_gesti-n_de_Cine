<?php
header('Content-Type: application/json');
require_once("conexion.php");

try {
    // GET - Listar salas con paginación
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['id'])) {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 10;
        $offset = ($page - 1) * $limit;

        // Filtros opcionales
        $capacidadMin = isset($_GET['capacidad_min']) ? (int)$_GET['capacidad_min'] : null;
        $capacidadMax = isset($_GET['capacidad_max']) ? (int)$_GET['capacidad_max'] : null;

        // Construir WHERE clause dinámico
        $whereConditions = [];
        $params = [];

        if ($capacidadMin) {
            $whereConditions[] = "capacidad >= ?";
            $params[] = $capacidadMin;
        }

        if ($capacidadMax) {
            $whereConditions[] = "capacidad <= ?";
            $params[] = $capacidadMax;
        }

        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

        // Contar total de salas
        $countQuery = "SELECT COUNT(*) as total FROM Salas $whereClause";
        $countStmt = $conn->prepare($countQuery);
        $countStmt->execute($params);
        $totalResult = $countStmt->fetch();
        $total = $totalResult['total'];
        
        // Calcular información de paginación
        $totalPages = ceil($total / $limit);
        $hasNextPage = $page < $totalPages;
        $hasPrevPage = $page > 1;

        // Obtener salas con estadísticas
        $query = "SELECT s.*, 
                         COUNT(f.id_funcion) as total_funciones,
                         COUNT(CASE WHEN f.fecha >= CURRENT_DATE THEN 1 END) as funciones_futuras
                  FROM Salas s
                  LEFT JOIN Funciones f ON s.id_sala = f.id_sala
                  $whereClause
                  GROUP BY s.id_sala, s.nombre, s.capacidad
                  ORDER BY s.id_sala ASC
                  LIMIT ? OFFSET ?";

        $finalParams = array_merge($params, [$limit, $offset]);
        $stmt = $conn->prepare($query);
        $stmt->execute($finalParams);
        $salas = $stmt->fetchAll();

        echo json_encode([
            'success' => true, 
            'data' => $salas,
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
        exit;
    }

    // GET - Obtener sala específica por ID
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
        $id_sala = (int)$_GET['id'];
        
        $query = "SELECT s.*, 
                         COUNT(f.id_funcion) as total_funciones,
                         COUNT(CASE WHEN f.fecha >= CURRENT_DATE THEN 1 END) as funciones_futuras,
                         COUNT(CASE WHEN f.fecha = CURRENT_DATE THEN 1 END) as funciones_hoy
                  FROM Salas s
                  LEFT JOIN Funciones f ON s.id_sala = f.id_sala
                  WHERE s.id_sala = ?
                  GROUP BY s.id_sala, s.nombre, s.capacidad";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$id_sala]);
        $sala = $stmt->fetch();

        if ($sala) {
            echo json_encode(['success' => true, 'data' => $sala]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Sala no encontrada']);
        }
        exit;
    }

    // POST - Crear nueva sala
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['nombre']) || !isset($data['capacidad'])) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
            exit;
        }

        $nombre = $data['nombre'];
        $capacidad = (int)$data['capacidad'];

        if ($capacidad < 1) {
            echo json_encode(['success' => false, 'error' => 'La capacidad debe ser mayor a 0']);
            exit;
        }

        $query = "INSERT INTO Salas (nombre, capacidad) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $success = $stmt->execute([$nombre, $capacidad]);

        if ($success) {
            $id_sala = $conn->lastInsertId();
            echo json_encode(['success' => true, 'id_sala' => $id_sala, 'message' => 'Sala creada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al crear la sala']);
        }
        exit;
    }

    // PUT - Actualizar sala existente
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['id_sala']) || !isset($data['nombre']) || !isset($data['capacidad'])) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
            exit;
        }

        $id_sala = (int)$data['id_sala'];
        $nombre = $data['nombre'];
        $capacidad = (int)$data['capacidad'];

        if ($capacidad < 1) {
            echo json_encode(['success' => false, 'error' => 'La capacidad debe ser mayor a 0']);
            exit;
        }

        $query = "UPDATE Salas SET nombre = ?, capacidad = ? WHERE id_sala = ?";
        $stmt = $conn->prepare($query);
        $success = $stmt->execute([$nombre, $capacidad, $id_sala]);

        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Sala actualizada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al actualizar la sala']);
        }
        exit;
    }

    // DELETE - Eliminar sala
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['id_sala'])) {
            echo json_encode(['success' => false, 'error' => 'ID de sala requerido']);
            exit;
        }

        $id_sala = (int)$data['id_sala'];

        // Verificar si la sala tiene funciones programadas
        $checkQuery = "SELECT COUNT(*) as count FROM Funciones WHERE id_sala = ? AND fecha >= CURRENT_DATE";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute([$id_sala]);
        $result = $checkStmt->fetch();

        if ($result['count'] > 0) {
            echo json_encode(['success' => false, 'error' => 'No se puede eliminar la sala porque tiene funciones programadas']);
            exit;
        }

        $query = "DELETE FROM Salas WHERE id_sala = ?";
        $stmt = $conn->prepare($query);
        $success = $stmt->execute([$id_sala]);

        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Sala eliminada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al eliminar la sala']);
        }
        exit;
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// Si no coincide ningún método
echo json_encode(['success' => false, 'error' => 'Método no válido']);
?>