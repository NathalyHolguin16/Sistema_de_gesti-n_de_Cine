<?php
/**
 * Funciones auxiliares para obtener información del cliente
 * IP Address y User-Agent para bitácoras
 */

/**
 * Obtiene la dirección IP real del cliente
 * Considera proxies, load balancers y CDNs
 */
function obtenerIPCliente() {
    // Lista de headers que pueden contener la IP real
    $headers = [
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'HTTP_CLIENT_IP',
        'HTTP_CF_CONNECTING_IP', // Cloudflare
        'HTTP_X_CLUSTER_CLIENT_IP',
        'REMOTE_ADDR'
    ];
    
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            $ip = trim($ips[0]);
            
            // Validar que sea una IP válida
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                // Log para debugging
                error_log("IP detectada desde $header: $ip");
                
                // Si es localhost IPv6, intentar obtener IP real de LAN
                if ($ip === '::1') {
                    $hostname = gethostname();
                    $localIP = gethostbyname($hostname);
                    
                    error_log("Localhost IPv6 detectado, intentando obtener IP real");
                    error_log("Hostname: $hostname, IP local: $localIP");
                    
                    // Si obtenemos una IP válida que no sea localhost
                    if ($localIP && $localIP !== '127.0.0.1' && $localIP !== $hostname && filter_var($localIP, FILTER_VALIDATE_IP)) {
                        error_log("Usando IP real de LAN: $localIP");
                        return $localIP;
                    }
                }
                
                return $ip;
            }
        }
    }
    
    // Si no se encuentra ninguna IP válida, usar un fallback
    $fallback = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    error_log("IP fallback utilizada: $fallback");
    return $fallback;
}

/**
 * Obtiene y limpia el User-Agent del cliente
 */
function obtenerUserAgent() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // Log para debugging
    error_log("User-Agent original: " . $userAgent);
    
    // Limitar longitud para evitar ataques
    $userAgent = substr($userAgent, 0, 500);
    
    // Escapar caracteres especiales
    $cleaned = htmlspecialchars($userAgent, ENT_QUOTES, 'UTF-8');
    
    error_log("User-Agent limpiado: " . $cleaned);
    
    return $cleaned;
}

/**
 * Obtiene solo el navegador y versión para la bitácora (formato limpio)
 */
function obtenerNavegadorLimpio() {
    $userAgent = obtenerUserAgent();
    $infoCompleta = parsearUserAgent($userAgent);
    
    // Formato: "Navegador Versión"
    $navegadorLimpio = $infoCompleta['navegador'];
    if (!empty($infoCompleta['version'])) {
        $navegadorLimpio .= ' ' . $infoCompleta['version'];
    }
    
    error_log("Navegador limpio para bitácora: " . $navegadorLimpio);
    return $navegadorLimpio;
}

/**
 * Obtiene información completa del cliente
 */
