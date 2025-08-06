<?php
require_once 'conexion.php';

class Comidas {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->conectar();
    }

    // Obtener todas las comidas
    public function obtenerComidas() {
        $query = "SELECT * FROM Comidas ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener comida por ID
    public function obtenerComidaPorId($id) {
        $query = "SELECT * FROM Comidas WHERE id_comida = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Agregar nueva comida
    public function agregarComida($nombre_comida, $descripcion, $precio, $tipo, $imagen) {
        $query = "INSERT INTO comida (nombre_comida, descripcion, precio, tipo, imagen) 
                 VALUES (:nombre_comida, :descripcion, :precio, :tipo, :imagen)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre_comida', $nombre_comida);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':precio', $precio);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':imagen', $imagen);
        return $stmt->execute();
    }

    // Actualizar comida
    public function actualizarComida($id, $nombre_comida, $descripcion, $precio, $tipo, $imagen) {
        $query = "UPDATE comida 
                 SET nombre_comida = :nombre_comida, 
                     descripcion = :descripcion, 
                     precio = :precio, 
                     tipo = :tipo";
        
        if ($imagen) {
            $query .= ", imagen = :imagen";
        }
        
        $query .= " WHERE id_comida = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':precio', $precio);
        $stmt->bindParam(':tipo', $tipo);
        
        if ($imagen) {
            $stmt->bindParam(':imagen', $imagen);
        }
        
        return $stmt->execute();
    }

    // Eliminar comida
    public function eliminarComida($id) {
        $query = "DELETE FROM Comidas WHERE id_comida = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // Obtener comidas por tipo
    public function obtenerComidasPorTipo($tipo) {
        $query = "SELECT * FROM Comidas WHERE tipo = :tipo ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
