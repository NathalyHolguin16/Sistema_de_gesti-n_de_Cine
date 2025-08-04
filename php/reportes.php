<?php
header('Content-Type: application/json');
require_once("conexion.php");
require_once("client_info.php");

$action = $_GET['action'] ?? 'default';

try {
    switch ($action) {
        case 'dashboard_stats':
            // Estadísticas para el dashboard principal
            $stats = [];
            
            // Reservas de hoy - usar tabla entradas
            try {
                $query = "SELECT COUNT(*) as total FROM entradas WHERE DATE(fecha_compra) = CURRENT_DATE";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $result = $stmt->fetch();
                $stats['reservas_hoy'] = $result['total'] ?? 0;
            } catch (PDOException $e) {
                $stats['reservas_hoy'] = 0;
            }
            
            // Total de clientes registrados
            $query = "SELECT COUNT(*) as total FROM clientes";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch();
            $stats['total_clientes'] = $result['total'] ?? 0;
            
            // Ingresos del mes actual - usar tabla entradas
            try {
                $query = "SELECT COALESCE(SUM(total_pagado), 0) as total FROM entradas 
                          WHERE EXTRACT(MONTH FROM fecha_compra) = EXTRACT(MONTH FROM CURRENT_DATE)
                          AND EXTRACT(YEAR FROM fecha_compra) = EXTRACT(YEAR FROM CURRENT_DATE)";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $result = $stmt->fetch();
                $stats['ingresos_mes'] = number_format($result['total'] ?? 0, 2);
            } catch (PDOException $e) {
                $stats['ingresos_mes'] = '0.00';
            }
            
            echo json_encode(['success' => true, 'reservas_hoy' => $stats['reservas_hoy'], 'total_clientes' => $stats['total_clientes'], 'ingresos_mes' => $stats['ingresos_mes']]);
            break;
            
        case 'reservas':
            // Reporte de reservas - usar tabla entradas
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;
            $offset = ($page - 1) * $limit;
            
            $query = "SELECT e.id_entrada, e.total_pagado, e.fecha_compra, e.asientos,
                             c.nombre as cliente_nombre, c.correo as cliente_correo,
                             p.titulo as pelicula_titulo, 
                             f.hora_inicio, f.fecha as fecha_funcion
                      FROM entradas e
                      LEFT JOIN clientes c ON e.id_cliente = c.id_cliente
                      LEFT JOIN funciones f ON e.id_funcion = f.id_funcion
                      LEFT JOIN peliculas p ON f.id_pelicula = p.id_pelicula
                      ORDER BY e.fecha_compra DESC
                      LIMIT ? OFFSET ?";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$limit, $offset]);
            $reservas = $stmt->fetchAll();
            
            // Formatear datos
            $reservasFormateadas = [];
            foreach ($reservas as $reserva) {
                $reservasFormateadas[] = [
                    'id_reserva' => $reserva['id_entrada'] ?? 'N/A',
                    'cliente_nombre' => $reserva['cliente_nombre'] ?? 'Cliente eliminado',
                    'cliente_correo' => $reserva['cliente_correo'],
                    'pelicula_titulo' => $reserva['pelicula_titulo'] ?? 'Película eliminada',
                    'fecha' => $reserva['fecha_funcion'] ?? 'N/A',
                    'hora' => $reserva['hora_inicio'] ?? 'N/A',
                    'num_asientos' => $reserva['asientos'] ?? '1',
                    'total_pagado' => number_format($reserva['total_pagado'] ?? 0, 2),
                    'fecha_reserva' => $reserva['fecha_compra'] ?? 'N/A'
                ];
            }
            
            echo json_encode(['success' => true, 'data' => $reservasFormateadas]);
            break;
            
        case 'bitacora':
            // Bitácora del sistema
            $tipo = $_GET['tipo'] ?? 'all';
            $fecha = $_GET['fecha'] ?? '';
            $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 50;
            
            $registros = [];
            
            if ($tipo === 'all' || $tipo === 'clientes') {
                // Bitácora de clientes
                $query = "SELECT bc.*, c.nombre as usuario_nombre, 'cliente' as tipo
                          FROM bitacoraclientes bc
                          LEFT JOIN clientes c ON bc.id_cliente = c.id_cliente";
                
                if ($fecha) {
                    $query .= " WHERE DATE(bc.fecha_hora) = ?";
                }
                
                $query .= " ORDER BY bc.fecha_hora DESC LIMIT ?";
                
                $stmt = $conn->prepare($query);
                if ($fecha) {
                    $stmt->execute([$fecha, $limit]);
                } else {
                    $stmt->execute([$limit]);
                }
                
                $bitacoraClientes = $stmt->fetchAll();
                foreach ($bitacoraClientes as $registro) {
                    $registros[] = [
                        'fecha_hora' => $registro['fecha_hora'],
                        'usuario_nombre' => $registro['usuario_nombre'] ?? 'Cliente eliminado',
                        'tipo' => 'cliente',
                        'accion' => $registro['accion'],
                        'ip_address' => $registro['ip_address'],
                        'user_agent' => $registro['user_agent'],
                        'detalles' => $registro['detalles'] ?? ''
                    ];
                }
            }
            
            if ($tipo === 'all' || $tipo === 'empleados') {
                // Bitácora de empleados
                $query = "SELECT be.*, e.nombre as usuario_nombre, 'empleado' as tipo
                          FROM bitacoraempleados be
                          LEFT JOIN empleados e ON be.id_empleado = e.id_empleado";
                
                if ($fecha) {
                    $query .= " WHERE DATE(be.fecha_hora) = ?";
                }
                
                $query .= " ORDER BY be.fecha_hora DESC LIMIT ?";
                
                $stmt = $conn->prepare($query);
                if ($fecha) {
                    $stmt->execute([$fecha, $limit]);
                } else {
                    $stmt->execute([$limit]);
                }
                
                $bitacoraEmpleados = $stmt->fetchAll();
                foreach ($bitacoraEmpleados as $registro) {
                    $registros[] = [
                        'fecha_hora' => $registro['fecha_hora'],
                        'usuario_nombre' => $registro['usuario_nombre'] ?? 'Empleado eliminado',
                        'tipo' => 'empleado',
                        'accion' => $registro['accion'],
                        'ip_address' => $registro['ip_address'],
                        'user_agent' => $registro['user_agent'],
                        'detalles' => $registro['detalles'] ?? ''
                    ];
                }
            }
            
            // Ordenar por fecha desc
            usort($registros, function($a, $b) {
                return strtotime($b['fecha_hora']) - strtotime($a['fecha_hora']);
            });
            
            // Limitar resultados
            $registros = array_slice($registros, 0, $limit);
            
            echo json_encode(['success' => true, 'data' => $registros]);
            break;
            
        default:
            // Código original para reportes de entradas
            // Parámetros de paginación con validación
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 10;
            $offset = ($page - 1) * $limit;

    // Filtros opcionales
    $fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : null;
    $fechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : null;
    $idPelicula = isset($_GET['id_pelicula']) ? (int)$_GET['id_pelicula'] : null;

    // Construir WHERE clause dinámico
    $whereConditions = [];
    $params = [];

    if ($fechaInicio) {
        $whereConditions[] = "f.fecha >= ?";
        $params[] = $fechaInicio;
    }

    if ($fechaFin) {
        $whereConditions[] = "f.fecha <= ?";
        $params[] = $fechaFin;
    }

    if ($idPelicula) {
        $whereConditions[] = "p.id_pelicula = ?";
        $params[] = $idPelicula;
    }

    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

    // Contar total de registros
    $countQuery = "SELECT COUNT(*) as total
                   FROM entradas e
                   JOIN clientes c ON e.id_cliente = c.id_cliente
                   JOIN funciones f ON e.id_funcion = f.id_funcion
                   JOIN peliculas p ON f.id_pelicula = p.id_pelicula
                   $whereClause";
    
    $countStmt = $conn->prepare($countQuery);
    $countStmt->execute($params);
    $totalResult = $countStmt->fetch();
    $total = $totalResult['total'];
    
    // Calcular información de paginación
    $totalPages = ceil($total / $limit);
    $hasNextPage = $page < $totalPages;
    $hasPrevPage = $page > 1;

    // Obtener datos del reporte con paginación
    $query = "SELECT c.nombre AS cliente, 
                     c.correo AS cliente_correo,
                     p.titulo AS pelicula, 
                     f.fecha, 
                     f.hora_inicio AS hora, 
                     e.asientos, 
                     e.total_pagado,
                     e.fecha_compra,
                     e.id_entrada
              FROM entradas e
              JOIN clientes c ON e.id_cliente = c.id_cliente
              JOIN funciones f ON e.id_funcion = f.id_funcion
              JOIN peliculas p ON f.id_pelicula = p.id_pelicula
              $whereClause
              ORDER BY e.fecha_compra DESC
              LIMIT ? OFFSET ?";

    $finalParams = array_merge($params, [$limit, $offset]);
    $stmt = $conn->prepare($query);
    $stmt->execute($finalParams);
    $entradas = $stmt->fetchAll();

    // Calcular totales para el reporte
    $totalVentasQuery = "SELECT 
                            COUNT(*) as total_entradas,
                            COALESCE(SUM(e.total_pagado), 0) as ingresos_totales,
                            COALESCE(AVG(e.total_pagado), 0) as promedio_por_entrada
                         FROM entradas e
                         JOIN funciones f ON e.id_funcion = f.id_funcion
                         JOIN peliculas p ON f.id_pelicula = p.id_pelicula
                         $whereClause";
    
    $totalStmt = $conn->prepare($totalVentasQuery);
    $totalStmt->execute($params);
    $totales = $totalStmt->fetch();

            echo json_encode([
                'success' => true,
                'data' => $entradas,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_items' => $total,
                    'items_per_page' => $limit,
                    'has_next_page' => $hasNextPage,
                    'has_prev_page' => $hasPrevPage,
                    'showing_from' => $offset + 1,
                    'showing_to' => min($offset + $limit, $total)
                ],
                'summary' => [
                    'total_entradas' => (int)$totales['total_entradas'],
                    'ingresos_totales' => (float)$totales['ingresos_totales'],
                    'promedio_por_entrada' => (float)$totales['promedio_por_entrada']
                ]
            ]);
            break;
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
?>