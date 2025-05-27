<?php
class User {
    public int $id;
    public string $username;
    private string $password;

    public function __construct(string $username) {
        $this->username = $username;
    }

    public function setPassword(string $password): void {
        if (str_starts_with($password, '$2y$')) {
            $this->password = $password;
        } else {
            $this->password = password_hash($password, PASSWORD_DEFAULT);
        }
    }

    public function verifyPassword(string $password): bool {
        error_log("Verificando contraseña para usuario: " . $this->username);
        error_log("Hash almacenado: " . $this->password);
        $result = password_verify($password, $this->password);
        error_log("Resultado de verificación: " . ($result ? "true" : "false"));
        return $result;
    }

    public function getPassword(): string {
        return $this->password;
    }
}

class UserRepository {
    private PDO $db;

    public function __construct() {
        require_once __DIR__ . '/../config/Database.php';
        $this->db = Database::getInstance()->getConnection();
        $this->createTableIfNotExists();
    }

    private function createTableIfNotExists(): void {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $this->db->exec($sql);
        } catch (PDOException $e) {
            error_log("Error creando tabla users: " . $e->getMessage());
            throw new Exception("Error inicializando la base de datos");
        }
    }

    public function create(User $user): bool {
        try {
            error_log("Intentando crear usuario: " . $user->username);
            
            $stmt = $this->db->prepare(
                'INSERT INTO users (username, password) VALUES (?, ?)'
            );
            
            $result = $stmt->execute([
                $user->username,
                $user->getPassword()
            ]);

            if ($result) {
                $user->id = (int)$this->db->lastInsertId();
                error_log("Usuario creado exitosamente. ID: " . $user->id);
            } else {
                error_log("Error al crear usuario: " . implode(", ", $stmt->errorInfo()));
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Error en create: " . $e->getMessage());
            throw new Exception("Error al crear el usuario: " . $e->getMessage());
        }
    }

    public function findByUsername(string $username): ?User {
        try {
            error_log("Buscando usuario: " . $username);
            
            $stmt = $this->db->prepare('SELECT * FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$row) {
                error_log("Usuario no encontrado: " . $username);
                return null;
            }

            $user = new User($row['username']);
            $user->id = (int)$row['id'];
            $user->setPassword($row['password']);
            
            error_log("Usuario encontrado: " . $username);
            return $user;
        } catch (PDOException $e) {
            error_log("Error en findByUsername: " . $e->getMessage());
            throw new Exception("Error al buscar el usuario: " . $e->getMessage());
        }
    }
}