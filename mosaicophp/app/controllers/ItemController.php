<?php
/** app/controllers/ItemController.php
 * Controlador que maneja las peticiones relacionadas con los items
 */
class ItemController {
    private ItemRepository $itemRepository;

    public function __construct() {
        $this->itemRepository = new ItemRepository();
    }

    // Centralized JSON response method for this controller
    private function jsonResponse(array $data, int $status = 200): void {
        // Ensure no previous output interferes.
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        // Check if headers are already sent to prevent errors.
        if (headers_sent($file, $line)) {
            error_log("ItemController::jsonResponse - Headers already sent in $file on line $line. Data: " . print_r($data, true));
            // Avoid sending more headers or echoing if already sent.
            return;
        }
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        http_response_code($status);
        echo json_encode($data);
    }
    
    // Helper to ensure sum is 100% and items are >= 0
    private function _ensureSumIs100Strict(int $userId): void {
        $allItems = $this->itemRepository->getAll($userId);
        if (empty($allItems)) return;

        // First pass: ensure no item is negative and round individual items
        foreach($allItems as $item) {
            $item->percentage = round($item->percentage, 2);
            if ($item->percentage < 0) $item->percentage = 0.0;
            // Update immediately if clamped to 0 to reflect this change before sum calculation
            // However, to avoid too many DB calls, better to collect changes.
            // For now, this function primarily corrects the sum.
        }

        $currentSum = 0;
        foreach($allItems as $item) {
            $currentSum += $item->percentage;
        }
        $currentSum = round($currentSum, 2);
        $discrepancy = round(100.0 - $currentSum, 2);

        if (abs($discrepancy) >= 0.01) { // If sum is off by at least 0.01
            $itemToAdjust = null;
            $maxPercentage = -1.0; // Start with a value lower than any possible percentage

            // Find item with largest percentage to absorb discrepancy
            // Or, if discrepancy is positive (sum < 100), add to smallest to prevent overshooting 100 for one item.
            // Or, if discrepancy is negative (sum > 100), subtract from largest.
            // Simplest for now: always adjust the largest.
            foreach ($allItems as $item) {
                if ($item->percentage > $maxPercentage) {
                    $maxPercentage = $item->percentage;
                    $itemToAdjust = $item;
                }
            }
            
            // If all items are 0% (e.g. after clamping negatives, or if only one item remains after delete and was 0)
            // pick the first one to ensure it receives the 100%
            if ($itemToAdjust === null && !empty($allItems)) {
                $itemToAdjust = $allItems[0];
            }

            if ($itemToAdjust) {
                $adjustedPercentage = round($itemToAdjust->percentage + $discrepancy, 2);

                // Clamp the adjusted percentage between 0 and 100
                if ($adjustedPercentage < 0) $adjustedPercentage = 0.0;
                if ($adjustedPercentage > 100.0) $adjustedPercentage = 100.0;
                
                $itemToAdjust->percentage = $adjustedPercentage;
                $this->itemRepository->update($itemToAdjust);

                // If clamping occurred, the sum might *still* be off.
                // This indicates a more significant issue than simple rounding.
                // e.g. if sum was 110, largest was 5, discrepancy -10. largest becomes -5, clamped to 0. Sum still > 100.
                // This requires a more iterative or holistic redistribution.
                // For this function, one pass adjustment is the goal.
                // Re-check sum and if still off significantly, it's a deeper issue.
                $finalSumCheck = 0;
                foreach($this->itemRepository->getAll($userId) as $finalItem) $finalSumCheck += $finalItem->percentage;
                if(abs(100.0 - round($finalSumCheck,2)) >= 0.01) {
                     error_log("_ensureSumIs100Strict: Sum still significantly off after one pass adjustment. Sum: " . $finalSumCheck);
                     // At this point, a more robust multi-item redistribution might be needed if we have items clamped at 0 or 100
                     // For now, we accept this state to avoid infinite loops or overly complex logic here.
                }
            }
        }
         // Final save for any items that were clamped to 0 initially but not part of sum adjustment
        foreach($allItems as $item) {
            if ($item !== $itemToAdjust) { // Avoid double update if it was the one adjusted for sum
                 $clampedPercentage = round($item->percentage, 2);
                 if ($clampedPercentage < 0) $clampedPercentage = 0.0;
                 if ($item->percentage != $clampedPercentage) { // Only update if it changed
                     $item->percentage = $clampedPercentage;
                     $this->itemRepository->update($item);
                 }
            }
        }
    }

    /**
     * Distributes a target total percentage among a list of items based on their current proportions.
     * Updates items in the database.
     * Ensures no item percentage goes below zero.
     */
    private function _distributeTargetAmongItems(array $itemsToUpdate, float $targetTotalPercentage): void {
        if (empty($itemsToUpdate)) return;

        $currentSumOfItems = 0;
        foreach ($itemsToUpdate as $item) {
            // Ensure percentages are non-negative before summing up for distribution
            if ($item->percentage < 0) $item->percentage = 0.0;
            $currentSumOfItems += $item->percentage;
        }
        $currentSumOfItems = round($currentSumOfItems, 2);

        foreach ($itemsToUpdate as $item) {
            if ($currentSumOfItems > 0) {
                $item->percentage = round(($item->percentage / $currentSumOfItems) * $targetTotalPercentage, 2);
            } else { // All items in the list are 0% (or list was empty and filtered), distribute target equally
                $item->percentage = round($targetTotalPercentage / count($itemsToUpdate), 2);
            }
            // Clamp to ensure non-negativity after distribution, although ideally logic prevents this.
            if ($item->percentage < 0) $item->percentage = 0.0;
            $this->itemRepository->update($item);
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
            
            if (!headers_sent()) {
                header('Content-Type: application/json');
            }
            echo json_encode($itemsArray);
        } catch (Exception $e) {
            error_log("Error en getItems: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json');
            }
            echo json_encode(['error' => 'Error al obtener los items']);
        }
    }

