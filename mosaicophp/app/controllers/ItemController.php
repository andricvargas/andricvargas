<?php
/** app/controllers/ItemController.php
 * Controlador que maneja las peticiones relacionadas con los items
 */
class ItemController {
    private ItemRepository $itemRepository;

    public function __construct() {
        $this->itemRepository = new ItemRepository();
    }

    private function redistributePercentage(array $items, Item $excludeItem, float $percentageToDistribute): void {
        if (empty($items)) return;
        
        // Calcular la suma total de porcentajes excluyendo el item
        $totalPercentage = 0;
        foreach ($items as $item) {
            if ($item->id !== $excludeItem->id) {
                $totalPercentage += $item->percentage;
            }
        }

        // Si no hay porcentaje total, distribuir equitativamente
        if ($totalPercentage == 0) {
            $equalShare = $percentageToDistribute / count($items);
            foreach ($items as $item) {
                if ($item->id !== $excludeItem->id) {
                    $item->percentage += $equalShare;
                    $this->itemRepository->update($item);
                }
            }
            return;
        }

        // Distribuir proporcionalmente
        foreach ($items as $item) {
            if ($item->id !== $excludeItem->id) {
                $proportion = $item->percentage / $totalPercentage;
                $item->percentage += ($percentageToDistribute * $proportion);
                // Redondear a 2 decimales para evitar errores de punto flotante
                $item->percentage = round($item->percentage, 2);
                $this->itemRepository->update($item);
            }
        }
    }

    public function getItems(): void {
        try {
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['error' => 'No autorizado']);
                return;
            }

            error_log("Obteniendo items para usuario ID: " . $_SESSION['user_id']);
            $items = $this->itemRepository->getAll($_SESSION['user_id']);
            error_log("Items encontrados: " . count($items));
            
            $itemsArray = array_map(function($item) {
                return $item->toArray();
            }, $items);
            
            header('Content-Type: application/json');
            echo json_encode($itemsArray);
        } catch (Exception $e) {
            error_log("Error en getItems: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode(['error' => 'Error al obtener los items']);
        }
    }

    public function addItem(array $data): void {
        try {
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['error' => 'No autorizado']);
                return;
            }

            if (empty($data['name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'El nombre es requerido']);
                return;
            }

            // Encontrar el item con mayor porcentaje
            $allItems = $this->itemRepository->getAll($_SESSION['user_id']);
            $maxPercentageItem = null;
            $maxPercentage = 0;
            
            foreach ($allItems as $item) {
                if ($item->percentage > $maxPercentage) {
                    $maxPercentage = $item->percentage;
                    $maxPercentageItem = $item;
                }
            }

            // Crear nuevo item con 1%
            $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
            $newItem = new Item(
                $data['name'],
                1.0, // Porcentaje inicial de 1%
                $color,
                0,
                $_SESSION['user_id']
            );

            // Restar 1% al item con mayor porcentaje si existe
            if ($maxPercentageItem) {
                $maxPercentageItem->percentage = round($maxPercentageItem->percentage - 1.0, 2);
                $this->itemRepository->update($maxPercentageItem);
            }

            $this->itemRepository->add($newItem);
            $this->getItems();
        } catch (Exception $e) {
            error_log("Error en addItem: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Error al agregar el item']);
        }
    }

    public function updateItem(int $id, array $data): void {
        try {
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['error' => 'No autorizado']);
                return;
            }

            $item = $this->itemRepository->findById($id, $_SESSION['user_id']);
            if (!$item) {
                http_response_code(404);
                echo json_encode(['error' => 'Item no encontrado']);
                return;
            }

            if (isset($data['name'])) {
                $item->name = $data['name'];
                $this->itemRepository->updateName($item);
            }

            $this->getItems();
        } catch (Exception $e) {
            error_log("Error en updateItem: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar el item']);
        }
    }

    public function deleteItem(int $id): void {
        try {
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['error' => 'No autorizado']);
                return;
            }

            $itemToDelete = $this->itemRepository->findById($id, $_SESSION['user_id']);
            if (!$itemToDelete) {
                http_response_code(404);
                echo json_encode(['error' => 'Item no encontrado']);
                return;
            }

            // Obtener el porcentaje del item a eliminar
            $percentageToRedistribute = $itemToDelete->percentage;

            // Obtener todos los items
            $allItems = $this->itemRepository->getAll($_SESSION['user_id']);

            // Eliminar el item
            if (!$this->itemRepository->delete($id, $_SESSION['user_id'])) {
                http_response_code(404);
                echo json_encode(['error' => 'Error al eliminar el item']);
                return;
            }

            // Redistribuir el porcentaje entre los items restantes
            if ($percentageToRedistribute > 0) {
                $this->redistributePercentage($allItems, $itemToDelete, $percentageToRedistribute);
            }

            $this->getItems();
        } catch (Exception $e) {
            error_log("Error en deleteItem: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Error al eliminar el item']);
        }
    }

    public function adjustItem(array $data): void {
        try {
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['error' => 'No autorizado']);
                return;
            }

            if (!isset($data['id']) || !isset($data['percentage'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID y porcentaje son requeridos']);
                return;
            }

            $itemToUpdate = $this->itemRepository->findById($data['id'], $_SESSION['user_id']);
            if (!$itemToUpdate) {
                http_response_code(404);
                echo json_encode(['error' => 'Item no encontrado']);
                return;
            }

            $allItems = $this->itemRepository->getAll($_SESSION['user_id']);
            $oldPercentage = $itemToUpdate->percentage;
            $newPercentage = max(0, min(100, (float)$data['percentage']));
            $percentageDiff = $newPercentage - $oldPercentage;

            if ($percentageDiff != 0) {
                $this->redistributePercentage($allItems, $itemToUpdate, -$percentageDiff);
                $itemToUpdate->percentage = round($newPercentage, 2);
                $this->itemRepository->update($itemToUpdate);
            }

            $this->getItems();
        } catch (Exception $e) {
            error_log("Error en adjustItem: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Error al ajustar el item']);
        }
    }
}