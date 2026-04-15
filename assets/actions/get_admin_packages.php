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
    $colStmt = $pdo->query('SHOW COLUMNS FROM packages');
    foreach ($colStmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $columns[] = strtolower((string) ($col['Field'] ?? ''));
    }

    $has = fn(string $name): bool => in_array(strtolower($name), $columns, true);

    $keyExpr = $has('key') ? '`key`' : 'LOWER(REPLACE(name, " ", "-"))';
    $baseExpr = $has('base_price') ? 'base_price' : ($has('baseprice') ? 'basePrice' : '0');
    $capacityExpr = $has('guest_capacity') ? 'guest_capacity' : ($has('guestcapacity') ? 'guestCapacity' : '0');
    $taglineExpr = $has('tagline') ? 'tagline' : "''";
    $activeExpr = $has('active') ? 'active' : ($has('is_active') ? 'is_active' : '1');
    $roomsExpr = $has('max_private_rooms') ? 'max_private_rooms' : ($has('maxprivaterooms') ? 'maxPrivateRooms' : '0');

    $sql = "
      SELECT
        id,
        {$keyExpr} AS package_key,
        name,
        {$baseExpr} AS base_price,
        {$capacityExpr} AS guest_capacity,
        {$taglineExpr} AS tagline,
        {$activeExpr} AS active,
        {$roomsExpr} AS max_private_rooms
      FROM packages
      ORDER BY id ASC
    ";

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    $packages = array_map(static function (array $row): array {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'key' => (string) ($row['package_key'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
            'basePrice' => (float) ($row['base_price'] ?? 0),
            'guestCapacity' => (int) ($row['guest_capacity'] ?? 0),
            'tagline' => (string) ($row['tagline'] ?? ''),
            'active' => (int) ($row['active'] ?? 0) === 1,
            'maxPrivateRooms' => (int) ($row['max_private_rooms'] ?? 0),
        ];
    }, $rows);

    echo json_encode(['success' => true, 'packages' => $packages]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to load packages: ' . $e->getMessage()]);
}
