<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require_once("conexion.php");
$resourcesDir = realpath(__DIR__ . '/../resources/');

// Listar películas
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = pg_query($conn, "SELECT * FROM Peliculas WHERE estado = TRUE ORDER BY id_pelicula DESC");
    $peliculas = [];
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            // Agrega la ruta completa de la imagen si existe
            if (!empty($row['imagen'])) {
                $row['imagen_url'] = '../resources/' . $row['imagen'];
            }
            $peliculas[] = $row;
        }
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

    if ($modo === 'agregar') {
        $query = "INSERT INTO Peliculas (titulo, duracion_minutos, clasificacion, genero, sinopsis, estado, imagen)
                  VALUES ($1, $2, $3, $4, $5, $6, $7)";
        $result = pg_query_params($conn, $query, [$titulo, $duracion, $clasificacion, $genero, $sinopsis, $estado, $imagen]);
        echo json_encode(['success' => (bool)$result]);
        exit;
    } elseif ($modo === 'editar') {
        $id = $_POST['id_pelicula'] ?? null;
        if ($id) {
            if ($imagen) {
                $query = "UPDATE Peliculas SET titulo=$1, duracion_minutos=$2, clasificacion=$3, genero=$4, sinopsis=$5, estado=$6, imagen=$7 WHERE id_pelicula=$8";
                $params = [$titulo, $duracion, $clasificacion, $genero, $sinopsis, $estado, $imagen, $id];
            } else {
                $query = "UPDATE Peliculas SET titulo=$1, duracion_minutos=$2, clasificacion=$3, genero=$4, sinopsis=$5, estado=$6 WHERE id_pelicula=$7";
                $params = [$titulo, $duracion, $clasificacion, $genero, $sinopsis, $estado, $id];
            }
            $result = pg_query_params($conn, $query, $params);
            echo json_encode(['success' => (bool)$result]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'error' => 'Datos insuficientes']);
    exit;
}

// Eliminar película (borrado lógico)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $data);
    $id = $data['id_pelicula'] ?? null;
    if ($id) {
        // Borrado físico:
        $query = "DELETE FROM Peliculas WHERE id_pelicula=$1";
        $result = pg_query_params($conn, $query, [$id]);
        echo json_encode(['success' => (bool)$result]);
    } else {
        echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
    }
    exit;
}

// Si no coincide ningún método, responde vacío
echo json_encode([]);
?>