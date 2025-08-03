<?php
header('Content-Type: application/json');
require_once("conexion.php");

try {
    // Parámetros de paginación con validación
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 10;
    $offset = ($page - 1) * $limit;

    // Filtros opcionales
    $fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : null;
    $fechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : null;
    $idPelicula = isset($_GET['id_pelicula']) ? (int)$_GET['id_pelicula'] : null;

    // Construir WHERE clause dinámico
    $whereConditions = [];
    $params = [];

    if ($fechaInicio) {
        $whereConditions[] = "f.fecha >= ?";
        $params[] = $fechaInicio;
    }

    if ($fechaFin) {
        $whereConditions[] = "f.fecha <= ?";
        $params[] = $fechaFin;
    }

    if ($idPelicula) {
        $whereConditions[] = "p.id_pelicula = ?";
        $params[] = $idPelicula;
    }

    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

    // Contar total de registros
    $countQuery = "SELECT COUNT(*) as total
                   FROM Entradas e
                   JOIN Clientes c ON e.id_cliente = c.id_cliente
                   JOIN Funciones f ON e.id_funcion = f.id_funcion
                   JOIN Peliculas p ON f.id_pelicula = p.id_pelicula
                   $whereClause";
    
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute($params);
    $totalResult = $countStmt->fetch();
    $total = $totalResult['total'];
    
    // Calcular información de paginación
    $totalPages = ceil($total / $limit);
    $hasNextPage = $page < $totalPages;
    $hasPrevPage = $page > 1;

    // Obtener datos del reporte con paginación
    $query = "SELECT c.nombre AS cliente, 
                     c.correo AS cliente_correo,
                     p.titulo AS pelicula, 
                     f.fecha, 
                     f.hora_inicio AS hora, 
                     e.asientos, 
                     e.total_pagado,
                     e.fecha_compra,
                     e.id_entrada
              FROM Entradas e
              JOIN Clientes c ON e.id_cliente = c.id_cliente
              JOIN Funciones f ON e.id_funcion = f.id_funcion
              JOIN Peliculas p ON f.id_pelicula = p.id_pelicula
              $whereClause
              ORDER BY e.fecha_compra DESC
              LIMIT ? OFFSET ?";

    $finalParams = array_merge($params, [$limit, $offset]);
    $stmt = $conn->prepare($query);
    $stmt->execute($finalParams);
    $reservas = $stmt->fetchAll();

    // Calcular totales para el reporte
    $totalVentasQuery = "SELECT 
                            COUNT(*) as total_entradas,
                            SUM(e.total_pagado) as ingresos_totales,
                            AVG(e.total_pagado) as promedio_por_entrada
                         FROM Entradas e
                         JOIN Funciones f ON e.id_funcion = f.id_funcion
                         JOIN Peliculas p ON f.id_pelicula = p.id_pelicula
                         $whereClause";
    
    $totalStmt = $conn->prepare($totalVentasQuery);
    $totalStmt->execute($params);
    $totales = $totalStmt->fetch();

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
        ],
        'summary' => [
            'total_entradas' => (int)$totales['total_entradas'],
            'ingresos_totales' => (float)$totales['ingresos_totales'],
            'promedio_por_entrada' => (float)$totales['promedio_por_entrada']
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
