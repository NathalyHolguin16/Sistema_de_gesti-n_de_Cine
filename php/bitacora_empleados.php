<?php
header('Content-Type: application/json');
require_once("conexion.php");
require_once("client_info.php");

try {
    // GET - Listar registros de bitácora con paginación
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 10;
        $offset = ($page - 1) * $limit;

        // Filtros opcionales
        $idEmpleado = isset($_GET['id_empleado']) ? (int)$_GET['id_empleado'] : null;
        $fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : null;
        $fechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : null;
        $accion = isset($_GET['accion']) ? $_GET['accion'] : null;

        // Construir WHERE clause dinámico
        $whereConditions = [];
        $params = [];

        if ($idEmpleado) {
            $whereConditions[] = "be.id_empleado = ?";
            $params[] = $idEmpleado;
        }

        if ($fechaInicio) {
            $whereConditions[] = "DATE(be.fecha_hora) >= ?";
            $params[] = $fechaInicio;
        }

        if ($fechaFin) {
            $whereConditions[] = "DATE(be.fecha_hora) <= ?";
            $params[] = $fechaFin;
        }

        if ($accion) {
            $whereConditions[] = "be.accion ILIKE ?";
            $params[] = '%' . $accion . '%';
        }

        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

        // Contar total de registros
        $countQuery = "SELECT COUNT(*) as total
                       FROM BitacoraEmpleados be
                       LEFT JOIN Empleados e ON be.id_empleado = e.id_empleado
                       $whereClause";
        
        $countStmt = $conn->prepare($countQuery);
        $countStmt->execute($params);
        $totalResult = $countStmt->fetch();
        $total = $totalResult['total'];
        
        // Calcular información de paginación
        $totalPages = ceil($total / $limit);
        $hasNextPage = $page < $totalPages;
        $hasPrevPage = $page > 1;

        // Obtener registros de bitácora con información del empleado
        $query = "SELECT be.*, 
                         e.nombre as empleado_nombre,
                         e.correo as empleado_correo,
                         e.rol as empleado_rol,
                         be.ip_address,
                         be.user_agent
                  FROM BitacoraEmpleados be
                  LEFT JOIN Empleados e ON be.id_empleado = e.id_empleado
                  $whereClause
                  ORDER BY be.fecha_hora DESC
                  LIMIT ? OFFSET ?";

        $finalParams = array_merge($params, [$limit, $offset]);
        $stmt = $conn->prepare($query);
        $stmt->execute($finalParams);
        $registros = $stmt->fetchAll();

        // Procesar User-Agent para mejor visualización
        foreach ($registros as &$registro) {
            if (!empty($registro['user_agent'])) {
                $userAgentInfo = parsearUserAgent($registro['user_agent']);
                $registro['navegador_info'] = $userAgentInfo;
            }
        }

        echo json_encode([
            'success' => true, 
            'data' => $registros,
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

    // POST - Insertar nuevo registro en bitácora
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['id_empleado']) || !isset($data['accion'])) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
            exit;
        }

        $id_empleado = $data['id_empleado'];
        $accion = $data['accion'];
        $detalles = $data['detalles'] ?? '';

        // Usar la función helper para registrar en bitácora
        $success = registrarBitacoraEmpleado($conn, $id_empleado, $accion, $detalles);

        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Registro agregado a bitácora']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al insertar en bitácora']);
        }
        exit;
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// Si no coincide ningún método
echo json_encode(['success' => false, 'error' => 'Método no válido']);
?>
