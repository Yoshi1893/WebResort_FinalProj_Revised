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
    $colStmt = $pdo->query('SHOW COLUMNS FROM venues');
    foreach ($colStmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $columns[] = strtolower((string) ($col['Field'] ?? ''));
    }

    $has = fn(string $name): bool => in_array(strtolower($name), $columns, true);

    $descExpr = $has('description') ? 'description' : ($has('note') ? 'note' : "''");
    $capExpr = $has('guest_capacity')
        ? 'guest_capacity'
        : ($has('guestcapacity') ? 'guestCapacity' : ($has('capacity') ? 'capacity' : '0'));
    $activeExpr = $has('active') ? 'active' : ($has('is_active') ? 'is_active' : '1');

    $sql = "
      SELECT
        id,
        name,
        {$descExpr} AS description,
                {$capExpr} AS guest_capacity,
        {$activeExpr} AS active
      FROM venues
      ORDER BY id ASC
    ";

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    $venues = array_map(static function (array $row): array {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'description' => (string) ($row['description'] ?? ''),
            'guestCapacity' => (int) ($row['guest_capacity'] ?? 0),
            'active' => (int) ($row['active'] ?? 0) === 1,
        ];
    }, $rows);

    echo json_encode(['success' => true, 'venues' => $venues]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to load venues: ' . $e->getMessage()]);
}
