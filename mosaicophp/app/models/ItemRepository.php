public function updateName(Item $item): bool {
    $stmt = $this->db->prepare('UPDATE items SET name = ? WHERE id = ?');
    return $stmt->execute([$item->name, $item->id]);
}