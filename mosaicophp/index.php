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
if (strpos($request_uri, '/api/') === 0) {
    header('Content-Type: application/json');
    
    $auth = new AuthController();
    $itemController = new ItemController();
    $taskController = new TaskController();
    
    // Debug de la petición
    LogConfig::secureLog("=== Nueva petición API ===");
    LogConfig::secureLog("URI: " . $request_uri);
    LogConfig::secureLog("Método: " . $_SERVER['REQUEST_METHOD']);
    LogConfig::secureLog("Contenido", json_decode(file_get_contents('php://input'), true));
    
    switch ($request_uri) {
        case '/api/auth/login':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $auth->login($data);
            }
            break;

        case '/api/auth/register':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $auth->register($data);
            }
            break;

        case '/api/items':
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET':
                    $itemController->getItems();
                    break;
                case 'POST':
                    $data = json_decode(file_get_contents('php://input'), true);
                    $itemController->addItem($data);
                    break;
                default:
                    http_response_code(405);
                    echo json_encode(['error' => 'Método no permitido']);
            }
            break;

        case (preg_match('/^\/api\/items\/(\d+)$/', $request_uri, $matches) ? true : false):
            $itemId = (int)$matches[1];
            LogConfig::secureLog("Procesando petición para item ID: " . $itemId);
            
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'PUT':
                    LogConfig::secureLog("Procesando PUT request");
                    $data = json_decode(file_get_contents('php://input'), true);
                    LogConfig::secureLog("Datos recibidos: " . print_r($data, true));
                    if ($data === null) {
                        LogConfig::secureLog("Error decodificando JSON: " . json_last_error_msg());
                        http_response_code(400);
                        echo json_encode(['error' => 'JSON inválido']);
                        exit;
                    }
                    $itemController->updateItem($itemId, $data);
                    break;
                case 'DELETE':
                    $itemController->deleteItem($itemId);
                    break;
                default:
                    LogConfig::secureLog("Método no soportado: " . $_SERVER['REQUEST_METHOD']);
                    http_response_code(405);
                    echo json_encode(['error' => 'Método no permitido']);
            }
            break;

        case '/api/items/adjust':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $itemController->adjustItem($data);
            }
            break;

        case '/api/auth/logout':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $auth->logout();
            }
            break;

        case (preg_match('/^\/api\/items\/(\d+)\/tasks$/', $request_uri, $matches) ? true : false):
            $itemId = (int)$matches[1];
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $taskController->getTasksByItem($itemId);
            } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $taskController->addTask($itemId, $data);
            }
            break;

        case (preg_match('/^\/api\/tasks\/(\d+)$/', $request_uri, $matches) ? true : false):
            $taskId = (int)$matches[1];
            if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                $data = json_decode(file_get_contents('php://input'), true);
                $taskController->updateTask($taskId, $data);
            } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                $taskController->deleteTask($taskId);
            }
            break;

        default:
            LogConfig::secureLog("Ruta no encontrada: " . $request_uri);
            http_response_code(404);
            echo json_encode(['error' => 'Ruta API no encontrada']);
    }
    exit;
}

// Web routes
if ($request_uri === '/login') {
    if (isset($_SESSION['user_id'])) {
        header('Location: ' . $base_path);
        exit;
    }
    require_once __DIR__ . '/app/views/login.php';
    exit;
}

// Verificar autenticación para otras rutas
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_path . '/login');
    exit;
}

// Ruta por defecto para usuarios autenticados
require_once __DIR__ . '/app/views/index.php';