<?php
header('Content-Type: application/json');
require_once("conexion.php");

try {
    // Listar todas las funciones con paginación
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['id_pelicula']) && !isset($_GET['id_funcion'])) {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 10;
        $offset = ($page - 1) * $limit;

        // Contar total de funciones
        $countQuery = "SELECT COUNT(*) as total FROM funciones";
        $countStmt = $conn->prepare($countQuery);
        $countStmt->execute();
        $totalResult = $countStmt->fetch();
        $total = $totalResult['total'];
        
        // Calcular información de paginación
        $totalPages = ceil($total / $limit);
        $hasNextPage = $page < $totalPages;
        $hasPrevPage = $page > 1;

        // Obtener funciones con información de películas
        $query = "SELECT f.*, p.titulo as pelicula_titulo 
                  FROM funciones f 
                  JOIN peliculas p ON f.id_pelicula = p.id_pelicula 
                  ORDER BY f.fecha DESC, f.hora_inicio DESC 
                  LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$limit, $offset]);
        $funciones = $stmt->fetchAll();

        echo json_encode([
            'success' => true, 
            'data' => $funciones,
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

    // Listar funciones de una película específica
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id_pelicula'])) {
        $id_pelicula = $_GET['id_pelicula'];
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 10;
        $offset = ($page - 1) * $limit;

        // Contar funciones de la película
        $countQuery = "SELECT COUNT(*) as total FROM funciones WHERE id_pelicula = ?";
        $countStmt = $conn->prepare($countQuery);
        $countStmt->execute([$id_pelicula]);
        $totalResult = $countStmt->fetch();
        $total = $totalResult['total'];
        
        // Calcular información de paginación
        $totalPages = ceil($total / $limit);
        $hasNextPage = $page < $totalPages;
        $hasPrevPage = $page > 1;

        // Obtener funciones de la película
        $query = "SELECT * FROM funciones WHERE id_pelicula = ? ORDER BY fecha ASC, hora_inicio ASC LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id_pelicula, $limit, $offset]);
        $funciones = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true, 
            'data' => $funciones,
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

    // Obtener función por ID
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id_funcion'])) {
        $id_funcion = $_GET['id_funcion'];
        $query = "SELECT * FROM funciones WHERE id_funcion = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id_funcion]);
        $funcion = $stmt->fetch();
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
            $query = "INSERT INTO funciones (id_pelicula, fecha, hora_inicio, precio) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $success = $stmt->execute([$id_pelicula, $fecha, $hora, $precio]);

            if ($success && $id_empleado) {
                $bitacora_query = "INSERT INTO bitacoraempleados (id_empleado, accion, detalles) VALUES (?, 'Agregar Funcion', ?)";
                $bitacora_stmt = $conn->prepare($bitacora_query);
                $detalles = "ID Pelicula: $id_pelicula, Fecha: $fecha, Hora: $hora";
                $bitacora_stmt->execute([$id_empleado, $detalles]);
            }

            echo json_encode(['success' => $success]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
        }
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

// Si no coincide ningún método, responde vacío
echo json_encode([]);
?>

