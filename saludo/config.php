<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'saludos_db');
define('DB_USER', 'root');
define('DB_PASS', 'Anthony.17');

// Configuración de reCAPTCHA
define('RECAPTCHA_SITE_KEY', '6LcBSq4qAAAAAD9WV6hNQ5mFfiVmBSsMkgCbNBl3');
define('RECAPTCHA_SECRET_KEY', '6LcBSq4qAAAAAEOOBOxL5bc1G1swV0QA79n-Q6ho');

// Configuración de límite de tiempo (segundos)
define('TIEMPO_ESPERA', 300); // 5 minutos

// Función para obtener IP del cliente
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $_SERVER['REMOTE_ADDR'];
}

// Conexión PDO
try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>