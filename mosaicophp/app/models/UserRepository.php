<?php
namespace App\Models;

if (!class_exists('App\Models\UserRepository')) {

class UserRepository {
    private \PDO $db;

    public function __construct() {
        try {
            error_log("Inicializando UserRepository");
            $this->db = \Database::getInstance()->getConnection();
            error_log("Conexión obtenida exitosamente en UserRepository");
        } catch (\Exception $e) {
            error_log("Error al inicializar UserRepository: " . $e->getMessage());
            throw $e;
        }
    }

    public function findByUsername(string $username): ?User {
        try {
            error_log("Buscando usuario: " . $username);
            
            $stmt = $this->db->prepare('SELECT * FROM users WHERE username = ?');
            $stmt->execute([$username]);
            
            if ($row = $stmt->fetch()) {
                error_log("Usuario encontrado: " . $username);
                return new User(
                    $row['username'],
                    $row['password'],
                    (int)$row['id']
                );
            }
            
            error_log("Usuario no encontrado: " . $username);
            return null;
            
        } catch (\PDOException $e) {
            error_log("Error en findByUsername: " . $e->getMessage());
            throw $e;
        }
    }

    public function add(User $user): int {
        try {
            $stmt = $this->db->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
            $stmt->execute([$user->username, $user->getPassword()]);
            return (int)$this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Error en add: " . $e->getMessage());
            throw $e;
        }
    }
}

} // fin de if !class_exists