    public function addItem(array $data): void {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->jsonResponse(['error' => 'No autorizado'], 401); return;
            }
            $itemName = trim($data['name'] ?? '');
            if (empty($itemName)) {
                $this->jsonResponse(['error' => 'El nombre es requerido'], 400); return;
            }

            $existingItems = $this->itemRepository->getAll($_SESSION['user_id']);
            
            $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
            $newItem = new Item($itemName, 1.0, $color, 0, $_SESSION['user_id']);

            if (empty($existingItems)) {
                $newItem->percentage = 100.0;
            } else {
                // Existing items need to make space for the new 1% item. Their new sum must be 99%.
                $this->_distributeTargetAmongItems($existingItems, 99.0);
                // New item keeps its 1% (already set in constructor for $newItem)
            }
            
            $this->itemRepository->add($newItem); // Add the new item
            $this->_ensureSumIs100Strict($_SESSION['user_id']); // Correct any minor rounding issues
            $this->getItems(); // Respond with all items
        } catch (Exception $e) {
            error_log("Error en addItem: " . $e->getMessage());
            http_response_code(500); echo json_encode(['error' => 'Error al agregar el item']);
        }
    }

    public function updateItem(int $id, array $data): void {
        try {
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401); echo json_encode(['error' => 'No autorizado']); return;
            }
            if (empty($data)) {
                 http_response_code(400); echo json_encode(['error' => 'No data provided for update']); return;
            }
            $item = $this->itemRepository->findById($id, $_SESSION['user_id']);
            if (!$item) {
                 http_response_code(404); echo json_encode(['error' => 'Item no encontrado']); return;
            }

            if (isset($data['name'])) {
                $item->name = trim($data['name']);
                if (empty($item->name)) {
                    http_response_code(400); echo json_encode(['error' => 'El nombre no puede estar vacío']); return;
                }
                $this->itemRepository->updateName($item);
            }
            // Percentage updates are handled by adjustItem
            $this->getItems();
        } catch (Exception $e) {
            error_log("Error en updateItem: " . $e->getMessage());
            http_response_code(500); echo json_encode(['error' => 'Error al actualizar el item']);
        }
    }

    public function deleteItem(int $id): void {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->jsonResponse(['error' => 'No autorizado'], 401); return;
            }
            $itemToDelete = $this->itemRepository->findById($id, $_SESSION['user_id']);
            if (!$itemToDelete) {
                $this->jsonResponse(['error' => 'Item no encontrado'], 404); return;
            }

            // $percentageFreed = $itemToDelete->percentage; // Not needed with new logic
            if (!$this->itemRepository->delete($id, $_SESSION['user_id'])) {
                $this->jsonResponse(['error' => 'Error al eliminar el item'], 500); return;
            }
            
            $remainingItems = $this->itemRepository->getAll($_SESSION['user_id']);
            if (!empty($remainingItems)) {
                // Remaining items should now sum to 100%
                $this->_distributeTargetAmongItems($remainingItems, 100.0);
            }
            
            // Ensure sum is strictly 100% after potential rounding in _distributeTargetAmongItems
            $this->_ensureSumIs100Strict($_SESSION['user_id']);
            $this->getItems();
        } catch (Exception $e) {
            error_log("Error en deleteItem: " . $e->getMessage());
            http_response_code(500); echo json_encode(['error' => 'Error al eliminar el item']);
        }
    }

    public function adjustItem(array $data): void {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->jsonResponse(['error' => 'No autorizado'], 401); return;
            }
            if (!isset($data['id']) || !isset($data['percentage'])) {
                $this->jsonResponse(['error' => 'ID y porcentaje son requeridos'], 400); return;
            }

            $itemToAdjustId = (int)$data['id'];
            $newPercentageForItemToAdjust = round(max(0.0, min(100.0, (float)$data['percentage'])), 2);
            
            $allItems = $this->itemRepository->getAll($_SESSION['user_id']);
            $itemToAdjust = null;
            $otherItems = [];

            foreach($allItems as $item) {
                if ($item->id === $itemToAdjustId) {
                    $itemToAdjust = $item;
                } else {
                    $otherItems[] = $item;
                }
            }

            if (!$itemToAdjust) {
                $this->jsonResponse(['error' => 'Item no encontrado para ajustar'], 404); return;
            }

            // Current percentage of the item being adjusted isn't strictly needed for this logic,
            // as we are setting it to a new value and then adjusting others.
            // $oldPercentage = round($itemToAdjust->percentage, 2);

            $itemToAdjust->percentage = $newPercentageForItemToAdjust; // Set the target item's new percentage
            
            if (!empty($otherItems)) {
                // Other items must sum up to 100% - newPercentageForItemToAdjust
                $this->_distributeTargetAmongItems($otherItems, round(100.0 - $newPercentageForItemToAdjust, 2));
            } else if ($newPercentageForItemToAdjust < 100.0 && count($allItems) == 1) {
                 // Only one item exists, it must be 100%
                 $itemToAdjust->percentage = 100.0;
            }
            
            $this->itemRepository->update($itemToAdjust); // Save the main adjusted item
            $this->_ensureSumIs100Strict($_SESSION['user_id']); // Correct any minor rounding issues
            $this->getItems();
        } catch (Exception $e) {
            error_log("Error en adjustItem: " . $e->getMessage());
            http_response_code(500); echo json_encode(['error' => 'Error al ajustar el item']);
        }
    }
}
?>