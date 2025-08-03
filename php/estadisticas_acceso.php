<?php
header('Content-Type: application/json');
require_once("conexion.php");
require_once("client_info.php");

try {
    // GET - Obtener estadísticas de acceso
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'clientes'; // clientes o empleados
        $limite = isset($_GET['limite']) ? min(50, max(1, (int)$_GET['limite'])) : 20;
        $dias = isset($_GET['dias']) ? max(1, (int)$_GET['dias']) : 30;

        $tabla = $tipo === 'empleados' ? 'BitacoraEmpleados' : 'BitacoraClientes';
        $campo_id = $tipo === 'empleados' ? 'id_empleado' : 'id_cliente';
        $tabla_usuario = $tipo === 'empleados' ? 'Empleados' : 'Clientes';

        // Estadísticas generales
        $statsQuery = "SELECT 
                          COUNT(*) as total_accesos,
                          COUNT(DISTINCT ip_address) as ips_unicas,
                          COUNT(DISTINCT $campo_id) as usuarios_activos,
                          COUNT(DISTINCT DATE(fecha_hora)) as dias_con_actividad
                       FROM $tabla 
                       WHERE fecha_hora >= NOW() - INTERVAL '$dias days'";
        
        $statsStmt = $conn->prepare($statsQuery);
        $statsStmt->execute();
        $stats = $statsStmt->fetch();

        // Top IPs por número de accesos
        $topIPsQuery = "SELECT ip_address, 
                               COUNT(*) as total_accesos,
                               COUNT(DISTINCT $campo_id) as usuarios_diferentes,
                               MAX(fecha_hora) as ultimo_acceso,
                               MIN(fecha_hora) as primer_acceso
                        FROM $tabla 
                        WHERE ip_address IS NOT NULL 
                          AND fecha_hora >= NOW() - INTERVAL '$dias days'
                        GROUP BY ip_address 
                        ORDER BY total_accesos DESC 
                        LIMIT ?";
        
        $topIPsStmt = $conn->prepare($topIPsQuery);
        $topIPsStmt->execute([$limite]);
        $topIPs = $topIPsStmt->fetchAll();

        // Usuarios más activos
        $usuariosActivosQuery = "SELECT b.$campo_id,
                                        u.nombre,
                                        " . ($tipo === 'clientes' ? 'u.correo' : 'u.usuario') . " as identificador,
                                        COUNT(*) as total_accesos,
                                        COUNT(DISTINCT ip_address) as ips_diferentes,
                                        MAX(b.fecha_hora) as ultimo_acceso
                                 FROM $tabla b
                                 LEFT JOIN $tabla_usuario u ON b.$campo_id = u.$campo_id
                                 WHERE b.fecha_hora >= NOW() - INTERVAL '$dias days'
                                 GROUP BY b.$campo_id, u.nombre, identificador
                                 ORDER BY total_accesos DESC
                                 LIMIT ?";
        
        $usuariosStmt = $conn->prepare($usuariosActivosQuery);
        $usuariosStmt->execute([$limite]);
        $usuariosActivos = $usuariosStmt->fetchAll();

        // Navegadores más utilizados
        $navegadoresQuery = "SELECT 
                                CASE 
                                    WHEN user_agent ILIKE '%Chrome%' THEN 'Chrome'
                                    WHEN user_agent ILIKE '%Firefox%' THEN 'Firefox'
                                    WHEN user_agent ILIKE '%Safari%' AND user_agent NOT ILIKE '%Chrome%' THEN 'Safari'
                                    WHEN user_agent ILIKE '%Edge%' THEN 'Edge'
                                    WHEN user_agent ILIKE '%Opera%' THEN 'Opera'
                                    ELSE 'Otros'
                                END as navegador,
                                COUNT(*) as total_usos
                             FROM $tabla 
                             WHERE user_agent IS NOT NULL 
                               AND fecha_hora >= NOW() - INTERVAL '$dias days'
                             GROUP BY navegador
                             ORDER BY total_usos DESC";
        
        $navegadoresStmt = $conn->prepare($navegadoresQuery);
        $navegadoresStmt->execute();
        $navegadores = $navegadoresStmt->fetchAll();

        // Actividad por día
        $actividadDiariaQuery = "SELECT DATE(fecha_hora) as fecha,
                                        COUNT(*) as accesos,
                                        COUNT(DISTINCT $campo_id) as usuarios_unicos,
                                        COUNT(DISTINCT ip_address) as ips_unicas
                                 FROM $tabla 
                                 WHERE fecha_hora >= NOW() - INTERVAL '$dias days'
                                 GROUP BY DATE(fecha_hora)
                                 ORDER BY fecha DESC";
        
        $actividadStmt = $conn->prepare($actividadDiariaQuery);
        $actividadStmt->execute();
        $actividadDiaria = $actividadStmt->fetchAll();

        // Detección de posible actividad sospechosa
        $sospechosaQuery = "SELECT ip_address,
                                   COUNT(DISTINCT $campo_id) as usuarios_diferentes,
                                   COUNT(*) as total_accesos,
                                   array_agg(DISTINCT accion) as acciones
                            FROM $tabla 
                            WHERE fecha_hora >= NOW() - INTERVAL '24 hours'
                            GROUP BY ip_address
                            HAVING COUNT(DISTINCT $campo_id) > 3
                            ORDER BY usuarios_diferentes DESC, total_accesos DESC";
        
        $sospechosaStmt = $conn->prepare($sospechosaQuery);
        $sospechosaStmt->execute();
        $actividadSospechosa = $sospechosaStmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => [
                'estadisticas_generales' => $stats,
                'top_ips' => $topIPs,
                'usuarios_activos' => $usuariosActivos,
                'navegadores' => $navegadores,
                'actividad_diaria' => $actividadDiaria,
                'actividad_sospechosa' => $actividadSospechosa
            ],
            'configuracion' => [
                'tipo' => $tipo,
                'dias_analizados' => $dias,
                'limite' => $limite
            ]
        ]);
        exit;
    }

    // POST - Bloquear IP (solo para administradores)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['ip_address']) || !isset($data['id_empleado_admin'])) {
            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
            exit;
        }

        $ip_address = $data['ip_address'];
        $motivo = $data['motivo'] ?? 'Actividad sospechosa';
        $id_empleado_admin = $data['id_empleado_admin'];

        // Verificar que el empleado sea administrador
        $adminQuery = "SELECT rol FROM Empleados WHERE id_empleado = ?";
        $adminStmt = $conn->prepare($adminQuery);
        $adminStmt->execute([$id_empleado_admin]);
        $admin = $adminStmt->fetch();

        if (!$admin || $admin['rol'] !== 'Administrador') {
            echo json_encode(['success' => false, 'error' => 'Solo administradores pueden bloquear IPs']);
            exit;
        }

        // Registrar el bloqueo en bitácora
        registrarBitacoraEmpleado(
            $conn,
            $id_empleado_admin,
            'Bloqueo de IP',
            "IP bloqueada: $ip_address. Motivo: $motivo"
        );

        echo json_encode([
            'success' => true, 
            'message' => "IP $ip_address bloqueada exitosamente",
            'nota' => 'Implementar lógica de bloqueo según necesidades del sistema'
        ]);
        exit;
    }

} catch (PDOException $e) {
    error_log("Error en estadisticas_acceso.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error de base de datos']);
}

echo json_encode(['success' => false, 'error' => 'Método no válido']);
?>
