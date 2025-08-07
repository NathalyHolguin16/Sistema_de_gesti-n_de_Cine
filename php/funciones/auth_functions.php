<?php
require_once("../conexion.php");

/**
 * Genera un hash seguro para la contraseña de un cliente
 * @param string $password Contraseña en texto plano
 * @return string Hash de la contraseña
 */
function hash_cliente_password($password) {
    try {
        // Usamos el algoritmo PASSWORD_DEFAULT de PHP (actualmente BCRYPT)
        // Con un costo de 12 (2^12 iteraciones)
        $options = ['cost' => 12];
        return password_hash($password, PASSWORD_DEFAULT, $options);
    } catch (Exception $e) {
        error_log("Error al hashear contraseña de cliente: " . $e->getMessage());
        return false;
    }
}

/**
 * Genera un hash seguro para la contraseña de un empleado
 * @param string $password Contraseña en texto plano
 * @return string Hash de la contraseña
 */
function hash_empleado_password($password) {
    try {
        // Usamos el mismo método que para clientes, pero podríamos personalizarlo
        // si se necesitan diferentes niveles de seguridad
        $options = ['cost' => 12];
        return password_hash($password, PASSWORD_DEFAULT, $options);
    } catch (Exception $e) {
        error_log("Error al hashear contraseña de empleado: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica si una contraseña coincide con su hash
 * @param string $plain_password Contraseña en texto plano
 * @param string $hashed_password Hash almacenado de la contraseña
 * @return bool true si la contraseña es correcta
 */
function verify_password($plain_password, $hashed_password) {
    try {
        return password_verify($plain_password, $hashed_password);
    } catch (Exception $e) {
        error_log("Error al verificar contraseña: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene la información de un cliente
 * @return array Información del cliente o null si hay error
 */
function get_client_info($id_cliente) {
    global $conn;
    
    try {
        $query = "SELECT id_cliente, nombre, correo, telefono
                  FROM public.clientes 
                  WHERE id_cliente = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$id_cliente]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Agregamos información adicional si se necesita
        if ($cliente) {
            // Obtener número de entradas compradas
            $query_entradas = "SELECT COUNT(*) as total_entradas
                             FROM public.entradas
                             WHERE id_cliente = ?";
            $stmt = $conn->prepare($query_entradas);
            $stmt->execute([$id_cliente]);
            $entradas = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $cliente['total_entradas'] = $entradas['total_entradas'];
        }
        
        return $cliente;
    } catch (PDOException $e) {
        error_log("Error al obtener información del cliente: " . $e->getMessage());
        return null;
    }
}

/**
 * Valida si una contraseña cumple con los requisitos mínimos de seguridad
 * @param string $password Contraseña a validar
 * @return array ['valid' => bool, 'message' => string] Resultado de la validación
 */
function validate_password_strength($password) {
    $min_length = 8;
    $errors = [];
    
    if (strlen($password) < $min_length) {
        $errors[] = "La contraseña debe tener al menos $min_length caracteres";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "La contraseña debe contener al menos una letra mayúscula";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "La contraseña debe contener al menos una letra minúscula";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "La contraseña debe contener al menos un número";
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "La contraseña debe contener al menos un carácter especial";
    }
    
    return [
        'valid' => empty($errors),
        'message' => empty($errors) ? 'Contraseña válida' : implode('. ', $errors)
    ];
}
?>
