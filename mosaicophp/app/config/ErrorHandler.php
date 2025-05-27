<?php

class ErrorHandler {
    public static function init(): void {
        // Establecer el manejador de errores personalizado
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatalError']);
    }

    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool {
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

        // Consider removing exit calls for better testability and control flow
        if (isset($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], '/api/')) {
            if (!headers_sent()) {
                header('Content-Type: application/json');
                http_response_code(500); // Ensure a 500 status code for errors
            }
            echo json_encode(['error' => 'Error interno del servidor']);
            exit; // Exiting here might be problematic in some scenarios
        }

        return true;
    }

    public static function handleException(\Throwable $exception): void {
        $error = [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString() // Adding stack trace for more details
        ];

        error_log(print_r($error, true));

        // Consider removing exit calls for better testability and control flow
        if (isset($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], '/api/')) {
            if (!headers_sent()) {
                header('Content-Type: application/json');
                http_response_code(500); // Ensure a 500 status code for errors
            }
            echo json_encode(['error' => 'Error interno del servidor']);
            exit; // Exiting here might be problematic in some scenarios
        }
        // If not an API request, you might want to display a generic error page
        // or log the error differently.
        // For now, it just logs and exits for API, or does nothing for other request types.
    }

    public static function handleFatalError(): void {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            // It's good practice to also check headers_sent here if you plan to output anything
            self::handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }
}