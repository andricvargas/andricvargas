<?php
class AuthController {
    private UserRepository $userRepository;

    public function __construct() {
        $this->userRepository = new UserRepository();
    }

    private function verifyCaptcha($captchaResponse): bool {
        if (empty($captchaResponse)) {
            return false;
        }

        $secretKey = "6LcBSq4qAAAAAEOOBOxL5bc1G1swV0QA79n-Q6ho"; // Reemplaza con tu clave secreta de reCAPTCHA
        $url = "https://www.google.com/recaptcha/api/siteverify";
        
        $data = [
            'secret' => $secretKey,
            'response' => $captchaResponse
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $resultJson = json_decode($result);
        
        return $resultJson->success ?? false;
    }

    public function register(array $data): void {
        try {
            LogConfig::secureLog("=== Inicio del proceso de registro ===");
            LogConfig::secureLog("Datos recibidos", $data);

            // Verificar captcha primero
            if (!isset($data['captcha']) || !$this->verifyCaptcha($data['captcha'])) {
                $this->jsonResponse(['error' => 'Verificación de captcha fallida'], 400);
                return;
            }

            // Validar datos de entrada
            if (!isset($data['username']) || !isset($data['password'])) {
                error_log("Faltan datos requeridos");
                $this->jsonResponse(['error' => 'Usuario y contraseña requeridos'], 400);
                return;
            }

            $username = trim($data['username']);
            $password = trim($data['password']);

            // Validar campos vacíos
            if (empty($username) || empty($password)) {
                error_log("Campos vacíos detectados");
                $this->jsonResponse(['error' => 'Usuario y contraseña no pueden estar vacíos'], 400);
                return;
            }

            // Verificar usuario existente
            error_log("Verificando si el usuario existe: " . $username);
            $existingUser = $this->userRepository->findByUsername($username);
            
            if ($existingUser) {
                error_log("Usuario ya existe: " . $username);
                $this->jsonResponse(['error' => 'El usuario ya existe'], 400);
                return;
            }

            // Crear nuevo usuario
            error_log("Creando nuevo usuario: " . $username);
            $user = new User($username);
            $user->setPassword($password);

            try {
                if ($this->userRepository->create($user)) {
                    error_log("Usuario creado exitosamente: " . $username);
                    $this->jsonResponse(['message' => 'Usuario creado exitosamente']);
                } else {
                    error_log("Error al crear usuario en la base de datos");
                    $this->jsonResponse(['error' => 'Error al crear usuario'], 500);
                }
            } catch (Exception $e) {
                error_log("Error en la creación del usuario: " . $e->getMessage());
                $this->jsonResponse(['error' => 'Error al crear usuario: ' . $e->getMessage()], 500);
            }

        } catch (Exception $e) {
            LogConfig::secureLog("Error general en registro: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Error interno del servidor'], 500);
        }
    }

    public function login(array $data): void {
        try {
            LogConfig::secureLog("=== Inicio del proceso de login ===");
            LogConfig::secureLog("Datos recibidos", $data);

            // Verificar captcha primero
            if (!isset($data['captcha']) || !$this->verifyCaptcha($data['captcha'])) {
                $this->jsonResponse(['error' => 'Verificación de captcha fallida'], 400);
                return;
            }

            if (!isset($data['username']) || !isset($data['password'])) {
                error_log("Faltan credenciales");
                $this->jsonResponse(['error' => 'Usuario y contraseña requeridos'], 400);
                return;
            }

            $user = $this->userRepository->findByUsername($data['username']);
            error_log("Buscando usuario: " . $data['username']);

            if (!$user) {
                error_log("Usuario no encontrado");
                $this->jsonResponse(['error' => 'Credenciales inválidas'], 401);
                return;
            }

            if (!$user->verifyPassword($data['password'])) {
                error_log("Contraseña incorrecta");
                $this->jsonResponse(['error' => 'Credenciales inválidas'], 401);
                return;
            }

            // Limpiar y regenerar sesión
            session_regenerate_id(true);
            $_SESSION = array();
            
            // Establecer datos de sesión
            $_SESSION['user_id'] = $user->id;
            $_SESSION['username'] = $user->username;
            
            error_log("Sesión establecida para usuario: " . $user->username);
            error_log("ID de sesión: " . session_id());
            error_log("Datos de sesión: " . print_r($_SESSION, true));

            $this->jsonResponse([
                'message' => 'Login exitoso',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username
                ]
            ]);
        } catch (Exception $e) {
            LogConfig::secureLog("Error en login: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Error interno del servidor'], 500);
        }
    }

    public function logout(): void {
        try {
            $_SESSION = array();
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time()-42000, '/');
            }
            session_destroy();
            
            $this->jsonResponse(['message' => 'Sesión cerrada']);
        } catch (Exception $e) {
            error_log("Error en logout: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Error al cerrar sesión'], 500);
        }
    }

    private function jsonResponse(array $data, int $status = 200): void {
        try {
            // Limpiar cualquier salida anterior
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Verificar si los headers ya fueron enviados
            if (headers_sent($file, $line)) {
                error_log("Headers ya enviados en $file:$line");
                throw new Exception("Headers ya enviados");
            }

            // Establecer headers
            header('Content-Type: application/json');
            header('Cache-Control: no-cache, must-revalidate');
            http_response_code($status);
            
            // Codificar respuesta
            $jsonData = json_encode($data);
            if ($jsonData === false) {
                error_log("Error codificando JSON: " . json_last_error_msg());
                throw new Exception("Error codificando respuesta JSON");
            }
            
            error_log("Enviando respuesta JSON: " . $jsonData);
            echo $jsonData;
            exit;

        } catch (Exception $e) {
            error_log("Error en jsonResponse: " . $e->getMessage());
            // Intentar enviar una respuesta de error básica
            http_response_code(500);
            echo json_encode(['error' => 'Error interno del servidor']);
            exit;
        }
    }
} 