<?php
header('Content-Type: application/json');
require_once("conexion.php");

try {
    // Listar todas las reservas con paginación
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['id_cliente']) && !isset($_GET['id_funcion'])) {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 10;
        $offset = ($page - 1) * $limit;

        // Contar total de entradas/reservas
        $countQuery = "SELECT COUNT(*) as total FROM Entradas";
        $countStmt = $conn->prepare($countQuery);
        $countStmt->execute();
        $totalResult = $countStmt->fetch();
        $total = $totalResult['total'];
        
        // Calcular información de paginación
        $totalPages = ceil($total / $limit);
        $hasNextPage = $page < $totalPages;
        $hasPrevPage = $page > 1;

        // Obtener reservas con información completa
        $query = "SELECT e.*, 
                         c.nombre as cliente_nombre, 
                         c.correo as cliente_correo,
                         p.titulo as pelicula_titulo,
                         f.fecha as funcion_fecha,
                         f.hora_inicio as funcion_hora
                  FROM Entradas e
                  LEFT JOIN Clientes c ON e.id_cliente = c.id_cliente
                  JOIN Funciones f ON e.id_funcion = f.id_funcion
                  JOIN Peliculas p ON f.id_pelicula = p.id_pelicula
                  ORDER BY e.fecha_compra DESC 
                  LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$limit, $offset]);
        $reservas = $stmt->fetchAll();

        echo json_encode([
            'success' => true, 
            'data' => $reservas,
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

    // Listar reservas de un cliente específico
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id_cliente'])) {
        $id_cliente = $_GET['id_cliente'];
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 10;
        $offset = ($page - 1) * $limit;

        // Contar reservas del cliente
        $countQuery = "SELECT COUNT(*) as total FROM Entradas WHERE id_cliente = ?";
        $countStmt = $conn->prepare($countQuery);
        $countStmt->execute([$id_cliente]);
        $totalResult = $countStmt->fetch();
        $total = $totalResult['total'];
        
        // Calcular información de paginación
        $totalPages = ceil($total / $limit);
        $hasNextPage = $page < $totalPages;
        $hasPrevPage = $page > 1;

        // Obtener reservas del cliente
        $query = "SELECT e.*, 
                         p.titulo as pelicula_titulo,
                         f.fecha as funcion_fecha,
                         f.hora_inicio as funcion_hora
                  FROM Entradas e
                  JOIN Funciones f ON e.id_funcion = f.id_funcion
                  JOIN Peliculas p ON f.id_pelicula = p.id_pelicula
                  WHERE e.id_cliente = ?
                  ORDER BY e.fecha_compra DESC 
                  LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id_cliente, $limit, $offset]);
        $reservas = $stmt->fetchAll();

        echo json_encode([
            'success' => true, 
            'data' => $reservas,
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

    // Listar reservas de una función específica
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id_funcion'])) {
        $id_funcion = $_GET['id_funcion'];
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 10;
        $offset = ($page - 1) * $limit;

        // Contar reservas de la función
        $countQuery = "SELECT COUNT(*) as total FROM Entradas WHERE id_funcion = ?";
        $countStmt = $conn->prepare($countQuery);
        $countStmt->execute([$id_funcion]);
        $totalResult = $countStmt->fetch();
        $total = $totalResult['total'];
        
        // Calcular información de paginación
        $totalPages = ceil($total / $limit);
        $hasNextPage = $page < $totalPages;
        $hasPrevPage = $page > 1;

        // Obtener reservas de la función
        $query = "SELECT e.*, 
                         c.nombre as cliente_nombre, 
                         c.correo as cliente_correo
                  FROM Entradas e
                  LEFT JOIN Clientes c ON e.id_cliente = c.id_cliente
                  WHERE e.id_funcion = ?
                  ORDER BY e.fecha_compra DESC 
                  LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id_funcion, $limit, $offset]);
        $reservas = $stmt->fetchAll();

        echo json_encode([
            'success' => true, 
            'data' => $reservas,
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

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

// Si no coincide ningún método, responde vacío
echo json_encode(['success' => false, 'error' => 'Método no válido']);
?>
