<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Asegurarse de que no haya salida antes de este punto
ob_start();

require_once("conexion.php");
$resourcesDir = realpath(__DIR__ . '/../resources/');

// Si hay algún error en el script, devolver un JSON
set_error_handler(function($severity, $message, $file, $line) {
    echo json_encode([
        'success' => false,
        'error' => $message,
        'file' => $file,
        'line' => $line
    ]);
    exit;
});

try {

// Obtener película por ID
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id_pelicula'])) {
    $id_pelicula = $_GET['id_pelicula'];
    try {
        $query = "SELECT * FROM Peliculas WHERE id_pelicula = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id_pelicula]);
        $pelicula = $stmt->fetch();
        echo json_encode($pelicula);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Listar películas
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 8;
    $offset = ($page - 1) * $limit;

    try {
        // Contar total de películas activas
        $countQuery = "SELECT COUNT(*) as total FROM Peliculas WHERE estado IS NULL OR estado = true";
        $countStmt = $conn->prepare($countQuery);
        $countStmt->execute();
        $totalResult = $countStmt->fetch();
        $total = $totalResult['total'];
        
        // Calcular información de paginación
        $totalPages = ceil($total / $limit);
        $hasNextPage = $page < $totalPages;
        $hasPrevPage = $page > 1;

        // Obtener películas de la página actual
        $query = "SELECT * FROM Peliculas WHERE estado IS NULL OR estado = true ORDER BY id_pelicula DESC LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$limit, $offset]);
        $result = $stmt->fetchAll();

        $peliculas = [];
        foreach ($result as $row) {
            if (!empty($row['imagen'])) {
                $row['imagen_url'] = '../resources/' . $row['imagen'];
            }
            $peliculas[] = $row;
        }

        echo json_encode([
            'success' => true, 
            'data' => $peliculas,
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
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}

// Agregar o editar película con imagen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $modo = $_POST['modo'] ?? 'agregar';
    $titulo = $_POST['titulo'] ?? '';
    $duracion = $_POST['duracion_minutos'] ?? 0;
    $clasificacion = $_POST['clasificacion'] ?? '';
    $genero = $_POST['genero'] ?? '';
    $sinopsis = $_POST['sinopsis'] ?? '';
    $estado = isset($_POST['estado']) ? $_POST['estado'] : true;
    $imagen = null;

    // Manejo de imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $nombreArchivo = uniqid('peli_') . '.' . $ext;
        $destino = $resourcesDir . DIRECTORY_SEPARATOR . $nombreArchivo;
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $destino)) {
            $imagen = $nombreArchivo;
        }
    }

    try {
        // Add bitácora logging for movie actions
        if ($modo === 'agregar') {
            $query = "INSERT INTO Peliculas (titulo, duracion_minutos, clasificacion, genero, sinopsis, estado, imagen)
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $success = $stmt->execute([$titulo, $duracion, $clasificacion, $genero, $sinopsis, $estado, $imagen]);

            if ($success && isset($_POST['id_empleado'])) {
                $id_empleado = $_POST['id_empleado'];
                $accion = 'Agregar Película';
                $detalles = "Título: $titulo, Duración: $duracion, Clasificación: $clasificacion";
                $bitacora_query = "INSERT INTO BitacoraEmpleados (id_empleado, accion, detalles) VALUES (?, ?, ?)";
                $bitacora_stmt = $conn->prepare($bitacora_query);
                $bitacora_stmt->execute([$id_empleado, $accion, $detalles]);
            }

            echo json_encode(['success' => $success]);
            exit;
        } elseif ($modo === 'editar') {
            $id = $_POST['id_pelicula'] ?? null;
            if ($id) {
                if ($imagen) {
                    $query = "UPDATE Peliculas SET titulo=?, duracion_minutos=?, clasificacion=?, genero=?, sinopsis=?, estado=?, imagen=? WHERE id_pelicula=?";
                    $stmt = $conn->prepare($query);
                    $success = $stmt->execute([$titulo, $duracion, $clasificacion, $genero, $sinopsis, $estado, $imagen, $id]);
                } else {
                    $query = "UPDATE Peliculas SET titulo=?, duracion_minutos=?, clasificacion=?, genero=?, sinopsis=?, estado=? WHERE id_pelicula=?";
                    $stmt = $conn->prepare($query);
                    $success = $stmt->execute([$titulo, $duracion, $clasificacion, $genero, $sinopsis, $estado, $id]);
                }

                if ($success && isset($_POST['id_empleado'])) {
                    $id_empleado = $_POST['id_empleado'];
                    $accion = 'Editar Película';
                    $detalles = "ID: $id, Título: $titulo, Duración: $duracion";
                    $bitacora_query = "INSERT INTO BitacoraEmpleados (id_empleado, accion, detalles) VALUES (?, ?, ?)";
                    $bitacora_stmt = $conn->prepare($bitacora_query);
                    $bitacora_stmt->execute([$id_empleado, $accion, $detalles]);
                }

                echo json_encode(['success' => $success]);
                exit;
            }
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    echo json_encode(['success' => false, 'error' => 'Datos insuficientes']);
    exit;
}

// Eliminar película (borrado físico)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $data);
    $id = $data['id_pelicula'] ?? null;
    if ($id) {
        try {
            $query = "DELETE FROM Peliculas WHERE id_pelicula=?";
            $stmt = $conn->prepare($query);
            $success = $stmt->execute([$id]);
            echo json_encode(['success' => $success]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
    }
    exit;
}

// Si no coincide ningún método, responde vacío
echo json_encode([]);
?>