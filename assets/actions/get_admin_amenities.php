<?php
session_start();
require_once '../../db.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database unavailable.']);
    exit;
}

checkAdminAccess($pdo);

try {
    $columns = [];
    $colStmt = $pdo->query('SHOW COLUMNS FROM amenities');
    foreach ($colStmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $columns[] = strtolower((string) ($col['Field'] ?? ''));
    }

    $has = fn(string $name): bool => in_array(strtolower($name), $columns, true);

    $activeExpr = $has('active') ? 'active' : ($has('is_active') ? 'is_active' : '1');

    $sql = "
      SELECT
        id,
        name,
        price,
        {$activeExpr} AS active
      FROM amenities
      ORDER BY id ASC
    ";

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    $amenities = array_map(static function (array $row): array {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'price' => (float) ($row['price'] ?? 0),
            'active' => (int) ($row['active'] ?? 0) === 1,
        ];
    }, $rows);

    echo json_encode(['success' => true, 'amenities' => $amenities]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to load amenities: ' . $e->getMessage()]);
}
