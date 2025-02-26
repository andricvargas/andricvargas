<?php
/**
 * app/models/Item.php
 * Modelo para representar un item y su repositorio
 */
class Item {
    public int $id;
    public string $name;
    public float $percentage;
    public string $color;
    public int $userId;

    public function __construct(
        string $name, 
        float $percentage, 
        string $color, 
        int $id = 0, 
        int $userId = 0
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->percentage = $percentage;
        $this->color = $color;
        $this->userId = $userId;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'percentage' => round($this->percentage, 2),
            'color' => $this->color,
            'user_id' => $this->userId
        ];
    }
}

class ItemRepository {
    private PDO $db;

    public function __construct() {
        require_once __DIR__ . '/../config/Database.php';
        $this->db = Database::getInstance()->getConnection();
        $this->createTableIfNotExists();
    }

    private function createTableIfNotExists(): void {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                percentage DECIMAL(5,2) NOT NULL,
                color VARCHAR(7) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )";
            $this->db->exec($sql);
        } catch (PDOException $e) {
            error_log("Error creando tabla items: " . $e->getMessage());
            throw new Exception("Error inicializando la base de datos");
        }
    }

    public function getAll(int $userId): array {
        try {
            error_log("Obteniendo items para usuario ID: " . $userId);
            $stmt = $this->db->prepare('SELECT * FROM items WHERE user_id = ? ORDER BY created_at');
            $stmt->execute([$userId]);
            $items = [];
            
            while ($row = $stmt->fetch()) {
                $items[] = new Item(
                    $row['name'],
                    (float)$row['percentage'],
                    $row['color'],
                    (int)$row['id'],
                    (int)$row['user_id']
                );
            }
            
            error_log("Total de items encontrados: " . count($items));
            return $items;
        } catch (PDOException $e) {
            error_log("Error en getAll: " . $e->getMessage());
            throw $e;
        }
    }

    public function add(Item $item): void {
        $stmt = $this->db->prepare(
            'INSERT INTO items (name, percentage, color, user_id) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $item->name,
            $item->percentage,
            $item->color,
            $item->userId
        ]);
        $item->id = (int)$this->db->lastInsertId();
    }

    public function update(Item $item): void {
        $stmt = $this->db->prepare(
            'UPDATE items SET percentage = ? WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$item->percentage, $item->id, $item->userId]);
    }

    public function findById(int $id, int $userId): ?Item {
        $stmt = $this->db->prepare('SELECT * FROM items WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();
        
        if (!$row) {
            return null;
        }

        return new Item(
            $row['name'],
            (float)$row['percentage'],
            $row['color'],
            (int)$row['id'],
            (int)$row['user_id']
        );
    }

    public function delete(int $id, int $userId): bool {
        try {
            error_log("Eliminando item ID: $id para usuario ID: $userId");
            
            $stmt = $this->db->prepare('DELETE FROM items WHERE id = ? AND user_id = ?');
            $result = $stmt->execute([$id, $userId]);
            
            if ($result) {
                error_log("Item eliminado exitosamente");
            } else {
                error_log("Error al eliminar item: " . implode(", ", $stmt->errorInfo()));
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error en delete: " . $e->getMessage());
            throw new Exception("Error al eliminar el item: " . $e->getMessage());
        }
    }

    public function updateAllPercentages(array $items): void {
        if (empty($items)) return;
        
        $this->db->beginTransaction();
        try {
            foreach ($items as $item) {
                error_log("Actualizando item {$item->id} a {$item->percentage}%");
                $stmt = $this->db->prepare(
                    'UPDATE items SET percentage = ? WHERE id = ? AND user_id = ?'
                );
                $stmt->execute([
                    $item->percentage,
                    $item->id,
                    $item->userId
                ]);
            }
            $this->db->commit();
            error_log("Transacción completada exitosamente");
        } catch (Exception $e) {
            error_log("Error en updateAllPercentages: " . $e->getMessage());
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateName(Item $item): bool {
        try {
            error_log("=== Inicio updateName en repositorio ===");
            error_log("Item ID: " . $item->id);
            error_log("Nuevo nombre: " . $item->name);
            error_log("Usuario ID: " . $item->userId);
            
            $stmt = $this->db->prepare(
                'UPDATE items SET name = ? WHERE id = ? AND user_id = ?'
            );
            
            $params = [
                $item->name,
                $item->id,
                $item->userId
            ];
            error_log("Parámetros de la consulta: " . print_r($params, true));
            
            $result = $stmt->execute($params);
            
            if ($result) {
                error_log("Actualización exitosa en la base de datos");
                error_log("Filas afectadas: " . $stmt->rowCount());
            } else {
                error_log("Error en la actualización. Código de error: " . implode(", ", $stmt->errorInfo()));
            }
            
            return $result && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error PDO en updateName: " . $e->getMessage());
            error_log("Código de error: " . $e->getCode());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw new Exception("Error en la base de datos al actualizar el nombre: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Error general en updateName: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
}