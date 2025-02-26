<?php

class ErrorHandler {
    public static function init() {
        // Establecer el manejador de errores personalizado
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatalError']);
    }

    public static function handleError($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $error = [
            'type' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ];

        error_log(print_r($error, true));

        if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Error interno del servidor']);
            exit;
        }

        return true;
    }

    public static function handleException($exception) {
        $error = [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ];

        error_log(print_r($error, true));

        if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Error interno del servidor']);
            exit;
        }
    }

    public static function handleFatalError() {
        $error = error_get_last();
        if ($error !== null && $error['type'] === E_ERROR) {
            self::handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }
} 