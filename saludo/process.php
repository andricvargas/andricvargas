<?php
require 'config.php';
/*
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}*/

function getClientIP() { 
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) { 
    return $_SERVER['HTTP_CLIENT_IP']; 
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { 
    return $_SERVER['HTTP_X_FORWARDED_FOR']; 
    } 
    return $_SERVER['REMOTE_ADDR']; 
    }

function verificarReCaptcha($response) {
    if (!RECAPTCHA_ENABLED) {
        return true;
    }
    
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $response,
        'remoteip' => getClientIP()
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
    return $result->success ?? false;
}

$errors = [];
$success = false;
$saludos = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recaptcha = $_POST['g-recaptcha-response'] ?? '';
    if (!verificarReCaptcha($recaptcha)) {
        $errors[] = 'Error en reCAPTCHA';
    }

    $ip = getClientIP();
    $stmt = $pdo->prepare("SELECT fecha FROM saludos WHERE ip_address = ? ORDER BY fecha DESC LIMIT 1");
    $stmt->execute([$ip]);
    $ultimo = $stmt->fetch();
    
    if ($ultimo && (time() - strtotime($ultimo['fecha'])) < TIEMPO_ESPERA) {
        $errors[] = 'Debes esperar 5 minutos entre cada envío';
    }

    $saludo = trim($_POST['saludo'] ?? '');
    
    if (empty($saludo)) {
        $errors[] = 'El saludo no puede estar vacío';
    } elseif (mb_strlen($saludo) > 200) {
        $errors[] = 'El saludo no puede exceder 200 caracteres';
    }
    
    $saludo = strip_tags($saludo);
    if (preg_match('/https?:\/\/|www\.|\[url\]/i', $saludo)) {
        $errors[] = 'No se permiten enlaces en el saludo';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO saludos (saludo, fecha, ip_address) VALUES (?, NOW(), ?)");
            $stmt->execute([$saludo, $ip]);
            $success = true;
        } catch (PDOException $e) {
            $errors[] = 'Error al guardar el saludo';
        }
    }
}

$stmt = $pdo->query("SELECT saludo, fecha FROM saludos ORDER BY fecha DESC");
$saludos = $stmt->fetchAll();

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'errors' => $errors,
        'saludos' => $saludos
    ]);
    exit;
} else {
    include 'view.php';
}