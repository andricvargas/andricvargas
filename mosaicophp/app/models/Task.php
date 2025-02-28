<?php
/**
 * app/models/Task.php
 * Modelo para representar una tarea y su repositorio
 */
class Task {
    public int $id;
    public int $itemId;
    public string $description;
    public bool $completed;
    public string $createdAt;

    public function __construct(
        string $description,
        int $itemId,
        bool $completed = false,
        int $id = 0,
        string $createdAt = ''
    ) {
        $this->id = $id;
        $this->itemId = $itemId;
        $this->description = $description;
        $this->completed = $completed;
        $this->createdAt = $createdAt;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'item_id' => $this->itemId,
            'description' => $this->description,
            'completed' => $this->completed,
            'created_at' => $this->createdAt
        ];
    }
}

class TaskRepository {
    private PDO $db;

    public function __construct() {
        require_once __DIR__ . '/../config/Database.php';
        $this->db = Database::getInstance()->getConnection();
        $this->createTableIfNotExists();
    }

    private function createTableIfNotExists(): void {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS tasks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                item_id INT NOT NULL,
                description TEXT NOT NULL,
                completed BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
            )";
            $this->db->exec($sql);
        } catch (PDOException $e) {
            error_log("Error creando tabla tasks: " . $e->getMessage());
            throw new Exception("Error inicializando la base de datos");
        }
    }

    public function getByItemId(int $itemId): array {
        try {
            $stmt = $this->db->prepare('SELECT * FROM tasks WHERE item_id = ? ORDER BY created_at DESC');
            $stmt->execute([$itemId]);
            $tasks = [];
            
            while ($row = $stmt->fetch()) {
                $tasks[] = new Task(
                    $row['description'],
                    $row['item_id'],
                    (bool)$row['completed'],
                    (int)$row['id'],
                    $row['created_at']
                );
            }
            
            return $tasks;
        } catch (PDOException $e) {
            error_log("Error en getByItemId: " . $e->getMessage());
            throw $e;
        }
    }

    public function add(Task $task): void {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO tasks (item_id, description) VALUES (?, ?)'
            );
            $stmt->execute([
                $task->itemId,
                $task->description
            ]);
            $task->id = (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error en add: " . $e->getMessage());
            throw $e;
        }
    }

    public function update(Task $task): void {
        try {
            $stmt = $this->db->prepare(
                'UPDATE tasks SET description = ?, completed = ? WHERE id = ?'
            );
            $stmt->execute([
                $task->description,
                $task->completed,
                $task->id
            ]);
        } catch (PDOException $e) {
            error_log("Error en update: " . $e->getMessage());
            throw $e;
        }
    }

    public function delete(int $id): void {
        try {
            $stmt = $this->db->prepare('DELETE FROM tasks WHERE id = ?');
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error en delete: " . $e->getMessage());
            throw $e;
        }
    }

    public function findById(int $id): ?Task {
        try {
            $stmt = $this->db->prepare('SELECT * FROM tasks WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            
            if (!$row) {
                return null;
            }

            return new Task(
                $row['description'],
                $row['item_id'],
                (bool)$row['completed'],
                (int)$row['id'],
                $row['created_at']
            );
        } catch (PDOException $e) {
            error_log("Error en findById: " . $e->getMessage());
            throw $e;
        }
    }
} 