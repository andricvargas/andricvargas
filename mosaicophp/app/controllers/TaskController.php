<?php

class TaskController {
    private TaskRepository $taskRepository;
    
    public function __construct() {
        $this->taskRepository = new TaskRepository();
    }
    
    public function getTasksByItem(int $itemId): void {
        try {
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['error' => 'No autorizado']);
                return;
            }

            $tasks = $this->taskRepository->getByItemId($itemId);
            echo json_encode(array_map(fn($task) => $task->toArray(), $tasks));
        } catch (Exception $e) {
            error_log("Error en getTasksByItem: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Error al obtener las tareas']);
        }
    }
    
    public function addTask(int $itemId, array $data): void {
        try {
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['error' => 'No autorizado']);
                return;
            }

            if (empty($data['description'])) {
                http_response_code(400);
                echo json_encode(['error' => 'La descripción es requerida']);
                return;
            }

            $task = new Task(
                $data['description'],
                $itemId
            );

            $this->taskRepository->add($task);
            
            // Devolver la lista actualizada de tareas
            $tasks = $this->taskRepository->getByItemId($itemId);
            echo json_encode(array_map(fn($task) => $task->toArray(), $tasks));
        } catch (Exception $e) {
            error_log("Error en addTask: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Error al agregar la tarea']);
        }
    }
    
    public function updateTask(int $taskId, array $data): void {
        try {
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['error' => 'No autorizado']);
                return;
            }

            $task = $this->taskRepository->findById($taskId);
            if (!$task) {
                http_response_code(404);
                echo json_encode(['error' => 'Tarea no encontrada']);
                return;
            }

            if (isset($data['completed'])) {
                $task->completed = (bool)$data['completed'];
            }
            if (isset($data['description'])) {
                $task->description = $data['description'];
            }

            $this->taskRepository->update($task);
            
            // Devolver la lista actualizada de tareas
            $tasks = $this->taskRepository->getByItemId($task->itemId);
            echo json_encode(array_map(fn($task) => $task->toArray(), $tasks));
        } catch (Exception $e) {
            error_log("Error en updateTask: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar la tarea']);
        }
    }
    
    public function deleteTask(int $taskId): void {
        try {
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['error' => 'No autorizado']);
                return;
            }

            $task = $this->taskRepository->findById($taskId);
            if (!$task) {
                http_response_code(404);
                echo json_encode(['error' => 'Tarea no encontrada']);
                return;
            }

            $itemId = $task->itemId;
            $this->taskRepository->delete($taskId);
            
            // Devolver la lista actualizada de tareas
            $tasks = $this->taskRepository->getByItemId($itemId);
            echo json_encode(array_map(fn($task) => $task->toArray(), $tasks));
        } catch (Exception $e) {
            error_log("Error en deleteTask: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar la tarea']);
        }
    }
} 