<?php
require_once("funciones/auth_functions.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    // Registrar nuevo cliente
    if (isset($data['action']) && $data['action'] === 'register') {
        if (!isset($data['password']) || !isset($data['nombre']) || !isset($data['correo'])) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
            exit;
        }

        // Validar fortaleza de la contraseña
        $validacion = validate_password_strength($data['password']);
        if (!$validacion['valid']) {
            echo json_encode(['success' => false, 'error' => $validacion['message']]);
            exit;
        }

        // Generar hash de la contraseña
        $hashed_password = hash_cliente_password($data['password']);
        if (!$hashed_password) {
            echo json_encode(['success' => false, 'error' => 'Error al procesar la contraseña']);
            exit;
        }

        try {
            $query = "INSERT INTO Clientes (nombre, correo, telefono, contrasena) 
                     VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $success = $stmt->execute([
                $data['nombre'],
                $data['correo'],
                $data['telefono'] ?? null,
                $hashed_password
            ]);

            if ($success) {
                $id_cliente = $conn->lastInsertId();
                $cliente = get_client_info($id_cliente);
                echo json_encode(['success' => true, 'data' => $cliente]);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Error al registrar cliente']);
            error_log($e->getMessage());
        }
    }

    // Login de cliente
    if (isset($data['action']) && $data['action'] === 'login') {
        if (!isset($data['correo']) || !isset($data['password'])) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
            exit;
        }

        try {
            $query = "SELECT id_cliente, contrasena FROM Clientes WHERE correo = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$data['correo']]);
            $cliente = $stmt->fetch();

            if ($cliente && verify_password($data['password'], $cliente['contrasena'])) {
                // Obtener información completa del cliente
                $info_cliente = get_client_info($cliente['id_cliente']);
                echo json_encode(['success' => true, 'data' => $info_cliente]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Credenciales inválidas']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Error al iniciar sesión']);
            error_log($e->getMessage());
        }
    }
}
?>
