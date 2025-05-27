<?php
// Asegúrate de que estas líneas estén al inicio del archivo
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Cargar dependencias
require_once __DIR__ . '/app/config/Database.php';
require_once __DIR__ . '/app/models/User.php';
require_once __DIR__ . '/app/models/Item.php';
require_once __DIR__ . '/app/models/Task.php';
require_once __DIR__ . '/app/controllers/AuthController.php';
require_once __DIR__ . '/app/controllers/ItemController.php';
require_once __DIR__ . '/app/controllers/TaskController.php';
require_once __DIR__ . '/config/config.php';

// Configuración básica
$base_path = '/mosaicophp';
$request_uri = $_SERVER['REQUEST_URI'];
$request_uri = parse_url($request_uri, PHP_URL_PATH);
$request_uri = rtrim(str_replace($base_path, '', $request_uri), '/');
if (empty($request_uri)) $request_uri = '/';

// Debug
LogConfig::secureLog("Request URI: " . $request_uri);
LogConfig::secureLog("Request Method: " . $_SERVER['REQUEST_METHOD']);

// Obtener headers de la petición
$contentType = isset($_SERVER['HTTP_CONTENT_TYPE']) ? $_SERVER['HTTP_CONTENT_TYPE'] : 
              (isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '');
LogConfig::secureLog("Content Type: " . $contentType);

