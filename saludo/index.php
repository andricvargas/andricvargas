<?php
require 'config.php'; // Solo necesitamos la clave de recaptcha
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
    .hidden { display: none; }
    </style>
</head>
<body>
    <div class="container">
    <div class="form-section">
    <h2>Registrar Saludo</h2>
    <div id="alert-success" class="alert alert-success hidden">¡Saludo registrado correctamente!</div>
    <div id="alert-error" class="alert alert-danger hidden"></div>
    
    <form id="form-saludo">
    <div class="mb-3">
    <textarea 
    class="form-control" 
    id="saludo"
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
    <div id="saludos-container">
    <p class="text-muted">Cargando saludos...</p>
    </div>
    </div>

    <script>
    // Función para escapar HTML (prevenir XSS)
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Función para formatear fecha
    function formatearFecha(fechaStr) {
        try {
            const fecha = new Date(fechaStr);
            return fecha.toLocaleString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        } catch (e) {
            console.error('Error al formatear fecha:', e);
            return fechaStr; // Devolver fecha original si hay error
        }
    }
    
    // Función para cargar los saludos
    function cargarSaludos() {
        fetch('api.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos:', data); // Para depuración
            const container = document.getElementById('saludos-container');
            
            if (data.success && data.data && data.data.length > 0) {
                let html = '<table class="table table-striped table-hover">';
                html += '<thead><tr><th>Fecha</th><th>Saludo</th></tr></thead><tbody>';
                
                data.data.forEach(saludo => {
                    const fecha = formatearFecha(saludo.fecha);
                    // Reemplazar saltos de línea con 
 correctamente
                    const saludoTexto = escapeHtml(saludo.saludo).replace(/\n/g, '
');
                    
                    html += `<tr>
                    <td>${fecha}</td>
                    <td>${saludoTexto}</td>
                    </tr>`;
                });
                
                html += '</tbody></table>';
                container.innerHTML = html;
            } else {
                container.innerHTML = '<p class="text-muted">No hay saludos registrados aún</p>';
            }
        })
        .catch(error => {
            console.error('Error al cargar saludos:', error);
            document.getElementById('saludos-container').innerHTML = 
            '<p class="text-danger">Error al cargar los saludos. Por favor, intenta más tarde.</p>';
        });
    }

    // Cargar saludos al iniciar la página
    document.addEventListener('DOMContentLoaded', cargarSaludos);

    // Configurar envío del formulario
    document.getElementById('form-saludo').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Ocultar alertas previas
        document.getElementById('alert-success').classList.add('hidden');
        document.getElementById('alert-error').classList.add('hidden');
        
        // Solicitar token reCAPTCHA
        grecaptcha.execute('<?= RECAPTCHA_SITE_KEY ?>', {action: 'submit'})
        .then(token => {
            document.getElementById('g-recaptcha-response').value = token;
            
            // Preparar datos del formulario
            const formData = new FormData(this);
            
            // Enviar solicitud al backend
            return fetch('api.php', {
                method: 'POST',
                body: formData
            });
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar mensaje de éxito
                document.getElementById('alert-success').classList.remove('hidden');
                document.getElementById('saludo').value = '';
                
                // Recargar la lista de saludos
                cargarSaludos();
            } else {
                // Mostrar errores
                const errorContainer = document.getElementById('alert-error');
                errorContainer.innerHTML = '';
                
                data.errors.forEach(error => {
                    errorContainer.innerHTML += `<p>${escapeHtml(error)}</p>`;
                });
                
                errorContainer.classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Error al enviar saludo:', error);
            const errorContainer = document.getElementById('alert-error');
            errorContainer.innerHTML = '<p>Error al procesar la solicitud. Por favor, intenta más tarde.</p>';
            errorContainer.classList.remove('hidden');
        });
    });
    </script>
</body>
</html>