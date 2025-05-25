<?php
header('Content-Type: application/json');
require_once("conexion.php");

// Listar películas
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = pg_query($conn, "SELECT * FROM Peliculas WHERE estado = TRUE ORDER BY id_pelicula DESC");
    $peliculas = [];
    while ($row = pg_fetch_assoc($result)) {
        $peliculas[] = $row;
    }
    echo json_encode($peliculas);
    exit;
}

// Agregar película
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $titulo = $data['titulo'];
    $duracion = $data['duracion_minutos'];
    $clasificacion = $data['clasificacion'];
    $genero = $data['genero'];
    $sinopsis = $data['sinopsis'];
    $estado = isset($data['estado']) ? $data['estado'] : true;

    $query = "INSERT INTO Peliculas (titulo, duracion_minutos, clasificacion, genero, sinopsis, estado)
              VALUES ($1, $2, $3, $4, $5, $6)";
    $result = pg_query_params($conn, $query, [$titulo, $duracion, $clasificacion, $genero, $sinopsis, $estado]);
    echo json_encode(['success' => $result]);
    exit;
}

// Editar película
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = $data['id_pelicula'];
    $titulo = $data['titulo'];
    $duracion = $data['duracion_minutos'];
    $clasificacion = $data['clasificacion'];
    $genero = $data['genero'];
    $sinopsis = $data['sinopsis'];
    $estado = isset($data['estado']) ? $data['estado'] : true;

    $query = "UPDATE Peliculas SET titulo=$1, duracion_minutos=$2, clasificacion=$3, genero=$4, sinopsis=$5, estado=$6 WHERE id_pelicula=$7";
    $result = pg_query_params($conn, $query, [$titulo, $duracion, $clasificacion, $genero, $sinopsis, $estado, $id]);
    echo json_encode(['success' => $result]);
    exit;
}

// Eliminar película (borrado lógico)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $data);
    $id = $data['id_pelicula'];
    $query = "UPDATE Peliculas SET estado=FALSE WHERE id_pelicula=$1";
    $result = pg_query_params($conn, $query, [$id]);
    echo json_encode(['success' => $result]);
    exit;
}
?>