// API endpoints
if (str_starts_with($request_uri, '/api/')) {
    header('Content-Type: application/json');
    
    $auth = new AuthController();
    $itemController = new ItemController();
    $taskController = new TaskController();
    
    // Debug de la petición
    LogConfig::secureLog("=== Nueva petición API ===");
    LogConfig::secureLog("URI: " . $request_uri);
    LogConfig::secureLog("Método: " . $_SERVER['REQUEST_METHOD']);
    LogConfig::secureLog("Contenido", json_decode(file_get_contents('php://input'), true));

    // Determine effective route and parameters for complex routes
    $routeKey = $request_uri;
    $routeParams = [];

    $regexRoutes = [
        '/^\/api\/items\/(\d+)$/' => 'api_items_single',
        '/^\/api\/items\/(\d+)\/tasks$/' => 'api_items_tasks',
        '/^\/api\/tasks\/(\d+)$/' => 'api_tasks_single',
    ];

    foreach ($regexRoutes as $pattern => $key) {
        if (preg_match($pattern, $request_uri, $matches)) {
            $routeKey = $key;
            $routeParams = $matches;
            break;
        }
    }
    
    match ($routeKey) {
        '/api/auth/login' => match ($_SERVER['REQUEST_METHOD']) {
            'POST' => (function() use ($auth) {
                $rawInput = file_get_contents('php://input');
                $data = json_decode($rawInput, true);
                if ($rawInput && $data === null && json_last_error() !== JSON_ERROR_NONE) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid JSON format']);
                    return;
                }
                $auth->login($data ?? []); // Pass empty array if data is null (e.g. empty body)
            })(),
            default => (function() {
                http_response_code(405);
                echo json_encode(['error' => 'Método no permitido para /api/auth/login']);
            })()
        },
        '/api/auth/register' => match ($_SERVER['REQUEST_METHOD']) {
            'POST' => (function() use ($auth) {
                $rawInput = file_get_contents('php://input');
                $data = json_decode($rawInput, true);
                if ($rawInput && $data === null && json_last_error() !== JSON_ERROR_NONE) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid JSON format']);
                    return;
                }
                $auth->register($data ?? []);
            })(),
            default => (function() {
                http_response_code(405);
                echo json_encode(['error' => 'Método no permitido para /api/auth/register']);
            })()
        },
        '/api/items' => match ($_SERVER['REQUEST_METHOD']) {
            'GET' => $itemController->getItems(),
            'POST' => (function() use ($itemController) {
                $rawInput = file_get_contents('php://input');
                $data = json_decode($rawInput, true);
                if ($rawInput && $data === null && json_last_error() !== JSON_ERROR_NONE) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid JSON format']);
                    return;
                }
                $itemController->addItem($data ?? []);
            })(),
            default => (function() {
                http_response_code(405);
                echo json_encode(['error' => 'Método no permitido']);
            })()
        },
        'api_items_single' => (function() use ($itemController, $routeParams) {
            $itemId = (int)$routeParams[1];
            LogConfig::secureLog("Procesando petición para item ID: " . $itemId);
            
            match ($_SERVER['REQUEST_METHOD']) {
                'PUT' => (function() use ($itemController, $itemId) {
                    LogConfig::secureLog("Procesando PUT request");
                    $rawInput = file_get_contents('php://input');
                    $data = json_decode($rawInput, true);
                    LogConfig::secureLog("Datos recibidos: " . print_r($data, true));
                    if ($rawInput && $data === null && json_last_error() !== JSON_ERROR_NONE) {
                        LogConfig::secureLog("Error decodificando JSON: " . json_last_error_msg());
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid JSON format']);
                        return; 
                    }
                    $itemController->updateItem($itemId, $data ?? []);
                })(),
                'DELETE' => $itemController->deleteItem($itemId),
                default => (function() {
                    LogConfig::secureLog("Método no soportado: " . $_SERVER['REQUEST_METHOD']);
                    http_response_code(405);
                    echo json_encode(['error' => 'Método no permitido']);
                })()
            };
        })(),
        '/api/items/adjust' => match ($_SERVER['REQUEST_METHOD']) {
            'POST' => (function() use ($itemController) {
                $rawInput = file_get_contents('php://input');
                $data = json_decode($rawInput, true);
                if ($rawInput && $data === null && json_last_error() !== JSON_ERROR_NONE) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid JSON format']);
                    return;
                }
                $itemController->adjustItem($data ?? []);
            })(),
            default => (function() {
                http_response_code(405);
                echo json_encode(['error' => 'Método no permitido para /api/items/adjust']);
            })()
        },
        '/api/auth/logout' => match ($_SERVER['REQUEST_METHOD']) {
            'POST' => $auth->logout(), // Simplified if it's a single call
            default => (function() {
                http_response_code(405);
                echo json_encode(['error' => 'Método no permitido para /api/auth/logout']);
            })()
        },
        'api_items_tasks' => match ($_SERVER['REQUEST_METHOD']) {
            'GET' => (function() use ($taskController, $routeParams) {
                $itemId = (int)$routeParams[1];
                $taskController->getTasksByItem($itemId);
            })(),
            'POST' => (function() use ($taskController, $routeParams) {
                $itemId = (int)$routeParams[1];
                $rawInput = file_get_contents('php://input');
                $data = json_decode($rawInput, true);
                if ($rawInput && $data === null && json_last_error() !== JSON_ERROR_NONE) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid JSON format']);
                    return;
                }
                $taskController->addTask($itemId, $data ?? []);
            })(),
            default => (function() {
                http_response_code(405);
                echo json_encode(['error' => 'Método no permitido para /api/items/.../tasks']);
            })()
        },
        'api_tasks_single' => match ($_SERVER['REQUEST_METHOD']) {
            'PUT' => (function() use ($taskController, $routeParams) {
                $taskId = (int)$routeParams[1];
                $rawInput = file_get_contents('php://input');
                $data = json_decode($rawInput, true);
                if ($rawInput && $data === null && json_last_error() !== JSON_ERROR_NONE) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid JSON format']);
                    return;
                }
                $taskController->updateTask($taskId, $data ?? []);
            })(),
            'DELETE' => (function() use ($taskController, $routeParams) {
                $taskId = (int)$routeParams[1];
                $taskController->deleteTask($taskId);
            })(),
            default => (function() {
                http_response_code(405);
                echo json_encode(['error' => 'Método no permitido para /api/tasks/...']);
            })()
        },
        default => {
            LogConfig::secureLog("Ruta no encontrada: " . $request_uri);
            http_response_code(404);
            echo json_encode(['error' => 'Ruta API no encontrada']);
        }
    };
}

// Web routes
if ($request_uri === '/login') {
    if (isset($_SESSION['user_id'])) {
        header('Location: ' . $base_path);
    } else {
        require_once __DIR__ . '/app/views/login.php';
    }
} elseif (!isset($_SESSION['user_id'])) { // Verificar autenticación para otras rutas
    header('Location: ' . $base_path . '/login');
} else {
    // Ruta por defecto para usuarios autenticados
    require_once __DIR__ . '/app/views/index.php';
}