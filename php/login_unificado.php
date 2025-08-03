<?php
header('Content-Type: application/json');
require_once("conexion.php");
require_once("client_info.php");

// Desactivar advertencias
error_reporting(0);

$data = json_decode(file_get_contents("php://input"), true);

// Validar que las claves esperadas estén presentes
if (!isset($data['credencial'], $data['contrasena'])) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos.']);
    exit;
}

$credencial = trim($data['credencial']); // Puede ser correo o usuario
$contrasena = $data['contrasena'];

// Obtener información del cliente (IP y User-Agent)
$clientInfo = obtenerInfoCliente();

try {
    // Validar IP si está configurado
    if (!validarIP($clientInfo['ip'])) {
        error_log("Intento de login bloqueado desde IP: " . $clientInfo['ip']);
        echo json_encode(['success' => false, 'error' => 'Acceso denegado.']);
        exit;
    }

    $usuario = null;
    $tipoUsuario = null;

    // Paso 1: Intentar buscar en Clientes por correo
    $query_cliente = "SELECT *, 'cliente' as tipo FROM Clientes WHERE correo = ?";
    $stmt_cliente = $conn->prepare($query_cliente);
    $stmt_cliente->execute([$credencial]);
    $resultado_cliente = $stmt_cliente->fetch();

    if ($resultado_cliente) {
        $usuario = $resultado_cliente;
        $tipoUsuario = 'cliente';
    } else {
        // Paso 2: Si no se encontró en clientes, buscar en Empleados
        // Buscar por usuario o por correo (si tienen correo configurado)
        $query_empleado = "SELECT *, 'empleado' as tipo FROM Empleados WHERE usuario = ? OR correo = ?";
        $stmt_empleado = $conn->prepare($query_empleado);
        $stmt_empleado->execute([$credencial, $credencial]);
        $resultado_empleado = $stmt_empleado->fetch();

        if ($resultado_empleado) {
            $usuario = $resultado_empleado;
            $tipoUsuario = 'empleado';
        }
    }

    if ($usuario) {
        // Verificar contraseña usando la función de PostgreSQL
        $query_verify = "SELECT verify_password(?, ?) as password_match";
        $stmt_verify = $conn->prepare($query_verify);
        $stmt_verify->execute([$contrasena, $usuario['contrasena']]);
        $verify_result = $stmt_verify->fetch();
        
        if ($verify_result && $verify_result['password_match']) {
            // Detectar actividad sospechosa
            $userId = $tipoUsuario === 'cliente' ? $usuario['id_cliente'] : $usuario['id_empleado'];
            $actividadSospechosa = detectarActividadSospechosa($conn, $userId, $tipoUsuario);
            
            // Preparar información del User-Agent
            $userAgentInfo = parsearUserAgent($clientInfo['user_agent']);
            
            // Preparar datos de respuesta según el tipo de usuario
            if ($tipoUsuario === 'cliente') {
                // Registrar en bitácora de clientes
                $detalles = sprintf(
                    'Inicio de sesión exitoso. IP: %s, Navegador: %s %s, SO: %s, Dispositivo: %s%s',
                    $clientInfo['ip'],
                    $userAgentInfo['navegador'],
                    $userAgentInfo['version'],
                    $userAgentInfo['sistema_operativo'],
                    $userAgentInfo['dispositivo'],
                    $actividadSospechosa ? ' [ACTIVIDAD SOSPECHOSA DETECTADA]' : ''
                );
                
                registrarBitacoraCliente($conn, $usuario['id_cliente'], 'Inicio de sesión', $detalles);
                
                // Respuesta para cliente
                echo json_encode([
                    'success' => true,
                    'tipo' => 'cliente',
                    'usuario' => [
                        'id' => $usuario['id_cliente'],
                        'nombre' => $usuario['nombre'],
                        'correo' => $usuario['correo'],
                        'telefono' => $usuario['telefono']
                    ],
                    'redirect' => '/html/funciones.html',
                    'mensaje' => 'Bienvenido, ' . $usuario['nombre'],
                    'client_info' => $clientInfo,
                    'user_agent_info' => $userAgentInfo,
                    'actividad_sospechosa' => $actividadSospechosa
                ]);
                
            } else { // empleado
                // Registrar en bitácora de empleados
                $detalles = sprintf(
                    'Inicio de sesión exitoso. Usuario: %s, IP: %s, Navegador: %s %s, SO: %s, Dispositivo: %s%s',
                    $usuario['usuario'],
                    $clientInfo['ip'],
                    $userAgentInfo['navegador'],
                    $userAgentInfo['version'],
                    $userAgentInfo['sistema_operativo'],
                    $userAgentInfo['dispositivo'],
                    $actividadSospechosa ? ' [ACTIVIDAD SOSPECHOSA DETECTADA]' : ''
                );
                
                registrarBitacoraEmpleado($conn, $usuario['id_empleado'], 'Inicio de sesión', $detalles);
                
                // Respuesta para empleado
                echo json_encode([
                    'success' => true,
                    'tipo' => 'empleado',
                    'usuario' => [
                        'id' => $usuario['id_empleado'],
                        'nombre' => $usuario['nombre'],
                        'cargo' => $usuario['cargo'],
                        'rol' => $usuario['rol'],
                        'usuario' => $usuario['usuario'],
                        'correo' => $usuario['correo'] ?? null
                    ],
                    'redirect' => '/html/administracion.html',
                    'mensaje' => 'Bienvenido al panel de administración, ' . $usuario['nombre'],
                    'client_info' => $clientInfo,
                    'user_agent_info' => $userAgentInfo,
                    'actividad_sospechosa' => $actividadSospechosa
                ]);
            }
            
        } else {
            // Contraseña incorrecta - registrar intento fallido
            if ($tipoUsuario === 'cliente') {
                $detalles = sprintf(
                    'Intento de inicio de sesión fallido. IP: %s, Navegador: %s %s, SO: %s',
                    $clientInfo['ip'],
                    $userAgentInfo['navegador'],
                    $userAgentInfo['version'],
                    $userAgentInfo['sistema_operativo']
                );
                registrarBitacoraCliente($conn, $usuario['id_cliente'], 'Intento de acceso fallido', $detalles);
            } else {
                $detalles = sprintf(
                    'Intento de inicio de sesión fallido. Usuario: %s, IP: %s, Navegador: %s %s, SO: %s',
                    $usuario['usuario'],
                    $clientInfo['ip'],
                    $userAgentInfo['navegador'],
                    $userAgentInfo['version'],
                    $userAgentInfo['sistema_operativo']
                );
                registrarBitacoraEmpleado($conn, $usuario['id_empleado'], 'Intento de acceso fallido', $detalles);
            }
            
            echo json_encode(['success' => false, 'error' => 'Contraseña incorrecta.']);
        }
    } else {
        // Usuario/correo no encontrado
        error_log("Intento de login con credencial no encontrada: " . $credencial . " desde IP: " . $clientInfo['ip']);
        echo json_encode(['success' => false, 'error' => 'Credenciales incorrectas.']);
    }

} catch (Exception $e) {
    error_log("Error en login unificado: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor.']);
}
?>
