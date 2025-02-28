<?php
/**
 * Archivo de configuración principal
 * Define las configuraciones básicas y el entorno de la aplicación
 */

// Aseguramos que el script se está ejecutando en el servidor
defined('BASEPATH') || define('BASEPATH', dirname(__DIR__));

// Configuración del entorno
class Environment {
    const DEVELOPMENT = 'development';
    const PRODUCTION = 'production';
    
    public static function getCurrentEnvironment() {
        return getenv('APP_ENV') ?: self::DEVELOPMENT;
    }
}

// Configuración de errores según el entorno
$environment = Environment::getCurrentEnvironment();
if ($environment === Environment::DEVELOPMENT) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', BASEPATH . '/logs/error.log');
}

// Configuración de la zona horaria
date_default_timezone_set('America/Mexico_City');

// Manejo de sesión existente
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
    session_unset();
} elseif (session_status() === PHP_SESSION_NONE) {
    @session_start();
    session_unset();
    session_destroy();
}

// Configuración de sesión
$sessionConfig = [
    'cookie_httponly' => 1,
    'use_only_cookies' => 1,
    'cookie_secure' => $environment === Environment::PRODUCTION ? 1 : 0,
    'cookie_samesite' => 'Strict',
    'gc_maxlifetime' => 3600
];

// Aplicamos la configuración de sesión
foreach ($sessionConfig as $key => $value) {
    ini_set("session.$key", $value);
}

// Establecemos los parámetros de la cookie de sesión
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => '',
    'secure' => $environment === Environment::PRODUCTION,
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Iniciar la nueva sesión
session_start();

// Configuración de la base de datos (si se necesita en el futuro)
class DatabaseConfig {
    private static $config = [
        'development' => [
            'host' => 'localhost',
            'dbname' => 'mosaico_dev',
            'user' => 'root',
            'password' => '',
            'charset' => 'utf8mb4'
        ],
        'production' => [
            'host' => 'localhost',
            'dbname' => 'mosaico_prod',
            'user' => 'prod_user',
            'password' => '', // Establecer en producción
            'charset' => 'utf8mb4'
        ]
    ];

    public static function getDatabaseConfig() {
        $env = Environment::getCurrentEnvironment();
        return self::$config[$env];
    }
}

// Configuración de seguridad
class SecurityConfig {
    // Clave para encriptación (cambiar en producción)
    const ENCRYPTION_KEY = '6LcBSq4qAAAAAEOOBOxL5bc1G1swV0QA79n-Q6ho';
    
    // Tiempo máximo de inactividad de sesión (1 hora)
    const SESSION_LIFETIME = 3600;
    
    // Configuraciones de contraseña
    const PASSWORD_MIN_LENGTH = 8;
    const PASSWORD_REQUIRE_SPECIAL = true;
    
    // Headers de seguridad
    public static function setSecurityHeaders() {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        if (Environment::getCurrentEnvironment() === Environment::PRODUCTION) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}

// Configuración de la aplicación
class AppConfig {
    // Rutas de la aplicación
    const VIEWS_PATH = BASEPATH . '/app/views/';
    const MODELS_PATH = BASEPATH . '/app/models/';
    const CONTROLLERS_PATH = BASEPATH . '/app/controllers/';
    
    // Configuración del mosaico
    const MAX_ITEMS = 50;          // Número máximo de items permitidos
    const MIN_PERCENTAGE = 1;      // Porcentaje mínimo por item
    const MAX_PERCENTAGE = 100;    // Porcentaje máximo por item
    
    // Límites de la aplicación
    const MAX_NAME_LENGTH = 50;    // Longitud máxima del nombre de un item
    const REQUEST_TIMEOUT = 30;    // Timeout para peticiones en segundos
}

// Aplicar configuraciones de seguridad
SecurityConfig::setSecurityHeaders();

// Verificar y crear directorio de logs si no existe
if (!is_dir(BASEPATH . '/logs')) {
    mkdir(BASEPATH . '/logs', 0755, true);
}

// Establecer el manejador de errores personalizado
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $message = date('Y-m-d H:i:s') . " - Error ($errno): $errstr in $errfile on line $errline\n";
    error_log($message, 3, BASEPATH . '/logs/error.log');
    
    if (Environment::getCurrentEnvironment() === Environment::DEVELOPMENT) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
    
    return true;
});

// Configuración de logging
class LogConfig {
    private static $sensitiveFields = [
        'password',
        'token',
        'secret',
        'key',
        'hash'
    ];

    public static function sanitizeData($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_string($key) && self::isSensitiveField($key)) {
                    $data[$key] = '***REDACTED***';
                } else if (is_array($value)) {
                    $data[$key] = self::sanitizeData($value);
                }
            }
        }
        return $data;
    }

    private static function isSensitiveField($field) {
        $field = strtolower($field);
        foreach (self::$sensitiveFields as $sensitiveField) {
            if (strpos($field, $sensitiveField) !== false) {
                return true;
            }
        }
        return false;
    }

    public static function secureLog($message, $data = null) {
        if ($data !== null) {
            $sanitizedData = self::sanitizeData($data);
            error_log($message . ': ' . print_r($sanitizedData, true));
        } else {
            error_log($message);
        }
    }
}