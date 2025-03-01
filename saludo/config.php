<?php
// Configuración de la base de datos
$host = 'localhost';
$dbname = 'saludos_db';
$username = 'root';
$password = 'Anthony.17'; // Establece tu contraseña real aquí

// Crear conexión PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("ERROR: No se pudo conectar. " . $e->getMessage());
}

// Configuración de reCAPTCHA
define('RECAPTCHA_ENABLED', false); // Flag para activar/desactivar reCAPTCHA
define('RECAPTCHA_SECRET_KEY', '6LcBSq4qAAAAAEOOBOxL5bc1G1swV0QA79n');
define('RECAPTCHA_SITE_KEY', '6LcBSq4qAAAAAD9WV6hNQ5mFfiVmBSsMkgCbNBl3');

// Tiempo de espera entre envíos (en segundos)
define('TIEMPO_ESPERA', 300); // 5 minutos
