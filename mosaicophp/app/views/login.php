<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Mosaico de Porcentajes</title>
    <link rel="icon" type="image/x-icon" href="<?php echo $base_path; ?>/public/src/misc/favicon.ico">
    
    <style> 
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
        }
        .login-container {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
        }
        input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 0.75rem;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 1rem;
        }
        button:hover {
            background-color: #45a049;
        }
        .error-message {
            color: red;
            margin-top: 1rem;
        }
        .success-message {
            color: green;
            margin-top: 1rem;
        }
        .register-button {
            background-color: #2196F3;
        }
        .form-toggle {
            text-align: center;
            margin-top: 1rem;
        }
    </style>
    <script src="https://www.google.com/recaptcha/api.js?render=6LcBSq4qAAAAAD9WV6hNQ5mFfiVmBSsMkgCbNBl3"></script>
</head>
<body>
    <div class="login-container">
        <div id="loginForm">
            <h2 id="formTitle">Iniciar Sesión</h2>
            <div id="message"></div>
            <div class="form-group">
                <label for="username">Usuario:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <input type="hidden" id="recaptchaResponse" name="recaptcha_response">
            <button onclick="submitForm()" id="submitButton">Iniciar Sesión</button>
            <div class="form-toggle">
                <button onclick="toggleForm()" id="toggleButton" class="register-button">
                    Crear cuenta nueva
                </button>
            </div>
        </div>
    </div>

    <script>
        const BASE_URL = '/mosaicophp';
        
        let isLoginForm = true;
        
        function toggleForm() {
            isLoginForm = !isLoginForm;
            const formTitle = document.getElementById('formTitle');
            const submitButton = document.getElementById('submitButton');
            const toggleButton = document.getElementById('toggleButton');
            const messageDiv = document.getElementById('message');
            
            messageDiv.textContent = '';
            messageDiv.className = '';
            
            if (isLoginForm) {
                formTitle.textContent = 'Iniciar Sesión';
                submitButton.textContent = 'Iniciar Sesión';
                toggleButton.textContent = 'Crear cuenta nueva';
            } else {
                formTitle.textContent = 'Registro';
                submitButton.textContent = 'Registrarse';
                toggleButton.textContent = 'Volver al login';
            }
        }
        
        async function submitForm() {
            const messageDiv = document.getElementById('message');
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            try {
                // Obtener token de reCAPTCHA
                const captchaResponse = await grecaptcha.execute('6LcBSq4qAAAAAD9WV6hNQ5mFfiVmBSsMkgCbNBl3', {action: 'submit'});
                
                const endpoint = isLoginForm ? 'login' : 'register';
                const response = await fetch(`${BASE_URL}/api/auth/${endpoint}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        username: username,
                        password: password,
                        captcha: captchaResponse
                    }),
                    credentials: 'same-origin'
                });

                const text = await response.text();
                if (!text) {
                    throw new Error('Respuesta vacía del servidor');
                }

                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Respuesta no válida:', text);
                    throw new Error('Respuesta no válida del servidor');
                }

                if (!response.ok) {
                    throw new Error(data.error || 'Error en la solicitud');
                }

                if (data.error) {
                    messageDiv.textContent = data.error;
                    messageDiv.className = 'error-message';
                    return;
                }

                messageDiv.textContent = data.message;
                messageDiv.className = 'success-message';

                if (isLoginForm && data.message === 'Login exitoso') {
                    window.location.href = BASE_URL;
                } else if (!isLoginForm && data.message === 'Usuario creado exitosamente') {
                    setTimeout(() => {
                        toggleForm();
                    }, 1500);
                }
            } catch (error) {
                console.error('Error:', error);
                messageDiv.textContent = error.message;
                messageDiv.className = 'error-message';
            }
        }
    </script>
</body>
</html>
