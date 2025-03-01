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
            
            <form method="POST" id="form-saludo" action="process.php">
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