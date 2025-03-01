<?php
require 'config.php';

// Procesar formulario
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar reCAPTCHA
    $recaptcha = $_POST['g-recaptcha-response'] ?? '';
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $recaptcha,
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
    
    if (!$result->success) {
        $errors[] = 'Error en reCAPTCHA';
    }


    // Verificar límite de tiempo por IP
    $ip = getClientIP();
    $stmt = $pdo->prepare("SELECT fecha FROM saludos WHERE ip_address = ? ORDER BY fecha DESC LIMIT 1");
    $stmt->execute([$ip]);
    $ultimo = $stmt->fetch();

    if ($ultimo) {
        $ultimoTimestamp = strtotime($ultimo['fecha']);
        $tiempoTranscurrido = time() - $ultimoTimestamp;
        
        if ($tiempoTranscurrido < TIEMPO_ESPERA) {
            $tiempoRestante = TIEMPO_ESPERA - $tiempoTranscurrido;
            $minutos = floor($tiempoRestante / 60);
            $segundos = $tiempoRestante % 60;
            
            $errors[] = sprintf(
                'Debes esperar %d minutos y %d segundos antes de enviar otro saludo',
                $minutos,
                $segundos
            );
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO saludos (saludo, fecha, ip_address) VALUES (?, NOW(), ?)");
            $stmt->execute([$saludo, $ip]);
            $success = true;
            
            // Limpiar el campo del formulario después de un envío exitoso
            $_POST['saludo'] = '';
        } catch (PDOException $e) {
            $errors[] = 'Error al guardar el saludo: ' . $e->getMessage();
        }
    }

    echo "IP detectada: " . $ip . "<br>";
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

    // Insertar en base de datos si no hay errores
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


function getClientIP() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    // Si hay múltiples IPs (proxies), tomar la primera
    if (strpos($ip, ',') !== false) {
        $ips = explode(',', $ip);
        $ip = trim($ips[0]);
    }
    
    return $ip;
}

// Obtener todos los saludos
$stmt = $pdo->query("SELECT saludo, fecha FROM saludos ORDER BY fecha DESC");
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
    <style>
        .container { max-width: 800px; margin: 50px auto; }
        .table { margin-top: 30px; }
        .form-section { background: #f8f9fa; padding: 20px; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-section">
            <h2>Registrar Saludo</h2>
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
                        required></textarea>
                </div>
                
                <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                
                <button type="submit" class="btn btn-primary">Enviar Saludo</button>
            </form>
        </div>

        <h2 class="mt-5">Listado de Saludos</h2>
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
                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($s['fecha']))) ?></td>
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