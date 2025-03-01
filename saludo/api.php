<?php
require 'config.php';

// Establecer zona horaria para asegurar consistencia
date_default_timezone_set('America/Mexico_City');

// Define wait time if not in config.php (5 minutes in seconds)
if (!defined('TIEMPO_ESPERA')) {
    define('TIEMPO_ESPERA', 300); // 5 minutes in seconds
}

// Función mejorada para obtener IP
function getClientIP_safe() {
    $ip = getClientIP(); // Usar la función existente
    
    // Verificar que la IP es válida
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    
    // IP de respaldo si no se puede obtener una válida
    return '0.0.0.0';
}

// Inicializar respuesta
$response = [
    'success' => false,
    'errors' => [],
    'data' => []
];

// Procesamiento de API
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener IP de forma segura
    $ip = getClientIP_safe();
    $procesarFormulario = false;
    
    // Comprobar si esta IP ya ha enviado un saludo en los últimos 5 minutos
    $stmt = $pdo->prepare("
        SELECT UNIX_TIMESTAMP(fecha) as timestamp_ultimo 
        FROM saludos 
        WHERE ip_address = ? 
        ORDER BY fecha DESC 
        LIMIT 1
    ");
    $stmt->execute([$ip]);
    $ultimo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $tiempoActual = time();
    
    if ($ultimo && isset($ultimo['timestamp_ultimo'])) {
        $ultimoTimestamp = (int)$ultimo['timestamp_ultimo'];
        $tiempoTranscurrido = $tiempoActual - $ultimoTimestamp;
        
        // Verificar si ha pasado suficiente tiempo
        if ($tiempoTranscurrido < TIEMPO_ESPERA) {
            $tiempoRestante = TIEMPO_ESPERA - $tiempoTranscurrido;
            $minutos = floor($tiempoRestante / 60);
            $segundos = $tiempoRestante % 60;
            
            $response['errors'][] = sprintf(
                'Debes esperar %d minutos y %d segundos antes de enviar otro saludo',
                $minutos,
                $segundos
            );
        } else {
            $procesarFormulario = true;
        }
    } else {
        // No hay saludos previos desde esta IP
        $procesarFormulario = true;
    }

    // Solo procesar si ha pasado suficiente tiempo
    if ($procesarFormulario) {
        // Validar y sanitizar saludo
        $saludo = trim($_POST['saludo'] ?? '');
        
        if (empty($saludo)) {
            $response['errors'][] = 'El saludo no puede estar vacío';
        } elseif (mb_strlen($saludo) > 200) {
            $response['errors'][] = 'El saludo no puede exceder 200 caracteres';
        }
        
        // Eliminar etiquetas HTML y verificar enlaces
        $saludo = strip_tags($saludo);
        if (preg_match('/https?:\/\/|www\.|\[url\]/i', $saludo)) {
            $response['errors'][] = 'No se permiten enlaces en el saludo';
        }

        // Verificar reCAPTCHA
        $recaptcha = $_POST['g-recaptcha-response'] ?? '';
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => RECAPTCHA_SECRET_KEY,
            'response' => $recaptcha,
            'remoteip' => $ip
        ];
        
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = json_decode(file_get_contents($url, false, $context));
        
        if (!$result->success) {
            $response['errors'][] = 'Error en reCAPTCHA';
        }

        // Insertar en base de datos si no hay errores
        if (empty($response['errors'])) {
            try {
                // Bloquear tabla para evitar condiciones de carrera
                $pdo->exec("LOCK TABLES saludos WRITE");
                
                // Verificar nuevamente el límite de tiempo (evita race conditions)
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as recientes 
                    FROM saludos 
                    WHERE ip_address = ? AND fecha > DATE_SUB(NOW(), INTERVAL " . TIEMPO_ESPERA . " SECOND)
                ");
                $stmt->execute([$ip]);
                $verificacion = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($verificacion && $verificacion['recientes'] > 0) {
                    // Alguien intentó saltarse la validación
                    $response['errors'][] = 'Debes esperar 5 minutos entre cada saludo';
                } else {
                    // Usar NOW(3) para incluir milisegundos y evitar duplicados
                    $stmt = $pdo->prepare("
                        INSERT INTO saludos (saludo, fecha, ip_address) 
                        VALUES (?, NOW(3), ?)
                    ");
                    $stmt->execute([$saludo, $ip]);
                    $response['success'] = true;
                }
                
                // Desbloquear tabla
                $pdo->exec("UNLOCK TABLES");
            } catch (PDOException $e) {
                // Asegurar que se desbloquee la tabla en caso de error
                $pdo->exec("UNLOCK TABLES");
                $response['errors'][] = 'Error al guardar el saludo: ' . $e->getMessage();
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Obtener todos los saludos
    $stmt = $pdo->query("SELECT saludo, fecha FROM saludos ORDER BY fecha DESC");
    $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response['success'] = true;
}

// Devolver la respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);
exit;