function obtenerInfoCliente() {
    return [
        'ip' => obtenerIPCliente(),
        'user_agent' => obtenerUserAgent(),
        'navegador_limpio' => obtenerNavegadorLimpio(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

/**
 * Registra una acción en BitacoraClientes con información del cliente
 */
function registrarBitacoraCliente($conn, $id_cliente, $accion, $detalles = '', $datosReserva = null) {
    try {
        $info = obtenerInfoCliente();
        
        // Si es una reserva y se proporcionan datos específicos
        if ($datosReserva && is_array($datosReserva)) {
            $query = "INSERT INTO bitacoraclientes (
                        id_cliente, accion, detalles, fecha_hora, ip_address, user_agent,
                        id_funcion, id_pelicula, nombre_pelicula, fecha_funcion, hora_funcion,
                        sala, asientos_reservados, cantidad_asientos, total_pagado
                      ) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($query);
            return $stmt->execute([
                $id_cliente,
                $accion,
                $detalles,
                $info['ip'],
                $info['navegador_limpio'],
                $datosReserva['id_funcion'] ?? null,
                $datosReserva['id_pelicula'] ?? null,
                $datosReserva['nombre_pelicula'] ?? null,
                $datosReserva['fecha_funcion'] ?? null,
                $datosReserva['hora_funcion'] ?? null,
                $datosReserva['sala'] ?? null,
                $datosReserva['asientos_reservados'] ?? null,
                $datosReserva['cantidad_asientos'] ?? null,
                $datosReserva['total_pagado'] ?? null
            ]);
        } else {
            // Registro normal sin datos de reserva
            $query = "INSERT INTO bitacoraclientes (id_cliente, accion, detalles, fecha_hora, ip_address, user_agent) 
                      VALUES (?, ?, ?, NOW(), ?, ?)";
            
            $stmt = $conn->prepare($query);
            return $stmt->execute([
                $id_cliente,
                $accion,
                $detalles,
                $info['ip'],
                $info['navegador_limpio']
            ]);
        }
    } catch (PDOException $e) {
        error_log("Error en bitácora de cliente: " . $e->getMessage());
        return false;
    }
}

/**
 * Registra una acción en BitacoraEmpleados con información del cliente
 */
function registrarBitacoraEmpleado($conn, $id_empleado, $accion, $detalles = '') {
    try {
        $info = obtenerInfoCliente();
        
        $query = "INSERT INTO bitacoraempleados (id_empleado, accion, detalles, fecha_hora, ip_address, user_agent) 
                  VALUES (?, ?, ?, NOW(), ?, ?)";
        
        $stmt = $conn->prepare($query);
        return $stmt->execute([
            $id_empleado,
            $accion,
            $detalles,
            $info['ip'],
            $info['navegador_limpio'] // Usar navegador limpio en lugar del user_agent completo
        ]);
    } catch (PDOException $e) {
        error_log("Error en bitácora de empleado: " . $e->getMessage());
        return false;
    }
}

/**
 * Parsea el User-Agent para extraer información del navegador
 */
function parsearUserAgent($userAgent) {
    $info = [
        'navegador' => 'Desconocido',
        'version' => '',
        'sistema_operativo' => 'Desconocido',
        'dispositivo' => 'Desktop'
    ];
    
    // Detectar navegador (orden específico para evitar conflictos)
    if (preg_match('/OPR\/([0-9\.]+)/i', $userAgent, $matches)) {
        // Opera (nuevo basado en Chromium) - DEBE ir ANTES que Chrome
        $info['navegador'] = 'Opera';
        $info['version'] = $matches[1];
    } elseif (preg_match('/Opera[\/\s]([0-9\.]+)/i', $userAgent, $matches)) {
        // Opera (versión antigua)
        $info['navegador'] = 'Opera';
        $info['version'] = $matches[1];
    } elseif (preg_match('/Edg\/([0-9\.]+)/i', $userAgent, $matches)) {
        // Microsoft Edge (nuevo)
        $info['navegador'] = 'Edge';
        $info['version'] = $matches[1];
    } elseif (preg_match('/Chrome\/([0-9\.]+)/i', $userAgent, $matches) && !preg_match('/Edg\//i', $userAgent) && !preg_match('/OPR\//i', $userAgent)) {
        // Chrome (pero NO Edge ni Opera que también incluyen Chrome)
        $info['navegador'] = 'Chrome';
        $info['version'] = $matches[1];
    } elseif (preg_match('/Firefox\/([0-9\.]+)/i', $userAgent, $matches)) {
        $info['navegador'] = 'Firefox';
        $info['version'] = $matches[1];
    } elseif (preg_match('/Safari\/([0-9\.]+)/i', $userAgent, $matches) && !preg_match('/Chrome\//i', $userAgent)) {
        // Safari (pero no Chrome que también incluye Safari)
        $info['navegador'] = 'Safari';
        if (preg_match('/Version\/([0-9\.]+)/i', $userAgent, $versionMatches)) {
            $info['version'] = $versionMatches[1];
        } else {
            $info['version'] = $matches[1];
        }
    }
    
    // Detectar sistema operativo
    if (preg_match('/Windows NT 10/i', $userAgent)) {
        $info['sistema_operativo'] = 'Windows 10/11';
    } elseif (preg_match('/Windows NT ([0-9\.]+)/i', $userAgent, $matches)) {
        $winVersions = [
            '6.3' => 'Windows 8.1',
            '6.2' => 'Windows 8',
            '6.1' => 'Windows 7',
            '6.0' => 'Windows Vista',
            '5.1' => 'Windows XP'
        ];
        $info['sistema_operativo'] = $winVersions[$matches[1]] ?? 'Windows ' . $matches[1];
    } elseif (preg_match('/Mac OS X ([0-9_\.]+)/i', $userAgent, $matches)) {
        $info['sistema_operativo'] = 'macOS ' . str_replace('_', '.', $matches[1]);
    } elseif (preg_match('/Linux/i', $userAgent)) {
        if (preg_match('/Ubuntu/i', $userAgent)) {
            $info['sistema_operativo'] = 'Ubuntu Linux';
        } else {
            $info['sistema_operativo'] = 'Linux';
        }
    } elseif (preg_match('/Android ([0-9\.]+)/i', $userAgent, $matches)) {
        $info['sistema_operativo'] = 'Android ' . $matches[1];
        $info['dispositivo'] = 'Mobile';
    } elseif (preg_match('/iPhone OS ([0-9_\.]+)/i', $userAgent, $matches)) {
        $info['sistema_operativo'] = 'iOS ' . str_replace('_', '.', $matches[1]);
        $info['dispositivo'] = 'Mobile';
    } elseif (preg_match('/iPad/i', $userAgent)) {
        $info['sistema_operativo'] = 'iPadOS';
        $info['dispositivo'] = 'Tablet';
    }
    
    // Detectar dispositivo móvil adicional
    if (preg_match('/Mobile|Android|iPhone|iPad|Windows Phone/i', $userAgent)) {
        if ($info['dispositivo'] === 'Desktop') {
            $info['dispositivo'] = 'Mobile';
        }
    }
    
    return $info;
}

/**
 * Obtiene estadísticas de acceso por IP
 */
function obtenerEstadisticasIP($conn, $tabla = 'BitacoraClientes', $limite = 10) {
    try {
        $query = "SELECT ip_address, COUNT(*) as total_accesos, 
                         MAX(fecha_hora) as ultimo_acceso,
                         COUNT(DISTINCT DATE(fecha_hora)) as dias_activos
                  FROM $tabla 
                  WHERE ip_address IS NOT NULL 
                  GROUP BY ip_address 
                  ORDER BY total_accesos DESC 
                  LIMIT ?";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$limite]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error obteniendo estadísticas IP: " . $e->getMessage());
        return [];
    }
}

/**
 * Valida si una IP está en lista negra (opcional)
 */
function validarIP($ip) {
    // Lista básica de IPs a bloquear (ejemplo)
    $ips_bloqueadas = [
        '127.0.0.1', // ejemplo
        '0.0.0.0'    // ejemplo
    ];
    
    return !in_array($ip, $ips_bloqueadas);
}

/**
 * Detecta intentos sospechosos de acceso
 */
function detectarActividadSospechosa($conn, $id_usuario, $tipo = 'cliente') {
    try {
        $tabla = $tipo === 'cliente' ? 'BitacoraClientes' : 'BitacoraEmpleados';
        $campo_id = $tipo === 'cliente' ? 'id_cliente' : 'id_empleado';
        
        // Buscar múltiples intentos desde diferentes IPs en corto tiempo
        $query = "SELECT COUNT(DISTINCT ip_address) as ips_diferentes,
                         COUNT(*) as total_accesos
                  FROM $tabla 
                  WHERE $campo_id = ? 
                    AND fecha_hora >= NOW() - INTERVAL '1 hour'
                    AND accion LIKE '%sesión%'";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$id_usuario]);
        $resultado = $stmt->fetch();
        
        // Más de 3 IPs diferentes en 1 hora es sospechoso
        return $resultado['ips_diferentes'] > 3;
    } catch (PDOException $e) {
        error_log("Error detectando actividad sospechosa: " . $e->getMessage());
        return false;
    }
}
?>
