<?php
require 'config.php';

// Establecer zona horaria para asegurar consistencia 
date_default_timezone_set('America/Mexico_City'); // Ajusta a tu zona horaria

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

// Procesar formulario
$errors = [];
$success = false;
$procesarFormulario = false;
$tiempoRestanteMsg = '';
$debugInfo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener IP de forma segura
    $ip = getClientIP_safe();
    
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
        
        // Guardar información de depuración
        $debugInfo = "IP: {$ip}, Último envío: " . date('Y-m-d H:i:s', $ultimoTimestamp) . 
                    ", Tiempo actual: " . date('Y-m-d H:i:s', $tiempoActual) . 
                    ", Transcurrido: {$tiempoTranscurrido}s, Límite: " . TIEMPO_ESPERA . "s";
        
        // Verificar si ha pasado suficiente tiempo
        if ($tiempoTranscurrido < TIEMPO_ESPERA) {
            $tiempoRestante = TIEMPO_ESPERA - $tiempoTranscurrido;
            $minutos = floor($tiempoRestante / 60);
            $segundos = $tiempoRestante % 60;
            
            $tiempoRestanteMsg = sprintf(
                'Debes esperar %d minutos y %d segundos antes de enviar otro saludo',
                $minutos,
                $segundos
            );
            
            $errors[] = $tiempoRestanteMsg;
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
            $errors[] = 'El saludo no puede estar vacío';
        } elseif (mb_strlen($saludo) > 200) {
            $errors[] = 'El saludo no puede exceder 200 caracteres';
        }
        
        // Eliminar etiquetas HTML y verificar enlaces
        $saludo = strip_tags($saludo);
        if (preg_match('/https?:\/\/|www\.|\[url\]/i', $saludo)) {
            $errors[] = 'No se permiten enlaces en el saludo';
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
            $errors[] = 'Error en reCAPTCHA';
        }

        // Insertar en base de datos si no hay errores
        if (empty($errors)) {
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
                    $errors[] = 'Debes esperar 5 minutos entre cada saludo';
                } else {
                    // Usar NOW(3) para incluir milisegundos y evitar duplicados
                    $stmt = $pdo->prepare("
                        INSERT INTO saludos (saludo, fecha, ip_address) 
                        VALUES (?, NOW(3), ?)
                    ");
                    $stmt->execute([$saludo, $ip]);
                    $success = true;
                    $_POST['saludo'] = ''; // Limpiar el campo después de éxito
                }
                
                // Desbloquear tabla
                $pdo->exec("UNLOCK TABLES");
            } catch (PDOException $e) {
                // Asegurar que se desbloquee la tabla en caso de error
                $pdo->exec("UNLOCK TABLES");
                $errors[] = 'Error al guardar el saludo: ' . $e->getMessage();
            }
        }
    }
}

// Obtener todos los saludos
$stmt = $pdo->query("SELECT saludo, fecha FROM saludos ORDER BY fecha DESC LIMIT 10");
$saludos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saludos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js?render=<?= RECAPTCHA_SITE_KEY ?>"></script>
    <link href="styles.css" rel="stylesheet">
</head>
<body>
    <div class="container">
    <div class="form-section">
    <h2 class="birthday-title"><span>R</span><span>e</span><span>g</span><span>i</span><span>s</span><span>t</span><span>r</span><span>a</span><span>r</span> <span>S</span><span>a</span><span>l</span><span>u</span><span>d</span><span>o</span></h2>
    <?php if ($success): ?>
    <div class="alert alert-success">¡Saludo registrado correctamente!</div>
    <?php elseif (!empty($errors)): ?>
    <div class="alert alert-danger">
    <?php foreach ($errors as $error): ?>
    <p><?= htmlspecialchars($error) ?></p>
    <?php endforeach ?>
    </div>
    <?php endif ?>
    
    <form method="POST" id="form-saludo">
    <div class="mb-3">
    <textarea 
    class="form-control" 
    name="saludo" 
    maxlength="200"
    rows="3"
    placeholder="Escribe tu saludo aquí (máximo 200 caracteres)"
    required><?= htmlspecialchars($_POST['saludo'] ?? '') ?></textarea>
    </div>
    
    <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
    
    <button type="submit" class="btn btn-primary">Enviar Saludo</button>
    </form>
    </div>

    <h2 class="birthday-title mt-5"><span>L</span><span>i</span><span>s</span><span>t</span><span>a</span><span>d</span><span>o</span> <span>d</span><span>e</span> <span>S</span><span>a</span><span>l</span><span>u</span><span>d</span><span>o</span><span>s</span></h2>
    <?php if (!empty($saludos)): ?>
    <table class="table table-striped table-hover">
    <thead>
    <tr>
    <th>Fecha</th>
    <th>Saludo</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($saludos as $s): ?>
    <tr>
    <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($s['fecha']))) ?></td>
    <td><?= nl2br(htmlspecialchars($s['saludo'])) ?></td>
    </tr>
    <?php endforeach ?>
    </tbody>
    </table>
    <?php else: ?>
    <p class="text-muted">No hay saludos registrados aún</p>
    <?php endif ?>
    </div>

    <script>
    // reCAPTCHA v3
    grecaptcha.ready(function() {
    document.getElementById('form-saludo').addEventListener('submit', function(e) {
    e.preventDefault();
    grecaptcha.execute('<?= RECAPTCHA_SITE_KEY ?>', {action: 'submit'}).then(function(token) {
    document.getElementById('g-recaptcha-response').value = token;
    document.getElementById('form-saludo').submit();
    });
    });
    });
    </script>
</body>
</html>