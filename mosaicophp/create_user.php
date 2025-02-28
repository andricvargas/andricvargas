<?php
require_once __DIR__ . '/app/config/Database.php';
require_once __DIR__ . '/app/models/User.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Crear la tabla si no existe
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL
    )");
    
    // Datos del usuario
    $username = 'admin@test.com';
    $password = 'Admin123!';
    
    // Hash de la contraseña
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Preparar la inserción
    $stmt = $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->execute([$username, $hashedPassword]);
    
    echo "Usuario creado exitosamente!\n";
    echo "Username: $username\n";
    echo "Password: $password\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 