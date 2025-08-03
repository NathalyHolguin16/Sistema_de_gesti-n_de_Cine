<?php
header('Content-Type: application/json');
require_once("conexion.php");
require_once("client_info.php");

// Desactivar advertencias
error_reporting(0);

$data = json_decode(file_get_contents("php://input"), true);

// Validar que las claves esperadas estén presentes
if (!isset($data['nombre'], $data['correo'], $data['telefono'], $data['contrasena'])) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos.']);
    exit;
}

$nombre = $data['nombre'];
$correo = $data['correo'];
$telefono = $data['telefono'];
$contrasena = $data['contrasena']; // La base de datos se encarga del hasheo automáticamente

// Obtener información del cliente (IP y User-Agent)
$clientInfo = obtenerInfoCliente();

// Validar IP si está configurado
if (!validarIP($clientInfo['ip'])) {
    error_log("Intento de registro bloqueado desde IP: " . $clientInfo['ip']);
    echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
    exit;
}

try {
    // Verificar que el correo sea único
    $query = "SELECT * FROM Clientes WHERE correo = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$correo]);
    $result = $stmt->fetch();

    if ($result) {
        echo json_encode(['success' => false, 'error' => 'El correo ya está registrado.']);
        exit;
    }

    // Insertar el cliente y obtener el ID generado
    $query = "INSERT INTO Clientes (nombre, correo, telefono, contrasena) VALUES (?, ?, ?, ?) RETURNING id_cliente";
    $stmt = $conn->prepare($query);
    $stmt->execute([$nombre, $correo, $telefono, $contrasena]);
    $cliente = $stmt->fetch();

    if ($cliente) {
        // Registrar la acción de registro en la bitácora con información del cliente
        $userAgentInfo = parsearUserAgent($clientInfo['user_agent']);
        $detalles = sprintf(
            'Registro de nuevo cliente. IP: %s, Navegador: %s %s, SO: %s, Dispositivo: %s',
            $clientInfo['ip'],
            $userAgentInfo['navegador'],
            $userAgentInfo['version'],
            $userAgentInfo['sistema_operativo'],
            $userAgentInfo['dispositivo']
        );
        
        registrarBitacoraCliente(
            $conn, 
            $cliente['id_cliente'], 
            'Registro de cliente', 
            $detalles
        );

        $response = [
            'success' => true, 
            'id' => $cliente['id_cliente'],
            'message' => 'Cliente registrado exitosamente',
            'client_info' => [
                'ip' => $clientInfo['ip'],
                'navegador' => $userAgentInfo['navegador'],
                'sistema_operativo' => $userAgentInfo['sistema_operativo']
            ]
        ];

        echo json_encode($response);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al insertar el cliente.']);
    }
} catch (PDOException $e) {
    error_log("Error en registro_cliente.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error de base de datos.']);
}
exit;
?>