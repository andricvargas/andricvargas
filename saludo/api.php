<?php
require 'config.php';

// Establecer zona horaria
date_default_timezone_set('America/Mexico_City'); // Ajusta esto a tu zona horaria

// Define el tiempo de espera si no está en config.php
if (!defined('TIEMPO_ESPERA')) {
    define('TIEMPO_ESPERA', 300); // 5 minutos en segundos
}

// Inicializar respuesta
$response = [
    'success' => false,
    'errors' => [],
    'data' => []
];

// Manejo de solicitudes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Procesar envío de saludo
    $ip = getClientIP();
    $procesarFormulario = false;
    
    // Verificar límite de tiempo por IP
    $stmt = $pdo->prepare("
        SELECT MAX(fecha) as ultima_fecha 
        FROM saludos 
        WHERE ip_address = ?
    ");
    $stmt->execute([$ip]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado && !empty($resultado['ultima_fecha'])) {
        $ultimoTimestamp = strtotime($resultado['ultima_fecha']);
        $tiempoTranscurrido = time() - $ultimoTimestamp;
        
        if ($tiempoTranscurrido < TIEMPO_ESPERA) {
            // No ha pasado suficiente tiempo
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
        $procesarFormulario = true;
    }
    
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
                // Verificar nuevamente antes de insertar (doble check)
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as recientes 
                    FROM saludos 
                    WHERE ip_address = ? AND fecha > DATE_SUB(NOW(), INTERVAL " . TIEMPO_ESPERA . " SECOND)
                ");
                $stmt->execute([$ip]);
                $verificacion = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($verificacion && $verificacion['recientes'] > 0) {
                    $response['errors'][] = 'Debes esperar 5 minutos entre cada saludo';
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO saludos (saludo, fecha, ip_address) 
                        VALUES (?, NOW(), ?)
                    ");
                    $stmt->execute([$saludo, $ip]);
                    $response['success'] = true;
                }
            } catch (PDOException $e) {
                $response['errors'][] = 'Error al guardar el saludo: ' . $e->getMessage();
            }
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Obtener todos los saludos
    try {
        $stmt = $pdo->query("SELECT saludo, fecha FROM saludos ORDER BY fecha DESC");
        $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response['success'] = true;
    } catch (PDOException $e) {
        $response['errors'][] = 'Error al cargar los saludos: ' . $e->getMessage();
    }
}

// Asegurar que los encabezados HTTP son correctos
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
echo json_encode($response);
exit;