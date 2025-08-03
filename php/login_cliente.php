<?php
header('Content-Type: application/json');
require_once("conexion.php");
require_once("client_info.php");

// Desactivar advertencias
error_reporting(0);

$data = json_decode(file_get_contents("php://input"), true);

// Validar que las claves esperadas estén presentes
if (!isset($data['correo'], $data['contrasena'])) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos.']);
    exit;
}

$correo = $data['correo'];
$contrasena = $data['contrasena'];

// Obtener información del cliente (IP y User-Agent)
$clientInfo = obtenerInfoCliente();

try {
    // Verificar que el correo exista
    $query = "SELECT * FROM Clientes WHERE correo = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$correo]);
    $cliente = $stmt->fetch();

    if ($cliente) {
        // Validar IP si está configurado
        if (!validarIP($clientInfo['ip'])) {
            // Registrar intento bloqueado
            registrarBitacoraCliente(
                $conn, 
                $cliente['id_cliente'], 
                'Intento de acceso bloqueado', 
                'IP bloqueada: ' . $clientInfo['ip']
            );
            echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
            exit;
        }

        // Verificar contraseña usando la función de PostgreSQL
        $query_verify = "SELECT verify_password(?, ?) as password_match";
        $stmt_verify = $conn->prepare($query_verify);
        $stmt_verify->execute([$contrasena, $cliente['contrasena']]);
        $verify_result = $stmt_verify->fetch();
        
        if ($verify_result && $verify_result['password_match']) {
            // Detectar actividad sospechosa
            $actividadSospechosa = detectarActividadSospechosa($conn, $cliente['id_cliente'], 'cliente');
            
            // Registrar inicio de sesión exitoso en la bitácora con información del cliente
            $userAgentInfo = parsearUserAgent($clientInfo['user_agent']);
            $detalles = sprintf(
                'Inicio de sesión exitoso. IP: %s, Navegador: %s %s, SO: %s, Dispositivo: %s%s',
                $clientInfo['ip'],
                $userAgentInfo['navegador'],
                $userAgentInfo['version'],
                $userAgentInfo['sistema_operativo'],
                $userAgentInfo['dispositivo'],
                $actividadSospechosa ? ' [ACTIVIDAD SOSPECHOSA DETECTADA]' : ''
            );
            
            registrarBitacoraCliente(
                $conn, 
                $cliente['id_cliente'], 
                'Inicio de sesión', 
                $detalles
            );

            $response = [
                'success' => true, 
                'id' => $cliente['id_cliente'], 
                'nombre' => $cliente['nombre'],
                'client_info' => [
                    'ip' => $clientInfo['ip'],
                    'navegador' => $userAgentInfo['navegador'],
                    'sistema_operativo' => $userAgentInfo['sistema_operativo']
                ]
            ];

            // Agregar advertencia si hay actividad sospechosa
            if ($actividadSospechosa) {
                $response['warning'] = 'Se detectó actividad inusual en su cuenta. Si no fue usted, cambie su contraseña.';
            }

            echo json_encode($response);
        } else {
            // Registrar intento fallido
            registrarBitacoraCliente(
                $conn, 
                $cliente['id_cliente'], 
                'Intento de inicio de sesión fallido', 
                'Contraseña incorrecta. IP: ' . $clientInfo['ip']
            );
            echo json_encode(['success' => false, 'error' => 'Contraseña incorrecta.']);
        }
    } else {
        // Registrar intento con correo no existente (sin ID de cliente)
        error_log("Intento de login con correo no existente: $correo desde IP: " . $clientInfo['ip']);
        echo json_encode(['success' => false, 'error' => 'Correo no registrado.']);
    }
} catch (PDOException $e) {
    error_log("Error en login_cliente.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error de base de datos.']);
}
exit;
?>