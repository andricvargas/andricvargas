<?php
// Mostrar todos los errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Test de Conexión a Base de Datos</h1>";

try {
    echo "<h2>1. Probando inclusión de archivo Database.php</h2>";
    require_once __DIR__ . '/app/config/Database.php';
    echo "✅ Archivo Database.php cargado correctamente<br>";

    echo "<h2>2. Probando conexión PDO</h2>";
    echo "Intentando conectar a la base de datos...<br>";
    $db = Database::getInstance()->getConnection();
    echo "✅ Conexión PDO establecida correctamente<br>";

    echo "<h2>3. Probando consulta simple</h2>";
    $stmt = $db->query('SELECT 1');
    if ($stmt->fetch()) {
        echo "✅ Consulta simple ejecutada correctamente<br>";
    }

    echo "<h2>4. Mostrando tablas disponibles</h2>";
    $stmt = $db->query('SHOW TABLES');
    echo "<ul>";
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "<li>{$row[0]}</li>";
    }
    echo "</ul>";

    echo "<h2>5. Verificando tabla users</h2>";
    try {
        $stmt = $db->query('DESCRIBE users');
        echo "<table border='1'>
                <tr>
                    <th>Campo</th>
                    <th>Tipo</th>
                    <th>Nulo</th>
                    <th>Llave</th>
                    <th>Default</th>
                    <th>Extra</th>
                </tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr>
                    <td>{$row['Field']}</td>
                    <td>{$row['Type']}</td>
                    <td>{$row['Null']}</td>
                    <td>{$row['Key']}</td>
                    <td>{$row['Default']}</td>
                    <td>{$row['Extra']}</td>
                  </tr>";
        }
        echo "</table>";
        echo "✅ Tabla users existe y es accesible<br>";
    } catch (PDOException $e) {
        echo "❌ Error con la tabla users: " . $e->getMessage() . "<br>";
    }

    echo "<h2>6. Verificando usuarios existentes</h2>";
    try {
        $stmt = $db->query('SELECT id, username, created_at FROM users');
        echo "<table border='1'>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Creado</th>
                </tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr>
                    <td>{$row['id']}</td>
                    <td>{$row['username']}</td>
                    <td>{$row['created_at']}</td>
                  </tr>";
        }
        echo "</table>";
        echo "✅ Consulta de usuarios ejecutada correctamente<br>";
    } catch (PDOException $e) {
        echo "❌ Error al consultar usuarios: " . $e->getMessage() . "<br>";
    }

    echo "<h2>7. Información de la conexión</h2>";
    echo "<pre>";
    echo "Versión del servidor: " . $db->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
    echo "Versión del cliente: " . $db->getAttribute(PDO::ATTR_CLIENT_VERSION) . "\n";
    echo "Estado de la conexión: " . $db->getAttribute(PDO::ATTR_CONNECTION_STATUS) . "\n";
    echo "</pre>";

} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>";
    echo "<h3>❌ Error de conexión:</h3>";
    echo "Mensaje: " . $e->getMessage() . "<br>";
    echo "Código: " . $e->getCode() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
    echo "</div>";
}

echo "<hr>";
echo "<h2>8. Información del sistema</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Extensiones PDO disponibles: " . implode(', ', PDO::getAvailableDrivers()) . "\n";
echo "Sistema operativo: " . PHP_OS . "\n";
echo "</pre>"; 