<?php
header('Content-Type: application/json');
require_once("conexion.php");
require_once("client_info.php");

$data = json_decode(file_get_contents("php://input"), true);

// Validar datos de entrada
if (!isset($data['usuario'], $data['contrasena'])) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos.']);
    exit;
}

$usuario = $data['usuario'];
$contrasena = $data['contrasena'];

// Obtener información del cliente (IP y User-Agent)
$clientInfo = obtenerInfoCliente();

try {
    // Validar IP si está configurado
    if (!validarIP($clientInfo['ip'])) {
        error_log("Intento de login de empleado bloqueado desde IP: " . $clientInfo['ip']);
        echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
        exit;
    }

    // Verificar usuario y contraseña
    $query = "SELECT * FROM Empleados WHERE usuario = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$usuario]);
    $empleado = $stmt->fetch();

    if ($empleado) {
        // Verificar contraseña usando la función de PostgreSQL
        $query_verify = "SELECT verify_password(?, ?) as password_match";
        $stmt_verify = $conn->prepare($query_verify);
        $stmt_verify->execute([$contrasena, $empleado['contrasena']]);
        $verify_result = $stmt_verify->fetch();
        
        if ($verify_result && $verify_result['password_match']) {
            // Detectar actividad sospechosa
            $actividadSospechosa = detectarActividadSospechosa($conn, $empleado['id_empleado'], 'empleado');
            
            // Registrar inicio de sesión exitoso en la bitácora con información del cliente
            $userAgentInfo = parsearUserAgent($clientInfo['user_agent']);
            $detalles = sprintf(
                'Inicio de sesión exitoso. Usuario: %s, IP: %s, Navegador: %s %s, SO: %s, Dispositivo: %s%s',
                $empleado['usuario'],
                $clientInfo['ip'],
                $userAgentInfo['navegador'],
                $userAgentInfo['version'],
                $userAgentInfo['sistema_operativo'],
                $userAgentInfo['dispositivo'],
                $actividadSospechosa ? ' [ACTIVIDAD SOSPECHOSA DETECTADA]' : ''
            );
            
            registrarBitacoraEmpleado(
                $conn, 
                $empleado['id_empleado'], 
                'Inicio de sesión', 
                $detalles
            );

            $response = [
                'success' => true, 
                'id' => $empleado['id_empleado'], 
                'nombre' => $empleado['nombre'], 
                'rol' => $empleado['rol'],
                'client_info' => [
                    'ip' => $clientInfo['ip'],
                    'navegador' => $userAgentInfo['navegador'],
                    'sistema_operativo' => $userAgentInfo['sistema_operativo']
                ]
            ];

            // Agregar advertencia si hay actividad sospechosa
            if ($actividadSospechosa) {
                $response['warning'] = 'Se detectó actividad inusual en su cuenta. Si no fue usted, contacte al administrador.';
            }

            echo json_encode($response);
        } else {
            // Registrar intento fallido
            registrarBitacoraEmpleado(
                $conn, 
                $empleado['id_empleado'], 
                'Intento de inicio de sesión fallido', 
                'Contraseña incorrecta. Usuario: ' . $usuario . ', IP: ' . $clientInfo['ip']
            );
            echo json_encode(['success' => false, 'error' => 'Contraseña incorrecta.']);
        }
    } else {
        // Registrar intento con usuario no existente
        error_log("Intento de login de empleado con usuario no existente: $usuario desde IP: " . $clientInfo['ip']);
        echo json_encode(['success' => false, 'error' => 'Usuario no encontrado.']);
    }
} catch (PDOException $e) {
    error_log("Error en login_empleado.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error de base de datos.']);
}
?>