<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require_once("conexion.php");
$resourcesDir = realpath(__DIR__ . '/../resources/');

// Obtener película por ID
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id_pelicula'])) {
    $id_pelicula = $_GET['id_pelicula'];
    $query = "SELECT * FROM Peliculas WHERE id_pelicula = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id_pelicula);
    $stmt->execute();
    $result = $stmt->get_result();
    $pelicula = $result->fetch_assoc();
    echo json_encode($pelicula);
    exit;
}

// Listar películas
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Modify the query to include movies with estado = NULL
    $query = "SELECT * FROM Peliculas WHERE estado IS NULL OR estado = TRUE ORDER BY id_pelicula DESC";
    $result = $conn->query($query);

    if (!$result) {
        echo json_encode(['success' => false, 'error' => $conn->error]);
        exit;
    }

    $peliculas = [];
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['imagen'])) {
            $row['imagen_url'] = '../resources/' . $row['imagen'];
        }
        $peliculas[] = $row;
    }

    echo json_encode($peliculas);
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

    // Add bitácora logging for movie actions
    if ($modo === 'agregar') {
        $query = "INSERT INTO Peliculas (titulo, duracion_minutos, clasificacion, genero, sinopsis, estado, imagen)
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sisssis", $titulo, $duracion, $clasificacion, $genero, $sinopsis, $estado, $imagen);
        $success = $stmt->execute();

        if ($success && isset($_POST['id_empleado'])) {
            $id_empleado = $_POST['id_empleado'];
            $accion = 'Agregar Película';
            $detalles = "Título: $titulo, Duración: $duracion, Clasificación: $clasificacion";
            $bitacora_query = "INSERT INTO BitacoraEmpleados (id_empleado, accion, detalles) VALUES (?, ?, ?)";
            $bitacora_stmt = $conn->prepare($bitacora_query);
            $bitacora_stmt->bind_param("iss", $id_empleado, $accion, $detalles);
            $bitacora_stmt->execute();
        }

        echo json_encode(['success' => $success]);
        $stmt->close();
        exit;
    } elseif ($modo === 'editar') {
        $id = $_POST['id_pelicula'] ?? null;
        if ($id) {
            if ($imagen) {
                $query = "UPDATE Peliculas SET titulo=?, duracion_minutos=?, clasificacion=?, genero=?, sinopsis=?, estado=?, imagen=? WHERE id_pelicula=?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sisssisi", $titulo, $duracion, $clasificacion, $genero, $sinopsis, $estado, $imagen, $id);
            } else {
                $query = "UPDATE Peliculas SET titulo=?, duracion_minutos=?, clasificacion=?, genero=?, sinopsis=?, estado=? WHERE id_pelicula=?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sisssii", $titulo, $duracion, $clasificacion, $genero, $sinopsis, $estado, $id);
            }
            $success = $stmt->execute();

            if ($success && isset($_POST['id_empleado'])) {
                $id_empleado = $_POST['id_empleado'];
                $accion = 'Editar Película';
                $detalles = "ID: $id, Título: $titulo, Duración: $duracion";
                $bitacora_query = "INSERT INTO BitacoraEmpleados (id_empleado, accion, detalles) VALUES (?, ?, ?)";
                $bitacora_stmt = $conn->prepare($bitacora_query);
                $bitacora_stmt->bind_param("iss", $id_empleado, $accion, $detalles);
                $bitacora_stmt->execute();
            }

            echo json_encode(['success' => $success]);
            $stmt->close();
            exit;
        }
    }
    echo json_encode(['success' => false, 'error' => 'Datos insuficientes']);
    exit;
}

// Eliminar película (borrado físico)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $data);
    $id = $data['id_pelicula'] ?? null;
    if ($id) {
        $query = "DELETE FROM Peliculas WHERE id_pelicula=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        echo json_encode(['success' => $success]);
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
    }
    exit;
}

// Si no coincide ningún método, responde vacío
echo json_encode([]);
?>